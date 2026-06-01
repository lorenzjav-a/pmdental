<?php
session_start();
require_once('../class/database.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new database();
$msg = $msgType = '';

// AJAX patient details
if (isset($_GET['fetch_patient_details']) && isset($_GET['patient_id'])) {
    header('Content-Type: application/json');

    $data = $db->getPatientWithMedicalHistory((int)$_GET['patient_id']);

    echo json_encode($data ?: ['error' => 'No record found']);
    exit();
}

// Update fee
if (isset($_POST['update_fee_btn'])) {
    try {
        $sid = (int)$_POST['target_service_id'];
        $fee = (float)$_POST['consultation_fee'];

        $db->updateDentistConsultationFee($sid, $fee);

        $msg = 'Consultation fee updated successfully!';
        $msgType = 'success';
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

// Add service
if (isset($_POST['add_service_btn'])) {
    try {
        $service_name = trim($_POST['new_service_name'] ?? '');
        $service_fee = (float)($_POST['new_service_fee'] ?? 0);

        if (empty($service_name)) {
            throw new Exception("Service name is required.");
        }
        if ($service_fee < 0) {
            throw new Exception("Service fee must be a valid amount.");
        }

        $db->addService($service_name, $service_fee);

        $msg = 'New service added successfully!';
        $msgType = 'success';

        // Refresh services
        $clinicalServices = $db->viewServices();
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

// Prescription
if (isset($_POST['submit_prescription_form'])) {
    try {
        $appointment_id = (int)$_POST['rx_appointment_id'];

        $items = [];

        if (!empty($_POST['med_name']) && is_array($_POST['med_name'])) {
            foreach ($_POST['med_name'] as $i => $name) {
                if (!empty(trim($name))) {
                    $items[] = [
                        'name' => $name,
                        'qty' => $_POST['med_qty'][$i] ?? 1,
                        'dosage' => $_POST['med_dosage'][$i] ?? 'As instructed'
                    ];
                }
            }
        }

        if ($items) {
            $db->addPrescriptionWithItems($appointment_id, $items);
            $msg = "Prescription saved successfully!";
            $msgType = "success";
        } else {
            $msg = "Add at least one medicine.";
            $msgType = "warning";
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

// DATA
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

$allAppointments = $db->viewAppointments();
$clinicalServices = $db->viewServices();

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
    <title>Dentist Administration Console</title>
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


        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }


        .card-box {
            background: white;
            border-radius: 15px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            transition: 0.3s;
        }


        .card-box:hover {
            transform: translateY(-3px);
        }


        .card-box .icon {
            font-size: 32px;
            margin-bottom: 12px;
        }


        .card-box h3 {
            font-size: 16px;
            color: #555;
        }


        .card-box h1 {
            margin-top: 8px;
            font-size: 36px;
            font-weight: bold;
        }


        .table-section {
            margin-top: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }


        .table th {
            background: #0d1b2a;
            color: white;
            border-color: #1b263b;
        }
    </style>
</head>


<body>


    <?php if (file_exists('admin-sidebar.php')) {
        include 'admin-sidebar.php';
    } else { ?>
        <div class="sidebar">
            <h2>PM Dental</h2>
            <a class="active" href="admin-dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="appointments.php"><i class="fas fa-clock"></i> Appointments</a>
            <a href="clinical-vault.php"><i class="fas fa-prescription"></i> Clinical Vault</a>
        </div>
    <?php } ?>


    <div class="main">


        <div class="topbar">
            <div>
                <h3>Welcome, Dr. <?= htmlspecialchars($admin_name); ?></h3>
                <small class="text-muted">Clinical Case File Directory & Practice Manager Portal</small>
            </div>
            <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt me-2"></i>Exit System</a>
        </div>


        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msgType; ?> alert-dismissible fade show mt-4 border-0 shadow-sm" role="alert">
                <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
                <?= htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


        <div class="cards">
            <div class="card-box">
                <div class="icon text-primary"><i class="fas fa-users"></i></div>
                <h3>Total Users</h3>
                <h1><?= number_format($totalUsers); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-success"><i class="fas fa-calendar-check"></i></div>
                <h3>Appointments</h3>
                <h1><?= number_format($totalAppointments); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-warning"><i class="fas fa-clock"></i></div>
                <h3>Pending Arrivals</h3>
                <h1><?= number_format($pendingRequests); ?></h1>
            </div>
            <div class="card-box">
                <div class="icon text-danger"><i class="fas fa-user-doctor"></i></div>
                <h3>Dentist Roster</h3>
                <h1><?= number_format($totalDentists); ?></h1>
            </div>
        </div>


        <div class="row g-4">


            <!-- LEFT COLUMN -->
            <div class="col-lg-5">
                <div class="table-section h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">Clinic Consultation Costs</h4>
                            <p class="text-muted small mb-0">Adjust base procedure pricing fees and clinic financial collection parameters</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fa-solid fa-plus me-1"></i> Add Service
                        </button>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Service Procedure</th>
                                    <th>Rate Fee</th>
                                    <th>Dentist Earnings</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>


                            <tbody>
                                <?php if (empty($clinicalServices)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">
                                            No procedures tracked.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clinicalServices as $srv): ?>


                                        <?php
                                        $serviceFee = $srv['Service_Fee'] ?? 0;
                                        $dentistShare = $srv['Dentist_Fee'] ?? ($serviceFee * 0.6);
                                        ?>


                                        <tr>
                                            <td class="fw-semibold text-dark">
                                                <?= htmlspecialchars($srv['Service_Name'] ?? 'N/A'); ?>
                                            </td>


                                            <td>
                                                <div class="fw-bold text-success">
                                                    ₱<?= number_format($serviceFee, 2); ?>
                                                </div>
                                                <small class="text-muted">Total Consultation Fee</small>
                                            </td>


                                            <td>
                                                <div class="fw-bold text-primary">
                                                    ₱<?= number_format($dentistShare, 2); ?>
                                                </div>
                                                <small class="text-muted">Dentist Earnings</small>
                                            </td>


                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary update-fee-trigger"
                                                    data-id="<?= $srv['Service_ID'] ?>"
                                                    data-name="<?= htmlspecialchars($srv['Service_Name']) ?>"
                                                    data-fee="<?= $serviceFee ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>
                                            </td>
                                        </tr>


                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- RIGHT COLUMN -->
            <div class="col-lg-7">
                <div class="table-section h-100">
                    <div class="mb-3">
                        <h4 class="fw-bold text-dark mb-1">Clinical Case Records Queue</h4>
                        <p class="text-muted small mb-0">Audit baseline staff medical histories or compile prescription formulas</p>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Target Date</th>
                                    <th>Status Tracking</th>
                                    <th class="text-end">Clinical Action</th>
                                </tr>
                            </thead>


                            <tbody>
                                <?php if (empty($allAppointments) || !is_array($allAppointments)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No recent appointments found in directory queue matching grids.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($allAppointments, 0, 6) as $app): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <?= htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')); ?>
                                            </td>


                                            <td class="small text-muted">
                                                <?= !empty($app['Appointment_Date']) ? date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) : '-'; ?>
                                            </td>


                                            <td>
                                                <?php
                                                $status = $app['Appointment_Status'] ?? 'Unknown';
                                                $badge = ($status === 'Confirmed') ? 'bg-success' : (($status === 'Pending') ? 'bg-warning text-dark' : 'bg-danger');
                                                ?>
                                                <span class="badge <?= $badge; ?>"><?= htmlspecialchars($status); ?></span>
                                            </td>


                                            <td class="text-end">
                                                <div class="btn-group gap-1">

                                                    <a href="users.php?patient_id=<?= $app['Patient_ID'] ?? 0; ?>"
                                                        class="btn btn-sm btn-light border text-primary">
                                                        <i class="fa-solid fa-folder-medical"></i> View Chart
                                                    </a>

                                                    <a href="appointments.php?appointment_id=<?= $app['Appointment_ID'] ?? 0; ?>"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="fa-solid fa-pills me-1"></i> + Rx
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>


        </div>
    </div>


    <!-- MODALS (UNCHANGED) -->
    <!-- Fee Modal -->
    <div class="modal fade" id="feeSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form action="" method="POST" class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Update Service Consultation Fee</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="target_service_id" id="target_service_id">
                    <div class="mb-2">
                        <label class="form-label text-muted small mb-1">Target Procedure Label</label>
                        <input type="text" id="target_service_name" class="form-control form-control-sm bg-light fw-semibold text-secondary" readonly>
                    </div>
                    <div>
                        <label class="form-label small fw-bold mb-1">Consultation Value Fee ($)</label>
                        <input type="number" step="0.01" min="0.00" name="consultation_fee" id="consultation_fee" class="form-control form-control-sm fw-bold text-danger" required>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_fee_btn" class="btn btn-sm btn-success">Apply Rates</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form action="" method="POST" class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add New Service</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Service Name</label>
                        <input type="text" name="new_service_name" class="form-control form-control-sm" placeholder="e.g., Root Canal Therapy" required>
                    </div>
                    <div>
                        <label class="form-label small fw-bold mb-1">Service Fee (₱)</label>
                        <input type="number" step="0.01" min="0.00" name="new_service_fee" class="form-control form-control-sm fw-bold text-danger" placeholder="0.00" required>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_service_btn" class="btn btn-sm btn-success">Add Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- (OTHER MODALS + SCRIPT UNCHANGED — kept as-is in your original) -->


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {


            document.addEventListener('click', function(e) {


                // ================= VIEW CHART =================
                const viewBtn = e.target.closest('.view-intake-btn');
                if (viewBtn) {
                    const patId = viewBtn.getAttribute('data-patient-id');
                    const listbox = document.getElementById('history_feed_box');


                    listbox.innerHTML = `
                <div class="text-center py-3 text-muted small">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    Loading...
                </div>`;


                    const modal = new bootstrap.Modal(document.getElementById('patientIntakeModal'));
                    modal.show();


                    fetch('?fetch_patient_details=1&patient_id=' + patId)
                        .then(r => r.json())
                        .then(res => {


                            if (res.error) {
                                listbox.innerHTML = `<div class="alert alert-warning small p-2">${res.error}</div>`;
                                return;
                            }


                            document.getElementById('chart_name').innerText =
                                (res.Patient_FN || '') + ' ' + (res.Patient_LN || '');


                            document.getElementById('chart_phone').innerText =
                                res.Patient_PhoneNo || 'None';


                            document.getElementById('chart_dob').innerText =
                                res.Patient_BirthDate || 'Not specified';


                            document.getElementById('chart_gender').innerText =
                                res.Patient_Gender || 'unspecified';


                            listbox.innerHTML = '';


                            if (!res.medical_history || res.medical_history.length === 0) {
                                listbox.innerHTML = `<div class="text-muted small">No history found</div>`;
                                return;
                            }


                            res.medical_history.forEach(h => {
                                listbox.innerHTML += `
                            <div class="border p-2 mb-2 rounded">
                                <b class="text-danger">${h.Med_History_Name}</b>
                                <div class="small text-muted">${h.Med_History_Desc || ''}</div>
                            </div>`;
                            });
                        });
                }


                // ================= EDIT FEE =================
                const editBtn = e.target.closest('.update-fee-trigger');
                if (editBtn) {
                    document.getElementById('target_service_id').value = editBtn.dataset.id;
                    document.getElementById('target_service_name').value = editBtn.dataset.name;
                    document.getElementById('consultation_fee').value = editBtn.dataset.fee;


                    new bootstrap.Modal(document.getElementById('feeSettingsModal')).show();
                }


                // ================= RX =================
                const rxBtn = e.target.closest('.write-rx-btn');
                if (rxBtn) {
                    document.getElementById('rx_appointment_id').value = rxBtn.dataset.apptId;
                    document.getElementById('rx_patient_label').innerText = rxBtn.dataset.patientFullname;


                    new bootstrap.Modal(document.getElementById('prescriptionWritingModal')).show();
                }


            });


            // ================= ADD RX ROW =================
            document.getElementById('addRxItemRow').addEventListener('click', function() {
                const container = document.getElementById('rx_items_container');


                const row = document.createElement('div');
                row.className = 'row g-2 align-items-center rx-item-row mb-2 pb-2 border-bottom';


                row.innerHTML = `
            <div class="col-5"><input type="text" name="med_name[]" class="form-control form-control-sm" required></div>
            <div class="col-2"><input type="number" name="med_qty[]" value="1" class="form-control form-control-sm"></div>
            <div class="col-4"><input type="text" name="med_dosage[]" class="form-control form-control-sm"></div>
            <div class="col-1 text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-rx-row">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;


                container.appendChild(row);
            });


            // ================= REMOVE RX ROW =================
            document.getElementById('rx_items_container').addEventListener('click', function(e) {
                if (e.target.closest('.remove-rx-row')) {
                    const row = e.target.closest('.rx-item-row');
                    if (document.querySelectorAll('.rx-item-row').length > 1) {
                        row.remove();
                    } else {
                        alert('At least one row required.');
                    }
                }
            });


        });
    </script>


</body>

</html>