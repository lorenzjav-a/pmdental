<?php
session_start();



require_once('../class/database.php');
$db = new database();
$msg = $msgType = '';

if (isset($_POST['add_employee'])) {
    if ($_POST['pass'] !== $_POST['cpass']) {
        $msg = 'Passwords do not match.';
        $msgType = 'danger';
    } else {
        try {
            $db->insertEmployee($_POST['fn'], $_POST['ln'], $_POST['email'], $_POST['pass']);
            $msg = 'Employee added!';
            $msgType = 'success';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $msgType = 'danger';
        }
    }
}

if (isset($_POST['add_dentist'])) {
    if ($_POST['pass'] !== $_POST['cpass']) {
        $msg = 'Passwords do not match.';
        $msgType = 'danger';
    } else {
        try {
            $db->insertDentist($_POST['fn'], $_POST['ln'], $_POST['email'], $_POST['pass']);
            $msg = 'Dentist added!';
            $msgType = 'success';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $msgType = 'danger';
        }
    }
}



if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$activePage = 'dashboard';
$allAppointments = $db->viewAppointments();
$totalUsers = $db->countUsers();
$totalDentists = $db->countDentists();
$totalAppointments = $db->countAppointments();
$pendingRequests = $db->countAppointments('Pending');
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        /* SIDEBAR */

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: #0d1b2a;
            color: white;
            padding-top: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 35px;
            font-weight: bold;
        }

        .sidebar a {
            display: block;
            color: #d6d6d6;
            text-decoration: none;
            padding: 15px 25px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1b263b;
            color: white;
            padding-left: 30px;
        }

        .sidebar i {
            margin-right: 10px;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 25px;
        }

        /* TOPBAR */

        .topbar {
            background: white;
            border-radius: 12px;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #bb2d3b;
        }

        /* CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .card-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            transition: 0.3s;
        }

        .card-box:hover {
            transform: translateY(-5px);
        }

        .card-box .icon {
            font-size: 35px;
            margin-bottom: 15px;
        }

        .card-box h3 {
            font-size: 18px;
            color: #555;
        }

        .card-box h1 {
            margin-top: 10px;
            font-size: 40px;
            font-weight: bold;
        }

        /* TABLE */

        .table-section {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: #0d1b2a;
            color: white;
        }
    </style>

</head>

<body>

    <!-- SIDEBAR -->

    <?php include 'admin-sidebar.php'; ?>

    <!-- MAIN -->

    <div class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div>
                <h3>
                    Welcome, <?= htmlspecialchars($admin_name); ?>
                </h3>

                <small class="text-muted">
                    Admin Dashboard Overview
                </small>
            </div>

            <a href="login.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </div>

        <!-- CARDS -->

        <div class="cards">

            <div class="card-box">

                <div class="icon text-primary">
                    <i class="fas fa-users"></i>
                </div>

                <h3>Total Users</h3>

                <h1><?= number_format($totalUsers); ?></h1>

            </div>

            <div class="card-box">

                <div class="icon text-success">
                    <i class="fas fa-calendar-check"></i>
                </div>

                <h3>Appointments</h3>

                <h1><?= number_format($totalAppointments); ?></h1>

            </div>

            <div class="card-box">

                <div class="icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>

                <h3>Pending Requests</h3>

                <h1><?= number_format($pendingRequests); ?></h1>

            </div>

            <div class="card-box">

                <div class="icon text-danger">
                    <i class="fas fa-user-doctor"></i>
                </div>

                <h3>Dentists</h3>

                <h1><?= number_format($totalDentists); ?></h1>

            </div>

        </div>

        <!-- RECENT APPOINTMENTS -->

        <div class="table-section">

            <h4 class="mb-4">
                Recent Appointments
            </h4>

            <table class="table table-bordered table-hover">

                <thead>

                    <tr>
                        <th>Patient</th>
                        <th>Dentist</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>
                    <?php if (empty($allAppointments) || !is_array($allAppointments)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No recent appointments found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($allAppointments, 0, 5) as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')); ?></td>
                                <td><?= htmlspecialchars((!empty($app['Dentist_FN']) || !empty($app['Dentist_LN'])) ? trim(($app['Dentist_FN'] ?? '') . ' ' . ($app['Dentist_LN'] ?? '')) : 'Not assigned'); ?></td>
                                <td><?= !empty($app['Appointment_Date']) ? date('M d, Y', strtotime($app['Appointment_Date'])) : '-'; ?></td>
                                <td>
                                    <?php
                                    $status = $app['Appointment_Status'] ?? 'Unknown';
                                    $badge = 'bg-secondary';
                                    if ($status === 'Confirmed') {
                                        $badge = 'bg-success';
                                    } elseif ($status === 'Pending') {
                                        $badge = 'bg-warning text-dark';
                                    } elseif ($status === 'Cancelled') {
                                        $badge = 'bg-danger';
                                    }
                                    ?>
                                    <span class="badge <?= $badge; ?>">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>

    </div>



</body>

</html>