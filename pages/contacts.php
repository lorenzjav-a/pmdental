<?php include 'header.php'; ?>

<?php
$showData = false;
$fullname = $email = $phone = "";

if (isset($_POST['submit_request'])) {

    $fullname = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $showData = true;

    // Redirect after 5 seconds
    header("refresh:5;url=index.php");
}
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <h2 class="text-center">Appointment Request Form</h2>
            <p class="small text-muted text-center">
                Please note: this is not yet a confirmed booking.
            </p>

            <form method="POST" class="mt-4">

                <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>

                <input type="email" name="email" class="form-control mb-3" placeholder="Email Address" required>

                <input type="text" name="phone" class="form-control mb-3" placeholder="Phone Number" required>

                <button type="submit" name="submit_request" class="btn btn-primary w-100">
                    Submit Request
                </button>

            </form>

            <?php if ($showData) { ?>

                <div class="mt-4 p-3 border rounded bg-light">
                    <h5>Request Received!</h5>

                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>

                    <p class="text-muted">Redirecting to homepage in 5 seconds...</p>
                </div>

            <?php } ?>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>