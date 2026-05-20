<?php
session_start();
require_once('../class/database.php');
$db = new database();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$activePage = 'dentists';
$dentists = $db->viewDentists();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentists - PM Dental Admin</title>
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

        .sidebar a:hover,
        .sidebar a.active {
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

        .table-section {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <?php include 'admin-sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div>
                <h3>Welcome, <?= htmlspecialchars($admin_name); ?></h3>
                <small class="text-muted">Dentist directory and contact details</small>
            </div>
            <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
        <div class="table-section">
            <h4 class="mb-4">Dentist Masterlist</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dentists)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No dentist records available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dentists as $dentist): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dentist['Dentist_ID'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars(trim(($dentist['Dentist_FN'] ?? '') . ' ' . ($dentist['Dentist_LN'] ?? ''))); ?></td>
                                    <td><?= htmlspecialchars($dentist['email'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>