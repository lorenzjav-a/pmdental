<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
    header('Location: login.php');
    exit();
}

$activePage = 'appointments_queue';
$allAppointments = $con->viewAppointments();
$allDentists = $con->viewDentists();
$assignStatus = null;
$assignMessage = '';
$alertStatus = null;
$alertMessage = '';

if (isset($_POST['assign_dentist'])) {
    $appointment_id = $_POST['appointment_id'];
    $dentist_id = $_POST['dentist_id'];

    try {
        $con->assignDentistToAppointment($appointment_id, $_SESSION['user_id'], $dentist_id);
        $assignStatus = 'success';
        $assignMessage = 'Dentist assigned and appointment confirmed successfully!';
        $allAppointments = $con->viewAppointments();
    } catch (Exception $e) {
        $assignStatus = 'error';
        $assignMessage = $e->getMessage();
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
  <title>Patient Appointments Queue - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css" />
  <style>
    body { min-height: 100vh; background: #f4f7fb; }
    #employeeSidebar { position: fixed; top: 0; left: 0; width: 240px; height: 100vh; background: #0d1b2a; color: #fff; z-index: 1050; overflow-y: auto; padding-top: 1.5rem; }
    #employeeSidebar .sidebar-brand { font-size: 1.25rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 1.5rem; display: block; color: #fff; }
    #employeeSidebar .sidebar-links { padding: 0 1.2rem; }
    #employeeSidebar .sidebar-links a { display: block; color: #d6d6d6; padding: 0.9rem 0.75rem; text-decoration: none; border-radius: 0.65rem; margin-bottom: 0.35rem; transition: background 0.2s, color 0.2s; }
    #employeeSidebar .sidebar-links a.active, #employeeSidebar .sidebar-links a:hover { background: #1b263b; color: #fff; }
    nav.navbar { margin-left: 260px; transition: margin-left 0.2s ease; }
    #pageMain { margin-left: 260px; padding-top: 1.5rem; padding-bottom: 3rem; }
    @media (max-width: 992px) { #employeeSidebar { position: relative; height: auto; width: 100%; } nav.navbar, #pageMain { margin-left: 0; } }
  </style>
</head>
<body>
  <?php include 'employee-sidebar.php'; ?>
  <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="employee-dashboard.php">PM Dental Staff</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navStatic">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="navStatic" class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto gap-lg-1">
          <li class="nav-item"><a class="nav-link active" href="patient-appointments-queue.php">Appointments Queue</a></li>
        </ul>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary">Role: CLINIC STAFF</span>
          <a class="btn btn-sm btn-outline-secondary" href="login.php">Logout</a>
        </div>
      </div>
    </div>
  </nav>
  <main id="pageMain" class="container py-4">
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <div class="mb-3">
            <h4 class="mb-0 fw-bold text-dark">Patient Appointments Queue</h4>
            <p class="text-muted small mb-0">Review pending arrivals and match records with doctor assignments.</p>
            <input id="appointmentsSearch" type="search" class="form-control form-control-sm mt-3" placeholder="Search appointments by ID, patient, service, status...">
          </div>
          <div class="table-responsive">
            <table id="appointmentsTable" class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Appointment ID</th>
                  <th>Patient Name</th>
                  <th>Contact Info</th>
                  <th>Requested Service</th>
                  <th>Consultation Fee</th>
                  <th>Target Date</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allAppointments) || !is_array($allAppointments)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>
                <?php else: ?>
                  <?php foreach ($allAppointments as $app):
                    $paymentStatus = strtolower($app['Payment_Status'] ?? 'pending');
                    $appointmentStatus = strtolower($app['Appointment_Status'] ?? 'pending');
                    $statusBadge = $appointmentStatus === 'confirmed' ? 'bg-success' : ($appointmentStatus === 'completed' ? 'bg-secondary text-white' : 'bg-warning text-dark');
                    $fullName = htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? ''));
                    $serviceName = !empty($app['Service_Name']) ? $app['Service_Name'] : 'Not Specified';
                    $checkoutDisabled = $paymentStatus === 'paid' || $appointmentStatus === 'completed';
                    $checkoutButtonClass = $checkoutDisabled ? 'btn btn-sm btn-success px-2 disabled' : 'btn btn-sm btn-success px-2';
                    $checkoutButtonLabel = $checkoutDisabled ? 'Settled' : 'Checkout';
                    $showMatchButton = !in_array($appointmentStatus, ['confirmed', 'completed'], true);
                  ?>
                    <tr>
                      <td>#<?= $app['Appointment_ID']; ?></td>
                      <td class="fw-medium"><?= $fullName; ?></td>
                      <td><?= htmlspecialchars($app['Patient_PhoneNo'] ?? ''); ?></td>
                      <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($serviceName); ?></span></td>
                      <td><span class="badge bg-light text-dark border">₱<?= number_format($app['Service_Fee'] ?? 0, 2); ?></span></td>
                      <td><?= !empty($app['Appointment_Date']) ? date('M d, Y - h:i A', strtotime($app['Appointment_Date'])) : 'Unscheduled'; ?></td>
                      <td><span class="badge <?= $statusBadge; ?>"><?= htmlspecialchars($app['Appointment_Status'] ?? 'Pending'); ?></span></td>
                      <td class="text-end d-flex justify-content-end gap-1">
                        <?php if ($showMatchButton): ?>
                          <button type="button" class="btn btn-sm btn-primary px-2" data-bs-toggle="modal" data-bs-target="#assignModal" data-app-id="<?= $app['Appointment_ID']; ?>" data-patient-name="<?= $fullName; ?>" data-app-date="<?= htmlspecialchars($app['Appointment_Date']); ?>">Match Dentist</button>
                        <?php else: ?>
                          <button class="btn btn-sm btn-outline-secondary px-2" disabled><?= $appointmentStatus === 'completed' ? 'Completed' : 'Assigned'; ?></button>
                        <?php endif; ?>
                        <button type="button" class="<?= $checkoutButtonClass; ?>" <?= $checkoutDisabled ? 'disabled' : ''; ?> data-bs-toggle="modal" data-bs-target="#checkoutModal" data-app-id="<?= $app['Appointment_ID']; ?>" data-patient-name="<?= $fullName; ?>" data-service="<?= htmlspecialchars($serviceName); ?>" data-amount="<?= ($app['Payment_Amount'] ?? $app['Service_Fee'] ?? 0); ?>" data-method="<?= htmlspecialchars($app['Payment_Method'] ?? 'Cash'); ?>" data-payment-status="<?= htmlspecialchars($app['Payment_Status'] ?? 'Pending'); ?>" data-app-status="<?= htmlspecialchars($app['Appointment_Status'] ?? 'Pending'); ?>"><?= $checkoutButtonLabel; ?></button>
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
          <form action="patient-appointments-queue.php" method="POST">
            <input type="hidden" name="appointment_id" id="modal_appointment_id">
            <div class="mb-3 bg-light p-3 rounded">
              <span class="text-muted small d-block">Processing Patient:</span>
              <strong id="modal_patient_name" class="fs-5 text-primary"></strong>
              <div class="mt-2"><span class="text-muted small d-block">Requested Time:</span><strong id="modal_appointment_date" class="text-dark"></strong></div>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-medium">Available Specialists Matrix</label>
              <select class="form-select" name="dentist_id" required>
                <option value="" selected disabled>Select clinic doctor to assign...</option>
                <?php foreach ($allDentists as $doc): ?>
                  <option value="<?= $doc['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(($doc['Dentist_FN'] ?? '') . ' ' . ($doc['Dentist_LN'] ?? '')); ?></option>
                <?php endforeach; ?>
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

  <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-cash-register me-2"></i>Checkout Payment Terminal</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="patient-appointments-queue.php" method="POST" class="modal-body">
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
              <span class="input-group-text fw-bold">₱</span>
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
    const assignModal = document.getElementById('assignModal');
    assignModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      document.getElementById('modal_appointment_id').value = btn.getAttribute('data-app-id') || '';
      document.getElementById('modal_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
      const raw = btn.getAttribute('data-app-date') || '';
      try {
        document.getElementById('modal_appointment_date').innerText = raw ? new Date(raw.replace(' ', 'T')).toLocaleString() : ''; 
      } catch (e) {
        document.getElementById('modal_appointment_date').innerText = raw;
      }
    });

    const checkoutModal = document.getElementById('checkoutModal');
    checkoutModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      const paymentStatus = (btn.getAttribute('data-payment-status') || 'Pending').toLowerCase();
      const appStatus = (btn.getAttribute('data-app-status') || 'Pending').toLowerCase();
      const checkoutSubmit = checkoutModal.querySelector('button[name="execute_checkout"]');

      document.getElementById('checkout_appointment_id').value = btn.getAttribute('data-app-id') || '';
      document.getElementById('checkout_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
      document.getElementById('checkout_service_name').innerText = btn.getAttribute('data-service') || '';
      document.getElementById('checkout_amount').value = btn.getAttribute('data-amount') || '0.00';
      document.getElementById('checkout_method').value = btn.getAttribute('data-method') || 'Cash';
      document.getElementById('checkout_status').value = btn.getAttribute('data-payment-status') || 'Pending';

      if (paymentStatus === 'paid' || appStatus === 'completed') {
        checkoutSubmit.disabled = true;
        checkoutSubmit.innerText = 'Already Settled';
      } else {
        checkoutSubmit.disabled = false;
        checkoutSubmit.innerText = 'Settle Bill';
      }
    });

    function installTableSearch(inputId, tableId) {
      const input = document.getElementById(inputId);
      const table = document.getElementById(tableId);
      if (!input || !table) return;

      input.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
          if (row.id && row.id.endsWith('_no_results')) return;
          const isVisible = query === '' || row.textContent.toLowerCase().includes(query);
          row.style.display = isVisible ? '' : 'none';
          if (isVisible) visibleCount += 1;
        });

        const noResultsRowId = tableId + '_no_results';
        let noResultsRow = document.getElementById(noResultsRowId);
        if (visibleCount > 0 || query === '') {
          if (noResultsRow) noResultsRow.remove();
          return;
        }
        if (!noResultsRow) {
          noResultsRow = document.createElement('tr');
          noResultsRow.id = noResultsRowId;
          noResultsRow.innerHTML = '<td colspan="8" class="text-center text-muted py-4">No matching records found.</td>';
          table.querySelector('tbody').appendChild(noResultsRow);
        }
      });
    }

    installTableSearch('appointmentsSearch', 'appointmentsTable');

    const alertMsgStatus = <?php echo json_encode($alertStatus); ?>;
    const alertMsgText = <?php echo json_encode($alertMessage); ?>;
    const assignMsgStatus = <?php echo json_encode($assignStatus); ?>;
    const assignMsgText = <?php echo json_encode($assignMessage); ?>;

    if (alertMsgStatus === 'success') Swal.fire({ icon: 'success', title: 'Confirmed', text: alertMsgText });
    else if (alertMsgStatus === 'error') Swal.fire({ icon: 'error', title: 'Action Failed', text: alertMsgText });
    if (assignMsgStatus === 'success') Swal.fire({ icon: 'success', title: 'Assigned', text: assignMsgText });
    else if (assignMsgStatus === 'error') Swal.fire({ icon: 'error', title: 'Assignment Failed', text: assignMsgText });
  </script>
</body>
</html>
