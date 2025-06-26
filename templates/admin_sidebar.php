<?php
// templates/admin_sidebar.php
$current_admin_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="adminSidebar" class="col-md-3 col-lg-2 d-md-block bg-white sidebar">
    <div class="position-sticky sidebar-sticky pt-3">
        <div class="sidebar-header d-flex align-items-center mt-md-3">
            <a href="<?php echo BASE_PATH . 'admin/dashboard.php'; ?>" class="text-decoration-none d-flex align-items-center w-100">
                <img src="<?php echo ASSETS_PATH; ?>/images/logo.png" alt="Logo" class="mx-auto mx-md-0" style="width: 32px; height: 32px; border-radius: 50%;">
                <span class="sidebar-logo-text d-none d-md-inline fs-5 fw-semibold ms-2">Dari Admin</span>
            </a>
        </div>
                <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/dashboard.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="ph ph-chart-line"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/houses.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'houses.php') ? 'active' : ''; ?>">
                    <i class="ph ph-house-line"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Propriétés</span>
                </a>
            </li>
             <!-- NEW AVAILABILITY LINK -->
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/availability.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'availability.php') ? 'active' : ''; ?>">
                    <i class="ph ph-calendar-check"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Disponibilités</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/reservations.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'reservations.php') ? 'active' : ''; ?>">
                    <i class="ph ph-calendar"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Réservations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/voucher.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'voucher.php') ? 'active' : ''; ?>">
                    <i class="ph ph-receipt"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Voucher</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/analytics.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'analytics.php') ? 'active' : ''; ?>">
                    <i class="ph ph-chart-bar"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Statistiques</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_PATH . 'admin/features.php'; ?>" class="nav-link <?php echo ($current_admin_page === 'features.php') ? 'active' : ''; ?>">
                    <i class="ph ph-list-checks"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Équipements</span>
                </a>
            </li>
        </ul>

        <hr class="mx-3">

        <ul class="nav flex-column mb-2">
             <li class="nav-item">
                <a class="nav-link text-muted" href="#">
                    <i class="ph ph-user-circle"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">
                        <?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Profil'; ?>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo BASE_PATH . 'admin/logout.php'; ?>">
                    <i class="ph ph-sign-out"></i>
                    <span class="sidebar-nav-text d-none d-md-inline">Déconnexion</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
