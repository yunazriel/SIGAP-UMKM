<?php
// admin/users.php
require_once '../config/koneksi.php';
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle Update User (Role, Username, No Telepon)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $no_telepon = trim($_POST['no_telepon']);
    
    if (empty($username)) {
        $error = 'Username tidak boleh kosong!';
    } else {
        try {
            // Cek apakah username sudah digunakan user lain
            $query = "SELECT id FROM users WHERE username = :username AND id != :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan user lain!';
            } else {
                $query = "UPDATE users SET username = :username, role = :role, no_telepon = :no_telepon WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':no_telepon', $no_telepon);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Data user berhasil diupdate!';
                }
            }
        } catch (PDOException $e) {
            $error = 'Gagal update user: ' . $e->getMessage();
        }
    }
}

// Handle Reset Password oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $success = 'Password user berhasil direset!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal reset password: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = 'Tidak dapat menghapus akun sendiri!';
    } else {
        try {
            // Hapus foto profil jika ada
            $query = "SELECT foto_profil FROM users WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user_data = $stmt->fetch();
            
            if ($user_data && $user_data['foto_profil'] && file_exists("../uploads/users/" . $user_data['foto_profil'])) {
                unlink("../uploads/users/" . $user_data['foto_profil']);
            }
            
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $success = 'User berhasil dihapus!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal menghapus user: ' . $e->getMessage();
        }
    }
}

// Ambil semua user dengan statistik
$query = "SELECT u.*, 
          CASE 
              WHEN u.role = 'umkm' THEN (SELECT nama_usaha FROM umkm_data WHERE user_id = u.id LIMIT 1)
              ELSE NULL
          END as nama_usaha,
          CASE 
              WHEN u.role = 'umkm' THEN (SELECT status_verifikasi FROM umkm_data WHERE user_id = u.id LIMIT 1)
              ELSE NULL
          END as status_verifikasi
          FROM users u
          ORDER BY u.role, u.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll();

$stats = [
    'total' => count($users),
    'admin' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
    'umkm' => count(array_filter($users, fn($u) => $u['role'] === 'umkm'))
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management User - Admin SIGAP-UMKM</title>
    
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
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .user-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            border-radius: 20px 20px 0 0;
            border: none;
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
            <h1><i class="bi bi-people me-2"></i>Management User</h1>
            <p class="text-muted mb-0">Kelola akun pengguna sistem SIGAP-UMKM</p>
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
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
                            <small class="text-muted">Total User</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['admin'] ?></h3>
                            <small class="text-muted">Administrator</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0 fw-bold"><?= $stats['umkm'] ?></h3>
                            <small class="text-muted">User UMKM</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-card">
            <h5 class="fw-bold mb-4">Daftar User</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Nama UMKM</th>
                            <th>Status</th>
                            <th>No. Telepon</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?php if ($u['foto_profil']): ?>
                                    <img src="../uploads/users/<?= htmlspecialchars($u['foto_profil']) ?>" 
                                         class="user-avatar" alt="<?= htmlspecialchars($u['username']) ?>">
                                <?php else: ?>
                                    <div class="user-placeholder">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($u['username']) ?></strong>
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-2">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-shield-check me-1"></i>Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-shop me-1"></i>UMKM
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $u['nama_usaha'] ? htmlspecialchars($u['nama_usaha']) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'umkm' && $u['status_verifikasi']): ?>
                                    <?php
                                    $badge_class = [
                                        'pending' => 'bg-warning',
                                        'terverifikasi' => 'bg-success',
                                        'ditolak' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?= $badge_class[$u['status_verifikasi']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($u['status_verifikasi']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $u['no_telepon'] ? htmlspecialchars($u['no_telepon']) : '<span class="text-muted">-</span>' ?></td>
                            <td>
                                <small><?= date('d M Y', strtotime($u['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $u['id'] ?>"
                                            title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#resetModal<?= $u['id'] ?>"
                                            title="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $u['id'] ?>"
                                            title="Hapus User">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-pencil me-2"></i>Edit User
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-person me-1"></i>Username
                                                </label>
                                                <input type="text" name="username" class="form-control" 
                                                       value="<?= htmlspecialchars($u['username']) ?>" 
                                                       required placeholder="Masukkan username">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-shield-check me-1"></i>Role
                                                </label>
                                                <select name="role" class="form-select" required>
                                                    <option value="umkm" <?= $u['role'] === 'umkm' ? 'selected' : '' ?>>UMKM</option>
                                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Ubah role dengan hati-hati
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-telephone me-1"></i>No. Telepon
                                                </label>
                                                <input type="text" name="no_telepon" class="form-control" 
                                                       value="<?= htmlspecialchars($u['no_telepon'] ?? '') ?>" 
                                                       placeholder="Contoh: 081234567890">
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <small>Password tidak berubah. Gunakan tombol "Reset Password" untuk mengubah password.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="update_user" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reset Password Modal -->
                        <div class="modal fade" id="resetModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-key me-2"></i>Reset Password
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Anda akan mereset password untuk user: <strong><?= htmlspecialchars($u['username']) ?></strong>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Password Baru</label>
                                                <input type="password" name="new_password" class="form-control" 
                                                       required minlength="6" placeholder="Minimal 6 karakter">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="reset_password" class="btn btn-warning">
                                                <i class="bi bi-key me-2"></i>Reset Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-trash me-2"></i>Hapus User
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <div class="alert alert-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan.
                                            </div>
                                            <p>Anda akan menghapus user: <strong><?= htmlspecialchars($u['username']) ?></strong></p>
                                            <?php if ($u['role'] === 'umkm'): ?>
                                                <p class="text-danger">
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                    Semua data UMKM dan produk juga akan terhapus!
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="delete_user" class="btn btn-danger">
                                                <i class="bi bi-trash me-2"></i>Hapus User
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>