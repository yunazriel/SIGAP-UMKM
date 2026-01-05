<?php
// admin/settings.php
require_once '../config/koneksi.php';
requireAdmin();

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
            if ($user['foto_profil'] && file_exists("../uploads/users/" . $user['foto_profil'])) {
                unlink("../uploads/users/" . $user['foto_profil']);
            }
            
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
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                        $stmt->bindParam(':id', $_SESSION['user_id']);
                        $stmt->execute();
                        $user = $stmt->fetch();
                    }
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan foto: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'Format foto tidak valid!';
        }
    }
}

// Handle Hapus Foto
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
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = 'Username sudah digunakan!';
        } else {
            $error = 'Gagal memperbarui: ' . $e->getMessage();
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
        $error = 'Password minimal 6 karakter!';
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
    <title>Pengaturan Admin - SIGAP-UMKM</title>
    
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
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
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
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
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
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
            border-color: #2d3748;
            box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(45, 55, 72, 0.3);
            color: white;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
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
            <h1><i class="bi bi-gear me-2"></i>Pengaturan Admin</h1>
            <p class="mb-0 opacity-90">Kelola akun administrator sistem</p>
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
            <h5><i class="bi bi-person-circle me-2"></i>Foto Profil Admin</h5>
            
            <div class="profile-photo-section">
                <?php if ($user['foto_profil']): ?>
                    <img src="../uploads/users/<?= htmlspecialchars($user['foto_profil']) ?>" 
                         class="profile-photo" alt="Foto Profil">
                <?php else: ?>
                    <div class="profile-placeholder">
                        <i class="bi bi-person-circle"></i>
                    </div>
                <?php endif; ?>
                
                <div class="mb-2">
                    <span class="admin-badge">
                        <i class="bi bi-shield-check me-1"></i>Administrator
                    </span>
                </div>
                
                <div>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <input type="file" name="foto_profil" id="foto_profil" class="d-none" 
                               accept="image/*" onchange="this.form.submit()">
                        <label for="foto_profil" class="btn btn-admin me-2">
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
                    <small>Format: JPG, PNG, GIF (Max 2MB)</small>
                </div>
            </div>
        </div>
        
        <!-- Informasi Akun -->
        <div class="settings-card">
            <h5><i class="bi bi-person-badge me-2"></i>Informasi Akun</h5>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-circle me-2 text-primary"></i>Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="username" class="form-control py-3 border-2" required
                            style="border-radius: 12px; border-color: #e2e8f0;"
                            value="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-telephone me-2 text-info"></i>Nomor Telepon
                        </label>
                        <input type="tel" name="no_telepon" class="form-control py-3 border-2"
                            style="border-radius: 12px; border-color: #e2e8f0;"
                            value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>"
                            placeholder="Contoh: 081234567890">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted small">
                            <i class="bi bi-shield-check me-1"></i>ROLE
                        </label>
                        <div class="p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-left: 4px solid #667eea;">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-person-badge text-primary fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block" style="cursor: default;   font-size: 0.75rem;">Status Akun</small>
                                    <strong class="text-dark" style="cursor: default; font-size: 1.1rem;"><?= htmlspecialchars(ucfirst($user['role'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-muted small">
                            <i class="bi bi-calendar-check me-1"></i>BERGABUNG SEJAK
                        </label>
                        <div class="p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #48bb7815 0%, #38a16915 100%); border-left: 4px solid #48bb78;">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-clock-history text-success fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block" style="cursor: default; font-size: 0.75rem;">Tanggal Daftar</small>
                                    <strong class="text-dark" style="cursor: default; font-size: 1.1rem;"><?= date('d F Y', strtotime($user['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="update_info" class="btn btn-admin">
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
                    <i class="bi bi-shield-check me-2"></i>
                    <strong>Keamanan Admin:</strong> Gunakan password yang kuat dengan kombinasi huruf, angka, dan simbol.
                </div>
                
                <button type="submit" name="change_password" class="btn btn-admin mt-3">
                    <i class="bi bi-key me-2"></i>Ubah Password
                </button>
            </form>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>