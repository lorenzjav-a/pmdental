<?php

include '../class/database.php';

$db = new database();
$con = $db->opencon();

$first_name = $last_name = $email = $account_type = "";
$first_nameErr = $last_nameErr = $emailErr = $account_typeErr = "";
$successMessage = "";

if (isset($_POST["btnRegister"])) {

    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $account_type = $_POST["account_type"];

    // VALIDATION
    if (empty($first_name)) $first_nameErr = "Required!";
    if (empty($last_name)) $last_nameErr = "Required!";
    if (empty($email)) $emailErr = "Required!";
    if (empty($account_type)) $account_typeErr = "Required!";

    if ($first_name && $last_name && $email && $account_type) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        } else {

            $password_plain = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
            $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

            try {

                $role = ($account_type == "1") ? "employee" : "dentist";

                if ($role == "employee") {

                    $id = $db->insertEmployee(
                        $first_name,
                        $last_name,
                        $email,
                        $password_hash,
                        $role
                    );

                } else {

                    $id = $db->insertDentist(
                        $first_name,
                        $last_name,
                        $email,
                        $password_hash,
                        $role
                    );
                }

                $successMessage = "Registration successful! Password: <b>$password_plain</b>";

                $first_name = $last_name = $email = $account_type = "";

            } catch (Exception $e) {

                $successMessage = "Error: " . $e->getMessage();
            }
        }
    }
}
?>