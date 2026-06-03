<?php include 'header.php'; ?>

<style>
    .clinic-image-container {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .clinic-image-container:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3) !important;
    }

    .clinic-image-container:hover img {
        filter: brightness(1.1);
    }

    .btn-clinic {
        transition: all 0.3s ease;
    }

    .btn-clinic:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(197, 173, 237, 0.4) !important;
        background-color: #b89cd1 !important;
    }
</style>

<header class="py-5 overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center">

            <div class="col-lg-5">
                <h2 class="fw-bold mb-0" style="color: #1a2a40; font-size: 2.5rem;">Specialized</h2>
                <h1 class="display-1 fw-bold mb-3" style="color: #1a2a40; line-height: 0.9;">Dental Care</h1>
                <p class="fs-4 text-muted mb-4">for All Your Needs</p>

                <a href="about.php" class="btn rounded-pill px-4 shadow-sm py-2 btn-clinic" style="background-color: #c5aded; color: white; border: none;"> ↓ About Us </a>
            </div>

            <div class="col-lg-7 mt-5 mt-lg-0 position-relative">
                <div class="row g-3 justify-content-center">
                    <div class="col-6 col-md-5 mt-5">
                        <div class="rounded-5 overflow-hidden shadow mb-3 clinic-image-container" style="border-radius: 40px !important;" onclick="openImageInNewTab('../images/Image (1).jpg')">
                            <img src="../images/Image (1).jpg" class="img-fluid w-100" alt="Clinic Exterior">
                        </div>
                    </div>
                    <div class="col-6 col-md-5">
                        <div class="rounded-5 overflow-hidden shadow mb-3 clinic-image-container" style="border-radius: 40px !important;" onclick="openImageInNewTab('../images/118474472_157228176013564_3065694086086505040_n.jpg')">
                            <img src="../images/118474472_157228176013564_3065694086086505040_n.jpg" class="img-fluid w-100" alt="Clinic Office">
                        </div>
                        <div class="rounded-5 overflow-hidden shadow clinic-image-container" style="border-radius: 40px !important;" onclick="openImageInNewTab('../images/Image.jpg')">
                            <img src="../images/Image.jpg" class="img-fluid w-100" alt="Dental Chair">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>

<script>
    function openImageInNewTab(imagePath) {
        const baseUrl = window.location.origin + window.location.pathname.replace('pages/index.php', '');
        const fullImagePath = baseUrl + imagePath.replace('../', '');
        window.open(fullImagePath, '_blank');
    }
</script>


<?php include 'footer.php'; ?>