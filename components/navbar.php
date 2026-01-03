<?php
// components/navbar.php
$current_file = $_SERVER['PHP_SELF'];
$base_path = '';

// Jika dipanggil dari subfolder (admin, umkm, auth)
if (strpos($current_file, '/admin/') !== false || 
    strpos($current_file, '/umkm/') !== false || 
    strpos($current_file, '/auth/') !== false) {
    $base_path = '../';
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi untuk generate warna background berdasarkan username
function getAvatarColor($username) {
    $colors = [
        '#667eea', '#764ba2', '#f093fb', '#4facfe',
        '#43e97b', '#fa709a', '#fee140', '#30cfd0',
        '#a8edea', '#fed6e3', '#c471f5', '#fa8bff'
    ];
    $index = ord(strtoupper($username[0])) % count($colors);
    return $colors[$index];
}
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>index.php">
            <div class="brand-icon-wrapper">
                <i class="bi bi-geo-alt-fill brand-icon"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name">SIGAP</span>
                <span class="brand-subtitle">UMKM</span>
            </div>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link-custom <?= $current_page == 'index.php' && empty($base_path) ? 'active' : '' ?>" 
                       href="<?= $base_path ?>index.php">
                        <i class="bi bi-house-door me-1"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom" href="<?= $base_path ?>index.php#peta">
                        <i class="bi bi-map me-1"></i>
                        <span>Peta UMKM</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom <?= $current_page == 'katalog.php' ? 'active' : '' ?>" 
                       href="<?= $base_path ?>katalog.php">
                        <i class="bi bi-grid-3x3-gap me-1"></i>
                        <span>Katalog</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom <?= $current_page == 'tentang.php' ? 'active' : '' ?>" 
                       href="<?= $base_path ?>tentang.php">
                        <i class="bi bi-info-circle me-1"></i>
                        <span>Tentang</span>
                    </a>
                </li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                    $username = $_SESSION['username'];
                    $foto_profil = $_SESSION['foto_profil'] ?? '';
                    $avatar_letter = strtoupper(substr($username, 0, 1));
                    $avatar_color = getAvatarColor($username);
                    
                    ?>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link-custom user-dropdown-toggle d-flex align-items-center gap-2" 
                           href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar-modern">
                                <?php if (!empty($foto_profil)): ?>
                                    <img src="<?= $base_path ?>uploads/users/<?= htmlspecialchars($foto_profil) ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-letter" style="background: <?= $avatar_color ?>;">
                                        <?= $avatar_letter ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="user-info-modern d-none d-lg-block">
                                <div class="user-name-modern"><?= htmlspecialchars($username) ?></div>
                                <div class="user-role-modern">
                                    <i class="bi bi-shield-fill-check me-1"></i>
                                    <?= ucfirst($_SESSION['role']) ?>
                                </div>
                            </div>
                            <i class="bi bi-chevron-down ms-1 d-none d-lg-inline"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-modern shadow-lg" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="text-center">
                                    <div class="dropdown-avatar">
                                        <?php if (!empty($foto_profil)): ?>
                                            <img src="<?= $base_path ?>uploads/users/<?= htmlspecialchars($foto_profil) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <div class="avatar-letter-large" style="background: <?= $avatar_color ?>;">
                                                <?= $avatar_letter ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fw-bold mt-2"><?= htmlspecialchars($username) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item-modern" href="<?= $base_path ?><?= $_SESSION['role'] == 'admin' ? 'admin/index.php' : 'umkm/index.php' ?>">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <?php if ($_SESSION['role'] != 'admin'): ?>
                            <li>
                                <a class="dropdown-item-modern" href="<?= $base_path ?>umkm/profil.php">
                                    <i class="bi bi-building"></i>
                                    <span>Profil UMKM</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item-modern" href="<?= $base_path ?>umkm/produk.php">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Kelola Produk</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item-modern" href="<?= $base_path ?><?= $_SESSION['role'] == 'admin' ? 'admin/settings.php' : 'umkm/settings.php' ?>">
                                    <i class="bi bi-gear"></i>
                                    <span>Pengaturan</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item-modern text-danger" href="<?= $base_path ?>auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3">
                        <a href="<?= $base_path ?>auth/login.php" class="btn-login-modern">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            <span>Masuk</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $base_path ?>auth/register.php" class="btn-register-modern">
                            <i class="bi bi-person-plus me-2"></i>
                            <span>Daftar</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Modern Navbar Styling */
    .navbar-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 1rem 0;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.25);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .navbar-custom.scrolled {
        background: linear-gradient(135deg, #667eea 0%, #9466c3ff 100%);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
    }
    
    /* Modern Brand */
    .navbar-brand {
        text-decoration: none;
        transition: transform 0.3s ease;
    }
    
    .navbar-brand:hover {
        transform: translateY(-2px);
    }
    
    .brand-icon-wrapper {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #fff, #f0f0ff);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-right: 12px;
    }
    
    .brand-icon {
        font-size: 1.5rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    
    .brand-name {
        font-weight: 800;
        font-size: 1.3rem;
        color: white;
        letter-spacing: 1px;
    }
    
    .brand-subtitle {
        font-weight: 500;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.9);
        letter-spacing: 3px;
        text-transform: uppercase;
    }
    
    /* Modern Nav Links */
    .nav-link-custom {
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 500;
        padding: 0.6rem 1.2rem !important;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }
    
    .nav-link-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: -1;
    }
    
    .nav-link-custom:hover::before,
    .nav-link-custom.active::before {
        transform: translateX(0);
    }
    
    .nav-link-custom:hover,
    .nav-link-custom.active {
        color: white !important;
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }
    
    .nav-link-custom i {
        font-size: 1.1rem;
    }
    
    /* Modern Login/Register Buttons */
    .btn-login-modern {
        background: white;
        color: #667eea;
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        border: 2px solid transparent;
    }
    
    .btn-login-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(255, 255, 255, 0.4);
        background: #f8f9ff;
        color: #667eea;
    }
    
    .btn-register-modern {
        background: transparent;
        color: white;
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }
    
    .btn-register-modern:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.1);
        border-color: white;
        color: white;
    }
    
    /* Modern User Avatar */
    .user-avatar-modern {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        border: 3px solid rgba(255, 255, 255, 0.5);
        background: white;
        position: relative;
    }
    
    .user-avatar-modern img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .user-avatar-modern .avatar-letter {
        position: absolute;
        top: 0;
        left: 0;
    }
    
    /* Avatar Letter Styling */
    .avatar-letter {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: uppercase;
    }
    
    /* Show avatar letter when image fails to load */
    .user-avatar-modern img:not([src]),
    .user-avatar-modern img[src=""] {
        display: none !important;
    }
    
    .user-avatar-modern img + .avatar-letter {
        display: flex !important;
    }
    
    .user-info-modern {
        text-align: left;
        line-height: 1.3;
    }
    
    .user-name-modern {
        font-weight: 600;
        font-size: 0.95rem;
        color: white;
    }
    
    .user-role-modern {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
    }
    
    .user-dropdown-toggle {
        padding: 0.5rem 1rem !important;
    }
    
    /* Modern Dropdown */
    .dropdown-modern {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: white;
        min-width: 280px;
        padding: 0;
        margin-top: 1rem !important;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .dropdown-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
    }
    
    .dropdown-avatar {
        width: 70px;
        height: 70px;
        margin: 0 auto;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        position: relative;
    }
    
    .dropdown-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .dropdown-avatar .avatar-letter-large {
        position: absolute;
        top: 0;
        left: 0;
    }
    
    /* Avatar Letter Large for Dropdown */
    .avatar-letter-large {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 2rem;
        text-transform: uppercase;
    }
    
    /* Show avatar letter large when image fails to load */
    .dropdown-avatar img:not([src]),
    .dropdown-avatar img[src=""] {
        display: none !important;
    }
    
    .dropdown-avatar img + .avatar-letter-large {
        display: flex !important;
    }
    
    .dropdown-item-modern {
        padding: 0.8rem 1.5rem;
        color: #2d3748;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    
    .dropdown-item-modern:hover {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
        border-left-color: #667eea;
        padding-left: 1.8rem;
    }
    
    .dropdown-item-modern.text-danger:hover {
        background: linear-gradient(90deg, rgba(220, 53, 69, 0.1), transparent);
        border-left-color: #dc3545;
    }
    
    .dropdown-item-modern i {
        font-size: 1.2rem;
        width: 20px;
        text-align: center;
    }
    
    .dropdown-divider {
        margin: 0.5rem 0;
        opacity: 0.1;
    }
    
    /* Mobile Responsive */
    @media (max-width: 991px) {
        .navbar-custom {
            padding: 0.75rem 0;
        }
        
        .navbar-collapse {
            background: rgba(102, 126, 234, 0.98);
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link-custom {
            width: 100%;
            justify-content: flex-start;
        }
        
        .btn-login-modern,
        .btn-register-modern {
            width: 100%;
            justify-content: center;
            margin-top: 0.5rem;
        }
        
        .dropdown-modern {
            width: 100%;
            margin-top: 0.5rem !important;
        }
    }
    
    /* Smooth Scroll Behavior */
    html {
        scroll-behavior: smooth;
    }
    
    /* Navbar Toggler */
    .navbar-toggler {
        padding: 0.5rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .navbar-toggler:focus {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
    }
    
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
</style>

<script>
// Add scroll effect to navbar
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
});

// Fix dropdown toggle and avatar fallback
document.addEventListener('DOMContentLoaded', function() {
    // Handle image load errors for avatar
    const avatarImages = document.querySelectorAll('.user-avatar-modern img, .dropdown-avatar img');
    avatarImages.forEach(function(img) {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            const avatarLetter = this.nextElementSibling;
            if (avatarLetter && avatarLetter.classList.contains('avatar-letter')) {
                avatarLetter.style.display = 'flex';
            } else if (avatarLetter && avatarLetter.classList.contains('avatar-letter-large')) {
                avatarLetter.style.display = 'flex';
            }
        });
        
        // Check if image loaded successfully
        if (img.complete && img.naturalHeight === 0) {
            img.style.display = 'none';
            const avatarLetter = img.nextElementSibling;
            if (avatarLetter) {
                avatarLetter.style.display = 'flex';
            }
        }
    });
});
</script>