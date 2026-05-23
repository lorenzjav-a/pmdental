<?php
session_start();
require_once('../class/database.php');
$db = new database();
$msg = $msgType = '';

// Intercept asynchronous AJAX inquiries for patient intake history data profiles cleanly
if (isset($_GET['fetch_patient_details']) && isset($_GET['patient_id'])) {
    header('Content-Type: application/json');
    $data = $db->getPatientWithMedicalHistory((int)$_GET['patient_id']);
    echo json_encode($data ? $data : ['error' => 'No operational record matched']);
    exit();
}

// Intercept submission requests for changing existing consultation fee criteria updates
if (isset($_POST['update_fee_btn'])) {
    try {
        $sid = (int)$_POST['target_service_id'];
        $fee = (float)$_POST['consultation_fee'];
        $db->updateDentistConsultationFee($sid, $fee);
        $msg = 'Clinical procedure consultation cost structures adjusted successfully!';
        $msgType = 'success';
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

// Intercept structured dynamic prescription generation table posts
if (isset($_POST['submit_prescription_form'])) {
    try {
        $appointment_id = (int)$_POST['rx_appointment_id'];
        
        // Structure individual array listings safely
        $prescription_items = [];
        if (isset($_POST['med_name']) && is_array($_POST['med_name'])) {
            for ($i = 0; $i < count($_POST['med_name']); $i++) {
                if (!empty(trim($_POST['med_name'][$i]))) {
                    $prescription_items[] = [
                        'name'    => $_POST['med_name'][$i],
                        'qty'     => $_POST['med_qty'][$i] ?? 1,
                        'dosage'  => $_POST['med_dosage'][$i] ?? 'As instructed'
                    ];
                }
            }
        }

        if (!empty($prescription_items)) {
            $db->addPrescriptionWithItems($appointment_id, $prescription_items);
            $msg = 'Prescription matrix logged successfully to system patient chart registry.';
            $msgType = 'success';
        } else {
            $msg = 'Please append at least one explicit drug compound record before submitting.';
            $msgType = 'warning';
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msgType = 'danger';
    }
}

// Baseline data bindings
$admin_name = $_SESSION['admin_name'] ?? 'Attending Dentist';
$activePage = 'dashboard';
$allAppointments = $db->viewAppointments();
$clinicalServices = $db->viewServices(); // Load our billing service items
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f7fb; }
        .sidebar { width: 250px; height: 100vh; position: fixed; background: #0d1b2a; color: white; padding-top: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 35px; font-weight: bold; }
        .sidebar a { display: block; color: #d6d6d6; text-decoration: none; padding: 15px 25px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #1b263b; color: white; padding-left: 30px; }
        .sidebar i { margin-right: 10px; }
        .main { margin-left: 250px; padding: 25px; }
        .topbar { background: white; border-radius: 12px; padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .logout-btn { background: #dc3545; color: white; text-decoration: none; padding: 10px 15px; border-radius: 8px; transition: 0.3s; }
        .logout-btn:hover { background: #bb2d3b; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 25px; }
        .card-box { background: white; border-radius: 15px; padding: 22px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06); transition: 0.3s; }
        .card-box:hover { transform: translateY(-3px); }
        .card-box .icon { font-size: 32px; margin-bottom: 12px; }
        .card-box h3 { font-size: 16px; color: #555; }
        .card-box h1 { margin-top: 8px; font-size: 36px; font-weight: bold; }
        .table-section { margin-top: 30px; background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .table th { background: #0d1b2a; color: white; border-color: #1b263b; }
    </style>
</head>
<body>

    <?php if(file_exists('admin-sidebar.php')) { include 'admin-sidebar.php'; } else { ?>
    <div class="sidebar">
        <h2>PM Dental</h2>
        <a class="active" href="#"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="#"><i class="fas fa-clock"></i> Appointments</a>
        <a href="#"><i class="fas fa-prescription"></i> Clinical Vault</a>
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
            <div class="card-box"><div class="icon text-primary"><i class="fas fa-users"></i></div><h3>Total Users</h3><h1><?= number_format($totalUsers); ?></h1></div>
            <div class="card-box"><div class="icon text-success"><i class="fas fa-calendar-check"></i></div><h3>Appointments</h3><h1><?= number_format($totalAppointments); ?></h1></div>
            <div class="card-box"><div class="icon text-warning"><i class="fas fa-clock"></i></div><h3>Pending Arrivals</h3><h1><?= number_format($pendingRequests); ?></h1></div>
            <div class="card-box"><div class="icon text-danger"><i class="fas fa-user-doctor"></i></div><h3>Dentist Roster</h3><h1><?= number_format($totalDentists); ?></h1></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="table-section h-100">
                    <div class="mb-3">
                        <h4 class="fw-bold text-dark mb-1">Clinic Consultation Costs</h4>
                        <p class="text-muted small mb-0">Adjust base procedure pricing fees and clinic financial collection parameters</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Service Procedure</th>
                                    <th>Rate Fee</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($clinicalServices)): ?>
                                    <tr><td colspan="3" class="text-center py-3 text-muted">No procedures tracked.</td></tr>
                                <?php else: ?>
                                    <?php foreach($clinicalServices as $srv): ?>
                                        <tr>
                                            <td class="fw-semibold text-secondary small"><?= htmlspecialchars($srv['Service_Name']); ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($srv['Service_Fee'], 2); ?></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary px-2 update-fee-trigger" 
                                                        data-id="<?= $srv['Service_ID']; ?>"
                                                        data-name="<?= htmlspecialchars($srv['Service_Name']); ?>"
                                                        data-fee="<?= $srv['Service_Fee']; ?>">
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
                                    <tr><td colspan="4" class="text-center text-muted py-4">No recent appointments found in directory queue matching grids.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($allAppointments, 0, 6) as $app): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')); ?></td>
                                            <td class="small text-muted"><?= !empty($app['Appointment_Date']) ? date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) : '-'; ?></td>
                                            <td>
                                                <?php
                                                $status = $app['Appointment_Status'] ?? 'Unknown';
                                                $badge = ($status === 'Confirmed') ? 'bg-success' : (($status === 'Pending') ? 'bg-warning text-dark' : 'bg-danger');
                                                ?>
                                                <span class="badge <?= $badge; ?>"><?= htmlspecialchars($status); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group gap-1">
                                                    <button type="button" class="btn btn-sm btn-light border text-primary view-intake-btn" 
                                                            data-patient-id="<?= $app['Patient_ID'] ?? 0; ?>">
                                                        <i class="fa-solid fa-folder-medical"></i> View Chart
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-primary write-rx-btn"
                                                            data-appt-id="<?= $app['Appointment_ID']; ?>"
                                                            data-patient-fullname="<?= htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')); ?>">
                                                        <i class="fa-solid fa-pills me-1"></i> + Rx
                                                    </button>
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

    <div class="modal fade" id="patientIntakeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fw-bold small"><i class="fa-solid fa-address-card me-2 text-info"></i>Comprehensive Profile Chart Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="p-3 bg-light rounded mb-3 border">
                        <div class="row g-2 small">
                            <div class="col-6"><span class="text-muted">Full Account Name:</span> <strong id="chart_name" class="d-block text-dark fs-6"></strong></div>
                            <div class="col-6"><span class="text-muted">Contact Handle:</span> <strong id="chart_phone" class="d-block text-dark"></strong></div>
                            <div class="col-6"><span class="text-muted">Date of Birth:</span> <strong id="chart_dob" class="d-block text-secondary"></strong></div>
                            <div class="col-6"><span class="text-muted">Gender Footprint:</span> <strong id="chart_gender" class="d-block text-capitalize text-secondary"></strong></div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold text-danger border-bottom pb-2 mb-2"><i class="fa-solid fa-notes-medical me-2"></i>Staff Appended Medical History Profile</h6>
                    <div id="history_feed_box" style="max-height: 220px; overflow-y: auto;">
                        </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close Profile View</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="prescriptionWritingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <form action="" method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold small"><i class="fa-solid fa-prescription-bottle-medical me-2"></i>Generate Patient Prescription Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="rx_appointment_id" id="rx_appointment_id">
                    <div class="mb-3 bg-light p-3 rounded border">
                        <span class="text-muted small d-block">Attending Patient Reference Account:</span>
                        <strong id="rx_patient_label" class="text-primary fs-5"></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark small"><i class="fa-solid fa-list-check me-2"></i>Prescribed Medicine Item Breakdown</span>
                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" id="addRxItemRow" style="font-size: 12px;">+ Add Item Row</button>
                    </div>

                    <div id="rx_items_container" style="max-height: 250px; overflow-y: auto;" class="pe-1">
                        <div class="row g-2 align-items-center rx-item-row mb-2 pb-2 border-bottom">
                            <div class="col-5">
                                <input type="text" name="med_name[]" class="form-control form-control-sm" placeholder="Medicine / Item Name" required>
                            </div>
                            <div class="col-2">
                                <input type="number" min="1" value="1" name="med_qty[]" class="form-control form-control-sm text-center" placeholder="Qty">
                            </div>
                            <div class="col-4">
                                <input type="text" name="med_dosage[]" class="form-control form-control-sm" placeholder="Dosage (e.g., 500mg BID)">
                            </div>
                            <div class="col-1 text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger remove-rx-row p-0"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Discard Form</button>
                    <button type="submit" name="submit_prescription_form" class="btn btn-sm btn-primary">Save & Record Prescription</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Consultation fee popup trigger populate logic
        document.querySelectorAll('.update-fee-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('target_service_id').value = this.getAttribute('data-id');
                document.getElementById('target_service_name').value = this.getAttribute('data-name');
                document.getElementById('consultation_fee').value = this.getAttribute('data-fee');
                new bootstrap.Modal(document.getElementById('feeSettingsModal')).show();
            });
        });

        // Async patient history dashboard inspector retrieval routines
        document.querySelectorAll('.view-intake-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const patId = this.getAttribute('data-patient-id');
                const listbox = document.getElementById('history_feed_box');
                
                listbox.innerHTML = '<div class="text-center py-3 text-muted small"><div class="spinner-border spinner-border-sm me-2"></div>Accessing baseline medical profiles...</div>';
                const profileModal = new bootstrap.Modal(document.getElementById('patientIntakeModal'));
                profileModal.show();

                fetch('?fetch_patient_details=1&patient_id=' + patId)
                    .then(r => r.json())
                    .then(res => {
                        if(res.error) {
                            listbox.innerHTML = `<div class="alert alert-warning small p-2">${res.error}</div>`;
                            return;
                        }
                        document.getElementById('chart_name').innerText = (res.Patient_FN || '') + ' ' + (res.Patient_LN || '');
                        document.getElementById('chart_phone').innerText = res.Patient_PhoneNo || 'None Recorded';
                        document.getElementById('chart_dob').innerText = res.Patient_BirthDate || 'Not specified';
                        document.getElementById('chart_gender').innerText = res.Patient_Gender || 'unspecified';

                        listbox.innerHTML = '';
                        if(!res.medical_history || res.medical_history.length === 0) {
                            listbox.innerHTML = '<div class="text-center text-muted small py-4 bg-white border rounded">No historical parameters logged yet for this patient timeline.</div>';
                            return;
                        }
                        res.medical_history.forEach(h => {
                            listbox.innerHTML += `
                                <div class="bg-white p-2 rounded border mb-2 shadow-sm border-start border-danger border-3">
                                    <h6 class="mb-1 text-danger fw-bold small text-uppercase">${h.Med_History_Name}</h6>
                                    <p class="mb-0 text-muted small" style="font-size:12px;">${h.Med_History_Desc || 'No extended descriptions provided.'}</p>
                                </div>`;
                        });
                    }).catch(() => {
                        listbox.innerHTML = '<div class="text-center text-danger small py-3">Failed loading system diagnostic metrics.</div>';
                    });
            });
        });

        // Initialize modal configurations for dynamic interactive prescriptions
        document.querySelectorAll('.write-rx-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('rx_appointment_id').value = this.getAttribute('data-appt-id');
                document.getElementById('rx_patient_label').innerText = this.getAttribute('data-patient-fullname');
                new bootstrap.Modal(document.getElementById('prescriptionWritingModal')).show();
            });
        });

        // Dynamic formula row adding handler logic components
        document.getElementById('addRxItemRow').addEventListener('click', function() {
            const container = document.getElementById('rx_items_container');
            const targetRow = document.createElement('div');
            targetRow.className = 'row g-2 align-items-center rx-item-row mb-2 pb-2 border-bottom animate-fade';
            targetRow.innerHTML = `
                <div class="col-5"><input type="text" name="med_name[]" class="form-control form-control-sm" placeholder="Medicine / Item Name" required></div>
                <div class="col-2"><input type="number" min="1" value="1" name="med_qty[]" class="form-control form-control-sm text-center"></div>
                <div class="col-4"><input type="text" name="med_dosage[]" class="form-control form-control-sm" placeholder="Dosage structure instructions"></div>
                <div class="col-1 text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-rx-row p-0"><i class="fa-solid fa-trash"></i></button></div>`;
            container.appendChild(targetRow);
        });

        // Item context deletion handler routing loop updates
        document.getElementById('rx_items_container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-rx-row')) {
                const targetRow = e.target.closest('.rx-item-row');
                const totalRows = document.querySelectorAll('.rx-item-row').length;
                if (totalRows > 1) {
                    targetRow.remove();
                } else {
                    alert('Prescription sheets must maintain a baseline definition minimum of one medicine row entry.');
                }
            }
        });
    </script>
</body>
</html>