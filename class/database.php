<?php
class database
{

    function opencon(): PDO
    {
        return new PDO(
            'mysql:host=localhost; dbname=dentista',
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
            $stmt = $con->prepare("SELECT ua.*, e.Employee_ID, e.Employee_FN, e.Employee_LN, d.Dentist_ID, d.Dentist_FN, d.Dentist_LN 
                FROM user_accounts ua
                LEFT JOIN employee e ON ua.employee_id = e.Employee_ID
                LEFT JOIN dentist d ON ua.dentist_id = d.Dentist_ID
                WHERE ua.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();


            if ($user && $passwords == $user['passwords']) {
                $fullName = trim(
                    ($user['Employee_FN'] ?? $user['Dentist_FN'] ?? '') . ' ' .
                    ($user['Employee_LN'] ?? $user['Dentist_LN'] ?? '')
                );

                return [
                    'id'           => $user['Employee_ID'] ?? $user['Dentist_ID'],
                    'email'        => $user['email'],
                    'account_type' => $user['account_type'],
                    'name'         => $fullName ?: 'PM Dental User'
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

            // Normalize datetime-local (e.g. 2026-05-30T13:30) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
            if (strpos($appointment_date, 'T') !== false) {
                $appointment_date = str_replace('T', ' ', $appointment_date);
            }
            // ensure seconds
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $appointment_date)) {
                $appointment_date .= ':00';
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

    function viewAppointments() {
    $con = $this->opencon();
    try {
        // Updated to include the service bridge table and service masterlist table
        $query = "SELECT 
                    a.Appointment_ID, 
                    a.Appointment_Date, 
                    a.Appointment_Status, 
                                        pmt.Payment_Amount,
                                        pmt.Payment_Method,
                                        pmt.Payment_Status AS Payment_Status,
                    p.Patient_ID,
                    p.Patient_FN, 
                    p.Patient_LN,
                    p.Patient_BirthDate,
                    p.Patient_Gender,
                    p.Patient_PhoneNo,
                    d.Dentist_FN, 
                    d.Dentist_LN,
                    s.Service_Name,
                    s.Service_Fee
                  FROM appointment a
                  LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID
                  LEFT JOIN dentist d ON a.Dentist_ID = d.Dentist_ID
                  LEFT JOIN appointment_service asv ON a.Appointment_ID = asv.Appointment_ID
                  LEFT JOIN service s ON asv.Service_ID = s.Service_ID
                                    LEFT JOIN payment pmt ON a.Appointment_ID = pmt.Appointment_ID
                  ORDER BY a.Appointment_Date DESC";
        
        $stmt = $con->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

    function viewDentists()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Dentist_ID, Dentist_FN, Dentist_LN, email FROM dentist ORDER BY Dentist_LN ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }

    function getDentistCalendarEvents()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare('SELECT Den_Calendar_ID, Dentist_ID, Schedule_Date, Start_Time, End_Time FROM dentist_calendar');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    function getAppointmentById($appointment_id)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT * FROM appointment WHERE Appointment_ID = ? LIMIT 1");
            $stmt->execute([$appointment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
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
            return [];
        }
    }

    function viewUsers()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT ua.email, ua.account_type, e.Employee_FN, e.Employee_LN, d.Dentist_FN, d.Dentist_LN
                FROM user_accounts ua
                LEFT JOIN employee e ON ua.employee_id = e.Employee_ID
                LEFT JOIN dentist d ON ua.dentist_id = d.Dentist_ID
                ORDER BY ua.email ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    function countUsers()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM user_accounts");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) $row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    function countDentists()
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM dentist");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) $row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    function countAppointments($status = null)
    {
        $con = $this->opencon();
        try {
            if ($status !== null) {
                $stmt = $con->prepare("SELECT COUNT(*) AS total FROM appointment WHERE Appointment_Status = ?");
                $stmt->execute([$status]);
            } else {
                $stmt = $con->prepare("SELECT COUNT(*) AS total FROM appointment");
                $stmt->execute();
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) $row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
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
        $stmt = $con->prepare("UPDATE appointment SET Employee_ID=?, Dentist_ID=?, Appointment_Status='Confirmed'
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
function getPatientPrescriptions($patient_id)
    {
        $con = $this->opencon();
        try {
            $query = "SELECT 
                        pi.Item_Name, 
                        pi.Item_Quantity, 
                        pi.Pres_Dosage,
                        a.Appointment_Date,
                        d.Dentist_FN,
                        d.Dentist_LN
                      FROM prescription_items pi
                      INNER JOIN prescription pr ON pi.Prescription_ID = pr.Prescription_ID
                      INNER JOIN appointment a ON pr.Appointment_ID = a.Appointment_ID
                      LEFT JOIN dentist d ON a.Dentist_ID = d.Dentist_ID
                      WHERE a.Patient_ID = ?
                      ORDER BY a.Appointment_Date DESC";

            $stmt = $con->prepare($query);
            $stmt->execute([$patient_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Updates an existing billing statement or handles checking out/logging payments 
     * inside the payment terminal system
     */
    function updatePaymentStatus($appointment_id, $payment_method, $payment_status)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            // First, check if a payment tracker already exists for this appointment
            $stmt_check = $con->prepare("SELECT Payment_ID FROM payment WHERE Appointment_ID = ? LIMIT 1");
            $stmt_check->execute([$appointment_id]);
            $exists = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // Update the current invoice trace parameters
                $query = "UPDATE payment 
                          SET Payment_Method = ?, Payment_Status = ?, Payment_Date = CURDATE() 
                          WHERE Appointment_ID = ?";
                $stmt = $con->prepare($query);
                $res = $stmt->execute([$payment_method, $payment_status, $appointment_id]);
            } else {
                // If it doesn't exist, calculate the fee baseline via service_cost or service table
                $stmt_fee = $con->prepare("SELECT s.Service_Fee 
                                           FROM appointment_service asv 
                                           INNER JOIN service s ON asv.Service_ID = s.Service_ID 
                                           WHERE asv.Appointment_ID = ? LIMIT 1");
                $stmt_fee->execute([$appointment_id]);
                $service = $stmt_fee->fetch(PDO::FETCH_ASSOC);
                $amount = $service ? $service['Service_Fee'] : 0.00;

                // Insert a clean transaction row tracking parameters natively
                $query = "INSERT INTO payment (Appointment_ID, Payment_Amount, Payment_Method, Payment_Status, Payment_Date) 
                          VALUES (?, ?, ?, ?, CURDATE())";
                $stmt = $con->prepare($query);
                $res = $stmt->execute([$appointment_id, $amount, $payment_method, $payment_status]);
            }

            // If the payment is marked Paid, mark appointment as Completed
            if (strtolower($payment_status) === 'paid') {
                $stmt_upd = $con->prepare("UPDATE appointment SET Appointment_Status = 'Completed' WHERE Appointment_ID = ?");
                $stmt_upd->execute([$appointment_id]);
            }

            $con->commit();
            return $res ?? true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) $con->rollBack();
            throw new Exception("Failed to execute terminal payment checkout: " . $e->getMessage());
        }
    }
    public function updateDentistConsultationFee($service_id, $new_fee)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("UPDATE service SET Service_Fee = ? WHERE Service_ID = ?");
            return $stmt->execute([$new_fee, $service_id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to update clinical consultation fee parameters: " . $e->getMessage());
        }
    }

   
  function getPatientWithMedicalHistory($patient_id) {
        $con = $this->opencon();
        try {
            // Fetch patient core info
            $stmt1 = $con->prepare("SELECT Patient_ID, Patient_FN, Patient_LN, Patient_BirthDate, Patient_Gender, Patient_PhoneNo FROM patient WHERE Patient_ID = ?");
            $stmt1->execute([$patient_id]);
            $patient = $stmt1->fetch(PDO::FETCH_ASSOC);

            if (!$patient) return false;

            // Fetch history records compiled by staff employees
            $stmt2 = $con->prepare("SELECT Med_History_Name, Med_History_Desc FROM medical_history WHERE Patient_ID = ? ORDER BY Med_History_ID DESC");
            $stmt2->execute([$patient_id]);
            $patient['medical_history'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            return $patient;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * EMPLOYEE SIDE USE: Fetches prescription items logged by the Dentist
     */
    function getPrescriptionItemsByAppointment($appointment_id) {
        $con = $this->opencon();
        try {
            // Joins prescription items back to prescription using your table maps
            $stmt = $con->prepare("SELECT pi.Item_Name, pi.Item_Quantity, pi.Pres_Dosage 
                                   FROM prescription_items pi
                                   JOIN prescription p ON pi.Prescription_ID = p.Prescription_ID
                                   WHERE p.Appointment_ID = ?");
            $stmt->execute([$appointment_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    function getPrescriptionItemsByPatient($patient_id) {
    $con = $this->opencon();
    try {
        $query = "SELECT pi.Item_Name, pi.Item_Quantity, pi.Pres_Dosage 
                  FROM prescription_items pi
                  JOIN prescription pr ON pi.Prescription_ID = pr.Prescription_ID
                  JOIN appointment a ON pr.Appointment_ID = a.Appointment_ID
                  WHERE a.Patient_ID = :patient_id";
        
        $stmt = $con->prepare($query);
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

    /**
     * NEW METHOD: Adds a prescription record and loops through items using transactional queries.
     * Expects $items to be an array of arrays, e.g., [['name' => 'Amoxicillin', 'qty' => 10, 'dosage' => '500mg']]
     */
    function addPrescriptionWithItems($appointment_id, $items)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            // 1. Insert parent prescription record
            $stmt_pres = $con->prepare("INSERT INTO prescription (Appointment_ID) VALUES (?)");
            $stmt_pres->execute([$appointment_id]);
            $prescription_id = $con->lastInsertId();

            // 2. Insert individual items matching your structural schema rules
            $stmt_item = $con->prepare("INSERT INTO prescription_items (Prescription_ID, Item_Name, Item_Quantity, Pres_Dosage) VALUES (?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $stmt_item->execute([
                    $prescription_id,
                    $item['name'],
                    $item['qty'],
                    $item['dosage']
                ]);
            }

            $con->commit();
            return $prescription_id;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw new Exception("Failed to process prescription logging: " . $e->getMessage());
        }
    }

    function getActiveDentistFee($appointment_id)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT s.Service_Fee 
                                   FROM appointment_service asv 
                                   INNER JOIN service s ON asv.Service_ID = s.Service_ID 
                                   WHERE asv.Appointment_ID = ? LIMIT 1");
            $stmt->execute([$appointment_id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            return $service ? $service['Service_Fee'] : 0.00;
        } catch (PDOException $e) {
            return 0.00;
        }
    }

    /**
     * Check whether a requested appointment datetime is already taken.
     * Returns true if there exists an appointment within +/-30 minutes of the requested datetime.
     */
    function isSlotTaken($appointment_datetime)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Appointment_ID, Appointment_Date FROM appointment 
                                   WHERE ABS(TIMESTAMPDIFF(MINUTE, Appointment_Date, ?)) < 30 LIMIT 1");
            $stmt->execute([$appointment_datetime]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Save a quick payment when no appointment exists yet.
     * Creates a patient (if not found by exact name), creates a confirmed appointment, and logs payment.
     */
    function savePayment($patient_name, $dentist_id, $amount, $appointment_date = null)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            // split name
            $parts = preg_split('/\s+/', trim($patient_name), 2);
            $fn = $parts[0] ?? 'Patient';
            $ln = $parts[1] ?? 'Unknown';

            // try to find exact patient by name
            $stmt_find = $con->prepare("SELECT Patient_ID FROM patient WHERE Patient_FN = ? AND Patient_LN = ? LIMIT 1");
            $stmt_find->execute([$fn, $ln]);
            $row = $stmt_find->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $patient_id = $row['Patient_ID'];
            } else {
                $stmt_ins = $con->prepare("INSERT INTO patient (Patient_FN, Patient_LN, Patient_PhoneNo, Patient_BirthDate, Patient_Gender) VALUES (?, ?, ?, ?, ?)");
                $stmt_ins->execute([$fn, $ln, '', NULL, 'unspecified']);
                $patient_id = $con->lastInsertId();
            }

            // Prefer to link payment to an existing pending appointment (preserve its date)
            $stmt_pending = $con->prepare("SELECT Appointment_ID, Appointment_Date FROM appointment WHERE Patient_ID = ? AND Appointment_Status = 'Pending' ORDER BY Appointment_Date ASC LIMIT 1");
            $stmt_pending->execute([$patient_id]);
            $pending = $stmt_pending->fetch(PDO::FETCH_ASSOC);

            if ($pending) {
                $appointment_id = $pending['Appointment_ID'];
                $stmt_upd = $con->prepare("UPDATE appointment SET Dentist_ID = ?, Appointment_Status = 'Confirmed' WHERE Appointment_ID = ?");
                $stmt_upd->execute([$dentist_id, $appointment_id]);
            } else {
                // If no pending appointment exists, create a confirmed appointment only when a date is provided.
                if ($appointment_date) {
                    if (strpos($appointment_date, 'T') !== false) $appointment_date = str_replace('T', ' ', $appointment_date);
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $appointment_date)) $appointment_date .= ':00';
                    $stmt_app = $con->prepare("INSERT INTO appointment (Patient_ID, Employee_ID, Dentist_ID, Appointment_Date, Appointment_Status) VALUES (?, NULL, ?, ?, 'Confirmed')");
                    $stmt_app->execute([$patient_id, $dentist_id, $appointment_date]);
                } else {
                    $stmt_app = $con->prepare("INSERT INTO appointment (Patient_ID, Employee_ID, Dentist_ID, Appointment_Date, Appointment_Status) VALUES (?, NULL, ?, NULL, 'Confirmed')");
                    $stmt_app->execute([$patient_id, $dentist_id]);
                }
                $appointment_id = $con->lastInsertId();
            }

            // Log payment
            $stmt_pay = $con->prepare("INSERT INTO payment (Appointment_ID, Payment_Amount, Payment_Method, Payment_Status, Payment_Date) VALUES (?, ?, ?, ?, CURDATE())");
            $stmt_pay->execute([$appointment_id, $amount, 'Cash', 'Paid']);

            $con->commit();
            return true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) $con->rollBack();
            throw new Exception('Failed to save payment: ' . $e->getMessage());
        }
    }
}