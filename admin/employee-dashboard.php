<?php
session_start();
require_once('../class/database.php');
$con = new database();

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
    header('Location: login.php');
    exit();
}

$activePage = 'dashboard';
$appointmentCount = count($con->viewAppointments() ?? []);
$patientCount = count($con->viewPatients() ?? []);
$dentistCount = count($con->viewDentists() ?? []);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Dashboard - PM Dental Clinic</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { min-height: 100vh; background: #f4f7fb; }
    #employeeSidebar { position: fixed; top: 0; left: 0; width: 240px; height: 100vh; background: #0d1b2a; color: #fff; z-index: 1050; overflow-y: auto; padding-top: 1.5rem; }
    #employeeSidebar .sidebar-brand { font-size: 1.25rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 1.5rem; display: block; color: #fff; }
    #employeeSidebar .sidebar-links { padding: 0 1.2rem; }
    #employeeSidebar .sidebar-links a { display: block; color: #d6d6d6; padding: 0.9rem 0.75rem; text-decoration: none; border-radius: 0.65rem; margin-bottom: 0.35rem; transition: background 0.2s, color 0.2s; }
    #employeeSidebar .sidebar-links a.active, #employeeSidebar .sidebar-links a:hover { background: #1b263b; color: #fff; }
    nav.navbar { margin-left: 260px; transition: margin-left 0.2s ease; }
    #pageMain { margin-left: 260px; padding-top: 1.5rem; padding-bottom: 3rem; }
    .section-card { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .section-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important; }
    @media (max-width: 992px) { #employeeSidebar { position: relative; height: auto; width: 100%; } nav.navbar, #pageMain { margin-left: 0; } }
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
          <li class="nav-item"><a class="nav-link active" href="employee-dashboard.php">Home</a></li>
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
          <h4 class="mb-0 fw-bold text-dark">Employee Dashboard</h4>
          <p class="text-muted small mb-3">Quick access to appointment management, patient records, and dentist schedules.</p>
          <div class="row row-cols-1 row-cols-md-3 g-3">
            <div class="col">
              <a href="patient-appointments-queue.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm section-card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                      <div>
                        <h5 class="card-title fw-bold mb-1">Appointments Queue</h5>
                        <p class="text-muted small mb-0">Manage pending arrivals and assign dentists.</p>
                      </div>
                      <span class="badge bg-primary fs-6"><?= $appointmentCount; ?></span>
                    </div>
                    <div class="text-end"><i class="fa-solid fa-calendar-check text-primary" style="font-size: 2rem;"></i></div>
                  </div>
                </div>
              </a>
            </div>
            <div class="col">
              <a href="patient-profile-masterlist.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm section-card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                      <div>
                        <h5 class="card-title fw-bold mb-1">Patient Masterlist</h5>
                        <p class="text-muted small mb-0">View patient records and medical history.</p>
                      </div>
                      <span class="badge bg-success fs-6"><?= $patientCount; ?></span>
                    </div>
                    <div class="text-end"><i class="fa-solid fa-users text-success" style="font-size: 2rem;"></i></div>
                  </div>
                </div>
              </a>
            </div>
            <div class="col">
              <a href="dentist-schedule-rosters.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm section-card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                      <div>
                        <h5 class="card-title fw-bold mb-1">Dentist Rosters</h5>
                        <p class="text-muted small mb-0">View dentist schedules and availability.</p>
                      </div>
                      <span class="badge bg-warning text-dark fs-6"><?= $dentistCount; ?></span>
                    </div>
                    <div class="text-end"><i class="fa-solid fa-calendar-days text-warning" style="font-size: 2rem;"></i></div>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
