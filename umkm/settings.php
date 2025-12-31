<?php
// umkm/settings.php
require_once '../config/koneksi.php';
requireLogin();

if (isAdmin()) {
    header("Location: ../admin/settings.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Ambil data user
$query = "SELECT * FROM users WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

// Handle Update Foto Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_foto'])) {
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_profil']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Hapus foto lama jika ada
            if ($user['foto_profil'] && file_exists("../uploads/users/" . $user['foto_profil'])) {
                unlink("../uploads/users/" . $user['foto_profil']);
            }
            
            // Buat folder jika belum ada
            if (!is_dir('../uploads/users')) {
                mkdir('../uploads/users', 0777, true);
            }
            
            $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = "../uploads/users/$new_filename";
            
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
                try {
                    $query = "UPDATE users SET foto_profil = :foto WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':foto', $new_filename);
                    $stmt->bindParam(':id', $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success = 'Foto profil berhasil diperbarui!';
                        // Refresh data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                        $stmt->bindParam(':id', $_SESSION['user_id']);
                        $stmt->execute();
                        $user = $stmt->fetch();
                    }
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan foto: ' . $e->getMessage();
                }
            } else {
                $error = 'Gagal upload foto!';
            }
        } else {
            $error = 'Format foto tidak valid! Gunakan JPG, PNG, atau GIF.';
        }
    } else {
        $error = 'Tidak ada foto yang dipilih!';
    }
}

// Handle Hapus Foto Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_foto'])) {
    if ($user['foto_profil'] && file_exists("../uploads/users/" . $user['foto_profil'])) {
        unlink("../uploads/users/" . $user['foto_profil']);
    }
    
    try {
        $query = "UPDATE users SET foto_profil = NULL WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Foto profil berhasil dihapus!';
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus foto: ' . $e->getMessage();
    }
}

// Handle Update Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $username = sanitize($_POST['username']);
    $no_telepon = sanitize($_POST['no_telepon']);
    
    try {
        $query = "UPDATE users SET username = :username, no_telepon = :no_telepon WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':no_telepon', $no_telepon);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $success = 'Informasi akun berhasil diperbarui!';
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = 'Username sudah digunakan!';
        } else {
            $error = 'Gagal memperbarui informasi: ' . $e->getMessage();
        }
    }
}

// Handle Ubah Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konfirmasi = $_POST['password_konfirmasi'];
    
    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = 'Semua field password harus diisi!';
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = 'Password baru dan konfirmasi tidak cocok!';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } elseif (!password_verify($password_lama, $user['password'])) {
        $error = 'Password lama salah!';
    } else {
        try {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Password berhasil diubah!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal mengubah password: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - SIGAP-UMKM</title>
    
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
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
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
            font-size: 1.3rem;
        }
        
        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.875rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-size: 0.95rem;
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
        
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .settings-card h5 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-photo-section {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .profile-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
        }
        
        .profile-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
            font-weight: 700;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-outline-gradient {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-outline-gradient:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .danger-zone {
            background: #fff5f5;
            border: 2px solid #feb2b2;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h6 {
            color: #c53030;
            font-weight: 600;
            margin-bottom: 0.5rem;
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
                <a class="nav-link" href="produk.php">
                    <i class="bi bi-box-seam me-2"></i>Kelola Produk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="settings.php">
                    <i class="bi bi-gear me-2"></i>Pengaturan Akun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
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
            <h1><i class="bi bi-gear me-2"></i>Pengaturan Akun</h1>
            <p class="mb-0 opacity-90">Kelola foto profil, informasi akun, dan keamanan</p>
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
        
        <!-- Foto Profil -->
        <div class="settings-card">
            <h5><i class="bi bi-person-circle me-2"></i>Foto Profil</h5>
            
            <div class="profile-photo-section">
                <?php if ($user['foto_profil']): ?>
                    <img src="../uploads/users/<?= htmlspecialchars($user['foto_profil']) ?>" 
                         class="profile-photo" alt="Foto Profil">
                <?php else: ?>
                    <div class="profile-placeholder">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <input type="file" name="foto_profil" id="foto_profil" class="d-none" 
                               accept="image/*" onchange="this.form.submit()">
                        <label for="foto_profil" class="btn btn-gradient me-2">
                            <i class="bi bi-upload me-2"></i>
                            <?= $user['foto_profil'] ? 'Ganti Foto' : 'Upload Foto' ?>
                        </label>
                        <input type="hidden" name="update_foto" value="1">
                    </form>
                    
                    <?php if ($user['foto_profil']): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="delete_foto" class="btn btn-outline-danger"
                                    onclick="return confirm('Yakin ingin menghapus foto profil?')">
                                <i class="bi bi-trash me-2"></i>Hapus Foto
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="info-box mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>Format: JPG, PNG, GIF (Max 2MB). Foto lama akan otomatis terhapus saat upload baru.</small>
                </div>
            </div>
        </div>
        
        <!-- Informasi Akun -->
        <div class="settings-card">
            <h5><i class="bi bi-person-badge me-2"></i>Informasi Akun</h5>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required
                               value="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="tel" name="no_telepon" class="form-control"
                               value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>"
                               placeholder="Contoh: 081234567890">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" readonly
                               value="<?= ucfirst($user['role']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bergabung Sejak</label>
                        <input type="text" class="form-control" readonly
                               value="<?= date('d F Y', strtotime($user['created_at'])) ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_info" class="btn btn-gradient">
                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>
        
        <!-- Ubah Password -->
        <div class="settings-card">
            <h5><i class="bi bi-shield-lock me-2"></i>Ubah Password</h5>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" name="password_lama" class="form-control" required
                               placeholder="Masukkan password lama">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_baru" class="form-control" required
                               placeholder="Minimal 6 karakter" minlength="6">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_konfirmasi" class="form-control" required
                               placeholder="Ulangi password baru">
                    </div>
                </div>
                
                <div class="info-box">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Tips Keamanan:</strong> Gunakan kombinasi huruf besar, kecil, angka, dan simbol untuk password yang kuat.
                </div>
                
                <button type="submit" name="change_password" class="btn btn-gradient mt-3">
                    <i class="bi bi-key me-2"></i>Ubah Password
                </button>
            </form>
        </div>
        
        <!-- Danger Zone -->
        <div class="danger-zone">
            <h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Zona Berbahaya</h6>
            <p class="text-muted mb-3">Tindakan di bawah ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <button class="btn btn-danger" disabled>
                <i class="bi bi-trash me-2"></i>Hapus Akun (Coming Soon)
            </button>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>