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
        // Hapus foto usaha
        $query = "SELECT foto_usaha FROM umkm_data WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $umkm_id);
        $stmt->execute();
        $umkm_data = $stmt->fetch();
        
        if ($umkm_data && $umkm_data['foto_usaha'] && file_exists("../uploads/umkm_profile/" . $umkm_data['foto_usaha'])) {
            unlink("../uploads/umkm_profile/" . $umkm_data['foto_usaha']);
        }
        
        // Hapus semua foto produk
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
        
        // Hapus UMKM (produk akan terhapus otomatis karena CASCADE)
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

// Ambil semua UMKM dengan detail lengkap
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f8f9fa; }
        
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
        
        .umkm-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .umkm-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .umkm-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .umkm-photo {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
        }
        
        .umkm-photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-item i {
            color: #667eea;
            font-size: 1.2rem;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .detail-section h6 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
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
            <h1><i class="bi bi-shop-window me-2"></i>Management UMKM</h1>
            <p class="text-muted mb-0">Kelola detail data UMKM secara lengkap</p>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
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
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0 fw-bold">Rp <?= number_format($stats['total_omzet'] / 1000000, 1) ?>M</h4>
                            <small class="text-muted">Total Omzet</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['total_karyawan'] ?></h3>
                            <small class="text-muted">Total Karyawan</small>
                        </div>
                    </div>
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
                            <h4 class="mb-1 fw-bold"><?= htmlspecialchars($umkm['nama_usaha']) ?></h4>
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
                    <i class="bi bi-geo-alt-fill"></i>
                    <div>
                        <small class="text-muted d-block">Alamat</small>
                        <strong><?= htmlspecialchars(substr($umkm['alamat'], 0, 40)) ?>...</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-telephone-fill"></i>
                    <div>
                        <small class="text-muted d-block">Telepon</small>
                        <strong><?= $umkm['no_telepon'] ? htmlspecialchars($umkm['no_telepon']) : '-' ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-cash-stack"></i>
                    <div>
                        <small class="text-muted d-block">Omzet/Bulan</small>
                        <strong>Rp <?= number_format($umkm['omzet_bulanan'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <small class="text-muted d-block">Karyawan</small>
                        <strong><?= $umkm['jumlah_karyawan'] ?> orang</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-box-seam"></i>
                    <div>
                        <small class="text-muted d-block">Produk</small>
                        <strong><?= $umkm['jumlah_produk'] ?> item</strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="bi bi-calendar3"></i>
                    <div>
                        <small class="text-muted d-block">Bergabung</small>
                        <strong><?= date('d M Y', strtotime($umkm['created_at'])) ?></strong>
                    </div>
                </div>
            </div>
            
            <?php if ($umkm['deskripsi']): ?>
            <div class="detail-section">
                <h6><i class="bi bi-file-text me-2"></i>Deskripsi</h6>
                <p class="mb-0 text-muted"><?= htmlspecialchars($umkm['deskripsi']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Detail Modal -->
        <div class="modal fade" id="detailModal<?= $umkm['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title">
                            <i class="bi bi-info-circle me-2"></i>Detail UMKM
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <?php if ($umkm['foto_usaha']): ?>
                                    <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                         class="img-fluid rounded" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
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
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header bg-warning text-white" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title">
                            <i class="bi bi-gear me-2"></i>Ubah Status Verifikasi
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body p-4">
                            <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                            <p>UMKM: <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong></p>
                            <p>Status saat ini: <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>"><?= ucfirst($umkm['status_verifikasi']) ?></span></p>
                            
                            <label class="form-label fw-bold">Ubah Status Menjadi:</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?= $umkm['status_verifikasi'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="terverifikasi" <?= $umkm['status_verifikasi'] === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                <option value="ditolak" <?= $umkm['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_status" class="btn btn-warning">
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
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header bg-danger text-white" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title">
                            <i class="bi bi-trash me-2"></i>Hapus UMKM
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body p-4">
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
