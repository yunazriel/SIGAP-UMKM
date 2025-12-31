-- ============================================
-- Database: sigap_umkm - FULL VERSION
-- Sistem Informasi Geografis UMKM Semarang
-- ============================================

-- Drop database jika ingin reset total (HATI-HATI!)
-- DROP DATABASE IF EXISTS sigap_umkm;

CREATE DATABASE IF NOT EXISTS sigap_umkm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigap_umkm;

-- ============================================
-- Tabel Users (dengan foto profil & telepon)
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'umkm') NOT NULL DEFAULT 'umkm',
    foto_profil VARCHAR(255) NULL,
    no_telepon VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- Tabel UMKM Data (dengan email & telepon)
-- ============================================
CREATE TABLE umkm_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_usaha VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    alamat TEXT NOT NULL,
    no_telepon VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    omzet_bulanan DECIMAL(15, 2),
    jumlah_karyawan INT DEFAULT 0,
    status_verifikasi ENUM('pending', 'terverifikasi', 'ditolak') DEFAULT 'pending',
    foto_usaha VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status_verifikasi),
    INDEX idx_lokasi (lat, lng),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB;

-- ============================================
-- Tabel Produk (dengan deskripsi)
-- ============================================
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    umkm_id INT NOT NULL,
    nama_produk VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    harga DECIMAL(12, 2) NOT NULL,
    foto_produk VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (umkm_id) REFERENCES umkm_data(id) ON DELETE CASCADE,
    INDEX idx_umkm (umkm_id)
) ENGINE=InnoDB;

-- ============================================
-- Tabel Password Resets
-- ============================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- Insert Default Admin
-- ============================================
INSERT INTO users (username, password, role, created_at) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());
-- Password: password (WAJIB GANTI setelah login pertama!)

-- ============================================
-- Insert Sample UMKM untuk Testing
-- ============================================
INSERT INTO users (username, password, role, no_telepon, created_at) VALUES 
('umkm_demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'umkm', '081234567890', NOW());
-- Password: password

INSERT INTO umkm_data (user_id, nama_usaha, kategori, deskripsi, alamat, no_telepon, email, lat, lng, omzet_bulanan, jumlah_karyawan, status_verifikasi, created_at) VALUES
(2, 'Batik Semarang Jaya', 'Fashion', 'Produsen batik khas Semarang dengan motif tradisional dan modern berkualitas tinggi', 'Jl. Pandanaran No. 123, Semarang Tengah', '081234567890', 'batik.semarang@email.com', -6.9825, 110.4094, 25000000, 8, 'terverifikasi', NOW()),
(2, 'Lumpia Gang Lombok', 'Kuliner', 'Lumpia semarang legendaris sejak 1985 dengan resep turun temurun', 'Gang Lombok No. 5, Semarang', '081234567891', 'lumpia.lombok@email.com', -6.9667, 110.4208, 15000000, 5, 'terverifikasi', NOW());

-- ============================================
-- Insert Sample Produk untuk Testing
-- ============================================
INSERT INTO produk (umkm_id, nama_produk, deskripsi, harga, created_at) VALUES
(1, 'Batik Tulis Motif Asem Arang', 'Batik tulis premium dengan motif khas Semarang', 450000, NOW()),
(1, 'Batik Cap Motif Tugu Muda', 'Batik cap berkualitas dengan motif ikonik Semarang', 250000, NOW()),
(2, 'Lumpia Basah Isi Rebung', 'Lumpia basah dengan isian rebung segar', 25000, NOW()),
(2, 'Lumpia Goreng Original', 'Lumpia goreng renyah khas Semarang', 30000, NOW());

-- ============================================
-- Struktur Folder yang Diperlukan
-- ============================================
-- Folder akan dibuat otomatis oleh PHP:
-- uploads/
--   ├── users/          (foto profil user/admin)
--   ├── umkm_profile/   (foto usaha UMKM)
--   └── produk/         (foto produk)

-- ============================================
-- SELESAI! Database siap digunakan
-- ============================================
-- Jangan lupa:
-- 1. Ganti password admin default setelah login
-- 2. Buat folder uploads dengan permission 0777
-- 3. Test login dengan username: admin, password: password
-- ============================================