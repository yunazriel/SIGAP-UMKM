<?php
// umkm/index.php
require_once '../config/koneksi.php';
requireLogin();

// Pastikan bukan admin
if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ambil data UMKM user
$query = "SELECT * FROM umkm_data WHERE user_id = :user_id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$umkm = $stmt->fetch();

// Ambil produk UMKM
$query = "SELECT * FROM produk WHERE umkm_id = :umkm_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':umkm_id', $umkm['id']);
$stmt->execute();
$produk_list = $stmt->fetchAll();

// Statistik
$stats = [
    'total_produk' => count($produk_list),
    'omzet' => $umkm['omzet_bulanan'] ?? 0,
    'karyawan' => $umkm['jumlah_karyawan'] ?? 0,
    'status' => $umkm['status_verifikasi']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UMKM - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        body {
            background: #f8f9fa;
        }
        
        .nav-link i {
            width: 20px;
        }
        
        .content-wrapper {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 0.5rem 0;
        }
        
        .page-header p {
            color: #718096;
            margin: 0;
        }
        
        .status-banner {
            padding: 1.25rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .status-banner.pending {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
        }
        
        .status-banner.terverifikasi {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .status-banner.ditolak {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .status-banner i {
            font-size: 2rem;
        }
        
        .status-banner strong {
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.75rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .stats-label {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .profile-card h5 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        
        .profile-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .profile-item:last-child {
            border-bottom: none;
        }
        
        .profile-label {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .profile-value {
            color: #2d3748;
            font-weight: 500;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .product-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .product-body {
            padding: 1.5rem;
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
            font-size: 1.25rem;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <?php include './includes/umkm_sidebar.php'; ?>    
    
    <!-- Main Content -->
    <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Dashboard UMKM</h1>
                    <p>Selamat datang, <?= htmlspecialchars($umkm['nama_usaha']) ?>!</p>
                </div>
                <div>
                    <a href="profil.php" class="btn btn-gradient rounded-pill px-4">
                        <i class="bi bi-pencil-square me-2"></i>Edit Profil
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Status Banner -->
        <div class="status-banner <?= $stats['status'] ?>">
            <i class="bi bi-<?= $stats['status'] === 'pending' ? 'clock-history' : ($stats['status'] === 'terverifikasi' ? 'check-circle-fill' : 'x-circle-fill') ?>"></i>
            <div>
                <strong>Status: <?= ucfirst($stats['status']) ?></strong>
                <p class="mb-0 opacity-90">
                    <?php if ($stats['status'] === 'pending'): ?>
                        UMKM Anda sedang menunggu verifikasi dari admin. Pastikan data profil sudah lengkap.
                    <?php elseif ($stats['status'] === 'terverifikasi'): ?>
                        Selamat! UMKM Anda sudah terverifikasi dan muncul di peta publik.
                    <?php else: ?>
                        Maaf, UMKM Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['total_produk'] ?></h2>
                    <p class="stats-label">Total Produk</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h2 class="stats-number">Rp <?= number_format($stats['omzet'], 0, ',', '.') ?></h2>
                    <p class="stats-label">Omzet Bulanan</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['karyawan'] ?></h2>
                    <p class="stats-label">Jumlah Karyawan</p>
                </div>
            </div>
        </div>
        
        <!-- Profile & Products -->
        <div class="row g-3">
            <!-- Profile Summary -->
            <div class="col-lg-5 mb-3">
                <div class="profile-card">
                    <h5><i class="bi bi-info-circle me-2 text-primary"></i>Informasi UMKM</h5>
                    
                    <?php if ($umkm['foto_usaha']): ?>
                        <div class="mb-3">
                            <img src="../uploads/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                 class="img-fluid rounded-3" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-item">
                        <div class="profile-label">Nama Usaha</div>
                        <div class="profile-value"><?= htmlspecialchars($umkm['nama_usaha']) ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-label">Kategori</div>
                        <div class="profile-value">
                            <span class="badge bg-primary"><?= htmlspecialchars($umkm['kategori']) ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-label">Alamat</div>
                        <div class="profile-value"><?= htmlspecialchars($umkm['alamat']) ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-label">Koordinat Lokasi</div>
                        <div class="profile-value">
                            <?php if ($umkm['lat'] && $umkm['lng']): ?>
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= $umkm['lat'] ?>, <?= $umkm['lng'] ?>
                            <?php else: ?>
                                <span class="text-muted">Belum diset</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-label">Deskripsi</div>
                        <div class="profile-value"><?= htmlspecialchars($umkm['deskripsi']) ?></div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="profil.php" class="btn btn-gradient w-100 rounded-pill">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profil Lengkap
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Products -->
            <div class="col-lg-7 mb-3">
                <div class="profile-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Produk Terbaru</h5>
                        <a href="produk.php" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Produk
                        </a>
                    </div>
                    
                    <?php if (count($produk_list) > 0): ?>
                        <div class="row g-3">
                            <?php 
                            // Tampilkan maksimal 4 produk terakhir
                            $recent_products = array_slice($produk_list, 0, 4);
                            foreach ($recent_products as $produk): 
                            ?>
                                <div class="col-md-6">
                                    <div class="product-card">
                                        <?php if ($produk['foto_produk']): ?>
                                            <img src="../uploads/produk/<?= htmlspecialchars($produk['foto_produk']) ?>" 
                                                 class="product-image" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                                        <?php else: ?>
                                            <div class="product-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-body">
                                            <h6 class="product-title"><?= htmlspecialchars($produk['nama_produk']) ?></h6>
                                            <div class="product-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($produk_list) > 4): ?>
                            <div class="text-center mt-3">
                                <a href="produk.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                    Lihat Semua Produk <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <h6>Belum Ada Produk</h6>
                            <p class="mb-3">Mulai tambahkan produk UMKM Anda</p>
                            <a href="produk.php" class="btn btn-gradient rounded-pill px-4">
                                <i class="bi bi-plus-lg me-2"></i>Tambah Produk Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>