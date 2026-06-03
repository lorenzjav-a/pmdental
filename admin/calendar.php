<?php
session_start();
require_once('../class/database.php');
$db = new database();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['update_appointment_end_time'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $end_date = trim($_POST['end_date'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    
    if (empty($end_date) || empty($end_time)) {
        header('Location: calendar.php?error=' . urlencode('Date and time are required'));
        exit();
    }

    
    
    $end_datetime = date('Y-m-d H:i:s', strtotime($end_date . ' ' . $end_time));
    

    try {
        $db->updateAppointmentEndTime($appointment_id, $end_datetime);
        header('Location: calendar.php?success=1');
        exit();
    } catch (Exception $e) {
        header('Location: calendar.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

if (isset($_GET['fetch_dentist_calendar'], $_GET['dentist_id'])) {
    header('Content-Type: application/json');
    $dentist_id = (int)$_GET['dentist_id'];
    $appointments = $db->getDentistAppointments($dentist_id);
    
    $events = [];

    // Add appointments
    foreach ($appointments as $appt) {
        $events[] = [
            'id' => 'appt_' . $appt['Appointment_ID'],
            'title' => trim((!empty($appt['Service_Name']) ? $appt['Service_Name'] . ' - ' : '') . ($appt['Patient_FN'] ?? '') . ' ' . ($appt['Patient_LN'] ?? '')),
            'start' => $appt['Appointment_Date'],
            'color' => $appt['Appointment_Status'] === 'Confirmed' ? '#198754' : '#ffc107',
            'borderColor' => $appt['Appointment_Status'] === 'Confirmed' ? '#155724' : '#ff9800',
            'extendedProps' => [
                'type' => 'appointment',
                'appointmentId' => $appt['Appointment_ID'],
                'appointmentDate' => $appt['Appointment_Date'],
                'status' => $appt['Appointment_Status'] ?? 'Pending',
                'patient' => trim(($appt['Patient_FN'] ?? '') . ' ' . ($appt['Patient_LN'] ?? '')),
                'service' => $appt['Service_Name'] ?? '',
                'endTime' => $appt['Appointment_End_Time'] ?? null
            ]
        ];
    }

    
    
    echo json_encode($events);
    exit();
}

$dentists = $db->viewDentists();
$activePage = 'calendar';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentist Calendar - PM Dental Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: #f4f7fb;
            min-height: 100vh;
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

        nav.navbar {
            margin-left: 260px;
            transition: margin-left 0.2s ease;
            background: #fff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        #pageMain {
            margin-left: 260px;
            padding: 1.5rem;
            height: calc(100vh - 70px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        #pageMain .row:first-child {
            flex-shrink: 0;
            margin-bottom: 1rem;
        }

        #pageMain .row:last-child {
            flex: 1;
            min-height: 0;
        }

        #pageMain .row:last-child .col-12 {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .calendar-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        #dentistCalendar {
            border-radius: 10px;
            flex: 1;
            min-height: 0;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .page-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .calendar-legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid;
            flex-shrink: 0;
        }

        .legend-confirmed {
            background: #198754;
            border-color: #155724;
        }

        .legend-pending {
            background: #ffc107;
            border-color: #ff9800;
        }

        .dentist-selector-row {
            background: #fafbfc;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .dentist-selector-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .dentist-selector-description {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0;
        }

        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.6rem 0.875rem;
            font-size: 0.95rem;
        }

        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        #dentistCalendar {
            border-radius: 10px;
            flex: 1;
            min-height: 0;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .page-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.2rem;
            margin-bottom: 0;
        }

        .calendar-legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid;
            flex-shrink: 0;
        }

        .legend-confirmed {
            background: #198754;
            border-color: #155724;
        }

        .legend-pending {
            background: #ffc107;
            border-color: #ff9800;
        }

        .dentist-selector-row {
            background: #fafbfc;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .dentist-selector-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .dentist-selector-description {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0;
        }

        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.6rem 0.875rem;
            font-size: 0.95rem;
        }

        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        @media (max-width: 992px) {
            #adminSidebar {
                position: relative;
                height: auto;
                width: 100%;
            }

            nav.navbar,
            #pageMain {
                margin-left: 0;
            }
        }

        /* FullCalendar styling */
        .fc {
            font-family: 'Roboto', sans-serif;
        }

        .fc .fc-button-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .fc .fc-button-primary:not(:disabled):hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .fc .fc-col-header-cell {
            background-color: #f9fafb;
            border-color: #e5e7eb;
            padding: 12px 4px;
        }

        .fc .fc-daygrid-day {
            border-color: #e5e7eb;
        }

        .fc .fc-daygrid-day-frame {
            padding-top: 8px;
        }

        .fc .fc-event {
            border-radius: 6px;
            border: none;
            font-size: 0.85rem;
            padding: 4px 8px;
        }

        .fc .fc-event-title {
            font-weight: 600;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include 'admin-sidebar.php'; ?>

    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold" href="admin-dashboard.php">PM Dental Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navStatic">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div id="navStatic" class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto gap-lg-1">
                    <li class="nav-item"><a class="nav-link" href="admin-dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="calendar.php">Calendar</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="login.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="pageMain">
        <div class="row">
            <div class="col-12">
                <div class="page-title"><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Dentist Calendar</div>
                <p class="page-description">Select a dentist to view their confirmed and pending appointment schedule.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="calendar-card">
                    <div class="dentist-selector-row">
                        <div class="row align-items-end">
                            <div class="col-lg-3">
                                <label class="dentist-selector-label">Select Dentist</label>
                                <p class="dentist-selector-description">Choose a dentist to see their schedule.</p>
                            </div>
                            <div class="col-lg-9">
                                <select id="dentistPicker" class="form-select">
                                    <option value="" selected disabled>Choose a dentist...</option>
                                    <?php foreach ($dentists as $dentist): ?>
                                        <option value="<?= $dentist['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(trim($dentist['Dentist_FN'] . ' ' . $dentist['Dentist_LN'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-color legend-confirmed"></div>
                            <span>Confirmed Appointment</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-pending"></div>
                            <span>Pending Appointment</span>
                        </div>
                    </div>

                    <div id="dentistCalendar"></div>
                </div>
            </div>
        </div>
    </div>

    // ppointment completion modal
    <div class="modal fade" id="appointmentEndModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-check-circle me-2"></i>Record Appointment Completion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="calendar.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="modal_appointment_id">
                        <div class="alert alert-info small mb-3">
                            <i class="fa-solid fa-info-circle me-2"></i>Enter the time when this appointment was completed.
                        </div>
                        <div class="mb-3 bg-light p-3 rounded">
                            <span class="text-muted small d-block">Patient:</span>
                            <strong id="modal_patient_name" class="d-block text-primary"></strong>
                            <span class="text-muted small d-block mt-2">Service:</span>
                            <span id="modal_service_name" class="badge bg-primary"></span>
                        </div>
                        <div class="mb-3">
                            <label for="modal_end_date" class="form-label small fw-medium">Completion Date</label>
                            <input type="date" id="modal_end_date" name="end_date" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label for="modal_end_time" class="form-label small fw-medium">Completion Time</label>
                            <input type="time" id="modal_end_time" name="end_time" class="form-control" required>
                        </div>
                        <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                            <small class="text-warning"><strong>Note:</strong> The end time was previously not recorded. Entering this will mark the appointment as completed.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_appointment_end_time" class="btn btn-primary">Save Completion Time</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('dentistCalendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                contentHeight: 'auto',
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
                eventMaxStack: 3,
                eventClick: function(info) {
                    if (info.event.extendedProps.type === 'appointment') {
                        // Open end time modal for appointments only
                        document.getElementById('modal_appointment_id').value = info.event.extendedProps.appointmentId;
                        document.getElementById('modal_patient_name').innerText = info.event.extendedProps.patient;
                        document.getElementById('modal_service_name').innerText = info.event.extendedProps.service || 'Not Specified';

                        // Use the actual appointment date from the database
                        const appointmentDateStr = info.event.extendedProps.appointmentDate;
                        console.log("Raw appointmentDate:", appointmentDateStr);

                        // Parse the date string directly (format: "2026-06-03 12:30:00")
                        const parts = appointmentDateStr.split(' ');
                        const dateOnly = parts[0]; // "2026-06-03"
                        const timeOnly = parts[1] ? parts[1].substring(0, 5) : '00:00'; // "12:30"

                        console.log("Parsed - dateOnly:", dateOnly, "timeOnly:", timeOnly);

                        // Pre-fill with appointment's actual scheduled date and time
                        document.getElementById('modal_end_date').value = dateOnly;
                        document.getElementById('modal_end_time').value = timeOnly;

                        console.log("Modal set - end_date value:", document.getElementById('modal_end_date').value);
                        console.log("Modal set - end_time value:", document.getElementById('modal_end_time').value);

                        var endModal = new bootstrap.Modal(document.getElementById('appointmentEndModal'));
                        endModal.show();
                    }
                }
            });

            calendar.render();

            var picker = document.getElementById('dentistPicker');
            picker.addEventListener('change', function() {
                var dentistId = this.value;
                if (!dentistId) {
                    return;
                }

                fetch('calendar.php?fetch_dentist_calendar=1&dentist_id=' + dentistId)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(events) {
                        calendar.removeAllEvents();
                        calendar.addEventSource(events);
                    })
                    .catch(function(error) {
                        console.error('Calendar load failed:', error);
                        calendar.removeAllEvents();
                    });
            });

            // Show success message if applicable
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Appointment completion time recorded successfully!',
                    icon: 'success',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'calendar.php';
                });
            }
            if (urlParams.has('error')) {
                Swal.fire({
                    title: 'Error!',
                    text: decodeURIComponent(urlParams.get('error')),
                    icon: 'error',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'OK'
                });
            }
        });
    </script>

</body>

</html>