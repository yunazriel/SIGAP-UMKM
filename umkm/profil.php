<?php
// umkm/profil.php
require_once '../config/koneksi.php';
requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Ambil data UMKM
$query = "SELECT * FROM umkm_data WHERE user_id = :user_id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$umkm = $stmt->fetch();

// Handle Update Profil UMKM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_umkm'])) {
    $nama_usaha = sanitize($_POST['nama_usaha']);
    $kategori = sanitize($_POST['kategori']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $alamat = sanitize($_POST['alamat']);
    $no_telepon = sanitize($_POST['no_telepon']);
    $email = sanitize($_POST['email']);
    $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;
    $omzet_bulanan = floatval($_POST['omzet_bulanan']);
    $jumlah_karyawan = intval($_POST['jumlah_karyawan']);
    
    // Handle foto usaha
    $foto_usaha = $umkm['foto_usaha'] ?? '';
    if (isset($_FILES['foto_usaha']) && $_FILES['foto_usaha']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_usaha']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Hapus foto lama jika ada
            if ($foto_usaha && file_exists("../uploads/umkm_profile/" . $foto_usaha)) {
                unlink("../uploads/umkm_profile/" . $foto_usaha);
            }
            
            // Buat folder jika belum ada
            if (!is_dir('../uploads/umkm_profile')) {
                mkdir('../uploads/umkm_profile', 0777, true);
            }
            
            $new_filename = 'umkm_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = "../uploads/umkm_profile/$new_filename";
            
            if (move_uploaded_file($_FILES['foto_usaha']['tmp_name'], $upload_path)) {
                $foto_usaha = $new_filename;
            }
        } else {
            $error = 'Format foto tidak valid! Gunakan JPG, PNG, atau GIF.';
        }
    }
    
    if (!$error) {
        try {
            if ($umkm) {
                // Update
                $query = "UPDATE umkm_data SET 
                          nama_usaha = :nama_usaha,
                          kategori = :kategori,
                          deskripsi = :deskripsi,
                          alamat = :alamat,
                          no_telepon = :no_telepon,
                          email = :email,
                          lat = :lat,
                          lng = :lng,
                          omzet_bulanan = :omzet,
                          jumlah_karyawan = :karyawan,
                          foto_usaha = :foto
                          WHERE user_id = :user_id";
                $stmt = $conn->prepare($query);
            } else {
                // Insert
                $query = "INSERT INTO umkm_data 
                          (user_id, nama_usaha, kategori, deskripsi, alamat, no_telepon, email, lat, lng, omzet_bulanan, jumlah_karyawan, foto_usaha) 
                          VALUES 
                          (:user_id, :nama_usaha, :kategori, :deskripsi, :alamat, :no_telepon, :email, :lat, :lng, :omzet, :karyawan, :foto)";
                $stmt = $conn->prepare($query);
            }
            
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':nama_usaha', $nama_usaha);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':alamat', $alamat);
            $stmt->bindParam(':no_telepon', $no_telepon);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->bindParam(':omzet', $omzet_bulanan);
            $stmt->bindParam(':karyawan', $jumlah_karyawan);
            $stmt->bindParam(':foto', $foto_usaha);
            
            if ($stmt->execute()) {
                $success = 'Profil UMKM berhasil diperbarui!';
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM umkm_data WHERE user_id = :user_id LIMIT 1");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $umkm = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    }
}

// Ambil data user
$query = "SELECT * FROM users WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil UMKM - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f8f9fa; }
        
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
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .form-card h5 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .foto-preview-container {
            position: relative;
            display: inline-block;
        }
        
        .foto-preview {
            max-width: 300px;
            max-height: 300px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
        }
        
        .foto-placeholder {
            width: 300px;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            margin-top: 1rem;
        }
        
        #map {
            height: 400px;
            border-radius: 15px;
            margin-top: 1rem;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .profile-settings-link {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        
        .profile-settings-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
            <h1><i class="bi bi-shop me-2"></i>Profil UMKM</h1>
            <p class="mb-0 opacity-90">Kelola informasi lengkap tentang usaha Anda</p>
        </div>
        
        <!-- Link to Account Settings -->
        <a href="settings.php" class="profile-settings-link">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-person-gear text-primary fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold">Pengaturan Akun Pengguna</h6>
                        <small class="text-muted">Ubah foto profil, password, dan info akun</small>
                    </div>
                </div>
                <i class="bi bi-chevron-right fs-4 text-muted"></i>
            </div>
        </a>
        
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
        
        <form method="POST" enctype="multipart/form-data">
            
            <!-- Informasi Dasar -->
            <div class="form-card">
                <h5><i class="bi bi-info-circle me-2"></i>Informasi Dasar</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Usaha <span class="text-danger">*</span></label>
                        <input type="text" name="nama_usaha" class="form-control" required
                               value="<?= $umkm ? htmlspecialchars($umkm['nama_usaha']) : '' ?>"
                               placeholder="Contoh: Batik Semarang Jaya">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori Usaha <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php
                            $categories = ['Kuliner', 'Fashion', 'Kerajinan', 'Jasa', 'Perdagangan', 'Lainnya'];
                            foreach ($categories as $cat) {
                                $selected = ($umkm && $umkm['kategori'] === $cat) ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Deskripsi Usaha</label>
                        <textarea name="deskripsi" class="form-control" rows="4" 
                                  placeholder="Ceritakan tentang usaha Anda..."><?= $umkm ? htmlspecialchars($umkm['deskripsi']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Foto Usaha</label>
                        <input type="file" name="foto_usaha" class="form-control" accept="image/*"
                               onchange="previewImage(event)">
                        <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB). Foto lama akan terhapus otomatis jika upload baru.</small>
                        
                        <?php if ($umkm && $umkm['foto_usaha']): ?>
                            <div class="foto-preview-container">
                                <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                     class="foto-preview" id="preview" alt="Foto Usaha">
                            </div>
                        <?php else: ?>
                            <div class="foto-placeholder d-none" id="preview">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Kontak & Lokasi -->
            <div class="form-card">
                <h5><i class="bi bi-geo-alt me-2"></i>Kontak & Lokasi</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="tel" name="no_telepon" class="form-control" required
                               value="<?= $umkm ? htmlspecialchars($umkm['no_telepon']) : '' ?>"
                               placeholder="Contoh: 081234567890">
                        <small class="text-muted">Nomor ini akan ditampilkan di website publik</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= $umkm ? htmlspecialchars($umkm['email']) : '' ?>"
                               placeholder="Contoh: usaha@email.com">
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="3" required
                                  placeholder="Contoh: Jl. Pandanaran No. 123, Semarang"><?= $umkm ? htmlspecialchars($umkm['alamat']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="lat" id="lat" class="form-control" 
                               value="<?= $umkm && $umkm['lat'] ? $umkm['lat'] : '' ?>"
                               placeholder="Contoh: -6.9825">
                        <small class="text-muted">Klik pada peta untuk set lokasi</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="lng" id="lng" class="form-control"
                               value="<?= $umkm && $umkm['lng'] ? $umkm['lng'] : '' ?>"
                               placeholder="Contoh: 110.4094">
                        <small class="text-muted">Klik pada peta untuk set lokasi</small>
                    </div>
                    
                    <div class="col-12">
                        <div class="info-box">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Cara set lokasi:</strong> Klik pada peta di bawah untuk menandai lokasi usaha Anda
                        </div>
                        <div id="map"></div>
                    </div>
                </div>
            </div>
            
            <!-- Data Usaha -->
            <div class="form-card">
                <h5><i class="bi bi-graph-up me-2"></i>Data Usaha</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Omzet Bulanan (Rp)</label>
                        <input type="number" name="omzet_bulanan" class="form-control" min="0" step="100000"
                               value="<?= $umkm ? $umkm['omzet_bulanan'] : '' ?>"
                               placeholder="Contoh: 5000000">
                        <small class="text-muted">Data ini hanya untuk pemantauan admin</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah Karyawan</label>
                        <input type="number" name="jumlah_karyawan" class="form-control" min="0"
                               value="<?= $umkm ? $umkm['jumlah_karyawan'] : '' ?>"
                               placeholder="Contoh: 5">
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" name="update_umkm" class="btn btn-gradient btn-lg px-5">
                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                </button>
            </div>
            
        </form>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Preview image
        function previewImage(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.outerHTML = '<img src="' + e.target.result + '" class="foto-preview" id="preview" alt="Preview">';
                    }
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Initialize Map
        const defaultLat = <?= $umkm && $umkm['lat'] ? $umkm['lat'] : '-6.9825' ?>;
        const defaultLng = <?= $umkm && $umkm['lng'] ? $umkm['lng'] : '110.4094' ?>;
        
        const map = L.map('map').setView([defaultLat, defaultLng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        
        // Update koordinat saat marker di-drag
        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            document.getElementById('lat').value = pos.lat.toFixed(8);
            document.getElementById('lng').value = pos.lng.toFixed(8);
        });
        
        // Set marker dengan klik
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(8);
            document.getElementById('lng').value = e.latlng.lng.toFixed(8);
        });
    </script>
    
</body>
</html>