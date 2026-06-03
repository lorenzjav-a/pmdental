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

            // Always create a new patient record for each appointment request
            $stmt_pat = $con->prepare("INSERT INTO patient (Patient_FN, Patient_LN, Patient_PhoneNo, Patient_BirthDate, Patient_Gender, Patient_Email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_pat->execute([$first_name, $last_name, $phone, $birthday, $gender, $email]);
            $patient_id = $con->lastInsertId();

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
            // Updated to include the service bridge table and service masterlist table
            $query = "SELECT 
                    a.Appointment_ID, 
                    a.Appointment_Date, 
                    a.Appointment_Status,
                    pay.Payment_Amount AS Payment_Amount,
                    pay.Payment_Status AS Payment_Status,
                    pay.Payment_Method AS Payment_Method,
                    p.Patient_ID,
                    p.Patient_FN, 
                    p.Patient_LN,
                    p.Patient_BirthDate,
                    p.Patient_Gender,
                    p.Patient_PhoneNo,
                    p.Patient_Email,
                    d.Dentist_FN, 
                    d.Dentist_LN,
                    s.Service_ID,
                    s.Service_Name,
                    s.Service_Fee
                  FROM appointment a
                  LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID
                  LEFT JOIN dentist d ON a.Dentist_ID = d.Dentist_ID
                  LEFT JOIN appointment_service asv ON a.Appointment_ID = asv.Appointment_ID
                  LEFT JOIN service s ON asv.Service_ID = s.Service_ID
                  LEFT JOIN payment pay ON a.Appointment_ID = pay.Appointment_ID
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
            return [];
        }
    }

    function getDentistById($dentist_id)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Dentist_ID, Dentist_FN, Dentist_LN, email FROM dentist WHERE Dentist_ID = ?");
            $stmt->execute([$dentist_id]);
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
                    Patient_PhoneNo,
                    Patient_Email
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
        try {
            $con->beginTransaction();

            // Update appointment to confirm
            $stmt = $con->prepare("UPDATE appointment SET Employee_ID=?, Dentist_ID=?, Appointment_Status='Confirmed', Appointment_Date=Appointment_Date
            WHERE Appointment_ID=?
        ");
            $stmt->execute([$employee_id, $dentist_id, $appointment_id]);

            $con->commit();
            return true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw $e;
        }
    }
    function getDentistAppointments($dentist_id)
    {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT a.Appointment_ID, a.Appointment_Date, a.Appointment_Status, a.Appointment_End_Time,
               p.Patient_ID, p.Patient_FN, p.Patient_LN, p.Patient_PhoneNo, p.Patient_Email, s.Service_Name
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

    function getDentistCalendarByDentist($dentist_id)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT Den_Calendar_ID, Dentist_ID, Schedule_Date, Start_Time, End_Time FROM dentist_calendar WHERE Dentist_ID = ? ORDER BY Schedule_Date ASC");
            $stmt->execute([$dentist_id]);
            $calendar_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enhance calendar slots with appointment information (including patient email) if available
            foreach ($calendar_slots as &$slot) {
                $slot_start = $slot['Schedule_Date'] . ' ' . $slot['Start_Time'];
                $slot_end = $slot['Schedule_Date'] . ' ' . $slot['End_Time'];

                // Check for appointments that fall within this time slot
                $stmt_appt = $con->prepare("SELECT a.Appointment_ID, p.Patient_ID, p.Patient_FN, p.Patient_LN, p.Patient_PhoneNo, p.Patient_Email, s.Service_Name 
                    FROM appointment a 
                    LEFT JOIN patient p ON a.Patient_ID = p.Patient_ID 
                    LEFT JOIN appointment_service asv ON a.Appointment_ID = asv.Appointment_ID 
                    LEFT JOIN service s ON asv.Service_ID = s.Service_ID 
                    WHERE a.Dentist_ID = ? AND a.Appointment_Date >= ? AND a.Appointment_Date < ? AND a.Appointment_Status IN ('Pending', 'Confirmed')");
                $stmt_appt->execute([$dentist_id, $slot_start, $slot_end]);
                $appointment = $stmt_appt->fetch(PDO::FETCH_ASSOC);

                if ($appointment) {
                    $slot['Appointment_ID'] = $appointment['Appointment_ID'];
                    $slot['Patient_ID'] = $appointment['Patient_ID'];
                    $slot['Patient_FN'] = $appointment['Patient_FN'];
                    $slot['Patient_LN'] = $appointment['Patient_LN'];
                    $slot['Patient_PhoneNo'] = $appointment['Patient_PhoneNo'];
                    $slot['Patient_Email'] = $appointment['Patient_Email'];
                    $slot['Service_Name'] = $appointment['Service_Name'];
                }
            }

            return $calendar_slots;
        } catch (PDOException $e) {
            return [];
        }
    }

    function isTimeSlotBooked($appointment_datetime)
    {
        $con = $this->opencon();
        try {
            // Extract date and time from the appointment datetime
            $schedule_date = date('Y-m-d', strtotime($appointment_datetime));
            $appointment_time = date('H:i', strtotime($appointment_datetime));

            // Check if there's already a booking in dentist_calendar that overlaps with this time
            // A slot is considered booked if the requested time falls within any existing time slot
            $stmt = $con->prepare("SELECT COUNT(*) as count FROM dentist_calendar 
                WHERE Schedule_Date = ? 
                AND Start_Time <= ? 
                AND End_Time > ?
            ");
            $stmt->execute([$schedule_date, $appointment_time, $appointment_time]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    function updateAppointmentDateTime($appointment_id, $new_appointment_datetime)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("UPDATE appointment SET Appointment_Date = ? WHERE Appointment_ID = ?");
            return $stmt->execute([$new_appointment_datetime, $appointment_id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to update appointment time: " . $e->getMessage());
        }
    }

    function updateAppointmentEndTime($appointment_id, $end_datetime)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            // Update the appointment end time
            $stmt = $con->prepare("UPDATE appointment SET Appointment_End_Time = ? WHERE Appointment_ID = ?");
            $stmt->execute([$end_datetime, $appointment_id]);

            // Get appointment details to save to dentist_calendar
            $stmt_appt = $con->prepare("SELECT Dentist_ID, Appointment_Date FROM appointment WHERE Appointment_ID = ?");
            $stmt_appt->execute([$appointment_id]);
            $appointment = $stmt_appt->fetch(PDO::FETCH_ASSOC);

            if ($appointment && $appointment['Dentist_ID']) {
                // Use the date from the appointment's original scheduled time (not the end_datetime date)
                $schedule_date = date('Y-m-d', strtotime($appointment['Appointment_Date']));
                // Use the appointment's scheduled time as the start time
                $start_time = date('H:i', strtotime($appointment['Appointment_Date']));
                // Extract only the time from the end_datetime (what employee entered as end time)
                $end_time = date('H:i', strtotime($end_datetime));

                // Check if this appointment slot already exists in dentist_calendar
                $stmt_check = $con->prepare("SELECT Den_Calendar_ID FROM dentist_calendar WHERE Dentist_ID = ? AND Schedule_Date = ? AND Start_Time = ?");
                $stmt_check->execute([$appointment['Dentist_ID'], $schedule_date, $start_time]);
                $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    // Save to dentist_calendar with the date/time from the appointment and end time from the form
                    $stmt_cal = $con->prepare("INSERT INTO dentist_calendar (Dentist_ID, Schedule_Date, Start_Time, End_Time) VALUES (?, ?, ?, ?)");
                    $stmt_cal->execute([$appointment['Dentist_ID'], $schedule_date, $start_time, $end_time]);
                } else {
                    // Update existing entry if found
                    $stmt_update = $con->prepare("UPDATE dentist_calendar SET End_Time = ? WHERE Den_Calendar_ID = ?");
                    $stmt_update->execute([$end_time, $existing['Den_Calendar_ID']]);
                }
            }

            $con->commit();
            return true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw new Exception("Failed to update appointment end time: " . $e->getMessage());
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
            // First, check if a payment tracker already exists for this appointment
            $stmt_check = $con->prepare("SELECT Payment_ID FROM payment WHERE Appointment_ID = ? LIMIT 1");
            $stmt_check->execute([$appointment_id]);
            $exists = $stmt_check->fetch(PDO::FETCH_ASSOC);

            $shouldComplete = strcasecmp($payment_status, 'Paid') === 0;

            if ($exists) {
                // Update the current invoice trace parameters
                $query = "UPDATE payment 
                          SET Payment_Method = ?, Payment_Status = ?, Payment_Date = CURDATE() 
                          WHERE Appointment_ID = ?";
                $stmt = $con->prepare($query);
                $result = $stmt->execute([$payment_method, $payment_status, $appointment_id]);

                if ($result && $shouldComplete) {
                    $stmt_update = $con->prepare("UPDATE appointment SET Appointment_Status = 'Completed', Appointment_Date=Appointment_Date WHERE Appointment_ID = ?");
                    $stmt_update->execute([$appointment_id]);
                }

                return $result;
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
                $result = $stmt->execute([$appointment_id, $amount, $payment_method, $payment_status]);

                if ($result && $shouldComplete) {
                    $stmt_update = $con->prepare("UPDATE appointment SET Appointment_Status = 'Completed', Appointment_Date=Appointment_Date WHERE Appointment_ID = ?");
                    $stmt_update->execute([$appointment_id]);
                }

                return $result;
            }
        } catch (PDOException $e) {
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

    public function addService($service_name, $service_fee)
    {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("INSERT INTO service (Service_Name, Service_Fee) VALUES (?, ?)");
            return $stmt->execute([$service_name, $service_fee]);
        } catch (PDOException $e) {
            throw new Exception("Failed to add service: " . $e->getMessage());
        }
    }


    function getPatientWithMedicalHistory($patient_id)
    {
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

    
   
    function getPrescriptionItemsByPatient($patient_id)
    {
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

    // This function allows dentists to log prescriptions with multiple items for a given appointment
    function addPrescriptionWithItems($appointment_id, $items)
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            //Insert parent prescription record
            $stmt_pres = $con->prepare("INSERT INTO prescription (Appointment_ID) VALUES (?)");
            $stmt_pres->execute([$appointment_id]);
            $prescription_id = $con->lastInsertId();

            //Insert individual items matching your structural schema rules
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

    function isSlotTaken($appointment_date, $appointment_time)
    {

        $con = $this->opencon();

        try {

            $stmt = $con->prepare("SELECT COUNT(*) 
            FROM appointments
            WHERE appointment_date = ?
            AND appointment_time = ?
        ");

            $stmt->execute([
                $appointment_date,
                $appointment_time
            ]);

            $count = $stmt->fetchColumn();

            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    function getActiveDentistFee($dentist_id)
    {
        $con = $this->opencon();

        try {

            $stmt = $con->prepare("SELECT rates
            FROM dentist_consul_fee
            WHERE dentist_id = ?
            AND CURDATE() BETWEEN valid_from AND valid_to
            ORDER BY valid_from DESC
            LIMIT 1
        ");

            $stmt->execute([$dentist_id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['rates'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    function savePayment($patient_name, $dentist_id, $amount)
    {
        $con = $this->opencon();

        try {

            $stmt = $con->prepare("INSERT INTO payment (patient_name, dentist_id, payment_amount)
            VALUES (?, ?, ?)
        ");

            return $stmt->execute([
                $patient_name,
                $dentist_id,
                $amount
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    function updateDentistFee($dentist_id, $rates, $valid_from, $valid_to)
    {
        $con = $this->opencon();

        $sql = "UPDATE dentist_consul_fee 
            SET rates = ?, valid_from = ?, valid_to = ?
            WHERE dentist_id = ?";

        $stmt = $con->prepare($sql);

        return $stmt->execute([
            $rates,
            $valid_from,
            $valid_to,
            $dentist_id
        ]);
    }

    function addDentistFee($dentist_id, $rates, $valid_from, $valid_to)
    {
        $con = $this->opencon();

        $sql = "INSERT INTO dentist_consul_fee 
            (dentist_id, rates, valid_from, valid_to, created_at)
            VALUES (?, ?, ?, ?, NOW())";

        $stmt = $con->prepare($sql);

        return $stmt->execute([
            $dentist_id,
            $rates,
            $valid_from,
            $valid_to
        ]);
    }

    function cancelAppointment($appointment_id, $cancellation_reason = '')
    {
        $con = $this->opencon();
        try {
            $con->beginTransaction();

            
            $stmt = $con->prepare("UPDATE appointment SET Appointment_Status = 'Cancelled' WHERE Appointment_ID = ?");
            $stmt->execute([$appointment_id]);

           

            $con->commit();
            return true;
        } catch (PDOException $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            throw new Exception("Failed to cancel appointment: " . $e->getMessage());
        }
    }
}
