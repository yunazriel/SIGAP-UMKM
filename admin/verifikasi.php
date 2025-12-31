<?php
// admin/verifikasi.php
require_once '../config/koneksi.php';
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    $action = $_POST['action'];
    
    $status = ($action === 'approve') ? 'terverifikasi' : 'ditolak';
    
    try {
        $query = "UPDATE umkm_data SET status_verifikasi = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $umkm_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Status UMKM berhasil diperbarui menjadi: " . ucfirst($status);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui status: " . $e->getMessage();
    }
    
    header("Location: verifikasi.php");
    exit();
}

// Ambil semua data UMKM dengan JOIN ke tabel users
$query = "SELECT u.*, us.username, 
          (SELECT COUNT(*) FROM produk WHERE umkm_id = u.id) as jumlah_produk
          FROM umkm_data u 
          JOIN users us ON u.user_id = us.id 
          ORDER BY 
            CASE u.status_verifikasi
                WHEN 'pending' THEN 1
                WHEN 'terverifikasi' THEN 2
                WHEN 'ditolak' THEN 3
            END,
            u.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$umkm_list = $stmt->fetchAll();

// Hitung statistik
$stats = [
    'total' => count($umkm_list),
    'pending' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'pending')),
    'terverifikasi' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'terverifikasi')),
    'ditolak' => count(array_filter($umkm_list, fn($u) => $u['status_verifikasi'] === 'ditolak'))
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi UMKM - Admin SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        body {
            background: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
            padding: 2rem 0;
            z-index: 1000;
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
        }
        
        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 0.875rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #667eea;
        }
        
        .content-wrapper {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2d3748;
            border: none;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .umkm-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
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
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="verifikasi.php">
                    <i class="bi bi-check-circle me-2"></i>Verifikasi UMKM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i>Management User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="umkm-management.php">
                    <i class="bi bi-shop-window me-2"></i>Management UMKM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
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
    
    <!-- Main Content -->
    <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Verifikasi UMKM</h1>
                    <p class="text-muted mb-0">Kelola dan verifikasi pendaftaran UMKM</p>
                </div>
                <div>
                    <span class="text-muted">
                        <i class="bi bi-person-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
                            <small class="text-muted">Total UMKM</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['pending'] ?></h3>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['terverifikasi'] ?></h3>
                            <small class="text-muted">Terverifikasi</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['ditolak'] ?></h3>
                            <small class="text-muted">Ditolak</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- UMKM Table -->
        <div class="table-card">
            <h5 class="fw-bold mb-4">Daftar UMKM</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nama Usaha</th>
                            <th>Kategori</th>
                            <th>Pemilik</th>
                            <th>Alamat</th>
                            <th>Omzet/Bulan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($umkm_list as $umkm): ?>
                        <tr>
                            <td>
                                <?php if ($umkm['foto_usaha']): ?>
                                    <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                         class="umkm-image" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                                <?php else: ?>
                                    <div class="umkm-image bg-secondary d-flex align-items-center justify-content-center text-white">
                                        <i class="bi bi-image fs-4"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-people me-1"></i><?= $umkm['jumlah_karyawan'] ?> karyawan
                                    <i class="bi bi-box-seam ms-2 me-1"></i><?= $umkm['jumlah_produk'] ?> produk
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <?= htmlspecialchars($umkm['kategori']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($umkm['username']) ?></td>
                            <td>
                                <small><?= htmlspecialchars(substr($umkm['alamat'], 0, 40)) ?>...</small>
                            </td>
                            <td>
                                <strong>Rp <?= number_format($umkm['omzet_bulanan'], 0, ',', '.') ?></strong>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                <?php if ($umkm['status_verifikasi'] === 'pending'): ?>
                                    <div class="btn-group" role="group">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-action btn-sm" 
                                                    onclick="return confirm('Verifikasi UMKM ini?')">
                                                <i class="bi bi-check-lg"></i> Verifikasi
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-action btn-sm ms-1" 
                                                    onclick="return confirm('Tolak UMKM ini?')">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-action btn-sm" disabled>
                                        Sudah Diproses
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>