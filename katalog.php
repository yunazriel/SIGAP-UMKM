<?php
// katalog.php
require_once 'config/koneksi.php';

$database = new Database();
$conn = $database->getConnection();

// Filter
$kategori_filter = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT p.*, u.nama_usaha, u.kategori, u.no_telepon, u.alamat, u.foto_usaha
          FROM produk p
          JOIN umkm_data u ON p.umkm_id = u.id
          WHERE u.status_verifikasi = 'terverifikasi'";

if ($kategori_filter) {
    $query .= " AND u.kategori = :kategori";
}

if ($search) {
    $query .= " AND (p.nama_produk LIKE :search OR u.nama_usaha LIKE :search)";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);

if ($kategori_filter) {
    $stmt->bindParam(':kategori', $kategori_filter);
}

if ($search) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$produk_list = $stmt->fetchAll();

// Ambil kategori untuk filter
$query_kategori = "SELECT DISTINCT kategori FROM umkm_data WHERE status_verifikasi = 'terverifikasi' ORDER BY kategori";
$stmt_kategori = $conn->prepare($query_kategori);
$stmt_kategori->execute();
$kategori_list = $stmt_kategori->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk UMKM - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f8f9fa; }
        
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
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
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0 3rem;
            margin-bottom: 3rem;
        }
        
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .search-bar {
            background: white;
            border-radius: 50px;
            padding: 0.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .search-bar input {
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
        }
        
        .search-bar input:focus {
            box-shadow: none;
        }
        
        .search-bar .btn {
            border-radius: 50px;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .filter-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: sticky;
            top: 2rem;
        }
        
        .filter-section h5 {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .filter-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
            color: #2d3748;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        
        .product-placeholder {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }
        
        .product-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .product-price {
            color: #667eea;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .umkm-info {
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }
        
        .umkm-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }
        
        .umkm-location {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .btn-contact {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
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
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#peta">Peta UMKM</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="katalog.php">Katalog Produk</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= isAdmin() ? 'admin/index.php' : 'umkm/index.php' ?>">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
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
                            <a href="auth/login.php" class="btn btn-light rounded-pill px-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="text-center mb-4">
                <h1>Katalog Produk UMKM</h1>
                <p class="lead opacity-90">Temukan produk berkualitas dari UMKM Kota Semarang</p>
            </div>
            
            <!-- Search Bar -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form action="" method="GET" class="search-bar">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari produk atau nama UMKM..."
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search me-2"></i>Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Content -->
    <div class="container mb-5">
        <div class="row">
            
            <!-- Filter Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-section">
                    <h5><i class="bi bi-funnel me-2"></i>Filter Kategori</h5>
                    
                    <a href="katalog.php<?= $search ? '?search=' . urlencode($search) : '' ?>" 
                       class="filter-btn <?= !$kategori_filter ? 'active' : '' ?>">
                        <i class="bi bi-grid me-2"></i>Semua Kategori
                    </a>
                    
                    <?php foreach ($kategori_list as $kat): ?>
                        <a href="katalog.php?kategori=<?= urlencode($kat['kategori']) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="filter-btn <?= $kategori_filter === $kat['kategori'] ? 'active' : '' ?>">
                            <i class="bi bi-tag me-2"></i><?= htmlspecialchars($kat['kategori']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0">
                            <?php if ($kategori_filter): ?>
                                Kategori: <?= htmlspecialchars($kategori_filter) ?>
                            <?php elseif ($search): ?>
                                Hasil Pencarian: "<?= htmlspecialchars($search) ?>"
                            <?php else: ?>
                                Semua Produk
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-0"><?= count($produk_list) ?> produk ditemukan</p>
                    </div>
                </div>
                
                <?php if (count($produk_list) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($produk_list as $produk): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="product-card">
                                    <?php if ($produk['foto_produk']): ?>
                                        <img src="uploads/produk/<?= htmlspecialchars($produk['foto_produk']) ?>" 
                                             class="product-image" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-body">
                                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($produk['kategori']) ?></span>
                                        <h5 class="product-title"><?= htmlspecialchars($produk['nama_produk']) ?></h5>
                                        
                                        <?php if ($produk['deskripsi']): ?>
                                            <p class="text-muted small mb-2">
                                                <?= htmlspecialchars(substr($produk['deskripsi'], 0, 80)) ?>
                                                <?= strlen($produk['deskripsi']) > 80 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="product-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                                        
                                        <div class="umkm-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if ($produk['foto_usaha']): ?>
                                                    <img src="uploads/umkm_profile/<?= htmlspecialchars($produk['foto_usaha']) ?>" 
                                                         style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; margin-right: 0.75rem;"
                                                         alt="<?= htmlspecialchars($produk['nama_usaha']) ?>">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; margin-right: 0.75rem;">
                                                        <?= strtoupper(substr($produk['nama_usaha'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="umkm-name"><?= htmlspecialchars($produk['nama_usaha']) ?></div>
                                                    <div class="umkm-location">
                                                        <i class="bi bi-geo-alt-fill me-1"></i>
                                                        <?= htmlspecialchars(substr($produk['alamat'], 0, 30)) ?>...
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($produk['no_telepon']): ?>
                                                <a href="https://wa.me/62<?= ltrim($produk['no_telepon'], '0') ?>?text=Halo%20<?= urlencode($produk['nama_usaha']) ?>,%20saya%20tertarik%20dengan%20produk%20<?= urlencode($produk['nama_produk']) ?>" 
                                                   target="_blank" class="btn btn-contact">
                                                    <i class="bi bi-whatsapp me-2"></i>Hubungi Penjual
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>Tidak Ada Produk Ditemukan</h4>
                        <p class="text-muted">Coba ubah filter atau kata kunci pencarian Anda</p>
                        <a href="katalog.php" class="btn btn-primary rounded-pill px-4 mt-3">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: #2d3748; color: white; padding: 3rem 0;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h5>
                    <p class="text-white-50">Platform UMKM Kota Semarang</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Link</h5>
                    <p><a href="index.php" class="text-white-50 text-decoration-none">Beranda</a></p>
                    <p><a href="katalog.php" class="text-white-50 text-decoration-none">Katalog Produk</a></p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Kontak</h5>
                    <p class="text-white-50">
                        <i class="bi bi-envelope me-2"></i>info@sigap-umkm.semarang.go.id
                    </p>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; 2024 SIGAP-UMKM Kota Semarang</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>