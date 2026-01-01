<?php
// includes/admin_sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h4>
        <small>Admin Panel</small>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'verifikasi.php' ? 'active' : '' ?>" href="verifikasi.php">
                <i class="bi bi-check-circle me-2"></i>Verifikasi UMKM
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="users.php">
                <i class="bi bi-people me-2"></i>Management User
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'umkm-management.php' ? 'active' : '' ?>" href="umkm-management.php">
                <i class="bi bi-shop-window me-2"></i>Management UMKM
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                <i class="bi bi-gear me-2"></i>Pengaturan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="bi bi-globe me-2"></i>Lihat Website
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="../auth/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
        padding: 2rem 0;
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar-brand {
        padding: 0 1.5rem 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 2rem;
    }
    
    .sidebar-brand h4 {
        color: white;
        font-weight: 700;
        margin: 0;
        font-size: 1.3rem;
    }
    
    .sidebar-brand small {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.85rem;
    }
    
    .nav-link {
        color: rgba(255, 255, 255, 0.7);
        padding: 0.875rem 1.5rem;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        font-size: 0.95rem;
    }
    
    .nav-link:hover, .nav-link.active {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border-left-color: #667eea;
    }
    
    .nav-link i {
        width: 20px;
    }
</style>