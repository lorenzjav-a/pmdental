<?php include 'header.php'; ?>

<?php
require_once('../class/database.php');
$con = new database();

$availableServices = $con->viewServices();

$showData = false;
$fullname = $email = $phone = $birthday = $gender = $appointment_date = $service_id = "";
$dbMessage = "";
$dbStatus = "";

if (isset($_POST['submit_request'])) {
    $fullname         = $_POST['name'];
    $email            = $_POST['email'];
    $phone            = $_POST['phone'];
    $birthday         = $_POST['birthday'];
    $gender           = $_POST['gender'];
    $appointment_date = $_POST['appointment_date'];
    $service_id       = $_POST['service_id'];

    // Normalize the datetime-local value for MySQL DATETIME storage
    $appointment_datetime = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $appointment_date)));

    // Check if the requested time slot is already booked
    if ($con->isTimeSlotBooked($appointment_datetime)) {
        $dbStatus = "error";
        $dbMessage = "The requested date and time slot is already booked. Please choose another date or time.";
    } else {
        try {
            $con->createAppointmentRequest(
                $fullname,
                $email,
                $phone,
                $birthday,
                $gender,
                $appointment_datetime,
                $service_id
            );

            $showData = true;
            $dbStatus = "success";
            $dbMessage = "Your booking request has been forwarded to our desk successfully.";

            header("refresh:10;url=index.php");
        } catch (PDOException $e) {

            $dbStatus = "error";
            $dbMessage = "Invalid service selected. Please try again.";

            // For debugging:
            // echo $e->getMessage();
        }
    }
}

?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <h2 class="text-center fw-bold">Appointment Request Form</h2>
            <p class="small text-muted text-center mb-4">
                Please note: this is not yet a confirmed booking. Our desk team will assign a doctor to finalize your schedule.
            </p>

            <?php if (!empty($dbMessage) && $dbStatus == 'error'): ?>
                <div class="alert alert-danger mb-3 small"><?= $dbMessage; ?></div>
            <?php elseif (!empty($dbMessage) && $dbStatus == 'success'): ?>
                <div class="alert alert-success mb-3 small"><?= $dbMessage; ?></div>
            <?php endif; ?>

            <form method="POST" class="card p-4 shadow-sm border-0 bg-white">

                <div class="mb-3">
                    <label class="form-label small fw-medium">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder=" " value="<?= htmlspecialchars($fullname); ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Birthday</label>
                        <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($birthday); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Gender</label>
                        <input type="text" name="gender" class="form-control" placeholder=" " value="<?= htmlspecialchars($gender); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($email); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder=" " value="<?= htmlspecialchars($phone); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Requested Dental Service</label>
                    <select name="service_id" class="form-select" required>
                        <option value="" selected disabled>Select desired treatment procedure...</option>
                        <?php foreach ($availableServices as $srv): ?>
                            <option value="<?= $srv['Service_ID']; ?>" <?= ($service_id == $srv['Service_ID']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($srv['Service_Name']); ?> (₱<?= number_format($srv['Service_Fee'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-medium">Preferred Schedule Arrival Date & Time</label>
                    <input type="datetime-local" name="appointment_date" class="form-control" value="<?= htmlspecialchars($appointment_date); ?>" required>
                </div>

                <button type="submit" name="submit_request" class="btn btn-primary w-100 py-2 fw-semibold">
                    Submit Request
                </button>

            </form>

            <?php if ($showData && $dbStatus == 'success') { ?>
                <div class="mt-4 p-4 border rounded bg-light border-success">
                    <h5 class="text-success fw-bold">Request Received Successfully!</h5>
                    <p class="small text-muted mb-3"><?= $dbMessage; ?></p>

                    <p class="mb-1 small"><strong>Patient Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
                    <p class="mb-1 small"><strong>Gender / Birthday:</strong> <?php echo htmlspecialchars($gender) . ' / ' . htmlspecialchars($birthday); ?></p>
                    <p class="mb-1 small"><strong>Contact Identifier:</strong> <?php echo htmlspecialchars($phone); ?></p>
                    <p class="mb-1 small"><strong>Target Timestamp:</strong> <?php echo date('M d, Y - h:i A', strtotime($appointment_date)); ?></p>

                    <hr class="my-3">
                    <p class="text-muted small mb-0">Redirecting to home view structure layout in 10 seconds...</p>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<?php if ($dbStatus == "taken"): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Slot Already Taken',
            text: 'This schedule is already booked. Please choose another time.',
            confirmButtonColor: '#d33'
        });
    </script>
<?php endif; ?>


<?php if ($dbStatus == "success"): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Request Submitted',
            text: 'Your appointment has been successfully sent.',
            confirmButtonColor: '#198754'
        });
    </script>
<?php endif; ?>


<?php if ($dbStatus == "error"): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'System Error',
            text: 'Something went wrong. Please try again.',
        });
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>