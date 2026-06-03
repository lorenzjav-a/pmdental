<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$activePage = 'patient_masterlist';
$allPatients = $con->viewPatients();
$alertStatus = null;
$alertMessage = '';

if (isset($_GET['fetch_prescription_json'], $_GET['patient_id'])) {
    header('Content-Type: application/json');
    $data = $con->getPrescriptionItemsByPatient((int)$_GET['patient_id']);
    echo json_encode($data ? $data : []);
    exit();
}

if (isset($_POST['save_med_history'])) {
    $patient_id = (int)$_POST['history_patient_id'];
    $condition = trim($_POST['med_condition']);
    $notes = trim($_POST['med_notes'] ?? '');

    try {
        $con->addMedicalHistory($patient_id, $condition, $notes);
        $alertStatus = 'success';
        $alertMessage = 'Medical history parameters successfully logged to patient profile.';
        $allPatients = $con->viewPatients();
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
  <title>Patient Profile Masterlist - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f4f7fb; }
    #adminSidebar { position: fixed; top: 0; left: 0; width: 240px; height: 100vh; background: #0d1b2a; color: #fff; z-index: 1050; overflow-y: auto; padding-top: 1.5rem; }
    #adminSidebar .sidebar-brand { font-size: 1.25rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 1.5rem; display: block; color: #fff; }
    #adminSidebar .sidebar-links { padding: 0 1.2rem; }
    #adminSidebar .sidebar-links a { display: block; color: #d6d6d6; padding: 0.9rem 0.75rem; text-decoration: none; border-radius: 0.65rem; margin-bottom: 0.35rem; transition: background 0.2s, color 0.2s; }
    #adminSidebar .sidebar-links a.active, #adminSidebar .sidebar-links a:hover { background: #1b263b; color: #fff; }
    .main { margin-left: 260px; padding: 25px; }
    .topbar { background: white; border-radius: 12px; padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 25px; }
    .logout-btn { background: #dc3545; color: white; text-decoration: none; padding: 10px 15px; border-radius: 8px; transition: 0.3s; }
    .logout-btn:hover { background: #bb2d3b; }
    .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
  </style>
</head>
<body>
  <?php include 'admin-sidebar.php'; ?>
  
  <div class="main">
    <div class="topbar">
      <div>
        <h3>Patient Profile Masterlist</h3>
        <small class="text-muted">Browse complete client credentials and append clinical history notes.</small>
      </div>
      <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt me-2"></i>Exit System</a>
    </div>

    <div class="row g-4">
      <div class="col-12">
        <div class="card p-4">
          <div class="mb-3">
            <h5 class="fw-bold text-dark mb-2">Patient Directory</h5>
            <input id="patientsSearch" type="search" class="form-control form-control-sm" placeholder="Search patients by name, ID, phone, gender...">
          </div>
          <div class="table-responsive">
            <table id="patientsTable" class="table table-hover align-middle">
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
                  <tr><td colspan="6" class="text-center text-muted py-4">No patient records available.</td></tr>
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
                        <button type="button" class="btn btn-sm btn-outline-info px-2 me-1 view-pres-btn" data-patient-id="<?= $pat['Patient_ID']; ?>" data-patient-name="<?= $pName; ?>">View Prescription</button>
                        <button type="button" class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#historyModal" data-patient-id="<?= $pat['Patient_ID']; ?>" data-patient-name="<?= $pName; ?>">+ Add Med History</button>
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

  <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Append Medical History Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form action="admin-patient-profile-masterlist.php" method="POST">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../sweetalert/dist/sweetalert2.js"></script>
  <script>
    const historyModal = document.getElementById('historyModal');
    historyModal.addEventListener('show.bs.modal', function(event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      document.getElementById('history_patient_id').value = btn.getAttribute('data-patient-id') || '';
      document.getElementById('history_patient_name').innerText = btn.getAttribute('data-patient-name') || '';
    });

    document.querySelectorAll('.view-pres-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const patientId = this.getAttribute('data-patient-id');
        const patientName = this.getAttribute('data-patient-name');
        document.getElementById('pres_patient_name').innerText = patientName;
        const tbody = document.getElementById('prescription_results_body');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-info me-2"></div>Loading prescription records...</td></tr>';
        new bootstrap.Modal(document.getElementById('prescriptionModal')).show();

        fetch('admin-patient-profile-masterlist.php?fetch_prescription_json=1&patient_id=' + patientId)
          .then(r => r.json())
          .then(data => {
            tbody.innerHTML = '';
            if (!data || data.length === 0) {
              tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No active prescription notes matched with this patient account.</td></tr>';
              return;
            }
            data.forEach(item => {
              tbody.innerHTML += `<tr><td class="fw-bold text-dark">${item.Item_Name || 'Unspecified'}</td><td class="text-center"><span class="badge bg-light text-dark border">Qty: ${item.Item_Quantity || 0}</span></td><td class="text-muted small">${item.Pres_Dosage || 'As written by attending dentist'}</td></tr>`;
            });
          })
          .catch(() => {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Error accessing prescription data.</td></tr>';
          });
      });
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
          noResultsRow.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No matching records found.</td>';
          table.querySelector('tbody').appendChild(noResultsRow);
        }
      });
    }

    installTableSearch('patientsSearch', 'patientsTable');

    const alertMsgStatus = <?php echo json_encode($alertStatus); ?>;
    const alertMsgText = <?php echo json_encode($alertMessage); ?>;
    if (alertMsgStatus === 'success') Swal.fire({ icon: 'success', title: 'Saved', text: alertMsgText });
    else if (alertMsgStatus === 'error') Swal.fire({ icon: 'error', title: 'Action Failed', text: alertMsgText });
  </script>
</body>
</html>
