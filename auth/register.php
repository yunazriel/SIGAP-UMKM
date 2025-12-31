<?php
// auth/register.php
require_once '../config/koneksi.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = isAdmin() ? '../admin/index.php' : '../umkm/index.php';
    header("Location: $redirect");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_usaha = sanitize($_POST['nama_usaha']);
    $kategori = sanitize($_POST['kategori']);
    $alamat = sanitize($_POST['alamat']);
    $no_telp = sanitize($_POST['no_telp'] ?? '');
    
    // Validasi
    if (empty($username) || empty($password) || empty($nama_usaha) || empty($kategori) || empty($alamat)) {
        $error = 'Username, Password, Nama Usaha, Kategori, dan Alamat harus diisi!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Cek username sudah ada
            $query = "SELECT id FROM users WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan! Silakan pilih username lain.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user dan data UMKM dalam satu transaksi
                $conn->beginTransaction();
                
                // Insert user
                $query = "INSERT INTO users (username, password, role) VALUES (:username, :password, 'umkm')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->execute();
                
                $user_id = $conn->lastInsertId();
                
                // Insert data UMKM dengan informasi lengkap
                $deskripsi = "Informasi lengkap akan diisi setelah verifikasi";
                $query = "INSERT INTO umkm_data (user_id, nama_usaha, kategori, deskripsi, alamat, omzet_bulanan, jumlah_karyawan) 
                          VALUES (:user_id, :nama_usaha, :kategori, :deskripsi, :alamat, 0, 0)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':nama_usaha', $nama_usaha);
                $stmt->bindParam(':kategori', $kategori);
                $stmt->bindParam(':deskripsi', $deskripsi);
                $stmt->bindParam(':alamat', $alamat);
                $stmt->execute();
                
                $conn->commit();
                
                $success = 'Registrasi berhasil! Silakan login dan lengkapi profil UMKM Anda.';
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi. Error: ' . $e->getMessage();
        }
    }
}

// Daftar kategori UMKM
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar UMKM - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .register-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
        }
        
        .register-header h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .register-body {
            padding: 2.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            padding: 0.875rem 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 12px 0 0 12px;
            padding: 0.875rem 1rem;
        }
        
        .input-group .form-control, .input-group .form-select {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            padding: 0.875rem;
            border-radius: 12px;
            border: none;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .divider span {
            padding: 0 1rem;
            color: #718096;
            font-size: 0.875rem;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-home a:hover {
            opacity: 0.8;
        }
        
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            width: 0%;
        }
        
        .info-box {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .info-box small {
            color: #4a5568;
            display: block;
            line-height: 1.6;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }
        
        .step {
            width: 40px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            transition: all 0.3s;
        }
        
        .step.active {
            background: #667eea;
        }
    </style>
</head>
<body>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-shop-window fs-1 mb-3 d-block"></i>
                <h2>Daftar UMKM</h2>
                <p>Bergabung dengan SIGAP-UMKM Kota Semarang</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?= $success ?></div>
                    </div>
                    <a href="login.php" class="btn btn-register">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang
                    </a>
                <?php else: ?>
                    <div class="info-box">
                        <small>
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Informasi:</strong> Setelah mendaftar, lengkapi profil UMKM Anda dan tunggu verifikasi dari admin untuk muncul di peta.
                        </small>
                    </div>
                    
                    <form method="POST" action="" id="registerForm">
                        <!-- Step 1: Informasi Usaha -->
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="bi bi-building me-2"></i>Informasi Usaha
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-shop me-1"></i>Nama Usaha <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-building text-muted"></i>
                                </span>
                                <input type="text" name="nama_usaha" class="form-control" 
                                       placeholder="Contoh: Batik Semarang Jaya" required
                                       value="<?= isset($_POST['nama_usaha']) ? htmlspecialchars($_POST['nama_usaha']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>Kategori Usaha <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-grid text-muted"></i>
                                </span>
                                <select name="kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?= $kat ?>" <?= (isset($_POST['kategori']) && $_POST['kategori'] === $kat) ? 'selected' : '' ?>>
                                            <?= $kat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-geo-alt me-1"></i>Alamat Lengkap <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-pin-map text-muted"></i>
                                </span>
                                <textarea name="alamat" class="form-control" rows="2" 
                                          placeholder="Contoh: Jl. Pandanaran No. 123, Semarang" required><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-telephone me-1"></i>No. Telepon (Opsional)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-phone text-muted"></i>
                                </span>
                                <input type="tel" name="no_telp" class="form-control" 
                                       placeholder="Contoh: 081234567890"
                                       value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Step 2: Akun Login -->
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="bi bi-person-circle me-2"></i>Akun Login
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>Username <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-circle text-muted"></i>
                                </span>
                                <input type="text" name="username" class="form-control" 
                                       placeholder="Minimal 4 karakter" required minlength="4"
                                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                            </div>
                            <small class="text-muted">Username akan digunakan untuk login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-lock me-1"></i>Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-shield-lock text-muted"></i>
                                </span>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Minimal 6 karakter" required minlength="6" 
                                       id="password" onkeyup="checkPasswordStrength()">
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <small class="text-muted" id="strengthText">Gunakan kombinasi huruf, angka, dan simbol</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>Konfirmasi Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-shield-check text-muted"></i>
                                </span>
                                <input type="password" name="confirm_password" class="form-control" 
                                       placeholder="Ketik ulang password" required id="confirmPassword">
                            </div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="showPasswords" 
                                   onclick="togglePasswords()">
                            <label class="form-check-label text-muted small" for="showPasswords">
                                Tampilkan password
                            </label>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label small" for="agreeTerms">
                                Saya setuju dengan <a href="#" class="text-primary">Syarat & Ketentuan</a> yang berlaku
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-register">
                            <i class="bi bi-check-circle me-2"></i>Daftar Sekarang
                        </button>
                    </form>
                    
                    <div class="divider">
                        <span>Sudah punya akun?</span>
                    </div>
                    
                    <a href="login.php" class="btn btn-outline-primary w-100" style="border-radius: 12px;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="back-home">
            <a href="../index.php">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePasswords() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            confirmPassword.type = type;
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            const colors = ['#ef4444', '#f59e0b', '#eab308', '#22c55e', '#10b981'];
            const texts = ['Sangat Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            
            strengthBar.style.width = widths[strength];
            strengthBar.style.backgroundColor = colors[strength];
            strengthText.textContent = texts[strength];
            strengthText.style.color = colors[strength];
        }
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan Konfirmasi Password tidak cocok!');
                return false;
            }
            
            if (!agreeTerms) {
                e.preventDefault();
                alert('Anda harus menyetujui Syarat & Ketentuan!');
                return false;
            }
        });
    </script>
    
</body>
</html>