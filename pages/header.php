<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PM Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        .custom-lavender { background-color: #c5aded !important; }
        .navbar-brand { color: #c5aded !important; font-weight: bold; font-size: 1.5rem; }
        
        .btn-appointment-outline { 
            border: 1px solid #0d6efd; 
            color: #0d6efd; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 5px; 
            white-space: nowrap;
            transition: 0.3s;
        }
        .btn-appointment-outline:hover {
            background-color: #0d6efd;
            color: white;
        }
        
        .nav-link { color: #555 !important; font-weight: 500; }
    </style>


</head>
<body class="d-flex flex-column h-100">
    <main class="flex-shrink-0">

    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">PM Dental Clinic</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center">

                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>

                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>

                    <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Us</a></li>

                    <li class="nav-item ms-lg-4 mt-2 mt-lg-0">

                        <a href="tel:09222981492" class="btn-appointment-outline">
                            Appointment: 09222981492

                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>