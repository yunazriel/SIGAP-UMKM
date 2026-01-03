<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5><i class="bi bi-geo-alt-fill me-2"></i>SIGAP-UMKM</h5>
                <p class="text-white-50">Sistem Informasi Geografis dan Pemantauan UMKM Kota Semarang</p>
            </div>
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5>Kontak</h5>
                <p class="mb-1">
                    <i class="bi bi-envelope me-2"></i>
                    <a href="mailto:info@sigap-umkm.semarang.go.id">info@sigap-umkm.semarang.go.id</a>
                </p>
                <p>
                    <i class="bi bi-telephone me-2"></i>
                    <a href="tel:+622483456789">(024) 8345 6789</a>
                </p>
            </div>
            <div class="col-lg-4">
                <h5>Alamat</h5>
                <p class="text-white-50">
                    <i class="bi bi-building me-2"></i>
                    Pemkot Semarang<br>
                    Jl. Pemuda No. 148, Semarang
                </p>
            </div>
        </div>
        <hr class="my-4 bg-white opacity-25">
        <div class="text-center text-white-50">
            <p class="mb-0">&copy; <?= date('Y') ?> SIGAP-UMKM Kota Semarang. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    .footer {
        background: #2d3748;
        color: white;
        padding: 3rem 0 1.5rem;
        margin-top: 4rem;
    }
    
    .footer h5 {
        font-weight: 600;
        margin-bottom: 1.5rem;
    }
    
    .footer a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer a:hover {
        color: white;
    }
</style>