<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
    header('Location: login.php');
    exit();
}

$activePage = 'dentist_rosters';
$allDentists = $con->viewDentists();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dentist Schedule Rosters - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; background: #f4f7fb; }
    #employeeSidebar { position: fixed; top: 0; left: 0; width: 240px; height: 100vh; background: #0d1b2a; color: #fff; z-index: 1050; overflow-y: auto; padding-top: 1.5rem; }
    #employeeSidebar .sidebar-brand { font-size: 1.25rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 1.5rem; display: block; color: #fff; }
    #employeeSidebar .sidebar-links { padding: 0 1.2rem; }
    #employeeSidebar .sidebar-links a { display: block; color: #d6d6d6; padding: 0.9rem 0.75rem; text-decoration: none; border-radius: 0.65rem; margin-bottom: 0.35rem; transition: background 0.2s, color 0.2s; }
    #employeeSidebar .sidebar-links a.active, #employeeSidebar .sidebar-links a:hover { background: #1b263b; color: #fff; }
    nav.navbar { margin-left: 260px; transition: margin-left 0.2s ease; width: calc(100% - 260px); padding-left: 1rem; padding-right: 1rem; }
    #pageMain { margin-left: 260px; padding-top: 1.5rem; padding-bottom: 3rem; padding-left: 1.5rem; padding-right: 1.5rem; width: calc(100% - 260px); max-width: 100%; }
    #dentistCalendar { background: #ffffff; border-radius: 12px; padding: 18px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06); }
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
          <li class="nav-item"><a class="nav-link active" href="dentist-schedule-rosters.php">Dentist Rosters</a></li>
        </ul>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary">Role: CLINIC STAFF</span>
          <a class="btn btn-sm btn-outline-secondary" href="login.php">Logout</a>
        </div>
      </div>
    </div>
  </nav>
  <main id="pageMain" class="container-fluid py-4">
    <div class="row g-4">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0 bg-white">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
              <h4 class="mb-0 fw-bold text-dark">Dentist Schedule Rosters</h4>
              <p class="text-muted small mb-0">Filter rosters to audit dentist availability in the clinic calendar.</p>
            </div>
            <div style="min-width: 280px;">
              <select class="form-select" id="dentistPicker">
                <option value="" selected disabled>Choose a dentist to view roster...</option>
                <?php foreach ($allDentists as $doc): ?>
                  <option value="<?= $doc['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(($doc['Dentist_FN'] ?? '') . ' ' . ($doc['Dentist_LN'] ?? '')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div id="dentistCalendar"></div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script>
    const cal = new FullCalendar.Calendar(document.getElementById('dentistCalendar'), {
      initialView: 'dayGridMonth',
      height: 600,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      events: [],
      eventDisplay: 'block',
      eventMinHeight: 50,
      eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short'
      },
      displayEventTime: true,
      eventMaxStack: 3
    });
    cal.render();

    function loadDentistSchedule(dentistId) {
      if (!dentistId) return;
      fetch('calendar.php?fetch_dentist_calendar=1&dentist_id=' + dentistId)
        .then(r => r.json())
        .then(events => {
          cal.removeAllEvents();
          cal.addEventSource(events);
        }).catch(() => {
          cal.removeAllEvents();
        });
    }

    document.getElementById('dentistPicker').addEventListener('change', function() {
      loadDentistSchedule(this.value);
    });
  </script>
</body>
</html>
