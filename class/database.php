<?php
class database{

  function opencon(): PDO{
    return new PDO(
      'mysql:host=localhost; dbname=pmdentaltry',
      username: 'root',
      password: '');

  }


function insertEmployee($first_name, $last_name, $email, $password, $account_type) {
    $con = $this->opencon();

    try {
        $con->beginTransaction();
        
        $stmt1 = $con->prepare('INSERT INTO employee (Employee_FN, Employee_LN, email) VALUES (?, ?, ?)');
        $stmt1->execute([$first_name, $last_name, $email]);
        $employee_id = $con->lastInsertId();
    
  
      
        $stmt2 = $con->prepare('INSERT INTO user_accounts (email, password, account_type, employee_id) VALUES (?, ?, ?, ?)');
        $stmt2->execute([$email, $password, $account_type, $employee_id]);
        
        $con->commit();
        return $employee_id;

    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        throw $e;
    }
}

 function insertDentist($first_name, $last_name, $email, $password, $account_type) {
    $con = $this->opencon();

    try {
        $con->beginTransaction();
        
        $stmt1 = $con->prepare('INSERT INTO dentist (Dentist_FN, Dentist_LN, email) VALUES (?, ?, ?)');
        $stmt1->execute([$first_name, $last_name, $email]);
        $dentist_id = $con->lastInsertId();
        
        $stmt2 = $con->prepare('INSERT INTO user_accounts (email, password, account_type, dentist_id) VALUES (?, ?, ?, ?)');
        $stmt2->execute([$email, $password, $account_type, $dentist_id]);
        
        $con->commit();
        return $dentist_id;

    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        throw $e;
    }
}
}