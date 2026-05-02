<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark premium-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <i class="fa-solid fa-heart-pulse text-danger fs-3 me-2 pulse-anim"></i>
            Med<span class="text-info">Pulse</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto fw-medium">
                <?php if($_SESSION['role'] !== 'Patient'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'patients.php' || $currentPage == 'patient_details.php' || $currentPage == 'add_observation.php') ? 'active' : '' ?>" href="patients.php"><i class="fa-solid fa-user-injured me-1"></i> Patients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'alerts.php') ? 'active' : '' ?>" href="alerts.php"><i class="fa-solid fa-bell me-1"></i> Alerts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'prescriptions.php') ? 'active' : '' ?>" href="prescriptions.php"><i class="fa-solid fa-pills me-1"></i> Prescriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'questionnaire.php') ? 'active' : '' ?>" href="questionnaire.php"><i class="fa-solid fa-clipboard-list me-1"></i> Questionnaire</a>
                    </li>
                    <?php if(isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'manage_dictionaries.php') ? 'active' : '' ?>" href="manage_dictionaries.php"><i class="fa-solid fa-gears me-1"></i> Settings</a>
                    </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'my_health.php') ? 'active' : '' ?>" href="my_health.php"><i class="fa-solid fa-heart-pulse me-1"></i> My Health</a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle btn btn-outline-light btn-sm px-3 text-white border-opacity-25" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><div class="dropdown-header small text-uppercase fw-bold">Role: <?= htmlspecialchars($_SESSION['role'] ?? 'HealthWorker') ?></div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
