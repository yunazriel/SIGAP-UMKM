<?php
// Modal untuk Detail, Status, dan Delete
?>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal<?= $umkm['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>Detail UMKM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <?php if ($umkm['foto_usaha']): ?>
                            <img src="../uploads/umkm_profile/<?= htmlspecialchars($umkm['foto_usaha']) ?>" 
                                 class="img-fluid rounded" style="border-radius: 12px !important;" 
                                 alt="<?= htmlspecialchars($umkm['nama_usaha']) ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white p-5 rounded">
                                <i class="bi bi-image fs-1"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h4 class="fw-bold mb-3"><?= htmlspecialchars($umkm['nama_usaha']) ?></h4>
                        
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Kategori</strong></td>
                                <td><?= htmlspecialchars($umkm['kategori']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Pemilik</strong></td>
                                <td><?= htmlspecialchars($umkm['username']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td><?= $umkm['email'] ? htmlspecialchars($umkm['email']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telepon</strong></td>
                                <td><?= $umkm['no_telepon'] ? htmlspecialchars($umkm['no_telepon']) : '-' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Alamat</strong></td>
                                <td><?= htmlspecialchars($umkm['alamat']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Koordinat</strong></td>
                                <td>
                                    <?php if ($umkm['lat'] && $umkm['lng']): ?>
                                        <?= $umkm['lat'] ?>, <?= $umkm['lng'] ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Omzet Bulanan</strong></td>
                                <td>Rp <?= number_format($umkm['omzet_bulanan'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Jumlah Karyawan</strong></td>
                                <td><?= $umkm['jumlah_karyawan'] ?> orang</td>
                            </tr>
                            <tr>
                                <td><strong>Jumlah Produk</strong></td>
                                <td><?= $umkm['jumlah_produk'] ?> item</td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>
                                    <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>">
                                        <?= ucfirst($umkm['status_verifikasi']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        <?php if ($umkm['deskripsi']): ?>
                        <div class="mt-3">
                            <strong>Deskripsi:</strong>
                            <p class="text-muted"><?= htmlspecialchars($umkm['deskripsi']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal<?= $umkm['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-gear me-2"></i>Ubah Status Verifikasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                    <p>UMKM: <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong></p>
                    <p>Status saat ini: <span class="badge <?= $badge_class[$umkm['status_verifikasi']] ?>"><?= ucfirst($umkm['status_verifikasi']) ?></span></p>
                    
                    <label class="form-label fw-bold mt-3">Ubah Status Menjadi:</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" <?= $umkm['status_verifikasi'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="terverifikasi" <?= $umkm['status_verifikasi'] === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                        <option value="ditolak" <?= $umkm['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_status" class="btn btn-warning text-white">
                        <i class="bi bi-check me-2"></i>Ubah Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal<?= $umkm['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-trash me-2"></i>Hapus UMKM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="umkm_id" value="<?= $umkm['id'] ?>">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <p>Anda akan menghapus UMKM: <strong><?= htmlspecialchars($umkm['nama_usaha']) ?></strong></p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Semua data produk, foto, dan informasi terkait akan terhapus!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="delete_umkm" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Hapus UMKM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>