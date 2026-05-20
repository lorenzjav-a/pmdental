<?php
$activePage = $activePage ?? '';
function sidebarActive($page)
{
    global $activePage;
    return $activePage === $page ? 'active' : '';
}
?>
<div class="sidebar">
    <h2>PM Dental</h2>

    <a href="admin-dashboard.php" class="<?= sidebarActive('dashboard'); ?>">
        <i class="fas fa-chart-line"></i>
        Dashboard
    </a>

    <a href="users.php" class="<?= sidebarActive('users'); ?>">
        <i class="fas fa-users"></i>
        Users
    </a>

    <a href="dentists.php" class="<?= sidebarActive('dentists'); ?>">
        <i class="fas fa-user-doctor"></i>
        Dentists
    </a>

    <a href="appointments.php" class="<?= sidebarActive('appointments'); ?>">
        <i class="fas fa-calendar-check"></i>
        Appointments
    </a>

    <a href="calendar.php" class="<?= sidebarActive('calendar'); ?>">
        <i class="fas fa-chart-line"></i>
        Calendar
    </a>

    <a href="reports.php" class="<?= sidebarActive('reports'); ?>">
        <i class="fas fa-file"></i>
        Reports
    </a>
</div>
