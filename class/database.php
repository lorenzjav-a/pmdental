<?php
class database
{

    function opencon(): PDO
    {
        return new PDO(
            'mysql:host=localhost; dbname=pmdentaltry',
            username: 'root',
            password: ''
        );
    }


    function insertEmployee($first_name, $last_name, $email, $passwords, $account_type)
    {
        $con = $this->opencon();

        try {
            $con->beginTransaction();

            $stmt1 = $con->prepare('INSERT INTO employee (Employee_FN, Employee_LN, email) VALUES (?, ?, ?)');
            $stmt1->execute([$first_name, $last_name, $email]);
            $employee_id = $con->lastInsertId();



            $stmt2 = $con->prepare('INSERT INTO user_accounts (email, password, account_type, employee_ID) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$email, $passwords, $account_type, $employee_id]);

            $con->commit();
            return $employee_id;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw $e;
        }
    }

    public function insertDentist($first_name, $last_name, $email, $passwords, $account_type)
    {

        $con = $this->opencon();

        try {

            $con->beginTransaction();


            $hashedPassword = password_hash($passwords, PASSWORD_DEFAULT);


            $stmt1 = $con->prepare('
            INSERT INTO dentist 
            (Dentist_FN, Dentist_LN, email, passwords) 
            VALUES (?, ?, ?, ?)
        ');

            $stmt1->execute([
                $first_name,
                $last_name,
                $email,
                $hashedPassword
            ]);

            $dentist_id = $con->lastInsertId();


            $stmt2 = $con->prepare('
            INSERT INTO user_accounts 
            (email, passwords, account_type, dentist_id) 
            VALUES (?, ?, ?, ?)
        ');

            $stmt2->execute([
                $email,
                $hashedPassword,
                $account_type,
                $dentist_id
            ]);

            $con->commit();

            return $dentist_id;
        } catch (PDOException $e) {

            if ($con->inTransaction()) {
                $con->rollBack();
            }

            throw $e;
        }
    }
    function login($email, $passwords)
    {

        $con = $this->opencon();

        try {

            // EMPLOYEE LOGIN
            $stmt = $con->prepare("
            SELECT * FROM employee
            WHERE email = ?
        ");

            $stmt->execute([$email]);

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {

                $empPass = $employee['passwords'] ?? null;

                if ($empPass == $passwords) {

                    return [
                        'id' => $employee['Employee_ID'],
                        'email' => $employee['email'],
                        'account_type' => 1
                    ];
                }
            }

            // DENTIST LOGIN
            $stmt = $con->prepare("
            SELECT * FROM dentist
            WHERE email = ?
        ");

            $stmt->execute([$email]);

            $dentist = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dentist) {

                $dentPass = $dentist['passwords'] ?? null;

                $dentPass = $dentist['passwords'] ?? null;

                if ($dentPass == $passwords) {

                    return [
                        'id' => $dentist['Dentist_ID'],
                        'email' => $dentist['email'],
                        'account_type' => 2
                    ];
                }
            }

            return false;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
