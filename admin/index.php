<?php
// admin/index.php
require_once '../config/koneksi.php';
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

// Statistik Dashboard
$stats = [];

// Total UMKM
$query = "SELECT COUNT(*) as total FROM umkm_data";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_umkm'] = $stmt->fetch()['total'];

// UMKM Pending
$query = "SELECT COUNT(*) as total FROM umkm_data WHERE status_verifikasi = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['pending'] = $stmt->fetch()['total'];

// UMKM Terverifikasi
$query = "SELECT COUNT(*) as total FROM umkm_data WHERE status_verifikasi = 'terverifikasi'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['terverifikasi'] = $stmt->fetch()['total'];

// Total Produk
$query = "SELECT COUNT(*) as total FROM produk";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_produk'] = $stmt->fetch()['total'];

// Total Users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'umkm'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['total'];

// Total Omzet (dalam juta)
$query = "SELECT SUM(omzet_bulanan) as total FROM umkm_data WHERE status_verifikasi = 'terverifikasi'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_omzet'] = $stmt->fetch()['total'] ?? 0;

// Total Karyawan
$query = "SELECT SUM(jumlah_karyawan) as total FROM umkm_data WHERE status_verifikasi = 'terverifikasi'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_karyawan'] = $stmt->fetch()['total'] ?? 0;

// Data UMKM Terbaru (5 terakhir)
$query = "SELECT u.*, us.username 
          FROM umkm_data u 
          JOIN users us ON u.user_id = us.id 
          ORDER BY u.created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_umkm = $stmt->fetchAll();

// Data per Kategori
$query = "SELECT kategori, COUNT(*) as jumlah 
          FROM umkm_data 
          WHERE status_verifikasi = 'terverifikasi' 
          GROUP BY kategori 
          ORDER BY jumlah DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$kategori_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        body {
            background: #f8f9fa;
        }
        
        .content-wrapper {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }
        
        .page-header p {
            opacity: 0.9;
            margin: 0;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.75rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
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
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .chart-card h5 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .category-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .category-name {
            font-weight: 500;
            color: #2d3748;
        }
        
        .category-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .recent-umkm-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .recent-umkm-card h5 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        
        .umkm-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .umkm-item:hover {
            background: #e9ecef;
        }
        
        .umkm-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .umkm-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .umkm-info h6 {
            margin: 0;
            font-weight: 600;
            color: #2d3748;
            font-size: 0.95rem;
        }
        
        .umkm-info small {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-weight: 500;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <?php include './includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</h1>
                    <p>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>! Kelola sistem SIGAP-UMKM</p>
                </div>
                <div class="text-end">
                    <small class="d-block opacity-75"><?= date('l, d F Y') ?></small>
                    <strong><?= date('H:i') ?> WIB</strong>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['total_umkm'] ?></h2>
                    <p class="stats-label">Total UMKM Terdaftar</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['pending'] ?></h2>
                    <p class="stats-label">Menunggu Verifikasi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['terverifikasi'] ?></h2>
                    <p class="stats-label">UMKM Terverifikasi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['total_produk'] ?></h2>
                    <p class="stats-label">Total Produk</p>
                </div>
            </div>
        </div>
        
        <!-- Financial & Employment Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h2 class="stats-number">Rp <?= number_format($stats['total_omzet'] / 1000000, 1) ?>M</h2>
                    <p class="stats-label">Total Omzet Bulanan</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-purple bg-opacity-10" style="color: #764ba2;">
                        <i class="bi bi-people"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['total_karyawan'] ?></h2>
                    <p class="stats-label">Total Tenaga Kerja</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h2 class="stats-number"><?= $stats['total_users'] ?></h2>
                    <p class="stats-label">Total User UMKM</p>
                </div>
            </div>
        </div>
        
        <!-- Charts & Recent Data -->
        <div class="row g-3">
            <!-- Kategori UMKM -->
            <div class="col-lg-6 mb-3">
                <div class="chart-card">
                    <h5><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Top 5 Kategori UMKM</h5>
                    <?php if (count($kategori_data) > 0): ?>
                        <?php foreach ($kategori_data as $kategori): ?>
                            <div class="category-item">
                                <span class="category-name">
                                    <i class="bi bi-tag-fill me-2 text-muted"></i>
                                    <?= htmlspecialchars($kategori['kategori']) ?>
                                </span>
                                <span class="category-badge"><?= $kategori['jumlah'] ?> UMKM</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            <p>Belum ada data kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent UMKM -->
            <div class="col-lg-6 mb-3">
                <div class="recent-umkm-card">
                    <h5><i class="bi bi-clock-history me-2 text-primary"></i>UMKM Terbaru</h5>
                    <?php if (count($recent_umkm) > 0): ?>
                        <?php foreach ($recent_umkm as $umkm): ?>
                            <div class="umkm-item">
                                <?php if ($umkm['foto_usaha']): ?>
                                    <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                         class="umkm-avatar" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                                <?php else: ?>
                                    <div class="umkm-placeholder">
                                        <?= strtoupper(substr($umkm['nama_usaha'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1 umkm-info">
                                    <h6><?= htmlspecialchars($umkm['nama_usaha']) ?></h6>
                                    <small>
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($umkm['username']) ?>
                                        <i class="bi bi-calendar3 ms-2 me-1"></i>
                                        <?= date('d M Y', strtotime($umkm['created_at'])) ?>
                                    </small>
                                </div>
                                <?php
                                $badge_class = [
                                    'pending' => 'bg-warning',
                                    'terverifikasi' => 'bg-success',
                                    'ditolak' => 'bg-danger'
                                ];
                                ?>
                                <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>">
                                    <?= ucfirst($umkm['status_verifikasi']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="verifikasi.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            <p>Belum ada UMKM terdaftar</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>