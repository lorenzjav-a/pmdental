<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
  header("Location: login.php");
  exit();
}


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
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Dashboard - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css">
  <link href="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css)" rel="stylesheet">
  <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js)"></script>

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
    <div class="row g-4">
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

                    echo '<tr>';
                    echo '<td>#' . $app['Appointment_ID'] . '</td>';
                    echo '<td class="fw-medium">' . htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')) . '</td>';
                    echo '<td>' . htmlspecialchars($app['Patient_PhoneNo'] ?? '') . '</td>';
                    echo '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($serviceName) . '</span></td>';
                    echo '<td>' . date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) . '</td>';
                    echo '<td><span class="badge ' . $statusBadge . '">' . $app['Appointment_Status'] . '</span></td>';
                    echo '<td class="text-end">';

                    if ($app['Appointment_Status'] != 'Confirmed') {
                      echo '<button type="button" class="btn btn-sm btn-primary px-3" 
                              data-bs-toggle="modal" 
                              data-bs-target="#assignModal" 
                              data-app-id="' . $app['Appointment_ID'] . '" 
                              data-patient-name="' . htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? '')) . '"
                            >Match Dentist</button>';
                    } else {
                      echo '<button class="btn btn-sm btn-outline-secondary px-3" disabled>Assigned</button>';
                    }
                    echo '</td></tr>';
                  } ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Dentist Calendar -->
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <h4 class="fw-bold mb-1">Dentist Schedule Calendar</h4>
          <p class="text-muted small mb-3">Select a dentist to view their schedule</p>

          <select id="dentistPicker" class="form-select w-auto mb-3">
            <option value="" disabled selected>— Select a dentist —</option>
            <?php foreach ($allDentists as $doc): ?>
              <option value="<?= $doc['Dentist_ID'] ?>">
                Dr. <?= htmlspecialchars($doc['Dentist_FN'] . ' ' . $doc['Dentist_LN']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div class="d-flex gap-3 mb-2 small">
            <span><span class="badge bg-success">&nbsp;</span> Confirmed</span>
            <span><span class="badge bg-warning text-dark">&nbsp;</span> Pending</span>
          </div>

          <div id="dentistCalendar"></div>
        </div>
      </div>


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
                    <tr>
                      <td>#<?= $pat['Patient_ID']; ?></td>
                      <td class="fw-semibold text-primary"><?= htmlspecialchars(($pat['Patient_FN'] ?? '') . ' ' . ($pat['Patient_LN'] ?? '')); ?></td>
                      <td><span class="text-capitalize"><?= htmlspecialchars($pat['Patient_Gender'] ?? 'unspecified'); ?></span></td>
                      <td><?= !empty($pat['Patient_BirthDate']) ? date('M d, Y', strtotime($pat['Patient_BirthDate'])) : 'Not Typed'; ?></td>
                      <td><?= htmlspecialchars($pat['Patient_PhoneNo'] ?? ''); ?></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger px-2"
                          data-bs-toggle="modal"
                          data-bs-target="#historyModal"
                          data-patient-id="<?= $pat['Patient_ID']; ?>"
                          data-patient-name="<?= htmlspecialchars(($pat['Patient_FN'] ?? '') . ' ' . ($pat['Patient_LN'] ?? '')); ?>">
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
  </script>

  <script>
    const alertMsgStatus = <?php echo json_encode($alertStatus) ?>;
    const alertMsgText = <?php echo json_encode($alertMessage) ?>;
    const assignMsgStatus = <?php echo json_encode($assignStatus) ?>;
    const assignMsgText = <?php echo json_encode($assignMessage) ?>;

    if (alertMsgStatus === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Confirmed',
        text: alertMsgText
      });
    } else if (alertMsgStatus === 'error') {
      Swal.fire({
        icon: 'error',
        title: 'Action Failed',
        text: alertMsgText
      });
    }

    if (assignMsgStatus === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Assigned',
        text: assignMsgText
      });
    } else if (assignMsgStatus === 'error') {
      Swal.fire({
        icon: 'error',
        title: 'Assignment Failed',
        text: assignMsgText
      });
    }
  </script>

  <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js)"></script>
  <script>
    // Calendar
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