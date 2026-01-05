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

// HANDLER UPDATE PROFIL UMKM
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
    
    // ================================
    // VALIDASI PANJANG DESKRIPSI (1000 karakter)
    // ================================
    $max_deskripsi = 1000;
    if (strlen($deskripsi) > $max_deskripsi) {
        $error = "Deskripsi terlalu panjang! Maksimal $max_deskripsi karakter.";
    }
    
    // Handle foto usaha
    $foto_usaha = $umkm['foto_usaha'] ?? '';
    
    // ✅ CEK APAKAH USER INGIN HAPUS FOTO
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1') {
        // Hapus file foto lama jika ada
        if ($foto_usaha && file_exists("../uploads/umkm_profile/" . $foto_usaha)) {
            unlink("../uploads/umkm_profile/" . $foto_usaha);
        }
        $foto_usaha = ''; // Set kosong (akan pakai default)
    }
    // ✅ CEK APAKAH ADA UPLOAD FOTO BARU
    elseif (isset($_FILES['foto_usaha']) && $_FILES['foto_usaha']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_usaha']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Validasi ukuran file (max 2MB)
            if ($_FILES['foto_usaha']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto terlalu besar! Maksimal 2MB.';
            } else {
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
                } else {
                    $error = 'Gagal mengupload foto.';
                }
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

        .btn-delete-apple {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(20px);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0;
        }

        .upload-area:hover .btn-delete-apple {
            opacity: 1;
        }

        .btn-delete-apple:hover {
            background: rgba(220, 38, 38, 0.8);
            transform: scale(1.05);
        }
        
        /* STYLE UNTUK COUNTER DESKRIPSI */
        .deskripsi-counter {
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 2px 8px;
            border-radius: 6px;
            background: transparent;
        }
        
        .deskripsi-counter.normal {
            color: #6c757d;
            background: transparent;
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
        
        .deskripsi-counter .count {
            font-weight: 700;
        }
        
        .textarea-deskripsi {
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .textarea-deskripsi:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .textarea-deskripsi.border-warning {
            border-color: #f59e0b;
        }
        
        .textarea-deskripsi.border-danger {
            border-color: #ef4444;
        }
        
        /* TOOLTIP STYLE */
        .tooltip-custom {
            --bs-tooltip-bg: #667eea;
            --bs-tooltip-color: white;
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
        
        <form method="POST" enctype="multipart/form-data" id="umkmForm">
            
            <!-- ================================ -->
            <!-- INFORMASI DASAR (YANG BARU) -->
            <!-- ================================ -->
            <div class="form-card">
                <h5><i class="bi bi-info-circle me-2"></i>Informasi Dasar</h5>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-shop me-2 text-primary"></i>Nama Usaha <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_usaha" class="form-control py-2" required
                                    value="<?= $umkm ? htmlspecialchars($umkm['nama_usaha']) : '' ?>"
                                    placeholder="Contoh: Batik Semarang Jaya">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-tag me-2 text-success"></i>Kategori Usaha <span class="text-danger">*</span>
                                </label>
                                <select name="kategori" class="form-select py-2" required>
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
                            
                            <div class="col-12 mb-2">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-file-text me-2 text-info"></i>Deskripsi Usaha 
                                    <span class="badge bg-light text-dark ms-2" data-bs-toggle="tooltip" 
                                          data-bs-placement="top" 
                                          title="Maksimal 1000 karakter">
                                        <i class="bi bi-info-circle me-1"></i>Maks. 1000 karakter
                                    </span>
                                </label>
                                <textarea name="deskripsi" 
                                          class="form-control textarea-deskripsi" 
                                          rows="8" 
                                          maxlength="1000"
                                          oninput="updateCharCount(this)"
                                          placeholder="Ceritakan tentang usaha Anda... (Maksimal 1000 karakter)"
                                          id="deskripsi"><?= $umkm ? htmlspecialchars($umkm['deskripsi']) : '' ?></textarea>
                                
                                <!-- Counter karakter -->
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-keyboard me-1"></i>Tulis deskripsi yang menarik untuk menarik pelanggan
                                    </small>
                                    <div>
                                        <span class="deskripsi-counter normal" id="charCounter">
                                            <span id="currentChars">0</span>/1000 karakter
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Progress bar mini (opsional) -->
                                <div class="progress mt-1" style="height: 4px; border-radius: 2px;">
                                    <div id="charProgress" class="progress-bar" 
                                         role="progressbar" 
                                         style="width: 0%; background: #667eea;"
                                         aria-valuenow="0" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-camera me-2 text-warning"></i>Foto Usaha
                        </label>
                        
                        <div class="upload-area" style="position: relative; border: 3px dashed #e2e8f0; border-radius: 20px; padding: 1rem; text-align: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 300px; display: flex; flex-direction: column; justify-content: center; align-items: center; cursor: pointer; transition: all 0.3s;" onclick="document.getElementById('foto_input').click()">
                            
                            <?php if ($umkm && $umkm['foto_usaha']): ?>
                                <button type="button" class="btn-delete-apple" onclick="hapusFoto(event)">
                                    <i class="bi bi-x-lg"></i>
                                </button>

                                <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                    class="img-fluid rounded-3 shadow" id="preview" 
                                    style="max-height: 280px; object-fit: cover; width: 100%;" 
                                    alt="Foto Usaha">
                                <div class="mt-2">
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Foto Tersimpan
                                    </span>
                                </div>
                            <?php else: ?>
                                <div id="preview" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <i class="bi bi-cloud-upload text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3 mb-0 fw-semibold">Klik untuk upload foto</p>
                                    <small class="text-muted">atau drag & drop disini</small>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" name="foto_usaha" class="d-none" id="foto_input" accept="image/*"
                                onchange="previewImage(event)">
                            
                            <!-- Hidden input untuk flag hapus foto -->
                            <input type="hidden" name="hapus_foto" id="hapus_foto" value="0">
                        </div>
                        
                        <div class="mt-2 text-center">
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-info-circle me-1"></i>JPG, PNG, GIF (Max 2MB)
                            </small>
                        </div>
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
        // ============================================
        // FUNGSI COUNTER DESKRIPSI
        // ============================================
        function updateCharCount(textarea) {
            const charCount = textarea.value.length;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            const counter = document.getElementById('charCounter');
            const currentChars = document.getElementById('currentChars');
            const progressBar = document.getElementById('charProgress');
            
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
                // Jika melebihi batas (ini tidak akan terjadi karena ada maxlength, tapi backup saja)
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
        
        // VALIDASI FORM SEBELUM SUBMIT
        document.getElementById('umkmForm').addEventListener('submit', function(e) {
            const deskripsi = document.getElementById('deskripsi');
            const charCount = deskripsi.value.length;
            const maxLength = parseInt(deskripsi.getAttribute('maxlength'));
            
            if (charCount > maxLength) {
                e.preventDefault();
                
                // Tampilkan alert Bootstrap
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show rounded-4 mt-3';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Deskripsi terlalu panjang!</strong> Maksimal ${maxLength} karakter. Anda telah menulis ${charCount} karakter.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Sisipkan alert di atas form
                const form = document.getElementById('umkmForm');
                form.parentNode.insertBefore(alertDiv, form);
                
                // Fokus ke textarea deskripsi
                deskripsi.focus();
                deskripsi.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                return false;
            }
        });
        
        // FUNGSI PREVIEW IMAGE
        function previewImage(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            
            if (file) {
                // Validasi ukuran file (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    // Tampilkan alert Bootstrap
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show rounded-4';
                    alertDiv.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Ukuran file terlalu besar! Maksimal 2MB.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Cari alert container atau buat baru
                    let alertContainer = document.querySelector('.alert-container');
                    if (!alertContainer) {
                        alertContainer = document.createElement('div');
                        alertContainer.className = 'alert-container';
                        const contentWrapper = document.querySelector('.content-wrapper');
                        const pageHeader = document.querySelector('.page-header');
                        contentWrapper.insertBefore(alertContainer, pageHeader.nextSibling);
                    }
                    
                    alertContainer.appendChild(alertDiv);
                    
                    // Auto remove alert setelah 5 detik
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 5000);
                    
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Jika preview adalah div (belum ada foto)
                    if (preview.tagName === 'DIV') {
                        preview.innerHTML = `
                            <img src="${e.target.result}" 
                                 class="img-fluid rounded-3 shadow" 
                                 style="max-height: 280px; object-fit: cover; width: 100%;" 
                                 alt="Preview">
                            <div class="mt-2">
                                <span class="badge bg-info">
                                    <i class="bi bi-image me-1"></i>Foto Baru (Belum Disimpan)
                                </span>
                            </div>
                        `;
                    } else {
                        // Jika preview adalah img (sudah ada foto)
                        preview.src = e.target.result;
                    }
                    
                    // Reset hapus_foto flag
                    document.getElementById('hapus_foto').value = '0';
                }
                reader.readAsDataURL(file);
            }
        }
        
        // ============================================
        // FUNGSI HAPUS FOTO
        // ============================================
        function hapusFoto(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Tampilkan modal konfirmasi Bootstrap
            const modalHTML = `
                <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title text-danger">
                                    <i class="bi bi-trash3 me-2"></i>Hapus Foto
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">Yakin ingin menghapus foto usaha?</h5>
                                <p class="text-muted">Foto akan dihapus permanen saat Anda menyimpan perubahan.</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-lg me-1"></i>Batal
                                </button>
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteFoto()">
                                    <i class="bi bi-trash3 me-1"></i>Ya, Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Tambahkan modal ke body
            let modal = document.getElementById('confirmDeleteModal');
            if (!modal) {
                const modalDiv = document.createElement('div');
                modalDiv.innerHTML = modalHTML;
                document.body.appendChild(modalDiv);
                modal = document.getElementById('confirmDeleteModal');
            }
            
            // Tampilkan modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
        
        // Fungsi konfirmasi hapus foto
        function confirmDeleteFoto() {
            const preview = document.getElementById('preview');
            
            // Set flag hapus foto
            document.getElementById('hapus_foto').value = '1';
            
            // Reset input file
            document.getElementById('foto_input').value = '';
            
            // Ganti dengan tampilan default
            preview.outerHTML = `
                <div id="preview" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="bi bi-cloud-upload text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0 fw-semibold">Klik untuk upload foto</p>
                    <small class="text-muted">atau drag & drop disini</small>
                    <div class="mt-2">
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-exclamation-triangle me-1"></i>Foto akan dihapus saat disimpan
                        </span>
                    </div>
                </div>
            `;
            
            // Tutup modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
            modal.hide();
            
            // Tampilkan notifikasi
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show rounded-4 mt-3';
            alertDiv.innerHTML = `
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Foto akan dihapus!</strong> Pastikan untuk menyimpan perubahan untuk menghapus foto.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.content-wrapper').insertBefore(alertDiv, document.querySelector('.page-header').nextSibling);
        }
        
        // ============================================
        // DRAG & DROP FUNCTIONALITY
        // ============================================
        const uploadArea = document.querySelector('.upload-area');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#667eea';
            uploadArea.style.background = 'linear-gradient(135deg, #667eea20 0%, #764ba220 100%)';
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#e2e8f0';
            uploadArea.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#e2e8f0';
            uploadArea.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('foto_input').files = files;
                previewImage({ target: { files: files } });
            }
        });
        
        // ============================================
        // LEAFLET MAP
        // ============================================
        const defaultLat = <?= $umkm && $umkm['lat'] ? $umkm['lat'] : '-6.9825' ?>;
        const defaultLng = <?= $umkm && $umkm['lng'] ? $umkm['lng'] : '110.4094' ?>;
        
        const map = L.map('map').setView([defaultLat, defaultLng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        
        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            document.getElementById('lat').value = pos.lat.toFixed(8);
            document.getElementById('lng').value = pos.lng.toFixed(8);
        });
        
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(8);
            document.getElementById('lng').value = e.latlng.lng.toFixed(8);
        });
        
        // ============================================
        // INITIALIZE TOOLTIPS
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    customClass: 'tooltip-custom'
                });
            });
            
            // Initialize char counter saat halaman load
            const deskripsi = document.getElementById('deskripsi');
            if (deskripsi) {
                updateCharCount(deskripsi);
            }
        });
    </script>
    
</body>
</html>