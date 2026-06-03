<?php
session_start();
require_once('../class/database.php');
$db = new database();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$activePage = 'reports';
$totalUsers = $db->countUsers();
$totalDentists = $db->countDentists();
$totalAppointments = $db->countAppointments();
$totalConfirmed = $db->countAppointments('Confirmed');
$totalPending = $db->countAppointments('Pending');
$totalCancelled = $db->countAppointments('Cancelled');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PM Dental Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f7fb; }
        #adminSidebar { position: fixed; top: 0; left: 0; width: 240px; height: 100vh; background: #0d1b2a; color: #fff; z-index: 1050; overflow-y: auto; padding-top: 1.5rem; }
        #adminSidebar .sidebar-brand { font-size: 1.25rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 1.5rem; display: block; color: #fff; }
        #adminSidebar .sidebar-links { padding: 0 1.2rem; }
        #adminSidebar .sidebar-links a { display: block; color: #d6d6d6; padding: 0.9rem 0.75rem; text-decoration: none; border-radius: 0.65rem; margin-bottom: 0.35rem; transition: background 0.2s, color 0.2s; }
        #adminSidebar .sidebar-links a.active, #adminSidebar .sidebar-links a:hover { background: #1b263b; color: #fff; }
        .main { margin-left: 260px; padding: 25px; }
        .topbar { background: white; border-radius: 12px; padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logout-btn { background: #dc3545; color: white; text-decoration: none; padding: 10px 15px; border-radius: 8px; transition: 0.3s; }
        .logout-btn:hover { background: #bb2d3b; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 25px; }
        .card-box { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); transition: 0.3s; }
        .card-box:hover { transform: translateY(-4px); }
        .card-box .icon { font-size: 35px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div>
                <h3>Welcome, <?= htmlspecialchars($admin_name); ?></h3>
                <small class="text-muted">Live business reports and appointment analytics</small>
            </div>
            <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
        <div class="cards">
            <div class="card-box">
                <div class="icon text-primary"><i class="fas fa-users"></i></div>
                <h3>Total Users</h3>
                <h1><?= number_format($totalUsers); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-danger"><i class="fas fa-user-doctor"></i></div>
                <h3>Total Dentists</h3>
                <h1><?= number_format($totalDentists); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-success"><i class="fas fa-calendar-check"></i></div>
                <h3>Total Appointments</h3>
                <h1><?= number_format($totalAppointments); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-warning"><i class="fas fa-clock"></i></div>
                <h3>Pending Appointments</h3>
                <h1><?= number_format($totalPending); ?></h1>
            </div>
        </div>
        <div class="table-section mt-4">
            <h4 class="mb-4">Appointment Status Breakdown</h4>
            <table class="table table-bordered">
                <tbody>
                    <tr><th class="w-50">Confirmed</th><td><?= number_format($totalConfirmed); ?></td></tr>
                    <tr><th>Pending</th><td><?= number_format($totalPending); ?></td></tr>
                    <tr><th>Cancelled</th><td><?= number_format($totalCancelled); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
