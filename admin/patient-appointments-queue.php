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

if (isset($_POST['update_appointment_time'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $new_appointment_date = $_POST['new_appointment_date'];
    $new_appointment_time = $_POST['new_appointment_time'];
    
    // Combine date and time into datetime format
    $new_appointment_datetime = date('Y-m-d H:i:s', strtotime($new_appointment_date . ' ' . $new_appointment_time));
    
    try {
        $con->updateAppointmentDateTime($appointment_id, $new_appointment_datetime);
        $alertStatus = 'success';
        $alertMessage = 'Appointment time updated successfully. You can now assign a dentist.';
        $allAppointments = $con->viewAppointments();
    } catch (Exception $e) {
        $alertStatus = 'error';
        $alertMessage = 'Failed to update appointment time: ' . $e->getMessage();
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

if (isset($_POST['cancel_appointment_btn'])) {
    $appointment_id = (int)$_POST['appointment_id_to_cancel'];
    $cancellation_reason = trim($_POST['cancellation_reason'] ?? '');

    try {
        if (empty($appointment_id)) {
            throw new Exception("Invalid appointment ID.");
        }

        $con->cancelAppointment($appointment_id, $cancellation_reason);

        $alertStatus = 'success';
        $alertMessage = 'Appointment cancelled successfully!';
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
    nav.navbar { margin-left: 260px; transition: margin-left 0.2s ease; width: calc(100% - 260px); padding-left: 1rem; padding-right: 1rem; }
    #pageMain { margin-left: 260px; padding-top: 1.5rem; padding-bottom: 3rem; padding-left: 1.5rem; padding-right: 1.5rem; width: calc(100% - 260px); max-width: 100%; }
    @media (max-width: 992px) { #employeeSidebar { position: relative; height: auto; width: 100%; } nav.navbar { margin-left: 0; width: 100%; } #pageMain { margin-left: 0; width: 100%; } }
  </style>
</head>
<body>
  <?php include 'employee-sidebar.php'; ?>
  <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid px-4">
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
  <main id="pageMain" class="container-fluid py-4">
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
                  <tr><td colspan="9" class="text-center text-muted py-4">No appointments found.</td></tr>
                <?php else: ?>
                  <?php foreach ($allAppointments as $app):
                    $paymentStatus = strtolower($app['Payment_Status'] ?? 'pending');
                    $appointmentStatus = strtolower($app['Appointment_Status'] ?? 'pending');
                    $statusBadge = $appointmentStatus === 'confirmed' ? 'bg-success' : ($appointmentStatus === 'completed' ? 'bg-secondary text-white' : ($appointmentStatus === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark'));
                    $fullName = htmlspecialchars(($app['Patient_FN'] ?? '') . ' ' . ($app['Patient_LN'] ?? ''));
                    $serviceName = !empty($app['Service_Name']) ? $app['Service_Name'] : 'Not Specified';
                    $checkoutDisabled = $paymentStatus === 'paid' || $appointmentStatus === 'completed';
                    $checkoutButtonClass = $checkoutDisabled ? 'btn btn-sm btn-success px-2 disabled' : 'btn btn-sm btn-success px-2';
                    $checkoutButtonLabel = $checkoutDisabled ? 'Settled' : 'Checkout';
                    $showMatchButton = !in_array($appointmentStatus, ['confirmed', 'completed'], true);
                    $allButtonsDisabled = in_array($appointmentStatus, ['cancelled', 'completed'], true);
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
                        <?php if ($allButtonsDisabled): ?>
                          <button type="button" class="btn btn-sm btn-primary px-2 disabled" disabled>Match Dentist</button>
                          <button type="button" class="btn btn-sm btn-success px-2 disabled" disabled>Checkout</button>
                          <button type="button" class="btn btn-sm btn-danger px-2 disabled" disabled><i class="fa-solid fa-times"></i></button>
                        <?php else: ?>
                          <?php if ($showMatchButton): ?>
                            <button type="button" class="btn btn-sm btn-primary px-2" data-bs-toggle="modal" data-bs-target="#assignModal" data-app-id="<?= $app['Appointment_ID']; ?>" data-patient-name="<?= $fullName; ?>" data-app-date="<?= htmlspecialchars($app['Appointment_Date']); ?>">Match Dentist</button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary px-2" disabled><?= $appointmentStatus === 'completed' ? 'Completed' : 'Assigned'; ?></button>
                          <?php endif; ?>
                          <?php if ($checkoutDisabled): ?>
                            <button type="button" class="btn btn-sm btn-success px-2 disabled" disabled>Settled</button>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-success px-2" data-bs-toggle="modal" data-bs-target="#checkoutModal" data-app-id="<?= $app['Appointment_ID']; ?>" data-patient-name="<?= $fullName; ?>" data-service="<?= htmlspecialchars($serviceName); ?>" data-amount="<?= ($app['Payment_Amount'] ?? $app['Service_Fee'] ?? 0); ?>" data-method="<?= htmlspecialchars($app['Payment_Method'] ?? 'Cash'); ?>" data-payment-status="<?= htmlspecialchars($app['Payment_Status'] ?? 'Pending'); ?>" data-app-status="<?= htmlspecialchars($app['Appointment_Status'] ?? 'Pending'); ?>">Checkout</button>
                          <?php endif; ?>
                          <button type="button" class="btn btn-sm btn-danger px-2" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setCancelAppointmentQueue(<?= htmlspecialchars($app['Appointment_ID']); ?>, '<?= htmlspecialchars($fullName); ?>')"><i class="fa-solid fa-times"></i></button>
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
              <div class="col-4"><button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancel</button></div>
              <div class="col-4"><button type="button" class="btn btn-warning w-100" id="editTimeBtn" data-bs-dismiss="modal"><i class="fa-solid fa-clock me-1"></i>Edit Time</button></div>
              <div class="col-4"><button name="assign_dentist" class="btn btn-primary w-100" type="submit">Confirm</button></div>
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
          <div class="mb-3">
            <span class="text-muted small d-block">Appointment Status</span>
            <strong id="checkout_app_status" class="d-block"></strong>
          </div>
          <div class="row g-2">
            <div class="col-6"><button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancel</button></div>
            <div class="col-6"><button type="submit" name="execute_checkout" class="btn btn-success w-100">Settle Bill</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Cancel Appointment Modal -->
  <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="patient-appointments-queue.php" method="POST">
          <div class="modal-body">
            <p><strong>Patient:</strong> <span id="cancelPatientNameQueue"></span></p>
            <label for="cancellationReasonQueue" class="form-label">Cancellation Reason (Optional)</label>
            <textarea class="form-control" id="cancellationReasonQueue" name="cancellation_reason" rows="4" placeholder="Enter reason for cancellation..."></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="cancel_appointment_btn" class="btn btn-danger">Confirm Cancellation</button>
          </div>
          <input type="hidden" name="appointment_id_to_cancel" id="appointmentIdToCancelQueue">
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Appointment Time Modal -->
  <div class="modal fade" id="editTimeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-clock me-2"></i>Edit Appointment Time</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="patient-appointments-queue.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="appointment_id" id="edit_appointment_id">
            <div class="alert alert-warning" role="alert">
              <strong>Note:</strong> The selected time slot is already booked by another appointment. Please choose a different date or time.
            </div>
            <div class="mb-3 bg-light p-3 rounded">
              <span class="text-muted small d-block">Patient:</span>
              <strong id="edit_patient_name" class="text-dark d-block"></strong>
            </div>
            <div class="mb-3">
              <label for="edit_appointment_date" class="form-label small fw-medium">New Date</label>
              <input type="date" id="edit_appointment_date" name="new_appointment_date" class="form-control" required>
            </div>
            <div class="mb-4">
              <label for="edit_appointment_time" class="form-label small fw-medium">New Time</label>
              <input type="time" id="edit_appointment_time" name="new_appointment_time" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_appointment_time" class="btn btn-primary">Update & Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../sweetalert/dist/sweetalert2.js"></script>
  <script>
    // Store the current assignment attempt data
    let currentAssignmentData = {
      appointmentId: null,
      patientName: null,
      appointmentDate: null,
      dentistId: null
    };
    const assignModal = document.getElementById('assignModal');
    assignModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      
      const appointmentId = btn.getAttribute('data-app-id') || '';
      const patientName = btn.getAttribute('data-patient-name') || '';
      const appointmentDate = btn.getAttribute('data-app-date') || '';
      
      // Store current assignment data
      currentAssignmentData = {
        appointmentId: appointmentId,
        patientName: patientName,
        appointmentDate: appointmentDate,
        dentistId: null
      };
      
      document.getElementById('modal_appointment_id').value = appointmentId;
      document.getElementById('modal_patient_name').innerText = patientName;
      try {
        document.getElementById('modal_appointment_date').innerText = appointmentDate ? new Date(appointmentDate.replace(' ', 'T')).toLocaleString() : ''; 
      } catch (e) {
        document.getElementById('modal_appointment_date').innerText = appointmentDate;
      }
    });

    // Override form submission to handle conflicts with edit option
    const assignForm = document.querySelector('#assignModal form');
    if (assignForm) {
      assignForm.addEventListener('submit', function(e) {
        // Allow normal submission - PHP will handle it
        // If there's a conflict error, it will be shown by SweetAlert below
      });
    }

    // Handle Edit Time button click
    document.getElementById('editTimeBtn').addEventListener('click', function() {
      const editModal = new bootstrap.Modal(document.getElementById('editTimeModal'));
      document.getElementById('edit_appointment_id').value = currentAssignmentData.appointmentId;
      document.getElementById('edit_patient_name').innerText = currentAssignmentData.patientName;
      
      // Pre-fill with current appointment date/time if available
      if (currentAssignmentData.appointmentDate) {
        const dateObj = new Date(currentAssignmentData.appointmentDate.replace(' ', 'T'));
        const dateStr = dateObj.toISOString().split('T')[0];
        const timeStr = dateObj.toISOString().split('T')[1].substring(0, 5);
        document.getElementById('edit_appointment_date').value = dateStr;
        document.getElementById('edit_appointment_time').value = timeStr;
      }
      
      editModal.show();
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
      const appStatusLabel = document.getElementById('checkout_app_status');
      const displayStatus = appStatus === 'completed' ? 'Completed' : (appStatus === 'confirmed' ? 'Confirmed' : 'Pending');
      appStatusLabel.innerText = displayStatus;
      appStatusLabel.className = displayStatus === 'Completed' ? 'text-success fw-bold' : (displayStatus === 'Confirmed' ? 'text-primary fw-bold' : 'text-warning fw-bold');

      if (paymentStatus === 'paid' || appStatus === 'completed') {
        checkoutSubmit.disabled = true;
        checkoutSubmit.innerText = 'Already Completed';
        document.getElementById('checkout_method').disabled = true;
        document.getElementById('checkout_status').disabled = true;
      } else {
        checkoutSubmit.disabled = false;
        checkoutSubmit.innerText = 'Settle Bill';
        document.getElementById('checkout_method').disabled = false;
        document.getElementById('checkout_status').disabled = false;
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
          noResultsRow.innerHTML = '<td colspan="9" class="text-center text-muted py-4">No matching records found.</td>';
          table.querySelector('tbody').appendChild(noResultsRow);
        }
      });
    }

    installTableSearch('appointmentsSearch', 'appointmentsTable');

    function setCancelAppointmentQueue(appointmentId, patientName) {
      document.getElementById('appointmentIdToCancelQueue').value = appointmentId;
      document.getElementById('cancelPatientNameQueue').textContent = patientName;
      document.getElementById('cancellationReasonQueue').value = '';
    }

    const alertMsgStatus = <?php echo json_encode($alertStatus); ?>;
    const alertMsgText = <?php echo json_encode($alertMessage); ?>;
    const assignMsgStatus = <?php echo json_encode($assignStatus); ?>;
    const assignMsgText = <?php echo json_encode($assignMessage); ?>;

    if (alertMsgStatus === 'success') Swal.fire({ icon: 'success', title: 'Success', text: alertMsgText });
    else if (alertMsgStatus === 'error') Swal.fire({ icon: 'error', title: 'Action Failed', text: alertMsgText });
    
    if (assignMsgStatus === 'success') {
      Swal.fire({ icon: 'success', title: 'Assigned', text: assignMsgText });
    } else if (assignMsgStatus === 'error') {
      // Check if this is a conflict error
      const isConflict = assignMsgText && assignMsgText.includes('Conflict');
      
      if (isConflict) {
        Swal.fire({
          icon: 'warning',
          title: 'Time Slot Conflict',
          text: assignMsgText,
          didClose: function() {
            // Auto-open edit time modal after conflict message
            const editModal = new bootstrap.Modal(document.getElementById('editTimeModal'));
            document.getElementById('edit_appointment_id').value = currentAssignmentData.appointmentId;
            document.getElementById('edit_patient_name').innerText = currentAssignmentData.patientName;
            editModal.show();
          }
        });
      } else {
        Swal.fire({ icon: 'error', title: 'Assignment Failed', text: assignMsgText });
      }
    }
  </script>
</body>
</html>
