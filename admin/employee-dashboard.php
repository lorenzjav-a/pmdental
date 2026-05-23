<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
  header("Location: login.php");
  exit();
}

// FIXED AJAX ENDPOINT: Serves data to the "+ View Prescription" button on the Patient Table via Patient ID
if (isset($_GET['fetch_prescription_json'], $_GET['patient_id'])) {
  header('Content-Type: application/json');
  // Routes data to your database method that fetches data fields based on Patient ID
  $data = $con->getPrescriptionItemsByPatient((int)$_GET['patient_id']);
  echo json_encode($data ? $data : []);
  exit();
}

// EXISTING AJAX ENDPOINT: Fetch calendar data
if (isset($_GET['fetch_dentist_calendar'], $_GET['dentist_id'])) {
  header('Content-Type: application/json');
  $appts = $con->getDentistAppointments((int)$_GET['dentist_id']);
  $events = [];
  foreach ($appts as $a) {
    $events[] = [
      'title' => $a['Patient_FN'] . ' ' . $a['Patient_LN'],
      'start' => $a['Appointment_Date'],
      'color' => $a['Appointment_Status'] === 'Confirmed' ? '#198754' : '#ffc107',
    ];
  }
  echo json_encode($events);
  exit();
}

$current_employee_id = $_SESSION['user_id'];

$allAppointments = $con->viewAppointments();
$allDentists = $con->viewDentists();
$allPatients = $con->viewPatients();

$assignStatus = null;
$assignMessage = '';
$alertStatus = null;
$alertMessage = '';

if (isset($_POST['assign_dentist'])) {
  $appointment_id = $_POST['appointment_id'];
  $dentist_id = $_POST['dentist_id'];

  try {
    $con->assignDentistToAppointment($appointment_id, $current_employee_id, $dentist_id);
    $assignStatus = 'success';
    $assignMessage = 'Dentist assigned and appointment confirmed successfully!';
    $allAppointments = $con->viewAppointments();
  } catch (Exception $e) {
    $assignStatus = 'error';
    $assignMessage = $e->getMessage();
  }
}

if (isset($_POST['save_med_history'])) {
  $patient_id = $_POST['history_patient_id'];
  $condition  = trim($_POST['med_condition']);
  $notes      = trim($_POST['med_notes'] ?? '');

  try {
    $con->addMedicalHistory($patient_id, $condition, $notes);
    $alertStatus = 'success';
    $alertMessage = 'Medical history parameters successfully logged to patient profile.';
  } catch (Exception $e) {
    $alertStatus = 'error';
    $alertMessage = $e->getMessage();
  }
}

if (isset($_POST['execute_checkout'])) {
  $appointment_id = (int)$_POST['checkout_appointment_id'];
  $payment_method = $_POST['payment_method'];
  $payment_status = $_POST['payment_status'];
  
  try {
      $con->updatePaymentStatus($appointment_id, $payment_method, $payment_status);
      $alertStatus = 'success';
      $alertMessage = 'Transaction receipt processed and settled successfully.';
      $allAppointments = $con->viewAppointments();
  } catch (Exception $e) {
      $alertStatus = 'error';
      $alertMessage = $e->getMessage();
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Dashboard - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="employee-dashboard.php">PM Dental Admin</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navStatic">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="navStatic" class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto gap-lg-1">
          <li class="nav-item"><a class="nav-link active" href="employee-dashboard.php">Appointments Desk</a></li>
        </ul>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary">Role: CLINIC STAFF</span>
          <a class="btn btn-sm btn-outline-secondary" href="login.php">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <main class="container py-4">

    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
              <h4 class="mb-0 fw-bold text-dark">Dentist Schedule Rosters</h4>
              <p class="text-muted small mb-0">Filter rosters using the selector to audit individual clinical availability</p>
            </div>
            <div style="min-width: 280px;">
              <select class="form-select" id="dentistPicker">
                <option value="" selected disabled>Choose a dentist to view roster...</option>
                <?php if (is_array($allDentists)): ?>
                  <?php foreach ($allDentists as $doc): ?>
                    <option value="<?= $doc['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(($doc['Dentist_FN'] ?? '') . ' ' . ($doc['Dentist_LN'] ?? '')); ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <div id="dentistCalendar"></div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <div class="mb-3">
            <h4 class="mb-0 fw-bold text-dark">Patient Appointments Queue</h4>
            <p class="text-muted small mb-0">Review pending arrivals and match records with dynamic doctor assignments</p>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Appointment ID</th>
                  <th>Patient Name</th>
                  <th>Contact Info</th>
                  <th>Requested Service</th>
                  <th>Target Date</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allAppointments) || !is_array($allAppointments)): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No logged records found inside the queue matrix.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($allAppointments as $app) {
                    $statusBadge = ($app['Appointment_Status'] == 'Confirmed') ? 'bg-success' : 'bg-warning text-dark';
                    $serviceName = !empty($app['Service_Name']) ? $app['Service_Name'] : 'Not Specified';
                    $fullName = htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? ''));
                    $paymentAmount = number_format($app['Payment_Amount'] ?? 0.00, 2);

                    echo '<tr>';
                    echo '<td>#' . $app['Appointment_ID'] . '</td>';
                    echo '<td class="fw-medium">' . $fullName . '</td>';
                    echo '<td>' . htmlspecialchars($app['Patient_PhoneNo'] ?? '') . '</td>';
                    echo '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($serviceName) . '</span></td>';
                    echo '<td>' . date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) . '</td>';
                    echo '<td><span class="badge ' . $statusBadge . '">' . $app['Appointment_Status'] . '</span></td>';
                    echo '<td class="text-end d-flex justify-content-end gap-1">';

                    if ($app['Appointment_Status'] != 'Confirmed') {
                      echo '<button type="button" class="btn btn-sm btn-primary px-2" 
                              data-bs-toggle="modal" 
                              data-bs-target="#assignModal" 
                              data-app-id="' . $app['Appointment_ID'] . '" 
                              data-patient-name="' . $fullName . '"
                            >Match Dentist</button>';
                    } else {
                      echo '<button class="btn btn-sm btn-outline-secondary px-2" disabled>Assigned</button>';
                    }

                    // CLEANED/REMOVED: The repetitive "View Dentist Rx" button has been cleanly taken out of here

                    echo '<button type="button" class="btn btn-sm btn-success px-2"
                            data-bs-toggle="modal"
                            data-bs-target="#checkoutModal"
                            data-app-id="' . $app['Appointment_ID'] . '"
                            data-patient-name="' . $fullName . '"
                            data-service="' . htmlspecialchars($serviceName) . '"
                            data-amount="' . $paymentAmount . '"
                            data-method="' . htmlspecialchars($app['Payment_Method'] ?? 'Cash') . '"
                            data-status="' . htmlspecialchars($app['Payment_Status'] ?? 'Pending') . '"
                          >Checkout</button>';

                    echo '</td></tr>';
                  } ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <div class="mb-3">
            <h4 class="mb-0 fw-bold text-dark">Patient Profile Masterlist</h4>
            <p class="text-muted small mb-0">Browse complete baseline client credentials and append history charts</p>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Patient ID</th>
                  <th>Full Name</th>
                  <th>Gender</th>
                  <th>Date of Birth</th>
                  <th>Contact Line</th>
                  <th class="text-end">Clinical Intake</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allPatients) || !is_array($allPatients)): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No structural patient registry footprints recorded.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($allPatients as $pat): ?>
                    <?php $pName = htmlspecialchars(($pat['Patient_FN'] ?? '') . ' ' . ($pat['Patient_LN'] ?? '')); ?>
                    <tr>
                      <td>#<?= $pat['Patient_ID']; ?></td>
                      <td class="fw-semibold text-primary"><?= $pName; ?></td>
                      <td><span class="text-capitalize"><?= htmlspecialchars($pat['Patient_Gender'] ?? 'unspecified'); ?></span></td>
                      <td><?= !empty($pat['Patient_BirthDate']) ? date('M d, Y', strtotime($pat['Patient_BirthDate'])) : 'Not Typed'; ?></td>
                      <td><?= htmlspecialchars($pat['Patient_PhoneNo'] ?? ''); ?></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-info px-2 me-1 view-pres-btn"
                          data-patient-id="<?= $pat['Patient_ID']; ?>"
                          data-patient-name="<?= $pName; ?>">
                          View Prescription
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger px-2"
                          data-bs-toggle="modal"
                          data-bs-target="#historyModal"
                          data-patient-id="<?= $pat['Patient_ID']; ?>"
                          data-patient-name="<?= $pName; ?>">
                          + Add Med History
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
    </div>
  </main>

  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Assign Dentist to Schedule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form action="employee-dashboard.php" method="POST">
            <input type="hidden" name="appointment_id" id="modal_appointment_id">
            <div class="mb-3 bg-light p-3 rounded">
              <span class="text-muted small d-block">Processing Patient:</span>
              <strong id="modal_patient_name" class="fs-5 text-primary"></strong>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-medium">Available Specialists Matrix</label>
              <select class="form-select" name="dentist_id" required>
                <option value="" selected disabled>Select clinic doctor to assign...</option>
                <?php if (is_array($allDentists)): ?>
                  <?php foreach ($allDentists as $doc): ?>
                    <option value="<?= $doc['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(($doc['Dentist_FN'] ?? '') . ' ' . ($doc['Dentist_LN'] ?? '')); ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="row g-2">
              <div class="col-6"><button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancel</button></div>
              <div class="col-6"><button name="assign_dentist" class="btn btn-primary w-100" type="submit">Confirm Assignment</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Append Medical History Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form action="employee-dashboard.php" method="POST">
            <input type="hidden" name="history_patient_id" id="history_patient_id">
            <div class="mb-3 bg-light p-3 rounded">
              <span class="text-muted small d-block">Recording health metrics for:</span>
              <strong id="history_patient_name" class="text-danger fs-5"></strong>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-medium">Condition Name / Allergy</label>
              <input type="text" name="med_condition" class="form-control" placeholder="e.g., Hypertension, Penicillin Allergy, Diabetes" required>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-medium">Clinical Notes / Descriptions (Optional)</label>
              <textarea name="med_notes" class="form-control" rows="3" placeholder="Specify severity levels..."></textarea>
            </div>
            <div class="row g-2">
              <div class="col-6"><button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Close</button></div>
              <div class="col-6"><button type="submit" name="save_med_history" class="btn btn-danger w-100">Save History Record</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="prescriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-prescription me-2"></i>Dentist Authorized Patient Prescription</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3 bg-light p-3 rounded border">
            <span class="text-muted small d-block">Patient Information View:</span>
            <strong id="pres_patient_name" class="fs-5 text-dark"></strong>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Prescribed Item Name</th>
                  <th class="text-center" style="width: 100px;">Quantity</th>
                  <th>Dosage / Clinical Instructions</th>
                </tr>
              </thead>
              <tbody id="prescription_results_body"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer bg-light p-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close View</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-cash-register me-2"></i>Checkout Payment Terminal</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="employee-dashboard.php" method="POST" class="modal-body">
          <input type="hidden" name="checkout_appointment_id" id="checkout_appointment_id">
          
          <div class="mb-3 bg-light p-3 rounded">
            <span class="text-muted small d-block">Patient Reference:</span>
            <strong id="checkout_patient_name" class="text-dark d-block"></strong>
            <span class="text-muted small d-block mt-2">Procedure Performed:</span>
            <span id="checkout_service_name" class="badge bg-white text-primary border"></span>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-medium">Total Balance Statement Due</label>
            <div class="input-group">
              <span class="input-group-text fw-bold">$</span>
              <input type="text" id="checkout_amount" class="form-control fw-bold text-danger bg-white" readonly>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-6">
              <label class="form-label small fw-medium">Method Tracker</label>
              <select class="form-select" name="payment_method" id="checkout_method" required>
                <option value="Cash">Cash Terminal</option>
                <option value="Card">Debit/Credit Card</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">Billing Audit Status</label>
              <select class="form-select" name="payment_status" id="checkout_status" required>
                <option value="Paid">Paid / Settled</option>
                <option value="Pending">Pending / Unpaid</option>
              </select>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-6"><button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancel</button></div>
            <div class="col-6"><button type="submit" name="execute_checkout" class="btn btn-success w-100">Settle Bill</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../sweetalert/dist/sweetalert2.js"></script>

  <script>
    const assignModal = document.getElementById("assignModal");
    assignModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      document.getElementById('modal_appointment_id').value = btn.getAttribute('data-app-id') || '';
      document.getElementById('modal_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
    });

    const historyModal = document.getElementById("historyModal");
    historyModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      document.getElementById('history_patient_id').value = btn.getAttribute('data-patient-id') || '';
      document.getElementById('history_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
    });

    const checkoutModal = document.getElementById("checkoutModal");
    checkoutModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      document.getElementById('checkout_appointment_id').value = btn.getAttribute('data-app-id') || '';
      document.getElementById('checkout_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
      document.getElementById('checkout_service_name').innerText = btn.getAttribute('data-service') || '';
      document.getElementById('checkout_amount').value = btn.getAttribute('data-amount') || '0.00';
      document.getElementById('checkout_method').value = btn.getAttribute('data-method') || 'Cash';
      document.getElementById('checkout_status').value = btn.getAttribute('data-status') || 'Pending';
    });

    // INTEGRATED SINGLE ROUTINE: Intercepts clicks on Master Patient view-pres-btn list elements
    document.querySelectorAll('.view-pres-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const patientId = this.getAttribute('data-patient-id');
        const patientName = this.getAttribute('data-patient-name');
        
        document.getElementById('pres_patient_name').innerText = patientName;
        const tbody = document.getElementById('prescription_results_body');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-info me-2"></div>Pulling consolidated prescription records...</td></tr>';
        
        new bootstrap.Modal(document.getElementById('prescriptionModal')).show();

        fetch('employee-dashboard.php?fetch_prescription_json=1&patient_id=' + patientId)
          .then(r => r.json())
          .then(data => {
            tbody.innerHTML = '';
            if(!data || data.length === 0) {
              tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No active prescription notes matched with this patient account.</td></tr>';
              return;
            }
            data.forEach(item => {
              tbody.innerHTML += `<tr>
                <td class="fw-bold text-dark">${item.Item_Name || 'Unspecified'}</td>
                <td class="text-center"><span class="badge bg-light text-dark border">Qty: ${item.Item_Quantity || 0}</span></td>
                <td class="text-muted small">${item.Pres_Dosage || 'As written by attending dentist'}</td>
              </tr>`;
            });
          }).catch(() => {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Error accessing database values.</td></tr>';
          });
      });
    });
  </script>

  <script>
    const alertMsgStatus = <?php echo json_encode($alertStatus) ?>;
    const alertMsgText = <?php echo json_encode($alertMessage) ?>;
    const assignMsgStatus = <?php echo json_encode($assignStatus) ?>;
    const assignMsgText = <?php echo json_encode($assignMessage) ?>;

    if (alertMsgStatus === 'success') {
      Swal.fire({ icon: 'success', title: 'Confirmed', text: alertMsgText });
    } else if (alertMsgStatus === 'error') {
      Swal.fire({ icon: 'error', title: 'Action Failed', text: alertMsgText });
    }

    if (assignMsgStatus === 'success') {
      Swal.fire({ icon: 'success', title: 'Assigned', text: assignMsgText });
    } else if (assignMsgStatus === 'error') {
      Swal.fire({ icon: 'error', title: 'Assignment Failed', text: assignMsgText });
    }
  </script>

  <script>
    const cal = new FullCalendar.Calendar(document.getElementById('dentistCalendar'), {
      initialView: 'dayGridMonth',
      height: 500,
      events: []
    });
    cal.render();

    document.getElementById('dentistPicker').addEventListener('change', function() {
      fetch('employee-dashboard.php?fetch_dentist_calendar=1&dentist_id=' + this.value)
        .then(r => r.json())
        .then(events => {
          cal.removeAllEvents();
          cal.addEventSource(events);
        });
    });
  </script>

</body>
</html>