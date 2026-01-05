<?php
// includes/umkm_sidebar.php
// Component Sidebar untuk UMKM Panel

// Ambil data user dan UMKM
$user_id = $_SESSION['user_id'];

// Query untuk ambil data user
$query_user = "SELECT username, foto_profil FROM users WHERE id = :user_id";
$stmt_user = $conn->prepare($query_user);
$stmt_user->bindParam(':user_id', $user_id);
$stmt_user->execute();
$user_data = $stmt_user->fetch();

// Query untuk ambil nama usaha
$query_umkm = "SELECT nama_usaha FROM umkm_data WHERE user_id = :user_id LIMIT 1";
$stmt_umkm = $conn->prepare($query_umkm);
$stmt_umkm->bindParam(':user_id', $user_id);
$stmt_umkm->execute();
$umkm_data = $stmt_umkm->fetch();

// Set default values
$username = $user_data['username'] ?? 'User';
$foto_profil = $user_data['foto_profil'] ?? '';
$nama_usaha = $umkm_data['nama_usaha'] ?? 'Belum ada usaha';

// Path foto profil
$foto_path = $foto_profil ? "../uploads/users/" . htmlspecialchars($foto_profil) : '';

// Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">
    <!-- Brand Section (Paling Atas) -->
    <div class="sidebar-brand">
        <h4><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h4>
        <small>Panel UMKM</small>
    </div>
    
    <div class="user-profile-card">
        <div class="profile-avatar">
            <?php if ($foto_path && file_exists($foto_path)): ?>
                <img src="<?= $foto_path ?>" alt="<?= htmlspecialchars($username) ?>">
            <?php else: ?>
                <div class="avatar-initial">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h6 class="username">@<?= htmlspecialchars($username) ?></h6>
            <p class="business-name"><?= htmlspecialchars($nama_usaha) ?></p>
        </div>
    </div>
    
    <!-- Menu Navigation -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'profil.php' ? 'active' : '' ?>" href="profil.php">
                <i class="bi bi-shop"></i>
                <span>Profil UMKM</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'produk.php' ? 'active' : '' ?>" href="produk.php">
                <i class="bi bi-box-seam"></i>
                <span>Kelola Produk</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="bi bi-globe"></i>
                <span>Lihat Website</span>
            </a>
        </li>
    </ul>
    
    <!-- Action Buttons (Settings & Logout) -->
    <div class="sidebar-actions">
        <a href="settings.php" class="btn-action btn-settings" title="Pengaturan Akun">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
        <a href="../auth/logout.php" class="btn-action btn-logout" title="Keluar">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        padding: 0;
        z-index: 1000;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
    }
    
    /* BRAND SECTION */
    .sidebar-brand {
        padding: 1.5rem 1.5rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
    }
    
    .sidebar-brand h4 {
        color: white;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        font-size: 1.3rem;
    }
    
    .sidebar-brand small {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
    }
    
    /* USER PROFILE CARD (Horizontal) */
    .user-profile-card {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 8px 10px;
        background: rgba(255, 255, 255, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        margin-bottom: 1rem;
    }
    
    .profile-avatar {
        width: 50px;
        height: 50px;
        flex-shrink: 0;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50px;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .avatar-initial {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .profile-info {
        flex: 1;
        min-width: 0;
        color: white;
    }
    
    .username {
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0 0 0.15rem 0;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .business-name {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        margin: 0;
        font-weight: 400;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* NAVIGATION MENU */
    .nav {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }
    
    .nav-item {
        list-style: none;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        padding: 0.875rem 1.5rem;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        text-decoration: none;
        font-size: 0.95rem;
    }
    
    .nav-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border-left-color: rgba(255, 255, 255, 0.5);
    }
    
    .nav-link.active {
        color: white;
        background: rgba(255, 255, 255, 0.15);
        border-left-color: white;
        font-weight: 600;
    }
    
    .nav-link i {
        font-size: 1.1rem;
        width: 24px;
        text-align: center;
    }
    
    /* ACTION BUTTONS (Settings & Logout) */
    .sidebar-actions {
        padding: 1rem 1rem 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-action {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        padding: 0.75rem 0.5rem;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .btn-settings {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-settings:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .btn-logout {
        background: rgba(239, 68, 68, 0.2);
        color: white;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .btn-logout:hover {
        background: rgba(239, 68, 68, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        color: white;
    }
    
    .btn-action i {
        font-size: 1.25rem;
    }
    
    .btn-action span {
        font-size: 0.75rem;
    }
    
    /* SCROLLBAR STYLING */
    .nav::-webkit-scrollbar {
        width: 4px;
    }
    
    .nav::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    
    .nav::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>