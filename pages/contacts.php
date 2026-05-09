<?php include 'header.php'; ?>



<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <h2 class="text-center">Appointment Request Form</h2>
            <p class="small text-muted text-center">Please note: this is not yet a confirmed booking.</p>
            <form action="" method="POST" class="mt-4">

                <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>

                <input type="email" name="email" class="form-control mb-3" placeholder="Email Address" required>

                <input type="text" name="phone" class="form-control mb-3" placeholder="Phone Number" required>
                
                <button type="submit" class="btn btn-primary w-100">Submit Request</button>


            </form>
        </div>
    </div>
</div>







<?php include 'footer.php'; ?>