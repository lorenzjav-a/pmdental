<?php
session_start();
require_once('../class/database.php');
$db = new database();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$activePage = 'appointments';
$msg = $msgType = '';

// Cancel appointment
if (isset($_POST['cancel_appointment_btn'])) {
    try {
        $appointment_id = (int)$_POST['appointment_id_to_cancel'];
        $cancellation_reason = trim($_POST['cancellation_reason'] ?? '');

        if (empty($appointment_id)) {
            throw new Exception("Invalid appointment ID.");
        }

        $db->cancelAppointment($appointment_id, $cancellation_reason);

        $msg = 'Appointment cancelled successfully!';
        $msgType = 'success';
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

$appointments = $db->viewAppointments();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - PM Dental Admin</title>
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
                <small class="text-muted">Appointment overview and status tracking</small>
            </div>
            <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
        <?php if ($msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msgType); ?> alert-dismissible fade show mt-3" role="alert">
                <?= htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="table-section">
            <h4 class="mb-4">Appointment Registry</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Dentist</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No appointments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $app): ?>
                                <?php
                                $dentistName = trim(($app['Dentist_FN'] ?? '') . ' ' . ($app['Dentist_LN'] ?? '')) ?: 'Not assigned';
                                $patientName = trim(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')) ?: 'Unknown patient';
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
                                <tr>
                                    <td>#<?= htmlspecialchars($app['Appointment_ID'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($patientName); ?></td>
                                    <td><?= htmlspecialchars($dentistName); ?></td>
                                    <td><?= !empty($app['Appointment_Date']) ? date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) : '-'; ?></td>
                                    <td><span class="badge <?= $badge; ?>"><?= htmlspecialchars($status); ?></span></td>
                                    <td>
                                        <?php if ($status === 'Cancelled' || $status === 'Completed'): ?>
                                            <button class="btn btn-sm btn-danger disabled" disabled>
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setCancelAppointment(<?= htmlspecialchars($app['Appointment_ID']); ?>, '<?= htmlspecialchars($patientName); ?>')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cancel Appointment Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin-dashboard.php" method="POST">
                    <div class="modal-body">
                        <p><strong>Patient:</strong> <span id="cancelPatientName"></span></p>
                        <label for="cancellationReason" class="form-label">Cancellation Reason (Optional)</label>
                        <textarea class="form-control" id="cancellationReason" name="cancellation_reason" rows="4" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="cancel_appointment_btn" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                    <input type="hidden" name="appointment_id_to_cancel" id="appointmentIdToCancel">
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setCancelAppointment(appointmentId, patientName) {
            document.getElementById('appointmentIdToCancel').value = appointmentId;
            document.getElementById('cancelPatientName').textContent = patientName;
        }
    </script>