<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
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

        .sidebar a:hover {
            background: #1b263b;
            color: white;
            padding-left: 30px;
        }

        .sidebar i {
            margin-right: 10px;
        }



        .main {
            margin-left: 250px;
            padding: 25px;
        }



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



    <div class="sidebar">

        <h2>PM Dental</h2>

        <a href="#">
            <i class="fas fa-chart-line"></i>
            Dashboard
        </a>

        <a href="#">
            <i class="fas fa-users"></i>
            Users
        </a>

        <a href="#">
            <i class="fas fa-user-doctor"></i>
            Dentists
        </a>

        <a href="#">
            <i class="fas fa-calendar-check"></i>
            Appointments
        </a>

        <a href="#">
            <i class="fas fa-file"></i>
            Reports
        </a>

    </div>



    <div class="main">



        <div class="topbar">

            <div>
                <h3>
                    Welcome, <?= htmlspecialchars($admin_name); ?>
                </h3>

                <small class="text-muted">
                    Admin Dashboard Overview
                </small>
            </div>

            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </div>



        <div class="cards">

            <div class="card-box">

                <div class="icon text-primary">
                    <i class="fas fa-users"></i>
                </div>

                <h3>Total Users</h3>

                <h1>120</h1>

            </div>

            <div class="card-box">

                <div class="icon text-success">
                    <i class="fas fa-calendar-check"></i>
                </div>

                <h3>Appointments</h3>

                <h1>45</h1>

            </div>

            <div class="card-box">

                <div class="icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>

                <h3>Pending Requests</h3>

                <h1>8</h1>

            </div>

            <div class="card-box">

                <div class="icon text-danger">
                    <i class="fas fa-user-doctor"></i>
                </div>

                <h3>Dentists</h3>

                <h1>12</h1>

            </div>

        </div>



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

                    <tr>
                        <td>Juan Dela Cruz</td>
                        <td>Dr. Kyle</td>
                        <td>July 20, 2025</td>
                        <td>
                            <span class="badge bg-success">
                                Approved
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>Maria Santos</td>
                        <td>Dr. Clarence</td>
                        <td>July 21, 2025</td>
                        <td>
                            <span class="badge bg-warning text-dark">
                                Pending
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>James Reid</td>
                        <td>Dr. Kimi</td>
                        <td>July 22, 2025</td>
                        <td>
                            <span class="badge bg-danger">
                                Cancelled
                            </span>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</body>

</html>