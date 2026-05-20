
<?php
require_once('../class/database.php');
$db = new database();
$activePage = 'calendar';
$calendarEvents = [];

$rows = $db->getDentistCalendarEvents();
foreach ($rows as $row) {
    $calendarEvents[] = [
        'id' => $row['Den_Calendar_ID'],
        'title' => 'Dentist #' . $row['Dentist_ID'],
        'start' => $row['Schedule_Date'] . ' ' . $row['Start_Time'],
        'end' => $row['Schedule_Date'] . ' ' . $row['End_Time']
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../styles.css"> 
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.css">
    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.js"></script>
    <script src="script.js"></script> 
    
    <style>
        body {
            margin-bottom: 40px;
            margin-top: 40px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            background: url(http://www.digiphotohub.com/wp-content/uploads/2015/09/bigstock-Abstract-Blurred-Background-Of-92820527.jpg) no-repeat center center fixed;
            background-size: cover;
        }

        #calendar {
            background-color: #FFFFFF;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0px 0px 21px 2px rgba(0, 0, 0, 0.18);
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
            z-index: 1000;
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
        }

        .fc-event, .fc-event-inner, .fc-event-title, .fc-event-time {
            cursor: pointer !important;
        }
        .fc-event-bg {
            pointer-events: none !important;
        }
    </style>

    <script>
        $(document).ready(function() {
            var date = new Date();
            var d = date.getDate();
            var m = date.getMonth();
            var y = date.getFullYear();
            
            // Global variable to remember the clicked event
            var selectedEventInstance = null;
            var dbEvents = <?php echo json_encode($calendarEvents); ?>;
            var storedEvents = dbEvents.slice();

            /* initialize the calendar */
            var calendar = $('#calendar').fullCalendar({
                header: {
                    left: 'title',
                    center: 'agendaDay,agendaWeek,month',
                    right: 'prev,next today'
                },
                editable: true,
                firstDay: 1, 
                selectable: true,
                defaultView: 'month',
                axisFormat: 'h:mm',
                
                // ADD EVENT
                select: function(start, end, allDay) {
                    var title = prompt('Add New Event Title:');
                    if (title) {
                        var customId = 'evt_' + new Date().getTime();
                        var newEvent = {
                            id: customId,
                            title: title,
                            start: start,
                            end: end,
                            allDay: allDay
                        };
                        storedEvents.push(newEvent);
                        calendar.fullCalendar('renderEvent', newEvent, true);
                    }
                    calendar.fullCalendar('unselect');
                },

                // DELETE EVENT CLICK
                eventClick: function(event, jsEvent, view) {
                    jsEvent.preventDefault();
                    jsEvent.stopPropagation();

                    selectedEventInstance = event;
                    var title = event.title || 'this event';

                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            title: 'Delete Event',
                            text: 'Are you sure you want to delete "' + title + '"?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Delete',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                deleteSelectedEvent();
                            }
                        });
                    } else {
                        console.warn('Swal not available, using confirm fallback');
                        var confirmed = window.confirm('Delete event "' + title + '"?');
                        if (confirmed) {
                            deleteSelectedEvent();
                        }
                    }

                    return false;
                },

                // Load events from the database
                events: storedEvents
            });

            function deleteSelectedEvent() {
                if (selectedEventInstance) {
                    var finalTargetId = selectedEventInstance.id || selectedEventInstance._id;
                    $('#calendar').fullCalendar('removeEvents', finalTargetId);
                    storedEvents = storedEvents.filter(function(evt) {
                        return String(evt.id) !== String(finalTargetId);
                    });
                    selectedEventInstance = null;
                }
            }
        });
    </script>
</head>

<body>
    <?php include 'admin-sidebar.php'; ?>
    <div class="main">
        <div class="container">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>