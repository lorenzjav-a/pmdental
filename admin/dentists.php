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

        #adminSidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            background: #0d1b2a;
            color: #fff;
            z-index: 1050;
            overflow-y: auto;
            padding-top: 1.5rem;
        }

        #adminSidebar .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0 1.5rem;
            margin-bottom: 1.5rem;
            display: block;
            color: #fff;
        }

        #adminSidebar .sidebar-links {
            padding: 0 1.2rem;
        }

        #adminSidebar .sidebar-links a {
            display: block;
            color: #d6d6d6;
            padding: 0.9rem 0.75rem;
            text-decoration: none;
            border-radius: 0.65rem;
            margin-bottom: 0.35rem;
            transition: background 0.2s, color 0.2s;
        }

        #adminSidebar .sidebar-links a.active,
        #adminSidebar .sidebar-links a:hover {
            background: #1b263b;
            color: #fff;
        }

        .main {
            margin-left: 260px;
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