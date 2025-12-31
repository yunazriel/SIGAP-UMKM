<?php
// umkm/produk.php
require_once '../config/koneksi.php';
requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ambil data UMKM
$query = "SELECT id FROM umkm_data WHERE user_id = :user_id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$umkm = $stmt->fetch();

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $produk_id = (int)$_GET['delete'];
    
    try {
        // Ambil foto untuk dihapus
        $query = "SELECT foto_produk FROM produk WHERE id = :id AND umkm_id = :umkm_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $produk_id);
        $stmt->bindParam(':umkm_id', $umkm['id']);
        $stmt->execute();
        $produk = $stmt->fetch();
        
        if ($produk) {
            // Hapus file foto
            if ($produk['foto_produk'] && file_exists("../uploads/produk/" . $produk['foto_produk'])) {
                unlink("../uploads/produk/" . $produk['foto_produk']);
            }
            
            // Hapus dari database
            $query = "DELETE FROM produk WHERE id = :id AND umkm_id = :umkm_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $produk_id);
            $stmt->bindParam(':umkm_id', $umkm['id']);
            $stmt->execute();
            
            $success = 'Produk berhasil dihapus!';
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus produk!';
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = sanitize($_POST['nama_produk']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $produk_id = isset($_POST['produk_id']) ? (int)$_POST['produk_id'] : 0;
    
    // Handle foto
    $foto_produk = '';
    $foto_lama = '';
    
    if ($produk_id > 0) {
        // Ambil foto lama untuk dihapus
        $query = "SELECT foto_produk FROM produk WHERE id = :id AND umkm_id = :umkm_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $produk_id);
        $stmt->bindParam(':umkm_id', $umkm['id']);
        $stmt->execute();
        $produk_data = $stmt->fetch();
        $foto_lama = $produk_data ? $produk_data['foto_produk'] : '';
    }
    
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_produk']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Hapus foto lama jika ada
            if ($foto_lama && file_exists("../uploads/produk/" . $foto_lama)) {
                unlink("../uploads/produk/" . $foto_lama);
            }
            
            // Create folder if not exists
            if (!is_dir('../uploads/produk')) {
                mkdir('../uploads/produk', 0777, true);
            }
            
            $new_filename = 'produk_' . $umkm['id'] . '_' . time() . '.' . $ext;
            $upload_path = "../uploads/produk/$new_filename";
            
            if (move_uploaded_file($_FILES['foto_produk']['tmp_name'], $upload_path)) {
                $foto_produk = $new_filename;
            }
        }
    }
    
    try {
        if ($produk_id > 0) {
            // Update
            $query = "UPDATE produk SET nama_produk = :nama_produk, deskripsi = :deskripsi, harga = :harga" . 
                     ($foto_produk ? ", foto_produk = :foto_produk" : "") . 
                     " WHERE id = :id AND umkm_id = :umkm_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama_produk', $nama_produk);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':harga', $harga);
            if ($foto_produk) $stmt->bindParam(':foto_produk', $foto_produk);
            $stmt->bindParam(':id', $produk_id);
            $stmt->bindParam(':umkm_id', $umkm['id']);
            $stmt->execute();
            
            $success = 'Produk berhasil diperbarui!';
        } else {
            // Insert
            $query = "INSERT INTO produk (umkm_id, nama_produk, deskripsi, harga, foto_produk) 
                      VALUES (:umkm_id, :nama_produk, :deskripsi, :harga, :foto_produk)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':umkm_id', $umkm['id']);
            $stmt->bindParam(':nama_produk', $nama_produk);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':harga', $harga);
            $stmt->bindParam(':foto_produk', $foto_produk);
            $stmt->execute();
            
            $success = 'Produk berhasil ditambahkan!';
        }
    } catch (PDOException $e) {
        $error = 'Gagal menyimpan produk: ' . $e->getMessage();
    }
}

// Ambil semua produk
$query = "SELECT * FROM produk WHERE umkm_id = :umkm_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':umkm_id', $umkm['id']);
$stmt->execute();
$produk_list = $stmt->fetchAll();

// Get produk untuk edit
$edit_produk = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM produk WHERE id = :id AND umkm_id = :umkm_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $edit_id);
    $stmt->bindParam(':umkm_id', $umkm['id']);
    $stmt->execute();
    $edit_produk = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f8f9fa; }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }
        
        .sidebar-brand h4 {
            color: white;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.875rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-left-color: white;
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
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
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
        }
        
        .product-price {
            color: #667eea;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .foto-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h4>
            <small>Panel UMKM</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profil.php">
                    <i class="bi bi-shop me-2"></i>Profil UMKM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="produk.php">
                    <i class="bi bi-box-seam me-2"></i>Kelola Produk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear me-2"></i>Pengaturan Akun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php"">
                    <i class="bi bi-globe me-2"></i>Lihat Website
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-white" href="../auth/logout.php">
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
                    <h1><i class="bi bi-box-seam me-2"></i>Kelola Produk</h1>
                    <p class="text-muted mb-0">Tambah dan kelola produk UMKM Anda</p>
                </div>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalProduk">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Produk
                </button>
            </div>
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
        
        <!-- Product Grid -->
        <?php if (count($produk_list) > 0): ?>
            <div class="row g-4">
                <?php foreach ($produk_list as $produk): ?>
                    <div class="col-md-6 col-lg-4">
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
                                <h5 class="product-title"><?= htmlspecialchars($produk['nama_produk']) ?></h5>
                                <div class="product-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                                
                                <div class="d-flex gap-2">
                                    <a href="?edit=<?= $produk['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $produk['id'] ?>" class="btn btn-outline-danger btn-sm flex-fill"
                                       onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="form-card text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5>Belum Ada Produk</h5>
                <p class="text-muted mb-4">Mulai tambahkan produk UMKM Anda untuk dipromosikan</p>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalProduk">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Produk Pertama
                </button>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Modal Tambah/Edit Produk -->
    <div class="modal fade" id="modalProduk" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title">
                        <i class="bi bi-<?= $edit_produk ? 'pencil' : 'plus-circle' ?> me-2"></i>
                        <?= $edit_produk ? 'Edit' : 'Tambah' ?> Produk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <?php if ($edit_produk): ?>
                            <input type="hidden" name="produk_id" value="<?= $edit_produk['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama_produk" class="form-control" required
                                   value="<?= $edit_produk ? htmlspecialchars($edit_produk['nama_produk']) : '' ?>"
                                   placeholder="Contoh: Batik Tulis Lasem">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="harga" class="form-control" required min="0" step="1000"
                                   value="<?= $edit_produk ? $edit_produk['harga'] : '' ?>"
                                   placeholder="Contoh: 150000">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Foto Produk</label>
                            <input type="file" name="foto_produk" class="form-control" accept="image/*" 
                                   onchange="previewModalImage(event)" <?= $edit_produk ? '' : 'required' ?>>
                            <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                            
                            <?php if ($edit_produk && $edit_produk['foto_produk']): ?>
                                <img src="../uploads/produk/<?= htmlspecialchars($edit_produk['foto_produk']) ?>" 
                                     class="foto-preview" id="modalPreview" alt="Preview">
                            <?php else: ?>
                                <img src="" class="foto-preview d-none" id="modalPreview" alt="Preview">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="bi bi-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function previewModalImage(event) {
            const preview = document.getElementById('modalPreview');
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
        
        <?php if ($edit_produk): ?>
        // Auto open modal saat edit
        window.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalProduk'));
            modal.show();
        });
        <?php endif; ?>
    </script>
    
</body>
</html>