<?php
// index.php
require_once 'config/koneksi.php';

$database = new Database();
$conn = $database->getConnection();

// Ambil data UMKM terverifikasi untuk peta
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM produk WHERE umkm_id = u.id) as jumlah_produk
          FROM umkm_data u 
          WHERE u.status_verifikasi = 'terverifikasi' 
          AND u.lat IS NOT NULL 
          AND u.lng IS NOT NULL";
$stmt = $conn->prepare($query);
$stmt->execute();
$umkm_data = $stmt->fetchAll();

// Statistik produk
$query_produk = "SELECT COUNT(*) as total FROM produk p 
                 JOIN umkm_data u ON p.umkm_id = u.id 
                 WHERE u.status_verifikasi = 'terverifikasi'";
$stmt_produk = $conn->prepare($query_produk);
$stmt_produk->execute();
$total_produk = $stmt_produk->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAP-UMKM - Sistem Informasi Geografis dan Pemantauan UMKM Kota Semarang</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { overflow-x: hidden; }
        
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-login {
            background: white;
            color: #667eea;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.5rem;
        }
        
        .user-info {
            text-align: left;
            line-height: 1.2;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
            text-transform: uppercase;
        }
        
        .user-dropdown {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
            background-size: 50px 50px;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            opacity: 0.95;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }
        
        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        .map-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2d3748;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 3rem;
        }
        
        #map {
            height: 600px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .custom-popup {
            max-width: 300px;
        }
        
        .popup-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .popup-category {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }
        
        .popup-info {
            font-size: 0.9rem;
            color: #4a5568;
            margin: 0.25rem 0;
        }
        
        .footer {
            background: #2d3748;
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }
        
        .footer h5 {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#peta">Peta UMKM</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="katalog.php">Katalog Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar me-2">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="user-info">
                                    <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                    <div class="user-role"><?= ucfirst($_SESSION['role']) ?></div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?= isAdmin() ? 'admin/index.php' : 'umkm/index.php' ?>">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
                                <?php if (!isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item" href="umkm/profil.php">
                                        <i class="bi bi-person me-2"></i>Profil UMKM
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="umkm/produk.php">
                                        <i class="bi bi-box-seam me-2"></i>Kelola Produk
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-3">
                            <a href="auth/login.php" class="btn btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="beranda" class="hero-section">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-7">
                    <h1 class="hero-title">Sistem Informasi Geografis UMKM Kota Semarang</h1>
                    <p class="hero-subtitle">
                        Platform terpadu untuk pemetaan, pemantauan kesejahteraan, dan promosi UMKM di Kota Semarang. 
                        Membangun ekonomi lokal yang kuat dan berkelanjutan.
                    </p>
                    <a href="#peta" class="btn btn-light btn-lg rounded-pill px-4 me-3">
                        <i class="bi bi-map me-2"></i>Jelajahi Peta
                    </a>
                    <a href="katalog.php" class="btn btn-outline-light btn-lg rounded-pill px-4 me-3">
                        <i class="bi bi-cart me-2"></i>Lihat Produk
                    </a>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg rounded-pill px-4">
                        <i class="bi bi-person-plus me-2"></i>Daftar UMKM
                    </a>
                </div>
                <div class="col-lg-5 mt-5 mt-lg-0">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stats-card">
                                <i class="bi bi-shop fs-1 mb-2"></i>
                                <span class="stats-number"><?= count($umkm_data) ?></span>
                                <span class="stats-label">UMKM Terdaftar</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <i class="bi bi-box-seam fs-1 mb-2"></i>
                                <span class="stats-number"><?= $total_produk ?></span>
                                <span class="stats-label">Produk Tersedia</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section id="peta" class="map-section">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Peta Sebaran UMKM</h2>
                <p class="section-subtitle">Temukan UMKM terverifikasi di sekitar Kota Semarang</p>
            </div>
            <div id="map"></div>
        </div>
    </section>

    <!-- About Section -->
    <section id="tentang" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 p-4">
                        <div class="text-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4">
                                <i class="bi bi-map text-primary fs-1"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">Pemetaan Digital</h4>
                        <p class="text-muted">Visualisasi lokasi UMKM dengan teknologi GIS untuk memudahkan akses informasi</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 p-4">
                        <div class="text-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-4">
                                <i class="bi bi-graph-up text-success fs-1"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">Pemantauan Kesejahteraan</h4>
                        <p class="text-muted">Monitoring data omzet dan perkembangan UMKM untuk kebijakan yang tepat sasaran</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 p-4">
                        <div class="text-center mb-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-4">
                                <i class="bi bi-megaphone text-warning fs-1"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">Promosi UMKM</h4>
                        <p class="text-muted">Platform untuk mempromosikan produk UMKM kepada masyarakat luas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h5>
                    <p class="text-white-50">Sistem Informasi Geografis dan Pemantauan UMKM Kota Semarang</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kontak</h5>
                    <p class="mb-1">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:info@sigap-umkm.semarang.go.id">info@sigap-umkm.semarang.go.id</a>
                    </p>
                    <p>
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:+622483456789">(024) 8345 6789</a>
                    </p>
                </div>
                <div class="col-lg-4">
                    <h5>Alamat</h5>
                    <p class="text-white-50">
                        <i class="bi bi-building me-2"></i>
                        Pemkot Semarang<br>
                        Jl. Pemuda No. 148, Semarang
                    </p>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; 2024 SIGAP-UMKM Kota Semarang. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    
    <script>
        var map = L.map('map').setView([-6.9825, 110.4094], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        var markers = L.markerClusterGroup({
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: true,
            zoomToBoundsOnClick: true
        });
        
        var umkmData = <?= json_encode($umkm_data) ?>;
        
        var customIcon = L.divIcon({
            html: '<i class="bi bi-shop-window fs-4 text-primary"></i>',
            className: 'custom-marker',
            iconSize: [30, 30]
        });
        
        umkmData.forEach(function(umkm) {
            var popupContent = `
                <div class="custom-popup">
                    ${umkm.foto_usaha ? `<img src="uploads/umkm_profile/${umkm.foto_usaha}" class="img-fluid rounded mb-2" alt="${umkm.nama_usaha}">` : ''}
                    <div class="popup-title">${umkm.nama_usaha}</div>
                    <span class="popup-category">${umkm.kategori}</span>
                    <div class="popup-info">
                        <i class="bi bi-geo-alt-fill text-danger"></i> ${umkm.alamat}
                    </div>
                    <div class="popup-info">
                        <i class="bi bi-people-fill text-primary"></i> ${umkm.jumlah_karyawan} Karyawan
                    </div>
                    <div class="popup-info">
                        <i class="bi bi-box-seam-fill text-success"></i> ${umkm.jumlah_produk} Produk
                    </div>
                    ${umkm.deskripsi ? `<p class="mt-2 mb-0 text-muted small">${umkm.deskripsi}</p>` : ''}
                    ${umkm.no_telepon ? `<a href="https://wa.me/62${umkm.no_telepon.replace(/^0/, '')}" target="_blank" class="btn btn-sm btn-success w-100 mt-2"><i class="bi bi-whatsapp me-1"></i>Hubungi</a>` : ''}
                </div>
            `;
            
            var marker = L.marker([umkm.lat, umkm.lng], { icon: customIcon })
                .bindPopup(popupContent);
            
            markers.addLayer(marker);
        });
        
        map.addLayer(markers);
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
    
</body>
</html>