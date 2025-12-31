<?php
// admin/umkm-management.php
require_once '../config/koneksi.php';
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle Update Status Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    $status = $_POST['status'];
    
    try {
        $query = "UPDATE umkm_data SET status_verifikasi = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $umkm_id);
        
        if ($stmt->execute()) {
            $success = 'Status verifikasi berhasil diubah!';
        }
    } catch (PDOException $e) {
        $error = 'Gagal mengubah status: ' . $e->getMessage();
    }
}

// Handle Delete UMKM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_umkm'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    
    try {
        $query = "SELECT foto_usaha FROM umkm_data WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $umkm_id);
        $stmt->execute();
        $umkm_data = $stmt->fetch();
        
        if ($umkm_data && $umkm_data['foto_usaha'] && file_exists("../uploads/umkm_profile/" . $umkm_data['foto_usaha'])) {
            unlink("../uploads/umkm_profile/" . $umkm_data['foto_usaha']);
        }
        
        $query = "SELECT foto_produk FROM produk WHERE umkm_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $umkm_id);
        $stmt->execute();
        $produk_list = $stmt->fetchAll();
        
        foreach ($produk_list as $p) {
            if ($p['foto_produk'] && file_exists("../uploads/produk/" . $p['foto_produk'])) {
                unlink("../uploads/produk/" . $p['foto_produk']);
            }
        }
        
        $query = "DELETE FROM umkm_data WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $umkm_id);
        
        if ($stmt->execute()) {
            $success = 'Data UMKM berhasil dihapus!';
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus UMKM: ' . $e->getMessage();
    }
}

// Ambil semua UMKM
$query = "SELECT u.*, us.username, us.no_telepon as user_phone,
          (SELECT COUNT(*) FROM produk WHERE umkm_id = u.id) as jumlah_produk,
          (SELECT SUM(harga) FROM produk WHERE umkm_id = u.id) as total_nilai_produk
          FROM umkm_data u 
          JOIN users us ON u.user_id = us.id 
          ORDER BY u.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$umkm_list = $stmt->fetchAll();

// Statistik
$stats = [
    'total' => count($umkm_list),
    'pending' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'pending')),
    'terverifikasi' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'terverifikasi')),
    'ditolak' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'ditolak')),
    'total_omzet' => array_sum(array_column($umkm_list, 'omzet_bulanan')),
    'total_karyawan' => array_sum(array_column($umkm_list, 'jumlah_karyawan'))
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management UMKM - Admin SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { 
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        /* Sidebar Clean */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 270px;
            background: #2c3e50;
            padding: 0;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 2rem 1.5rem;
            background: #34495e;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h4 {
            color: white;
            font-weight: 700;
            margin: 0;
            font-size: 1.3rem;
        }
        
        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
            font-size: 0.85rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 1rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .nav-link.active {
            color: white;
            background: rgba(52, 152, 219, 0.15);
            border-left-color: #3498db;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            margin-left: 270px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        /* Page Header Clean */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-weight: 600;
            font-size: 1.75rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .page-header p {
            color: #7f8c8d;
            margin: 0;
            font-size: 0.95rem;
        }
        
        /* Stats Card Clean */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid #ecf0f1;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .stats-card h3 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.25rem;
            color: #2c3e50;
        }
        
        .stats-card small {
            font-weight: 500;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* UMKM Card Clean */
        .umkm-card {
            background: white;
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s;
            border: 1px solid #ecf0f1;
        }
        
        .umkm-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .umkm-header {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .umkm-photo {
            width: 110px;
            height: 110px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #ecf0f1;
        }
        
        .umkm-photo-placeholder {
            width: 110px;
            height: 110px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            border: 2px solid #ecf0f1;
        }
        
        .umkm-card h4 {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .umkm-card .text-muted {
            color: #7f8c8d !important;
            font-size: 0.9rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ecf0f1;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .info-item i {
            font-size: 1.25rem;
            margin-top: 0.15rem;
        }
        
        .info-item small {
            display: block;
            font-weight: 600;
            font-size: 0.75rem;
            color: #95a5a6;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-item strong {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        /* Badge Clean */
        .badge {
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }
        
        .badge.bg-primary {
            background: #3498db !important;
        }
        
        .badge.bg-success {
            background: #27ae60 !important;
        }
        
        .badge.bg-warning {
            background: #f39c12 !important;
        }
        
        .badge.bg-danger {
            background: #e74c3c !important;
        }
        
        .badge.bg-info {
            background: #00bcd4 !important;
        }
        
        /* Button Clean */
        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-sm {
            padding: 0.45rem 1rem;
            font-size: 0.8rem;
        }
        
        .btn-outline-primary {
            border: 1.5px solid #3498db !important;
            color: #3498db;
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: #3498db;
            color: white;
        }
        
        .btn-outline-warning {
            border: 1.5px solid #f39c12 !important;
            color: #f39c12;
            background: transparent;
        }
        
        .btn-outline-warning:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-outline-danger {
            border: 1.5px solid #e74c3c !important;
            color: #e74c3c;
            background: transparent;
        }
        
        .btn-outline-danger:hover {
            background: #e74c3c;
            color: white;
        }
        
        /* Detail Section */
        .detail-section {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            border-left: 3px solid #3498db;
        }
        
        .detail-section h6 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .detail-section p {
            color: #7f8c8d;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* Modal Clean */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            border-bottom: 1px solid #ecf0f1;
            padding: 1.5rem 2rem;
        }
        
        .modal-header h5 {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            border-top: 1px solid #ecf0f1;
            padding: 1.25rem 2rem;
            background: #fafafa;
        }
        
        /* Alert Clean */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Form Control */
        .form-select, .form-control {
            border-radius: 8px;
            border: 1.5px solid #dce1e6;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
        }
        
        /* Icon Colors */
        .text-primary-custom { color: #3498db; }
        .text-success-custom { color: #27ae60; }
        .text-warning-custom { color: #f39c12; }
        .text-info-custom { color: #00bcd4; }
        
        .bg-primary-light { background: #e3f2fd; }
        .bg-success-light { background: #e8f5e9; }
        .bg-warning-light { background: #fff3e0; }
        .bg-info-light { background: #e0f7fa; }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #ecf0f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #95a5a6;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h4>
            <small>Admin Panel</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="verifikasi.php">
                    <i class="bi bi-check-circle"></i>Verifikasi UMKM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i>Management User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="umkm-management.php">
                    <i class="bi bi-shop-window"></i>Management UMKM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear"></i>Pengaturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-globe"></i>Lihat Website
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i>Logout
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="bi bi-shop-window me-2"></i>Management UMKM</h1>
            <p>Kelola detail data UMKM secara lengkap</p>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary-light text-primary-custom">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h3><?= $stats['total'] ?></h3>
                    <small>Total UMKM</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success-light text-success-custom">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3><?= $stats['terverifikasi'] ?></h3>
                    <small>Terverifikasi</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info-light text-info-custom">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3>Rp <?= number_format($stats['total_omzet'] / 1000000, 1) ?>M</h3>
                    <small>Total Omzet</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning-light text-warning-custom">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3><?= $stats['total_karyawan'] ?></h3>
                    <small>Total Karyawan</small>
                </div>
            </div>
        </div>
        
        <!-- UMKM List -->
        <?php foreach ($umkm_list as $umkm): ?>
        <div class="umkm-card">
            <div class="umkm-header">
                <?php if ($umkm['foto_usaha']): ?>
                    <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                         class="umkm-photo" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                <?php else: ?>
                    <div class="umkm-photo-placeholder">
                        <?= strtoupper(substr($umkm['nama_usaha'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4><?= htmlspecialchars($umkm['nama_usaha']) ?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person-circle me-1"></i>
                                Owner: <?= htmlspecialchars($umkm['username']) ?>
                            </p>
                            <span class="badge bg-primary me-2"><?= htmlspecialchars($umkm['kategori']) ?></span>
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
                        
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailModal<?= $umkm['id'] ?>">
                                <i class="bi bi-eye"></i> Detail
                            </button>
                            <button class="btn btn-sm btn-outline-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#statusModal<?= $umkm['id'] ?>">
                                <i class="bi bi-gear"></i> Status
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal<?= $umkm['id'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <i class="bi bi-geo-alt-fill text-primary-custom"></i>
                    <div>
                        <small>Alamat</small>
                        <strong><?= htmlspecialchars(substr($umkm['alamat'], 0, 40)) ?>...</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-telephone-fill text-success-custom"></i>
                    <div>
                        <small>Telepon</small>
                        <strong><?= $umkm['no_telepon'] ? htmlspecialchars($umkm['no_telepon']) : '-' ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-cash-stack text-info-custom"></i>
                    <div>
                        <small>Omzet/Bulan</small>
                        <strong>Rp <?= number_format($umkm['omzet_bulanan'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-people-fill text-warning-custom"></i>
                    <div>
                        <small>Karyawan</small>
                        <strong><?= $umkm['jumlah_karyawan'] ?> orang</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-box-seam text-primary-custom"></i>
                    <div>
                        <small>Produk</small>
                        <strong><?= $umkm['jumlah_produk'] ?> item</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-calendar3 text-success-custom"></i>
                    <div>
                        <small>Bergabung</small>
                        <strong><?= date('d M Y', strtotime($umkm['created_at'])) ?></strong>
                    </div>
                </div>
            </div>
            
            <?php if ($umkm['deskripsi']): ?>
            <div class="detail-section">
                <h6><i class="bi bi-file-text me-2"></i>Deskripsi</h6>
                <p><?= htmlspecialchars($umkm['deskripsi']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Detail Modal -->
        <div class="modal fade" id="detailModal<?= $umkm['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-info-circle me-2"></i>Detail UMKM
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <?php if ($umkm['foto_usaha']): ?>
                                    <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                         class="img-fluid rounded" style="border-radius: 12px !important;" 
                                         alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                                <?php else: ?>
                                    <div class="bg-secondary text-white p-5 rounded">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-3"><?= htmlspecialchars($umkm['nama_usaha']) ?></h4>
                                
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Kategori</strong></td>
                                        <td><?= htmlspecialchars($umkm['kategori']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pemilik</strong></td>
                                        <td><?= htmlspecialchars($umkm['username']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td><?= $umkm['email'] ? htmlspecialchars($umkm['email']) : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Telepon</strong></td>
                                        <td><?= $umkm['no_telepon'] ? htmlspecialchars($umkm['no_telepon']) : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Alamat</strong></td>
                                        <td><?= htmlspecialchars($umkm['alamat']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Koordinat</strong></td>
                                        <td>
                                            <?php if ($umkm['lat'] && $umkm['lng']): ?>
                                                <?= $umkm['lat'] ?>, <?= $umkm['lng'] ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Omzet Bulanan</strong></td>
                                        <td>Rp <?= number_format($umkm['omzet_bulanan'], 0, ',', '.') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumlah Karyawan</strong></td>
                                        <td><?= $umkm['jumlah_karyawan'] ?> orang</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumlah Produk</strong></td>
                                        <td><?= $umkm['jumlah_produk'] ?> item</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>
                                            <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>">
                                                <?= ucfirst($umkm['status_verifikasi']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php if ($umkm['deskripsi']): ?>
                                <div class="mt-3">
                                    <strong>Deskripsi:</strong>
                                    <p class="text-muted"><?= htmlspecialchars($umkm['deskripsi']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Modal -->
        <div class="modal fade" id="statusModal<?= $umkm['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-gear me-2"></i>Ubah Status Verifikasi
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                            <p>UMKM: <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong></p>
                            <p>Status saat ini: <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>"><?= ucfirst($umkm['status_verifikasi']) ?></span></p>
                            
                            <label class="form-label fw-bold mt-3">Ubah Status Menjadi:</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?= $umkm['status_verifikasi'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="terverifikasi" <?= $umkm['status_verifikasi'] === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                <option value="ditolak" <?= $umkm['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_status" class="btn btn-warning text-white">
                                <i class="bi bi-check me-2"></i>Ubah Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal<?= $umkm['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-trash me-2"></i>Hapus UMKM
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan.
                            </div>
                            <p>Anda akan menghapus UMKM: <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong></p>
                            <p class="text-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Semua data produk, foto, dan informasi terkait akan terhapus!
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="delete_umkm" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i>Hapus UMKM
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php endforeach; ?>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>