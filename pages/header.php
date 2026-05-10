<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PM Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        .custom-lavender { background-color: #c5aded !important; }
        
        .navbar { background-color: #c5aded !important; }
        .nav-link, .navbar-brand, .appointment-text { 
            color: #444 !important; 
            font-weight: 500; 
        }
        
        
        section { padding: 60px 0; }
        .about-title { font-size: 3rem; font-weight: 800; color: #2c3e50; }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <main class="flex-shrink-0">

    <nav class="navbar navbar-expand-lg py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../images/305646737_600049088492951_2133638054290563897_n-removebg-preview.png" alt="PM Dental Clinic Logo" style="height: 80px; width: auto;" class="me-2">
            
            <div class="d-flex flex-column lh-1">
                <span class="fw-bold fs-3 text-dark" style="letter-spacing: 1px;">PM Dental Clinic</span>
                <small class="text-dark opacity-75 mt-1" style="letter-spacing: 4px; font-size: 0.7rem;">PESIGAN | MATANGUIHAN</small>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Us</a></li>
                <li class="nav-item ms-lg-4">
                    <span class="appointment-text">Appointment | 📞 09222981492</span>
                </li>
            </ul>
        </div>
    </div>
</nav>