<?php
$activePage = $activePage ?? '';

if (!function_exists('sidebarActive')) {
    function sidebarActive($page)
    {
        global $activePage;
        return $activePage === $page ? 'active' : '';
    }
}
?>
<div id="employeeSidebar">
    <div class="sidebar-brand">PM Dental Staff</div>
    <div class="sidebar-links">
        <a href="employee-dashboard.php" class="<?= sidebarActive('dashboard'); ?>">
            <i class="fa-solid fa-house me-2"></i>Dashboard Home
        </a>
        <a href="patient-appointments-queue.php" class="<?= sidebarActive('appointments_queue'); ?>">
            <i class="fa-solid fa-calendar-check me-2"></i>Patient Appointments Queue
        </a>
        <a href="patient-profile-masterlist.php" class="<?= sidebarActive('patient_masterlist'); ?>">
            <i class="fa-solid fa-user-doctor me-2"></i>Patient Profile Masterlist
        </a>
        <a href="dentist-schedule-rosters.php" class="<?= sidebarActive('dentist_rosters'); ?>">
            <i class="fa-solid fa-calendar-days me-2"></i>Dentist Schedule Rosters
        </a>
        <a href="login.php">
            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout
        </a>
    </div>
</div>
