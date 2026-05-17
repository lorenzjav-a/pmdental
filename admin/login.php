<?php
session_start();
require_once('../class/database.php');

$con = new database();

$email = "";
$password = "";

$emailErr = "";
$passwordErr = "";

$loginStatus = null;
$loginMessage = "";

if (isset($_POST['btnLogin'])) {

    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");


    if ($email == "") {
        $emailErr = "Required!";
    }

    if ($password == "") {
        $passwordErr = "Required!";
    }

    if (empty($emailErr) && empty($passwordErr)) {

        try {


            $user = $con->login($email, $password);

            if ($user) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['account_type'] = $user['account_type'];

                $loginStatus = "success";
                $loginMessage = "Login successful!";
            } else {

                $loginStatus = "error";
                $loginMessage = "Invalid email or password.";
            }
        } catch (Exception $e) {

            $loginStatus = "error";
            $loginMessage = $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login</title>

    <link rel="stylesheet" href="../bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-card {
            max-width: 450px;
            border: none;
            border-radius: 12px;
        }
    </style>

</head>

<body class="d-flex align-items-center min-vh-100">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-6 col-lg-5">

                <div class="card login-card shadow p-4 mx-auto">

                    <div class="text-center mb-4">

                        <h3 class="fw-bold text-primary">
                            PM Dental Clinic
                        </h3>

                        <p class="text-muted small">
                            Sign in to your workspace
                        </p>

                    </div>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email); ?>">

                            <small class="text-danger">
                                <?= $emailErr; ?>
                            </small>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control">

                            <small class="text-danger">
                                <?= $passwordErr; ?>
                            </small>

                        </div>

                        <button
                            type="submit"
                            name="btnLogin"
                            class="btn btn-primary w-100 py-2">
                            Sign In
                        </button>

                        <hr>

                        <div class="text-center">

                            <a
                                href="index.php"
                                class="text-decoration-none fw-semibold text-primary">
                                Create Account →
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.js"></script>

    <script>
    const status = <?= json_encode($loginStatus); ?>;
    const msg = <?= json_encode($loginMessage); ?>;

    if (status === "success") {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: msg,
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            // THIS IS THE MISSING PIECE
            window.location.href = "admin.php"; 
        });

    } else if (status === "error") {
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: msg
        });
    }
</script>
</body>

</html>