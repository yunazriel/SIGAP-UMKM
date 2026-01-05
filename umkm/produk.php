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
$refresh_page = false; // Flag untuk refresh halaman

// Handle Delete Produk
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
            $refresh_page = true;
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
    
    // ================================
    // VALIDASI PANJANG DESKRIPSI PRODUK (500 karakter)
    // ================================
    $max_deskripsi = 500;
    if (strlen($deskripsi) > $max_deskripsi) {
        $error = "Deskripsi produk terlalu panjang! Maksimal $max_deskripsi karakter.";
    }
    
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
    
    // ✅ CEK APAKAH USER INGIN HAPUS FOTO
    $hapus_foto = isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1';
    
    if (!$error) {
        if ($hapus_foto) {
            // Hapus foto lama jika ada
            if ($foto_lama && file_exists("../uploads/produk/" . $foto_lama)) {
                unlink("../uploads/produk/" . $foto_lama);
            }
            $foto_produk = '';
        }
        // ✅ CEK APAKAH ADA UPLOAD FOTO BARU
        elseif (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto_produk']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                // Validasi ukuran file (max 2MB)
                if ($_FILES['foto_produk']['size'] > 2 * 1024 * 1024) {
                    $error = 'Ukuran foto terlalu besar! Maksimal 2MB.';
                } else {
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
                    } else {
                        $error = 'Gagal mengupload foto produk.';
                    }
                }
            } else {
                $error = 'Format foto tidak valid! Gunakan JPG, PNG, atau GIF.';
            }
        } else {
            // Jika tidak upload foto baru dan tidak hapus foto, pakai foto lama (untuk edit)
            if ($produk_id > 0 && !$hapus_foto) {
                $foto_produk = $foto_lama;
            }
        }
    }
    
    if (!$error) {
        try {
            if ($produk_id > 0) {
                // Update
                $query = "UPDATE produk SET nama_produk = :nama_produk, deskripsi = :deskripsi, harga = :harga" . 
                         ", foto_produk = :foto_produk" . 
                         " WHERE id = :id AND umkm_id = :umkm_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_produk', $nama_produk);
                $stmt->bindParam(':deskripsi', $deskripsi);
                $stmt->bindParam(':harga', $harga);
                $stmt->bindParam(':foto_produk', $foto_produk);
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
            
            $refresh_page = true;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan produk: ' . $e->getMessage();
        }
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

// Jika sukses dan perlu refresh, redirect ke halaman ini sendiri tanpa parameter edit
if ($refresh_page && !$error) {
    header("Location: produk.php?success=" . urlencode($success));
    exit();
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
        
        .product-desc {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
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
        
        /* STYLE UNTUK COUNTER DESKRIPSI PRODUK */
        .deskripsi-counter {
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 2px 6px;
            border-radius: 4px;
            background: transparent;
            display: inline-block;
        }
        
        .deskripsi-counter.normal {
            color: #6c757d;
        }
        
        .deskripsi-counter.warning {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        
        .deskripsi-counter.danger {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            font-weight: 600;
        }
        
        .textarea-produk {
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .textarea-produk:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .textarea-produk.border-warning {
            border-color: #f59e0b;
        }
        
        .textarea-produk.border-danger {
            border-color: #ef4444;
        }
        
        .progress-sm {
            height: 3px;
            border-radius: 1.5px;
        }
        
        /* STYLE UNTUK UPLOAD AREA PRODUK */
        .upload-area-produk {
            position: relative;
            border: 3px dashed #e2e8f0;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            background: #f8f9fa;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area-produk:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
        }

        .upload-area-produk img {
            max-height: 280px;
            object-fit: cover;
            width: 100%;
            border-radius: 10px;
        }

        /* TOMBOL HAPUS HANYA TAMPIL SAAT HOVER */
        .btn-delete-foto {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0; /* SEMULA DISEMBUNYIKAN */
            visibility: hidden; /* SEMULA DISEMBUNYIKAN */
            z-index: 10;
        }

        /* SAAT AREA UPLOAD DI-HOVER, TOMBOL HAPUS MUNCUL */
        .upload-area-produk:hover .btn-delete-foto {
            opacity: 1;
            visibility: visible;
        }

        /* TOMBOL HAPUS SENDIRI JUGA DAPAT DI-HOVER */
        .btn-delete-foto:hover {
            background: rgba(220, 38, 38, 0.9);
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
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
                    <h1><i class="bi bi-box-seam me-2"></i>Kelola Produk</h1>
                    <p class="text-muted mb-0">Tambah dan kelola produk UMKM Anda</p>
                </div>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalProduk">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Produk
                </button>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success && !isset($_GET['edit'])): ?>
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
                                
                                <?php if ($produk['deskripsi']): ?>
                                    <p class="product-desc"><?= htmlspecialchars($produk['deskripsi']) ?></p>
                                <?php endif; ?>
                                
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
    <div class="modal fade" id="modalProduk" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title">
                        <i class="bi bi-<?= $edit_produk ? 'pencil' : 'plus-circle' ?> me-2"></i>
                        <?= $edit_produk ? 'Edit' : 'Tambah' ?> Produk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="resetForm()"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="formProduk">
                    <div class="modal-body p-4">
                        <?php if ($edit_produk): ?>
                            <input type="hidden" name="produk_id" value="<?= $edit_produk['id'] ?>">
                        <?php endif; ?>
                        
                        <!-- Hidden input untuk flag hapus foto -->
                        <input type="hidden" name="hapus_foto" id="hapusFoto" value="0">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Kiri: Form Input -->
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
                                    <label class="form-label fw-bold">Deskripsi Produk 
                                        <span class="badge bg-light text-dark ms-1" data-bs-toggle="tooltip" 
                                              title="Maksimal 500 karakter">
                                            <i class="bi bi-info-circle"></i>
                                        </span>
                                    </label>
                                    <textarea name="deskripsi" 
                                              class="form-control textarea-produk" 
                                              rows="5"
                                              maxlength="500"
                                              oninput="updateProdukCharCount(this)"
                                              placeholder="Jelaskan detail produk Anda... (Maksimal 500 karakter)"
                                              id="deskripsiProduk"><?= $edit_produk ? htmlspecialchars($edit_produk['deskripsi']) : '' ?></textarea>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-lightbulb me-1"></i>Deskripsi singkat dan menarik
                                        </small>
                                        <div>
                                            <span class="deskripsi-counter normal" id="produkCharCounter">
                                                <span id="produkCurrentChars">0</span>/500 karakter
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress bar mini -->
                                    <div class="progress progress-sm mt-1">
                                        <div id="produkCharProgress" class="progress-bar" 
                                             role="progressbar" 
                                             style="width: 0%; background: #667eea;"
                                             aria-valuenow="0" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Kanan: Foto Produk -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Foto Produk <?= !$edit_produk ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <div class="upload-area-produk" onclick="document.getElementById('fotoProdukInput').click()">
                                        
                                        <?php if ($edit_produk && $edit_produk['foto_produk']): ?>
                                            <button type="button" class="btn-delete-foto" onclick="hapusFotoProduk(event)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>

                                            <img src="../uploads/produk/<?= htmlspecialchars($edit_produk['foto_produk']) ?>" 
                                                 class="img-fluid rounded-3" id="modalPreview" 
                                                 alt="Preview Foto Produk">
                                            <div class="mt-2">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Foto Tersimpan
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div id="previewPlaceholder" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                                <i class="bi bi-cloud-upload text-muted" style="font-size: 2.5rem;"></i>
                                                <p class="text-muted mt-2 mb-0 fw-semibold">Klik untuk upload foto produk</p>
                                                <small class="text-muted">atau drag & drop disini</small>
                                            </div>
                                            <img src="" class="img-fluid rounded-3 d-none" id="modalPreview" 
                                                 alt="Preview">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <input type="file" name="foto_produk" class="d-none" id="fotoProdukInput" accept="image/*"
                                           onchange="previewModalImage(event)" <?= !$edit_produk ? 'required' : '' ?>>
                                    
                                    <div class="mt-2 text-center">
                                        <small class="text-muted d-block">
                                            <i class="bi bi-info-circle me-1"></i>JPG, PNG, GIF (Max 2MB)
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Info tambahan -->
                                <!-- <div class="alert alert-info bg-opacity-10 border-0 mt-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-info-circle-fill text-info me-2"></i>
                                        <small>
                                            <strong>Tips:</strong> Gunakan foto produk yang jelas dan menarik untuk meningkatkan penjualan
                                        </small>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetForm()">Batal</button>
                        <button type="submit" class="btn btn-gradient" id="btnSimpan">
                            <i class="bi bi-save me-2"></i>Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // ============================================
        // FUNGSI COUNTER DESKRIPSI PRODUK
        // ============================================
        function updateProdukCharCount(textarea) {
            const charCount = textarea.value.length;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            const counter = document.getElementById('produkCharCounter');
            const currentChars = document.getElementById('produkCurrentChars');
            const progressBar = document.getElementById('produkCharProgress');
            
            // Update angka
            currentChars.textContent = charCount;
            
            // Hitung persentase
            const percentage = (charCount / maxLength) * 100;
            
            // Update progress bar
            if (progressBar) {
                progressBar.style.width = percentage + '%';
                progressBar.setAttribute('aria-valuenow', percentage);
            }
            
            // Reset semua kelas
            textarea.classList.remove('border-warning', 'border-danger');
            counter.className = 'deskripsi-counter';
            
            // Update warna berdasarkan jumlah karakter
            if (charCount > maxLength) {
                // Jika melebihi batas
                counter.classList.add('danger');
                textarea.classList.add('border-danger');
                progressBar.style.background = '#ef4444';
            } else if (percentage >= 90) {
                // 90-100%: merah/warning tinggi
                counter.classList.add('danger');
                textarea.classList.add('border-danger');
                progressBar.style.background = '#ef4444';
            } else if (percentage >= 75) {
                // 75-89%: kuning/warning
                counter.classList.add('warning');
                textarea.classList.add('border-warning');
                progressBar.style.background = '#f59e0b';
            } else {
                // <75%: normal
                counter.classList.add('normal');
                progressBar.style.background = '#667eea';
            }
        }
        
        // ============================================
        // FUNGSI PREVIEW IMAGE MODAL
        // ============================================
        function previewModalImage(event) {
            const file = event.target.files[0];
            
            if (file) {
                // Validasi ukuran file (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 2MB');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('modalPreview');
                    const placeholder = document.getElementById('previewPlaceholder');
                    
                    // Tampilkan preview
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    
                    // Sembunyikan placeholder
                    if (placeholder) {
                        placeholder.classList.add('d-none');
                    }
                    
                    // Reset hapus_foto flag karena upload foto baru
                    document.getElementById('hapusFoto').value = '0';
                    
                    // Tambah tombol hapus jika belum ada
                    const uploadArea = document.querySelector('.upload-area-produk');
                    let deleteBtn = uploadArea.querySelector('.btn-delete-foto');
                    if (!deleteBtn) {
                        const newDeleteBtn = document.createElement('button');
                        newDeleteBtn.type = 'button';
                        newDeleteBtn.className = 'btn-delete-foto';
                        newDeleteBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
                        newDeleteBtn.onclick = hapusFotoProduk;
                        
                        // Set style untuk tombol hapus
                        newDeleteBtn.style.position = 'absolute';
                        newDeleteBtn.style.top = '15px';
                        newDeleteBtn.style.right = '15px';
                        newDeleteBtn.style.width = '32px';
                        newDeleteBtn.style.height = '32px';
                        newDeleteBtn.style.borderRadius = '8px';
                        newDeleteBtn.style.background = 'rgba(0, 0, 0, 0.7)';
                        newDeleteBtn.style.border = 'none';
                        newDeleteBtn.style.color = 'white';
                        newDeleteBtn.style.display = 'flex';
                        newDeleteBtn.style.alignItems = 'center';
                        newDeleteBtn.style.justifyContent = 'center';
                        newDeleteBtn.style.cursor = 'pointer';
                        newDeleteBtn.style.transition = 'all 0.2s';
                        newDeleteBtn.style.opacity = '0';
                        newDeleteBtn.style.visibility = 'hidden';
                        newDeleteBtn.style.zIndex = '10';
                        
                        uploadArea.appendChild(newDeleteBtn);
                        
                        // Tambah event listener untuk hover
                        uploadArea.addEventListener('mouseenter', function() {
                            newDeleteBtn.style.opacity = '1';
                            newDeleteBtn.style.visibility = 'visible';
                        });
                        
                        uploadArea.addEventListener('mouseleave', function() {
                            newDeleteBtn.style.opacity = '0';
                            newDeleteBtn.style.visibility = 'hidden';
                        });
                    }
                }
                reader.readAsDataURL(file);
            }
        }
        
        // ============================================
        // FUNGSI HAPUS FOTO PRODUK
        // ============================================
        function hapusFotoProduk(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (confirm('Yakin ingin menghapus foto produk ini?')) {
                const preview = document.getElementById('modalPreview');
                const placeholder = document.getElementById('previewPlaceholder');
                const uploadArea = document.querySelector('.upload-area-produk');
                
                // Set flag hapus foto
                document.getElementById('hapusFoto').value = '1';
                
                // Reset input file
                document.getElementById('fotoProdukInput').value = '';
                
                // Hapus tombol hapus
                const deleteBtn = uploadArea.querySelector('.btn-delete-foto');
                if (deleteBtn) {
                    deleteBtn.remove();
                }
                
                // Ganti dengan tampilan placeholder
                if (preview) {
                    preview.classList.add('d-none');
                }
                
                if (placeholder) {
                    placeholder.classList.remove('d-none');
                } else {
                    // Buat placeholder jika tidak ada
                    const newPlaceholder = document.createElement('div');
                    newPlaceholder.id = 'previewPlaceholder';
                    newPlaceholder.style.cssText = 'display: flex; flex-direction: column; align-items: center; justify-content: center;';
                    newPlaceholder.innerHTML = `
                        <i class="bi bi-cloud-upload text-muted" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mt-2 mb-0 fw-semibold">Klik untuk upload foto produk</p>
                        <small class="text-muted">atau drag & drop disini</small>
                    `;
                    uploadArea.appendChild(newPlaceholder);
                }
                
                // Tampilkan pesan
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    Foto akan dihapus saat Anda menyimpan produk.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const modalBody = document.querySelector('.modal-body');
                modalBody.insertBefore(alertDiv, modalBody.firstChild);
            }
        }
        
        // ============================================
        // DRAG & DROP UNTUK FOTO PRODUK
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const uploadAreaProduk = document.querySelector('.upload-area-produk');
            
            if (uploadAreaProduk) {
                uploadAreaProduk.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadAreaProduk.style.borderColor = '#667eea';
                    uploadAreaProduk.style.background = 'linear-gradient(135deg, #667eea20 0%, #764ba220 100%)';
                });

                uploadAreaProduk.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    uploadAreaProduk.style.borderColor = '#e2e8f0';
                    uploadAreaProduk.style.background = '#f8f9fa';
                });

                uploadAreaProduk.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadAreaProduk.style.borderColor = '#e2e8f0';
                    uploadAreaProduk.style.background = '#f8f9fa';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        document.getElementById('fotoProdukInput').files = files;
                        previewModalImage({ target: { files: files } });
                    }
                });
            }
            
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize char counter untuk produk
            const deskripsiProduk = document.getElementById('deskripsiProduk');
            if (deskripsiProduk) {
                updateProdukCharCount(deskripsiProduk);
            }
            
            <?php if ($edit_produk): ?>
            // Auto open modal saat edit
            const modal = new bootstrap.Modal(document.getElementById('modalProduk'));
            modal.show();
            
            // Jangan auto close saat edit
            const modalElement = document.getElementById('modalProduk');
            modalElement.addEventListener('hidden.bs.modal', function () {
                // Redirect ke halaman tanpa parameter edit
                window.location.href = 'produk.php';
            });
            <?php endif; ?>
            
            // Handle modal close setelah submit
            const formProduk = document.getElementById('formProduk');
            if (formProduk) {
                formProduk.addEventListener('submit', function(e) {
                    // Validasi sebelum submit
                    const deskripsi = document.getElementById('deskripsiProduk');
                    const charCount = deskripsi.value.length;
                    const maxLength = parseInt(deskripsi.getAttribute('maxlength'));
                    
                    if (charCount > maxLength) {
                        e.preventDefault();
                        alert(`Deskripsi terlalu panjang! Maksimal ${maxLength} karakter.`);
                        return false;
                    }
                    
                    // Tampilkan loading
                    const btnSimpan = document.getElementById('btnSimpan');
                    const originalText = btnSimpan.innerHTML;
                    btnSimpan.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Menyimpan...';
                    btnSimpan.disabled = true;
                    
                    // Lanjutkan submit
                    return true;
                });
            }
        });
        
        // ============================================
        // FUNGSI RESET FORM MODAL
        // ============================================
        function resetForm() {
            // Reset form jika modal ditutup
            const form = document.getElementById('formProduk');
            if (form) {
                form.reset();
                document.getElementById('hapusFoto').value = '0';
                
                // Reset preview
                const preview = document.getElementById('modalPreview');
                const placeholder = document.getElementById('previewPlaceholder');
                if (preview) {
                    preview.src = '';
                    preview.classList.add('d-none');
                }
                if (placeholder) {
                    placeholder.classList.remove('d-none');
                }
                
                // Reset char counter
                const deskripsi = document.getElementById('deskripsiProduk');
                if (deskripsi) {
                    updateProdukCharCount(deskripsi);
                }
            }
        }
        
        // ============================================
        // AUTO CLOSE MODAL SETELAH SUBMIT SUKSES
        // ============================================
        <?php if ($success && !$error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Tutup modal jika ada di halaman edit
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalProduk'));
            if (modal) {
                modal.hide();
            }
            
            // Redirect ke halaman tanpa parameter edit
            setTimeout(function() {
                window.location.href = 'produk.php';
            }, 1500);
        });
        <?php endif; ?>
    </script>
    
</body>
</html>