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


    function insertEmployee($first_name, $last_name, $email, $passwords)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();
            $account_type = 1;

            $stmt1 = $con->prepare('INSERT INTO employee (Employee_FN, Employee_LN, email) VALUES (?, ?, ?)');
            $stmt1->execute([$first_name, $last_name, $email]);
            $employee_id = $con->lastInsertId();


            $stmt2 = $con->prepare('INSERT INTO user_accounts (email, passwords, account_type, employee_id) VALUES (?, ?, ?, ?)');
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

    function insertDentist($first_name, $last_name, $email, $passwords, $account_type = 2)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            $stmt1 = $con->prepare('INSERT INTO dentist (Dentist_FN, Dentist_LN, email) VALUES (?, ?, ?)');
            $stmt1->execute([$first_name, $last_name, $email]);
            $dentist_id = $con->lastInsertId();


            $stmt2 = $con->prepare('INSERT INTO user_accounts (email, passwords, account_type, dentist_id) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$email, $passwords, $account_type, $dentist_id]);

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
            $stmt = $con->prepare("SELECT ua.*, e.Employee_ID, d.Dentist_ID 
                FROM user_accounts ua
                LEFT JOIN employee e ON ua.employee_id = e.Employee_ID
                LEFT JOIN dentist d ON ua.dentist_id = d.Dentist_ID
                WHERE ua.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();


            if ($user && $passwords == $user['passwords']) {
                return [
                    'id'           => $user['Employee_ID'] ?? $user['Dentist_ID'],
                    'email'        => $user['email'],
                    'account_type' => $user['account_type']
                ];
            }

            return false;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    function viewServices()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Service_ID, Service_Name, Service_Fee FROM service ORDER BY Service_Name ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    function createAppointmentRequest($fullname, $email, $phone, $birthday, $gender, $appointment_date, $service_id)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();


            $name_parts = explode(" ", trim($fullname), 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : 'Patient';


            $stmt_check = $con->prepare("SELECT Patient_ID FROM patient WHERE Patient_PhoneNo = ? LIMIT 1");
            $stmt_check->execute([$phone]);
            $existing_patient = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing_patient) {
                $patient_id = $existing_patient['Patient_ID'];
            } else {

                $stmt_pat = $con->prepare("INSERT INTO patient (Patient_FN, Patient_LN, Patient_PhoneNo, Patient_BirthDate, Patient_Gender) VALUES (?, ?, ?, ?, ?)");
                $stmt_pat->execute([$first_name, $last_name, $phone, $birthday, $gender]);
                $patient_id = $con->lastInsertId();
            }

            $stmt_app = $con->prepare("INSERT INTO appointment (Patient_ID, Employee_ID, Dentist_ID, Appointment_Date, Appointment_Status) VALUES (?, NULL, NULL, ?, 'Pending')");
            $stmt_app->execute([$patient_id, $appointment_date]);
            $appointment_id = $con->lastInsertId();

            $stmt_srv = $con->prepare("INSERT INTO appointment_service (Service_ID, Appointment_ID) VALUES (?, ?)");
            $stmt_srv->execute([$service_id, $appointment_id]);

            $con->commit();
            return true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw $e;
        }
    }

    function viewAppointments()
    {
        $con = $this->opencon();
        try {
            $query = "SELECT 
                    a.Appointment_ID, 
                    a.Appointment_Date, 
                    a.Appointment_Status, 
                    p.Patient_FN, 
                    p.Patient_LN, 
                    p.Patient_PhoneNo,
                    s.Service_Name
                  FROM appointment a
                  LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID
                  LEFT JOIN appointment_service asv ON a.Appointment_ID = asv.Appointment_ID
                  LEFT JOIN service s ON asv.Service_ID = s.Service_ID
                  ORDER BY a.Appointment_Date DESC";

            $stmt = $con->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }


    function viewDentists()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Dentist_ID, Dentist_FN, Dentist_LN FROM dentist ORDER BY Dentist_LN ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }


    function viewPatients()
    {
        $con = $this->opencon();
        try {

            $query = "SELECT 
                    Patient_ID, 
                    Patient_FN, 
                    Patient_LN, 
                    Patient_BirthDate, 
                    Patient_Gender, 
                    Patient_PhoneNo 
                  FROM patient 
                  ORDER BY Patient_ID DESC";

            $stmt = $con->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }

    function addMedicalHistory($patient_id, $condition_name, $description = '')
    {
        $con = $this->opencon();
        try {

            $query = "INSERT INTO medical_history (Patient_ID, Med_History_Name, Med_History_Desc) VALUES (?, ?, ?)";
            $stmt = $con->prepare($query);
            return $stmt->execute([$patient_id, $condition_name, $description]);
        } catch (PDOException $e) {
            throw new Exception("Failed to record medical history parameters: " . $e->getMessage());
        }
    }

    function checkScheduleConflict($dentist_id, $appointment_id)
    {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Appointment_Date FROM appointment WHERE Appointment_ID = ?");
        $stmt->execute([$appointment_id]);
        $target = $stmt->fetch();
        if (!$target) return false;
        $stmt2 = $con->prepare("SELECT a.Appointment_ID, a.Appointment_Date, p.Patient_FN, p.Patient_LN
        FROM appointment a
        LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID
        WHERE a.Dentist_ID = ?
          AND a.Appointment_ID != ?
          AND a.Appointment_Status IN ('Pending','Confirmed')
          AND ABS(TIMESTAMPDIFF(MINUTE, a.Appointment_Date, ?)) < 60
    ");
        $stmt2->execute([$dentist_id, $appointment_id, $target['Appointment_Date']]);
        return $stmt2->fetch() ?: false;
    }
    
    function assignDentistToAppointment($appointment_id, $employee_id, $dentist_id)
    {
        $conflict = $this->checkScheduleConflict($dentist_id, $appointment_id);
        if ($conflict) {
            $time = date('M d, Y h:i A', strtotime($conflict['Appointment_Date']));
            $name = $conflict['Patient_FN'] . ' ' . $conflict['Patient_LN'];
            throw new Exception("Conflict! This dentist already has an appointment with $name at $time.");
        }
        $con = $this->opencon();
        $stmt = $con->prepare("
        UPDATE appointment SET Employee_ID=?, Dentist_ID=?, Appointment_Status='Confirmed'
        WHERE Appointment_ID=?
    ");
        $stmt->execute([$employee_id, $dentist_id, $appointment_id]);
        return true;
    }
    function getDentistAppointments($dentist_id)
    {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT a.Appointment_ID, a.Appointment_Date, a.Appointment_Status,
               p.Patient_FN, p.Patient_LN, s.Service_Name
        FROM appointment a
        LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID
        LEFT JOIN appointment_service asv ON a.Appointment_ID = asv.Appointment_ID
        LEFT JOIN service s ON asv.Service_ID = s.Service_ID
        WHERE a.Dentist_ID = ?
          AND a.Appointment_Status IN ('Pending','Confirmed')
        ORDER BY a.Appointment_Date ASC
    ");
        $stmt->execute([$dentist_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
