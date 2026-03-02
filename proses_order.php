<?php
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp1\php\logs\php_error_log');
error_log("========== PROSES ORDER DIAKSES ==========");
// ==================== PROSES ORDER ====================
// File: proses_order.php - VERSI FIX KAOS SABLON

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request
error_log("PROSES ORDER STARTED: " . print_r($_POST, true));

try {
    // Validasi input
    $nama = sanitize($_POST['nama'] ?? '');
    $kelas = sanitize($_POST['kelas'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $link_drive = sanitize($_POST['link_drive'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $jenis_layanan = $_POST['jenis_layanan'] ?? '';
    $ukuran = sanitize($_POST['ukuran'] ?? '');
    $jenis_sablon = sanitize($_POST['jenis_sablon'] ?? '');
    $warna_kaos = sanitize($_POST['warna_kaos'] ?? '');
    $ukuran_kertas = sanitize($_POST['ukuran_kertas'] ?? '');
    
    // Validasi dasar
    if (empty($nama) || empty($kelas) || empty($telepon) || empty($email) || empty($link_drive) || $jumlah < 1) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        exit;
    }
    
    if (!validateEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
        exit;
    }
    
    if (!validatePhone($telepon)) {
        echo json_encode(['success' => false, 'message' => 'Nomor HP harus 10-13 digit']);
        exit;
    }
    
    if (empty($jenis_layanan)) {
        echo json_encode(['success' => false, 'message' => 'Jenis layanan tidak boleh kosong']);
        exit;
    }
    
    // ========== PARSE JENIS LAYANAN - FIX UNTUK KAOS SABLON ==========
    error_log("JENIS LAYANAN DITERIMA: " . $jenis_layanan);

    // CEK APAKAH INI KAOS SABLON
    if (strpos($jenis_layanan, 'kaos_sablon') !== false) {
        $layanan = 'kaos_sablon';
        
        // Ambil jenis dari akhir string (kaos/sablon/paket)
        if (strpos($jenis_layanan, '_kaos') !== false) {
            $jenis = 'kaos';
        } elseif (strpos($jenis_layanan, '_sablon') !== false) {
            $jenis = 'sablon';
        } elseif (strpos($jenis_layanan, '_paket') !== false) {
            $jenis = 'paket';
        } else {
            $jenis = 'paket'; // default
        }
        
        error_log("KAOS SABLON DETECTED: layanan = $layanan, jenis = $jenis");
    } else {
        // Bukan kaos sablon, langsung pake string aslinya
        $layanan = $jenis_layanan;
        $jenis = '';
        error_log("LAYANAN BIASA: $layanan");
    }
    // ================================================================
    
    // Data layanan
    $layananData = [
        'print_hitam' => ['nama' => 'Print Hitam Putih', 'harga' => 1000],
        'print_warna' => ['nama' => 'Print Full Color', 'harga' => 2000],
        'fotocopy' => ['nama' => 'Fotocopy', 'harga' => 250],
        'kaos_sablon' => [
            'nama' => 'Kaos & Sablon',
            'harga_kaos' => 50000,
            'harga_sablon' => 55000,
            'harga_paket' => 105000
        ]
    ];
    
    // Validasi layanan
    if ($layanan == 'print_hitam' || $layanan == 'print_warna' || $layanan == 'fotocopy' || $layanan == 'kaos_sablon') {
        // Ini valid, lanjutkan
        error_log("LAYANAN VALID: $layanan");
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Layanan tidak valid: ' . $layanan . ' (dari: ' . $jenis_layanan . ')'
        ]);
        exit;
    }
    
    // ========== HITUNG HARGA ==========
    if ($layanan === 'kaos_sablon') {
        // Untuk kaos sablon, cek jenisnya
        if (empty($jenis)) {
            $jenis = 'paket'; // default
        }
        
        $hargaKey = 'harga_' . $jenis;
        if (!isset($layananData['kaos_sablon'][$hargaKey])) {
            echo json_encode(['success' => false, 'message' => 'Jenis paket tidak valid: ' . $jenis]);
            exit;
        }
        
        $hargaSatuan = $layananData['kaos_sablon'][$hargaKey];
        $serviceName = $layananData['kaos_sablon']['nama'] . ' (' . $jenis . ')';
        error_log("KAOS SABLON: harga = $hargaSatuan, nama = $serviceName");
    } else {
        // Untuk layanan biasa (print/fotocopy)
        $hargaSatuan = $layananData[$layanan]['harga'];
        $serviceName = $layananData[$layanan]['nama'];
        error_log("LAYANAN BIASA: harga = $hargaSatuan, nama = $serviceName");
    }
    // ==================================
    
    $subtotal = $hargaSatuan * $jumlah;
    $uniqueCode = generateUniqueCode();
    $total = $subtotal + $uniqueCode;
    $orderNumber = generateOrderNumber();
    
    // ========== SEMUA PESANAN MASUK KE ANTRIAN ==========
    // Tidak auto-assign ke siapapun
    // Semua admin akan lihat pesanan ini sebagai "Menunggu"
    $assigned_to = null;
    $status = 'pending_payment';

    error_log("PESANAN BARU: $orderNumber - $serviceName - Total: $total (MENUNGGU ADMIN)");
    // ====================================================
    
    // ========== SIMPAN KE DATABASE ==========
    $stmt = $pdo->prepare("INSERT INTO orders (
        order_number, customer_name, customer_class, customer_phone, customer_email,
        service, service_name, jumlah, ukuran, jenis_sablon, warna_kaos, ukuran_kertas,
        drive_link, catatan, harga_satuan, subtotal, unique_code, total,
        status, payment_status, assigned_to, created_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, NOW()
    )");
    
    $result = $stmt->execute([
        $orderNumber, $nama, $kelas, $telepon, $email,
        $layanan, $serviceName, $jumlah, $ukuran, $jenis_sablon, $warna_kaos, $ukuran_kertas,
        $link_drive, $catatan, $hargaSatuan, $subtotal, $uniqueCode, $total,
        $status, 'belum_bayar', $assigned_to
    ]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
        exit;
    }
    
    // Log aktivitas
    logActivity($pdo, null, $nama, 'create_order', 'Membuat pesanan baru: ' . $orderNumber . ' (' . $serviceName . ')');
    
    // Return success
    echo json_encode([
        'success' => true,
        'order' => [
            'orderNumber' => $orderNumber,
            'nama' => $nama,
            'kelas' => $kelas,
            'total' => $total,
            'uniqueCode' => $uniqueCode,
            'serviceName' => $serviceName,
            'jumlah' => $jumlah,
            'assigned_to' => $assigned_to
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>