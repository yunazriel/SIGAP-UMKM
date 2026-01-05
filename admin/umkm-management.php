<?php
// admin/umkm-management.php
require_once '../config/koneksi.php';
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle Update Data UMKM Lengkap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_umkm'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    
    // Collect data from form
    $data = [
        'nama_usaha' => $_POST['nama_usaha'],
        'kategori' => $_POST['kategori'],
        'deskripsi' => $_POST['deskripsi'],
        'alamat' => $_POST['alamat'],
        'no_telepon' => $_POST['no_telepon'],
        'email' => $_POST['email'],
        'lat' => $_POST['lat'],
        'lng' => $_POST['lng'],
        'omzet_bulanan' => str_replace(['.', ','], ['', '.'], $_POST['omzet_bulanan']),
        'jumlah_karyawan' => (int)$_POST['jumlah_karyawan'],
        'status_verifikasi' => $_POST['status_verifikasi']
    ];
    
    // Handle foto usaha upload
    if (!empty($_FILES['foto_usaha']['name'])) {
        $foto_name = uploadFotoUsaha($_FILES['foto_usaha'], $umkm_id);
        if ($foto_name) {
            $data['foto_usaha'] = $foto_name;
            
            // Delete old foto if exists
            $query = "SELECT foto_usaha FROM umkm_data WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $umkm_id);
            $stmt->execute();
            $old_foto = $stmt->fetchColumn();
            
            if ($old_foto && file_exists("../uploads/umkm_profile/" . $old_foto)) {
                unlink("../uploads/umkm_profile/" . $old_foto);
            }
        }
    }
    
    try {
        // Build dynamic update query
        $set_clause = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $set_clause[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        $params[':id'] = $umkm_id;
        $query = "UPDATE umkm_data SET " . implode(', ', $set_clause) . ", updated_at = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $success = 'Data UMKM berhasil diperbarui!';
        
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui data: ' . $e->getMessage();
    }
}

// Handle Delete UMKM (tetap sama)
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

// Function to upload foto usaha
function uploadFotoUsaha($file, $umkm_id) {
    $target_dir = "../uploads/umkm_profile/";
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "umkm_" . $umkm_id . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (max 2MB)
    if ($file["size"] > 2097152) {
        return false;
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    }
    
    return false;
}

// Filter & Pagination (tetap sama)
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Build query with filters
$where_conditions = [];
$params = [];

if ($filter_status !== 'all') {
    $where_conditions[] = "u.status_verifikasi = :status";
    $params[':status'] = $filter_status;
}

if ($filter_kategori !== 'all') {
    $where_conditions[] = "u.kategori = :kategori";
    $params[':kategori'] = $filter_kategori;
}

if (!empty($search)) {
    $where_conditions[] = "(u.nama_usaha LIKE :search OR us.username LIKE :search OR u.alamat LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records
$count_query = "SELECT COUNT(*) as total FROM umkm_data u 
                JOIN users us ON u.user_id = us.id 
                $where_sql";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get filtered & paginated UMKM
$query = "SELECT u.*, us.username, us.no_telepon as user_phone,
          (SELECT COUNT(*) FROM produk WHERE umkm_id = u.id) as jumlah_produk,
          (SELECT SUM(harga) FROM produk WHERE umkm_id = u.id) as total_nilai_produk
          FROM umkm_data u 
          JOIN users us ON u.user_id = us.id 
          $where_sql
          ORDER BY u.created_at DESC
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$umkm_list = $stmt->fetchAll();

// Get all UMKM for statistics
$all_query = "SELECT * FROM umkm_data";
$all_stmt = $conn->prepare($all_query);
$all_stmt->execute();
$all_umkm = $all_stmt->fetchAll();

// Get unique categories
$cat_query = "SELECT DISTINCT kategori FROM umkm_data ORDER BY kategori";
$cat_stmt = $conn->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Statistik
$stats = [
    'total' => count($all_umkm),
    'pending' => count(array_filter($all_umkm, fn($u) => $u['status_verifikasi'] === 'pending')),
    'terverifikasi' => count(array_filter($all_umkm, fn($u) => $u['status_verifikasi'] === 'terverifikasi')),
    'ditolak' => count(array_filter($all_umkm, fn($u) => $u['status_verifikasi'] === 'ditolak')),
    'total_omzet' => array_sum(array_column($all_umkm, 'omzet_bulanan')),
    'total_karyawan' => array_sum(array_column($all_umkm, 'jumlah_karyawan'))
];

// Format rupiah function
function formatRupiah($angka) {
    if ($angka >= 1000000000) {
        return 'Rp ' . number_format($angka / 1000000000, 1, ',', '.') . ' Miliar';
    } elseif ($angka >= 1000000) {
        return 'Rp ' . number_format($angka / 1000000, 1, ',', '.') . ' Juta';
    } elseif ($angka >= 1000) {
        return 'Rp ' . number_format($angka / 1000, 0, ',', '.') . ' Ribu';
    } else {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

// Format untuk input number (tanpa Rp)
function formatNumberInput($angka) {
    return number_format($angka, 0, '', '.');
}
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
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--icon-color);
        }
        
        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--icon-color), var(--icon-color-light));
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .filter-card .form-control, .filter-card .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 0.7rem 1rem;
            transition: all 0.3s;
        }
        
        .filter-card .form-control:focus, .filter-card .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .umkm-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            overflow: hidden;
            position: relative;
        }
        
        .umkm-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2);
        }
        
        .umkm-photo {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid white;
        }
        
        .umkm-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px dashed #e2e8f0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .info-item:hover {
            background: #667eea;
            color: white;
            transform: scale(1.02);
        }
        
        .info-item:hover i {
            color: white !important;
        }
        
        .info-item i {
            color: #667eea;
            font-size: 1.5rem;
            min-width: 30px;
        }
        
        .badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .btn {
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .pagination {
            gap: 0.5rem;
        }
        
        .page-link {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            color: #667eea;
            padding: 0.6rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .detail-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 1.5rem;
            border-left: 4px solid #667eea;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .modal-header {
            border: none;
            padding: 2rem;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .filter-badge {
            background: white;
            border: 2px solid #e2e8f0;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            margin: 0.3rem;
        }
        
        .filter-badge:hover, .filter-badge.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin: 10px 0;
            border: 2px solid #dee2e6;
        }

        .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: none;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Tab Menu */
        .tab-menu {
            background: #f8f9fa;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            padding: 1rem 1.5rem;
            color: #6c757d;
            font-weight: 500;
            position: relative;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 12px 12px 0 0;
        }

        .tab-btn:hover {
            background: rgba(102, 126, 234, 0.05);
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            background: white;
            border-bottom: 3px solid #667eea;
        }

        .tab-btn.active .tab-indicator {
            display: block;
        }

        .tab-indicator {
            display: none;
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: #667eea;
            border-radius: 3px;
        }

        .tab-btn i {
            font-size: 1.1rem;
        }

        /* Tab Content */
        .tab-pane {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Profile Image */
        .profile-preview {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border: 4px solid white;
        }

        .profile-placeholder {
            width: 180px;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
        }

        .profile-placeholder span {
            font-size: 12px;
            margin-top: 8px;
            opacity: 0.9;
        }

        .upload-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .upload-overlay:hover {
            background: #764ba2;
            transform: scale(1.1);
        }

        /* Icon Wrapper */
        .icon-wrapper {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            background: #f8f9fa;
            border: 2px solid #e2e8f0;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control.is-invalid {
            border-color: #dc3545;
        }

        /* Status Radio Cards - PERBAIKAN */
        .form-check-card {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
            position: relative;
        }

        .form-check-card:hover {
            border-color: #cbd5e0;
            transform: translateY(-2px);
        }

        .form-check-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        /* Ganti dari .form-check-card.active ke .selected */
        .status-option:checked + .form-check-card {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .status-option {
            display: none;
        }

        .status-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* Tab Content */
        .tab-content {
            max-height: 500px;
            overflow-y: auto;
        }

        /* Scrollbar Styling */
        .tab-content::-webkit-scrollbar {
            width: 8px;
        }

        .tab-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .tab-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .tab-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Button Styling */
        .btn {
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border: none;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(72, 187, 120, 0.3);
        }

        /* Validation Styles */
        .is-invalid {
            border-color: #dc3545 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .is-invalid ~ .invalid-feedback {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tab-btn span {
                display: none;
            }
            
            .tab-btn {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .tab-btn i {
                font-size: 1.2rem;
                margin-right: 0;
            }
            
            .form-check-card {
                padding: 0.75rem;
            }
            
            .status-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
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
                    <h1 class="mb-2"><i class="bi bi-shop-window me-2"></i>Management UMKM</h1>
                    <p class="text-muted mb-0">Kelola dan pantau semua UMKM terdaftar secara detail</p>
                </div>
                <div class="text-end">
                    <h3 class="fw-bold text-primary mb-0"><?= $total_records ?></h3>
                    <small class="text-muted">Total Data</small>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="--icon-color: #667eea; --icon-color-light: #764ba2;">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h2 class="mb-0 fw-bold"><?= $stats['total'] ?></h2>
                            <small class="text-muted">Total UMKM</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="--icon-color: #48bb78; --icon-color-light: #38a169;">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h2 class="mb-0 fw-bold"><?= $stats['terverifikasi'] ?></h2>
                            <small class="text-muted">Terverifikasi</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="--icon-color: #4299e1; --icon-color-light: #3182ce;">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h5 class="mb-0 fw-bold"><?= formatRupiah($stats['total_omzet']) ?></h5>
                            <small class="text-muted">Total Omzet</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="--icon-color: #ed8936; --icon-color-light: #dd6b20;">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h2 class="mb-0 fw-bold"><?= $stats['total_karyawan'] ?></h2>
                            <small class="text-muted">Total Karyawan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-search me-2"></i>Cari UMKM
                        </label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama UMKM, pemilik, atau alamat..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-funnel me-2"></i>Status Verifikasi
                        </label>
                        <select name="status" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="terverifikasi" <?= $filter_status === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                            <option value="ditolak" <?= $filter_status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tag me-2"></i>Kategori
                        </label>
                        <select name="kategori" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= $filter_kategori === 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" 
                                        <?= $filter_kategori === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Cari
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($filter_status !== 'all' || $filter_kategori !== 'all' || !empty($search)): ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted me-2">Filter aktif:</small>
                    <?php if ($filter_status !== 'all'): ?>
                        <span class="filter-badge">
                            Status: <?= ucfirst($filter_status) ?>
                            <a href="?status=all&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>" 
                               class="text-decoration-none ms-1">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($filter_kategori !== 'all'): ?>
                        <span class="filter-badge">
                            Kategori: <?= htmlspecialchars($filter_kategori) ?>
                            <a href="?status=<?= $filter_status ?>&kategori=all&search=<?= urlencode($search) ?>" 
                               class="text-decoration-none ms-1">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($search)): ?>
                        <span class="filter-badge">
                            Pencarian: "<?= htmlspecialchars($search) ?>"
                            <a href="?status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>" 
                               class="text-decoration-none ms-1">×</a>
                        </span>
                    <?php endif; ?>
                    <a href="umkm-management.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- UMKM List -->
        <?php if (count($umkm_list) > 0): ?>
            <?php foreach ($umkm_list as $umkm): ?>
            <div class="umkm-card">
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <?php if ($umkm['foto_usaha']): ?>
                            <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                 class="umkm-photo" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                        <?php else: ?>
                            <div class="umkm-photo-placeholder">
                                <?= strtoupper(substr($umkm['nama_usaha'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-2 fw-bold"><?= htmlspecialchars($umkm['nama_usaha']) ?></h4>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-person-circle me-1"></i>
                                    Owner: <strong><?= htmlspecialchars($umkm['username']) ?></strong>
                                </p>
                                <div>
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($umkm['kategori']) ?>
                                    </span>
                                    <?php
                                    $badge_class = [
                                        'pending' => 'bg-warning',
                                        'terverifikasi' => 'bg-success',
                                        'ditolak' => 'bg-danger'
                                    ];
                                    $badge_icon = [
                                        'pending' => 'clock-history',
                                        'terverifikasi' => 'check-circle-fill',
                                        'ditolak' => 'x-circle-fill'
                                    ];
                                    ?>
                                    <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>">
                                        <i class="bi bi-<?= $badge_icon[$umkm['status_verifikasi']] ?> me-1"></i>
                                        <?= ucfirst($umkm['status_verifikasi']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button class="btn btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailModal<?= $umkm['id'] ?>">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                                <button class="btn btn-outline-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?= $umkm['id'] ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-outline-danger" 
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
                            <strong><?= htmlspecialchars(substr($umkm['alamat'], 0, 35)) ?>...</strong>
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
                            <strong><?= formatRupiah($umkm['omzet_bulanan']) ?></strong>
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
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
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
                                             class="img-fluid rounded-3 shadow" alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white p-5 rounded-3">
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
                                            <td><?= formatRupiah($umkm['omzet_bulanan']) ?></td>
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
            
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $umkm['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <!-- Modal Header -->
                        <div class="modal-header bg-gradient-primary text-white py-3 px-4">
                            <div class="d-flex align-items-center">
                                <div class="icon-wrapper bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="bi bi-pencil-square fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-1">Edit Data UMKM</h5>
                                    <small class="opacity-75"><?= htmlspecialchars($umkm['nama_usaha']) ?></small>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body p-0">
                                <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                                
                                <!-- Tab Menu -->
                                <div class="tab-menu px-4 pt-4">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="tab-btn active" data-tab="info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <span>Informasi Dasar</span>
                                            <div class="tab-indicator"></div>
                                        </button>
                                        <button type="button" class="tab-btn" data-tab="detail">
                                            <i class="bi bi-building me-2"></i>
                                            <span>Detail Usaha</span>
                                            <div class="tab-indicator"></div>
                                        </button>
                                        <button type="button" class="tab-btn" data-tab="status">
                                            <i class="bi bi-geo-alt me-2"></i>
                                            <span>Lokasi & Status</span>
                                            <div class="tab-indicator"></div>
                                        </button>
                                    </div>
                                </div>

                                <div class="tab-content p-4">
                                    <!-- Tab 1: Informasi Dasar -->
                                    <div class="tab-pane fade show active" id="tab-info-<?= $umkm['id'] ?>">
                                        <div class="row">
                                            <!-- Foto Profil -->
                                            <div class="col-md-5 mb-4">
                                                <div class="card border-0 bg-light h-100">
                                                    <div class="card-body text-center p-4">
                                                        <div class="position-relative d-inline-block">
                                                            <?php if ($umkm['foto_usaha']): ?>
                                                                <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                                                    class="profile-preview rounded-4 shadow mb-3" 
                                                                    id="previewImage<?= $umkm['id'] ?>">
                                                            <?php else: ?>
                                                                <div class="profile-placeholder rounded-4 shadow mb-3">
                                                                    <i class="bi bi-camera fs-1"></i>
                                                                    <span>Belum ada foto</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="upload-overlay" onclick="document.getElementById('fileInput<?= $umkm['id'] ?>').click()">
                                                                <i class="bi bi-camera-fill fs-4"></i>
                                                            </div>
                                                        </div>
                                                        <input type="file" name="foto_usaha" id="fileInput<?= $umkm['id'] ?>" 
                                                            class="d-none" accept="image/*" 
                                                            onchange="previewImage(this, <?= $umkm['id'] ?>)">
                                                        
                                                        <div class="mt-3">
                                                            <small class="text-muted d-block">
                                                                <i class="bi bi-info-circle me-1"></i>
                                                                Ukuran maksimal 2MB. Format: JPG, PNG, GIF
                                                            </small>
                                                            <small class="text-muted d-block">
                                                                <i class="bi bi-star me-1"></i>
                                                                Rasio 1:1 disarankan
                                                            </small>
                                                        </div>
                                                        
                                                        <!-- Info Pemilik -->
                                                        <div class="mt-4 pt-3 border-top">
                                                            <h6 class="fw-semibold mb-2">
                                                                <i class="bi bi-person-badge me-2"></i>Pemilik UMKM
                                                            </h6>
                                                            <div class="d-flex align-items-center bg-white p-3 rounded-3 shadow-sm">
                                                                <div class="icon-wrapper bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                                                    <i class="bi bi-person"></i>
                                                                </div>
                                                                <div>
                                                                    <strong class="d-block"><?= htmlspecialchars($umkm['username']) ?></strong>
                                                                    <small class="text-muted">Username pemilik</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Informasi Dasar -->
                                            <div class="col-md-7">
                                                <div class="row">
                                                    <div class="col-12 mb-3">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-shop me-2"></i>Nama Usaha
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-end-0">
                                                                <i class="bi bi-building"></i>
                                                            </span>
                                                            <input type="text" name="nama_usaha" class="form-control ps-0" 
                                                                value="<?= htmlspecialchars($umkm['nama_usaha']) ?>" required
                                                                placeholder="Masukkan nama usaha">
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Nama usaha wajib diisi
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-tag me-2"></i>Kategori
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-end-0">
                                                                <i class="bi bi-grid-1x2"></i>
                                                            </span>
                                                            <select name="kategori" class="form-select ps-0" required>
                                                                <option value="">Pilih Kategori</option>
                                                                <?php 
                                                                $kategori_list = [
                                                                    'Kuliner',
                                                                    'Fashion',
                                                                    'Kerajinan Tangan',
                                                                    'Elektronik',
                                                                    'Furniture',
                                                                    'Kesehatan & Kecantikan',
                                                                    'Pendidikan',
                                                                    'Jasa',
                                                                    'Pertanian',
                                                                    'Perikanan',
                                                                    'Otomotif',
                                                                    'Teknologi',
                                                                    'Lainnya'
                                                                ];
                                                                ?>
                                                                <?php foreach ($kategori_list as $kategori): ?>
                                                                    <option value="<?= $kategori ?>" 
                                                                        <?= $umkm['kategori'] === $kategori ? 'selected' : '' ?>>
                                                                        <?= $kategori ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Kategori wajib dipilih
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-phone me-2"></i>Telepon
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-end-0">
                                                                <i class="bi bi-telephone"></i>
                                                            </span>
                                                            <input type="text" name="no_telepon" class="form-control ps-0" 
                                                                value="<?= htmlspecialchars($umkm['no_telepon'] ?? '') ?>"
                                                                placeholder="08xxxxxxxxxx">
                                                        </div>
                                                    </div>

                                                    <div class="col-12 mb-3">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-envelope me-2"></i>Email
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-end-0">
                                                                <i class="bi bi-at"></i>
                                                            </span>
                                                            <input type="email" name="email" class="form-control ps-0" 
                                                                value="<?= htmlspecialchars($umkm['email'] ?? '') ?>"
                                                                placeholder="email@contoh.com">
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-text-paragraph me-2"></i>Deskripsi Singkat
                                                        </label>
                                                        <textarea name="deskripsi" class="form-control" rows="4" 
                                                                placeholder="Jelaskan tentang usaha Anda..."
                                                                maxlength="500"><?= htmlspecialchars($umkm['deskripsi'] ?? '') ?></textarea>
                                                        <div class="form-text">
                                                            <span id="charCount<?= $umkm['id'] ?>"><?= strlen($umkm['deskripsi'] ?? '') ?></span>/500 karakter
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 2: Detail Usaha -->
                                    <div class="tab-pane fade" id="tab-detail-<?= $umkm['id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-cash-coin me-2"></i>Omzet Bulanan
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        Rp
                                                    </span>
                                                    <input type="text" name="omzet_bulanan" class="form-control ps-0 omzet-input" 
                                                        value="<?= formatNumberInput($umkm['omzet_bulanan']) ?>"
                                                        placeholder="0">
                                                    <span class="input-group-text bg-light">
                                                        /bulan
                                                    </span>
                                                </div>
                                                <small class="text-muted">Gunakan titik (.) sebagai pemisah ribuan</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-people me-2"></i>Jumlah Karyawan
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-person-plus"></i>
                                                    </span>
                                                    <input type="number" name="jumlah_karyawan" class="form-control ps-0" 
                                                        value="<?= $umkm['jumlah_karyawan'] ?>" min="0"
                                                        placeholder="0">
                                                    <span class="input-group-text bg-light">
                                                        orang
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="col-12 mb-4">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-geo-alt me-2"></i>Alamat Lengkap
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0 align-items-start pt-3">
                                                        <i class="bi bi-geo"></i>
                                                    </span>
                                                    <textarea name="alamat" class="form-control ps-0" rows="3" required
                                                            placeholder="Jl. Contoh No. 123, Kecamatan, Kota"><?= htmlspecialchars($umkm['alamat']) ?></textarea>
                                                </div>
                                                <div class="invalid-feedback">
                                                    Alamat wajib diisi
                                                </div>
                                            </div>

                                            <!-- Statistik Info -->
                                            <div class="col-12">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-body p-3">
                                                        <h6 class="fw-semibold mb-3">
                                                            <i class="bi bi-graph-up me-2"></i>Statistik UMKM
                                                        </h6>
                                                        <div class="row text-center">
                                                            <div class="col-md-4 mb-2">
                                                                <div class="p-3 bg-white rounded-3">
                                                                    <div class="text-primary fw-bold fs-4"><?= $umkm['jumlah_produk'] ?></div>
                                                                    <small class="text-muted">Jumlah Produk</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="p-3 bg-white rounded-3">
                                                                    <div class="text-success fw-bold fs-4">
                                                                        <?= formatRupiah($umkm['total_nilai_produk'] ?? 0) ?>
                                                                    </div>
                                                                    <small class="text-muted">Nilai Total Produk</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="p-3 bg-white rounded-3">
                                                                    <div class="text-warning fw-bold fs-4">
                                                                        <?= date('d M Y', strtotime($umkm['created_at'])) ?>
                                                                    </div>
                                                                    <small class="text-muted">Tanggal Bergabung</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 3: Lokasi & Status -->
                                    <div class="tab-pane fade" id="tab-status-<?= $umkm['id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-geo-fill me-2"></i>Latitude
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-dot"></i>
                                                    </span>
                                                    <input type="text" name="lat" class="form-control ps-0" 
                                                        value="<?= htmlspecialchars($umkm['lat'] ?? '') ?>"
                                                        placeholder="-6.2088">
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-geo-fill me-2"></i>Longitude
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-dot"></i>
                                                    </span>
                                                    <input type="text" name="lng" class="form-control ps-0" 
                                                        value="<?= htmlspecialchars($umkm['lng'] ?? '') ?>"
                                                        placeholder="106.8456">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-shield-check me-2"></i>Status Verifikasi
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <div class="status-option-container">
                                                            <input class="status-option" type="radio" name="status_verifikasi" 
                                                                value="pending" id="pending<?= $umkm['id'] ?>" 
                                                                <?= $umkm['status_verifikasi'] === 'pending' ? 'checked' : '' ?> required>
                                                            <label for="pending<?= $umkm['id'] ?>" class="form-check-card <?= $umkm['status_verifikasi'] === 'pending' ? 'selected' : '' ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="status-icon bg-warning">
                                                                        <i class="bi bi-clock"></i>
                                                                    </div>
                                                                    <div class="ms-3">
                                                                        <strong class="d-block">Pending</strong>
                                                                        <small class="text-muted">Menunggu verifikasi</small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="status-option-container">
                                                            <input class="status-option" type="radio" name="status_verifikasi" 
                                                                value="terverifikasi" id="terverifikasi<?= $umkm['id'] ?>" 
                                                                <?= $umkm['status_verifikasi'] === 'terverifikasi' ? 'checked' : '' ?>>
                                                            <label for="terverifikasi<?= $umkm['id'] ?>" class="form-check-card <?= $umkm['status_verifikasi'] === 'terverifikasi' ? 'selected' : '' ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="status-icon bg-success">
                                                                        <i class="bi bi-check-circle"></i>
                                                                    </div>
                                                                    <div class="ms-3">
                                                                        <strong class="d-block">Terverifikasi</strong>
                                                                        <small class="text-muted">Sudah diverifikasi</small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="status-option-container">
                                                            <input class="status-option" type="radio" name="status_verifikasi" 
                                                                value="ditolak" id="ditolak<?= $umkm['id'] ?>" 
                                                                <?= $umkm['status_verifikasi'] === 'ditolak' ? 'checked' : '' ?>>
                                                            <label for="ditolak<?= $umkm['id'] ?>" class="form-check-card <?= $umkm['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="status-icon bg-danger">
                                                                        <i class="bi bi-x-circle"></i>
                                                                    </div>
                                                                    <div class="ms-3">
                                                                        <strong class="d-block">Ditolak</strong>
                                                                        <small class="text-muted">Verifikasi ditolak</small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback d-block">
                                                    Status verifikasi wajib dipilih
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer (Sekarang di setiap tab) -->
                            <div class="modal-footer bg-light px-4 py-3">
                                <div class="d-flex justify-content-between w-100">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle me-1"></i>Batal
                                        </button>
                                    </div>
                                    <div>
                                        <button type="submit" name="update_umkm" class="btn btn-success">
                                            <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center align-items-center mt-4">
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>">
                                <i class="bi bi-chevron-left"></i> Sebelumnya
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&kategori=<?= $filter_kategori ?>&search=<?= urlencode($search) ?>">
                                Selanjutnya <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    Menampilkan <?= ($offset + 1) ?> - <?= min($offset + $items_per_page, $total_records) ?> dari <?= $total_records ?> data
                </small>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4 class="mt-3 fw-bold">Tidak Ada Data UMKM</h4>
                <p class="text-muted">
                    <?php if ($filter_status !== 'all' || $filter_kategori !== 'all' || !empty($search)): ?>
                        Tidak ditemukan UMKM dengan filter yang dipilih. Coba ubah filter atau reset pencarian.
                    <?php else: ?>
                        Belum ada UMKM yang terdaftar dalam sistem.
                    <?php endif; ?>
                </p>
                <?php if ($filter_status !== 'all' || $filter_kategori !== 'all' || !empty($search)): ?>
                <a href="umkm-management.php" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reset Filter
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
// Image preview function
function previewImage(input, umkmId) {
    const previewContainer = input.parentElement;
    let preview = document.getElementById(`previewImage${umkmId}`);
    
    if (!preview) {
        preview = document.createElement('img');
        preview.id = `previewImage${umkmId}`;
        preview.className = 'profile-preview rounded-4 shadow mb-3';
        previewContainer.insertBefore(preview, input);
    }
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Remove placeholder if exists
            const placeholder = previewContainer.querySelector('.profile-placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize tab functionality for ALL modals when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Setup for ALL edit modals
    const editModals = document.querySelectorAll('[id^="editModal"]');
    
    editModals.forEach(modalElement => {
        // Extract umkmId from modal ID
        const modalId = modalElement.id.replace('editModal', '');
        setupModalTabs(modalId);
        
        // Setup omzet input formatting for this modal
        setupOmzetInput(modalId);
        
        // Setup character counter for description
        setupCharCounter(modalId);
        
        // Setup status option selection
        setupStatusOptions(modalId);
        
        // Setup form validation for this modal
        setupFormValidation(modalId);
    });
});

function setupModalTabs(umkmId) {
    const modalElement = document.getElementById(`editModal${umkmId}`);
    
    if (!modalElement) return;
    
    // Setup tab switching
    const tabBtns = modalElement.querySelectorAll('.tab-btn');
    const tabPanes = modalElement.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => {
                p.classList.remove('show', 'active');
                p.classList.add('fade');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding pane
            const targetPane = document.getElementById(`tab-${tabId}-${umkmId}`);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                targetPane.classList.remove('fade');
            }
        });
    });
}

function setupOmzetInput(umkmId) {
    const modalElement = document.getElementById(`editModal${umkmId}`);
    if (!modalElement) return;
    
    const omzetInput = modalElement.querySelector('.omzet-input');
    if (omzetInput) {
        omzetInput.addEventListener('blur', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value);
                this.value = value.toLocaleString('id-ID');
            }
        });
        
        omzetInput.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    }
}

function setupCharCounter(umkmId) {
    const modalElement = document.getElementById(`editModal${umkmId}`);
    if (!modalElement) return;
    
    const textarea = modalElement.querySelector('textarea[name="deskripsi"]');
    const charCount = document.getElementById(`charCount${umkmId}`);
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            if (this.value.length > 500) {
                charCount.style.color = '#dc3545';
            } else {
                charCount.style.color = '#6c757d';
            }
        });
    }
}

// PERBAIKAN: Setup status options dengan CSS yang benar
function setupStatusOptions(umkmId) {
    const modalElement = document.getElementById(`editModal${umkmId}`);
    if (!modalElement) return;
    
    // Get all status radio inputs
    const statusInputs = modalElement.querySelectorAll('.status-option');
    const statusCards = modalElement.querySelectorAll('.form-check-card');
    
    // Initialize selected state
    statusInputs.forEach(input => {
        if (input.checked) {
            const label = modalElement.querySelector(`label[for="${input.id}"]`);
            if (label) {
                label.classList.add('selected');
            }
        }
    });
    
    // Add click event to status cards
    statusCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            statusCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Check the corresponding radio button
            const inputId = this.getAttribute('for');
            if (inputId) {
                const input = modalElement.querySelector(`#${inputId}`);
                if (input) {
                    input.checked = true;
                    
                    // Trigger change event
                    const event = new Event('change');
                    input.dispatchEvent(event);
                }
            }
        });
    });
}

function setupFormValidation(umkmId) {
    const modalElement = document.getElementById(`editModal${umkmId}`);
    if (!modalElement) return;
    
    const form = modalElement.querySelector('form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validasi tab 1
        const namaUsaha = this.querySelector('input[name="nama_usaha"]');
        const kategori = this.querySelector('select[name="kategori"]');
        
        if (!namaUsaha.value.trim()) {
            namaUsaha.classList.add('is-invalid');
            isValid = false;
        } else {
            namaUsaha.classList.remove('is-invalid');
        }
        
        if (!kategori.value) {
            kategori.classList.add('is-invalid');
            isValid = false;
        } else {
            kategori.classList.remove('is-invalid');
        }
        
        // Validasi tab 2
        const alamat = this.querySelector('textarea[name="alamat"]');
        if (!alamat.value.trim()) {
            alamat.classList.add('is-invalid');
            isValid = false;
        } else {
            alamat.classList.remove('is-invalid');
        }
        
        // Validasi tab 3
        const statusVerifikasi = this.querySelector('input[name="status_verifikasi"]:checked');
        if (!statusVerifikasi) {
            const statusError = this.querySelector('.invalid-feedback.d-block');
            if (statusError) {
                statusError.style.display = 'block';
            }
            isValid = false;
        } else {
            const statusError = this.querySelector('.invalid-feedback.d-block');
            if (statusError) {
                statusError.style.display = 'none';
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
            
            // Show error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 z-3';
            alertDiv.style.minWidth = '300px';
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Perhatian!</strong> Mohon lengkapi semua data yang wajib diisi.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
            
            return false;
        }
        
        this.classList.add('was-validated');
        return true;
    });
}

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
    
</body>
</html>