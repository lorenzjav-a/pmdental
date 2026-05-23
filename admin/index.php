<?php
require_once('../class/database.php');
$con = new database();
$first_name = $last_name = $email = $account_type = $password = $confirm_password = "";
$first_nameErr = $last_nameErr = $emailErr = $account_typeErr = $passwordErr = $confirm_passwordErr = "";

$registerStatus = null;
$registerMessage = ' ';

if (isset($_POST["btnRegister"])) {

    if (empty($_POST["first_name"])) {
        $first_nameErr = "Required!";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    if (empty($_POST["last_name"])) {
        $last_nameErr = "Required!";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    if (empty($_POST["email"])) {
        $emailErr = "Required!";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty($_POST["account_type"])) {
        $account_typeErr = "Required!";
    } else {
        $account_type = $_POST["account_type"];
    }

    if (empty($_POST["password"])) {
        $passwordErr = "Required!";
    } else {
        $password = $_POST["password"];
    }

    if (empty($_POST["confirm_password"])) {
        $confirm_passwordErr = "Required!";
    } else {
        $confirm_password = $_POST["confirm_password"];
    }

    // Proceed if all fields are filled
    if (empty($first_nameErr) && empty($last_nameErr) && empty($emailErr) && empty($account_typeErr) && empty($passwordErr) && empty($confirm_passwordErr)) {

        if (!preg_match("/^[a-zA-Z ]*$/", $first_name)) {
            $first_nameErr = "Invalid format";
        } elseif (strlen($first_name) < 2) {
            $first_nameErr = "Too short";
        } elseif (strlen($last_name) < 2) {
            $last_nameErr = "Too short";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        } elseif (strlen($password) < 8) {
            $passwordErr = "Password must be at least 8 characters";
        } elseif ($password !== $confirm_password) {
            $confirm_passwordErr = "Passwords do not match!";
        } else {
            // If everything is valid and passwords match, register the user
            try {
                if ($account_type == "1") {
                    $con->insertEmployee($first_name, $last_name, $email, $password);
                } else {
                    $con->insertDentist($first_name, $last_name, $email, $password, $account_type);
                }

                $registerStatus = 'success';
                $registerMessage = 'Registration successful! You can now log in.';

                // Clear the form fields after successful registration
                $first_name = $last_name = $email = $account_type = $password = $confirm_password = "";
            } catch (Exception $e) {
                $registerStatus = 'error';
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $registerMessage = 'This email address is already registered in our system.';
                } else {
                    $registerMessage = $e->getMessage();
                }
            }
        }
    } else {
        $registerStatus = 'error';
        $registerMessage = 'Please fill out all required fields properly.';
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .register-card {
            max-width: 500px;
            border: none;
            border-radius: 12px;
        }
    </style>
</head>

<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card register-card shadow p-4 mx-auto">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">PM Dental Clinic</h3>
                        <p class="text-muted small">Account Registration</p>
                    </div>

                    <form action="" method="POST">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label small fw-medium">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name); ?>">
                                <div class="text-danger small mt-1"><?= $first_nameErr; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label small fw-medium">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name); ?>">
                                <div class="text-danger small mt-1"><?= $last_nameErr; ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-medium">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email); ?>">
                            <div class="text-danger small mt-1"><?= $emailErr; ?></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label small fw-medium">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="text-danger small mt-1"><?= $passwordErr; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label small fw-medium">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <div class="text-danger small mt-1"><?= $confirm_passwordErr; ?></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="account_type" class="form-label small fw-medium">Registering Role Assignment</label>
                            <select class="form-select" id="account_type" name="account_type">
                                <option value="" disabled <?= $account_type == "" ? "selected" : ""; ?>>Select system role</option>
                                <option value="1" <?= $account_type == "1" ? "selected" : ""; ?>>Clinic Employee (Front-desk Staff)</option>
                                <option value="2" <?= $account_type == "2" ? "selected" : ""; ?>>Dentist</option>
                            </select>
                            <div class="text-danger small mt-1"><?= $account_typeErr; ?></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btnRegister" class="btn btn-primary py-2 fw-semibold">Submit</button>
                        </div>
                        <hr class="text-muted my-4">
                        <div class="text-center">
                            <p class="small text-muted mb-0">Already registered? <a href="login.php" class="text-decoration-none">Sign in to workspace</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.js"></script>
    <script>
        const status = <?php echo json_encode($registerStatus) ?>;
        const msg = <?php echo json_encode($registerMessage) ?>;

        if (status == 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: msg,
                confirmButtonText: 'OK'
            });
        } else if (status == 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Registration Blocked',
                text: msg,
                confirmButtonText: 'OK'
            });
        }
    </script>
    
</body>

</html>