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

    .feature-card {
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15) !important;
    }
</style>

<div class="container py-5 mt-5">
    <div class="text-center mb-5">
        <h1 class="about-title">About Us</h1>
        <p class="lead text-muted">Behind the Scenes At Beyond</p>
    </div>

    <div class="row g-5">
        <div class="col-md-4">
            <h4 class="fw-bold mb-3">Where Quality Meets Affordability</h4>
            <p class="small text-muted text-justify">
                Our goal is to take care of the oral health of the entire family with skilled dentists
                and high-quality products at a fair price.
            </p>
            <p class="small text-muted text-justify">
                PM Dental avoids this by bringing numerous production processes in-house,
                whereas many dentists have a lot of expenses from suppliers to pass on to their patients.
            </p>
        </div>

        <div class="col-md-4 border-start border-end px-4">
            <h4 class="fw-bold mb-3">All Major Health Funds Accepted</h4>
            <p class="small text-muted text-justify">
                PM Dental has created its own in-house payment plan to accommodate all societal levels
                and is registered with all major health funds, so you may make a claim with your
                health insurance provider at the time of your visit.
            </p>
            <p class="mt-4"><a href="contacts.php" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold">Make a reservation right now →</a></p>
        </div>

        <div class="col-md-4 text-center">
            <div class="clinic-image-container rounded-4 overflow-hidden" onclick="openImageInNewTab('../images/dentist hams.jpg')">
                <img src="../images/dentist hams.jpg" class="img-fluid shadow" alt="PM Dental Clinic Interior">
            </div>
        </div>
    </div>
</div>

<section class="py-5 bg-white mt-5">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-4 rounded-5 shadow-sm text-center feature-card" style="background-color: #f2f2f2; min-height: 260px;">
                            <h5 class="fw-bold">We stop at nothing</h5>
                            <p class="small text-muted">One of the greatest and most cutting-edge dental offices in the Philippines, PM Dental Clinic provides excellent dental care. Beautiful smiles are created here.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded-5 shadow-sm text-center feature-card" style="background-color: #f2f2f2; min-height: 260px;">
                            <h5 class="fw-bold">We Love To Explore</h5>
                            <p class="small text-muted">PM Dental Clinic stands at the forefront of Philippine dentistry, providing innovative solutions and a world-class environment for comprehensive smile transformations.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded-5 shadow-sm text-center feature-card" style="background-color: #f2f2f2; min-height: 260px;">
                            <h5 class="fw-bold">We Take It Step-By-Step</h5>
                            <p class="small text-muted">PM Dental is easily one of the best dental spots in the Philippines. They use the latest tech to make sure your visit is top-tier and you walk out with a smile you love.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded-5 shadow-sm text-center feature-card" style="background-color: #f2f2f2; min-height: 260px;">
                            <h5 class="fw-bold">We Keep It Simple</h5>
                            <p class="small text-muted">PM Dental Clinic is a premier provider of innovative dental solutions in the Philippines, dedicated to excellence and superior patient care.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
                <p class="text-muted mb-2 fw-semibold">Quality And Top-Notch Dental Experience.</p>
                <h2 class="display-1 fw-bold" style="color: #1a2a40; line-height: 1.1;">Crafting smiles,<br>changing lives.</h2>
                <p class="mt-4 text-muted">
                    PM Dental Clinic is one of the best and most innovative dental clinics in the Philippines,
                    offering quality and top-notch dental experience. This is where beautiful smiles are made.
                </p>
            </div>

        </div>
    </div>
</section>

<script>
    function openImageInNewTab(imagePath) {
        // Convert relative path to absolute path for the new tab
        const baseUrl = window.location.origin + window.location.pathname.replace('pages/about.php', '');
        const fullImagePath = baseUrl + imagePath.replace('../', '');
        window.open(fullImagePath, '_blank');
    }
</script>

<?php include 'footer.php'; ?>