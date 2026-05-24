<?php
session_start();
require_once('../class/database.php');
$db = new database();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['fetch_dentist_calendar'], $_GET['dentist_id'])) {
    header('Content-Type: application/json');
    $dentist_id = intval($_GET['dentist_id']);
    $appointments = $db->getDentistAppointments($dentist_id);
    $events = [];
    foreach ($appointments as $appt) {
        $events[] = [
            'id' => $appt['Appointment_ID'],
            'title' => trim((!empty($appt['Service_Name']) ? $appt['Service_Name'] . ' - ' : '') . ($appt['Patient_FN'] ?? '') . ' ' . ($appt['Patient_LN'] ?? '')),
            'start' => date('Y-m-d\TH:i:s', strtotime($appt['Appointment_Date'])),
            'color' => $appt['Appointment_Status'] === 'Confirmed' ? '#198754' : '#ffc107',
            'extendedProps' => [
                'status' => $appt['Appointment_Status'] ?? 'Pending',
                'patient' => trim(($appt['Patient_FN'] ?? '') . ' ' . ($appt['Patient_LN'] ?? '')),
                'service' => $appt['Service_Name'] ?? ''
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
            background: #eef2f7;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            min-height: 100vh;
        }

        .calendar-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            min-height: 720px;
        }

        #dentistCalendar {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
        }

        .page-description {
            color: #6b7280;
            margin-top: 0.35rem;
        }
    </style>
</head>

<body>
    <?php include 'admin-sidebar.php'; ?>
    <div class="main">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="page-title">Dentist Calendar</div>
                    <p class="page-description">Select a dentist to load their confirmed and pending appointment roster.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="calendar-card">
                        <div class="row align-items-center mb-4">
                            <div class="col-md-6">
                                <h5 class="fw-bold mb-0">Dentist roster viewer</h5>
                                <p class="text-muted small mb-0">Choose a dentist to see their schedule on the calendar.</p>
                            </div>
                            <div class="col-md-6">
                                <select id="dentistPicker" class="form-select">
                                    <option value="" selected disabled>Choose a dentist...</option>
                                    <?php foreach ($dentists as $dentist): ?>
                                        <option value="<?= $dentist['Dentist_ID']; ?>">Dr. <?= htmlspecialchars(trim($dentist['Dentist_FN'] . ' ' . $dentist['Dentist_LN'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="dentistCalendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('dentistCalendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 680,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [],
                eventDisplay: 'block',
                eventMinHeight: 35
            });

            calendar.render();

            function loadDentistSchedule(dentistId) {
                if (!dentistId) return;
                fetch('calendar.php?fetch_dentist_calendar=1&dentist_id=' + dentistId)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(events) {
                        calendar.removeAllEvents();
                        calendar.addEventSource(events);
                    });
            }

            var picker = document.getElementById('dentistPicker');
            picker.addEventListener('change', function() {
                loadDentistSchedule(this.value);
            });
        });
    </script>
</body>

</html>
