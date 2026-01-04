<?PHP
    require_once 'config/koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - SIGAP-UMKM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .info-card .icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .info-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .info-card p {
            color: #718096;
            line-height: 1.8;
            margin: 0;
        }

        /* Features Section */
        .features-section {
            padding: 80px 0;
        }

        .feature-item {
            display: flex;
            align-items: start;
            gap: 1.5rem;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .feature-item:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateX(10px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .feature-item h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .feature-item p {
            color: #718096;
            margin: 0;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin: 80px 0;
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
        }

        .stat-item .number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-item .label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Team Section */
        .team-section {
            padding: 80px 0;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }

        .team-card h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .team-card .role {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }

        .team-card p {
            color: #718096;
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
        }

        .btn-cta {
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: all 0.3s;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Navbar (sama seperti index.php Anda) -->
     <?php include './components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <h1 class="text-center">Tentang SIGAP-UMKM</h1>
            <p class="text-center">
                Sistem Informasi Geografis UMKM Kota Semarang - Platform digital untuk 
                memberdayakan dan mengembangkan UMKM lokal menuju era digital
            </p>
        </div>
    </section>

    <!-- What is SIGAP -->
    <section class="py-5">
        <div class="container my-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-lightbulb-fill"></i>
                        </div>
                        <h3>Apa itu SIGAP-UMKM?</h3>
                        <p>
                            Platform digital berbasis web yang menghubungkan UMKM Kota Semarang 
                            dengan konsumen melalui peta interaktif, katalog produk, dan informasi lengkap.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h3>Visi Kami</h3>
                        <p>
                            Menjadi platform terdepan dalam pemberdayaan UMKM Kota Semarang 
                            yang mendorong pertumbuhan ekonomi lokal berkelanjutan.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-flag-fill"></i>
                        </div>
                        <h3>Misi Kami</h3>
                        <p>
                            Memfasilitasi digitalisasi UMKM, meningkatkan visibility bisnis lokal, 
                            dan mempermudah akses konsumen ke produk UMKM berkualitas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Fitur Unggulan</h2>
                <p>Berbagai fitur yang memudahkan UMKM dan konsumen</p>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="feature-item">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-map-fill"></i>
                        </div>
                        <div>
                            <h4>Peta Interaktif UMKM</h4>
                            <p>Temukan lokasi UMKM terdekat dengan mudah menggunakan peta digital yang terintegrasi dengan Google Maps</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-shop-window"></i>
                        </div>
                        <div>
                            <h4>Katalog Produk Lengkap</h4>
                            <p>Jelajahi berbagai produk UMKM dari kuliner, fashion, kerajinan, hingga jasa dengan informasi detail</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h4>Verifikasi UMKM</h4>
                            <p>Sistem verifikasi memastikan kredibilitas dan kualitas UMKM yang terdaftar dalam platform</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="feature-item">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div>
                            <h4>Dashboard UMKM</h4>
                            <p>Panel kontrol lengkap untuk mengelola profil usaha, produk, dan melihat statistik performa bisnis</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <h4>Analitik & Laporan</h4>
                            <p>Dapatkan insight bisnis melalui data omzet, jumlah karyawan, dan perkembangan UMKM</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-purple bg-opacity-10 text-purple" style="color: #764ba2 !important;">
                            <i class="bi bi-phone"></i>
                        </div>
                        <div>
                            <h4>Responsif & Mobile-Friendly</h4>
                            <p>Akses platform dari berbagai perangkat dengan tampilan yang optimal dan mudah digunakan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="number">100+</span>
                        <span class="label">UMKM Terdaftar</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="number">500+</span>
                        <span class="label">Produk Tersedia</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="number">16</span>
                        <span class="label">Kecamatan Terjangkau</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="number">24/7</span>
                        <span class="label">Akses Platform</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="team-section">
        <div class="container">
            <div class="section-title">
                <h2>Tim Pengembang</h2>
                <p>Orang-orang di balik SIGAP-UMKM</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h4>Tim Developer</h4>
                        <span class="role">Full-Stack Development</span>
                        <p>Bertanggung jawab atas pengembangan sistem, database, dan integrasi fitur</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="bi bi-palette-fill"></i>
                        </div>
                        <h4>Tim Designer</h4>
                        <span class="role">UI/UX Design</span>
                        <p>Merancang antarmuka yang user-friendly dan experience yang optimal</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <div class="team-avatar">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <h4>Tim Admin</h4>
                        <span class="role">System Management</span>
                        <p>Mengelola konten, verifikasi UMKM, dan maintenance sistem</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Bergabung?</h2>
            <p>Daftarkan UMKM Anda sekarang dan raih peluang pasar lebih luas!</p>
            <a href="auth/register.php" class="btn btn-light btn-cta">
                <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
            </a>
        </div>
    </section>

    <!-- Footer -->
    <?php include './components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>