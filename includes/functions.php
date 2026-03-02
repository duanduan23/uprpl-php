<?php
// ==================== FUNGSI-FUNGSI UMUM ====================
// File: includes/functions.php - VERSI FINAL DENGAN SMTP

require_once __DIR__ . '/../config/database.php';

// Load PHPMailer
$phpmailer_path = __DIR__ . '/../PHPMailer/src';
require_once $phpmailer_path . '/Exception.php';
require_once $phpmailer_path . '/PHPMailer.php';
require_once $phpmailer_path . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Hash password menggunakan bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifikasi password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%&';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Sanitasi input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validasi nomor HP
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 13;
}

/**
 * Generate nomor order
 */
function generateOrderNumber() {
    $date = date('ymd');
    $random = rand(1000, 9999);
    return "UPRPL{$date}{$random}";
}

/**
 * Generate kode unik
 */
function generateUniqueCode() {
    return rand(100, 999);
}

/**
 * Format rupiah
 */
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Log aktivitas
 */
function logActivity($pdo, $userId, $username, $action, $message, $level = 'info') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, level, message, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $username, $action, $level, $message, $ip, $userAgent]);
    } catch (\Exception $e) {
        // Silent fail
    }
}

/**
 * Log aktivitas admin pusat
 */
function logAdminActivity($pdo, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $username = $_SESSION['admin_pusat_username'] ?? $_SESSION['username'] ?? 'Unknown';
        $userId = $_SESSION['admin_pusat_id'] ?? $_SESSION['user_id'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, username, action, details, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $username, $action, $details, $ip, $userAgent]);
    } catch (\Exception $e) {
        // Silent fail
    }
}

/**
 * Cek login
 */
function isLoggedIn() {
    return isset($_SESSION['admin_pusat_id']) || isset($_SESSION['admin_up_id']) || isset($_SESSION['user_id']);
}

/**
 * Cek role
 */
function isAdminPusat() {
    return isset($_SESSION['admin_pusat_id']);
}

function isAdminUP() {
    return isset($_SESSION['admin_up_id']);
}

/**
 * Require login
 */
function requireLogin() {
    $current_page = basename($_SERVER['PHP_SELF']);
    $login_pages = ['login.php', 'login_pusat.php', 'register.php', 'install.php', 'test.php', 'test_email_smtp.php'];
    
    if (!isLoggedIn() && !in_array($current_page, $login_pages)) {
        $_SESSION['error'] = 'Silakan login terlebih dahulu';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Redirect based on role
 */
function redirectBasedOnRole() {
    if (isset($_SESSION['admin_pusat_id'])) {
        header('Location: ' . BASE_URL . '/admin_pusat.php');
        exit;
    }
    
    if (isset($_SESSION['admin_up_id'])) {
        header('Location: ' . BASE_URL . '/admin_up.php');
        exit;
    }
    
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Upload file
 */
function uploadFile($file, $folder = 'uploads') {
    $targetDir = UPLOAD_PATH . $folder . '/';
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $folder . '/' . $fileName];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

/**
 * KIRIM EMAIL VIA SMTP GMAIL - PASTI WORKING!
 */
function sendEmailViaSMTP($to_email, $to_name, $username, $password) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0; // 0 = off, 1 = client, 2 = client and server
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(ADMIN_EMAIL, 'Admin Pusat');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Login - Unit Produksi RPL';
        
        // Email template
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .password-box { background: #f0f0f0; border-left: 4px solid #667eea; padding: 20px; text-align: center; margin: 20px 0; font-size: 24px; font-weight: bold; letter-spacing: 2px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; }
                .warning { color: #e74c3c; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>UNIT PRODUKSI RPL</h2>
                    <p>SMK Negeri 24 Jakarta</p>
                </div>
                <div class="content">
                    <h3>Halo <strong>' . $to_name . '</strong>,</h3>
                    <p>Anda menerima email ini karena telah melakukan <strong>registrasi/reset password</strong> sebagai Admin Unit Produksi RPL.</p>
                    
                    <p>Berikut adalah informasi login Anda:</p>
                    
                    <table style="width: 100%; margin: 20px 0;">
                        <tr>
                            <td style="padding: 10px; background: #f8f9fa;"><strong>Username:</strong></td>
                            <td style="padding: 10px; background: #f8f9fa;">' . $username . '</td>
                        </tr>
                    </table>
                    
                    <div class="password-box">
                        ' . $password . '
                    </div>
                    
                    <p class="warning">⏰ PASSWORD HANYA BERLAKU ' . PASSWORD_EXPIRY . ' MENIT!</p>
                    
                    <p>Segera login di:</p>
                    <p><a href="' . BASE_URL . '/login.php" class="button">LOGIN SEKARANG</a></p>
                    
                    <p style="margin-top: 30px; font-size: 14px; color: #999;">Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2026 Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->AltBody = "Halo $to_name,\n\nAnda menerima email ini karena telah melakukan registrasi/reset password sebagai Admin Unit Produksi RPL.\n\nUsername: $username\nPassword: $password\n\nPASSWORD HANYA BERLAKU " . PASSWORD_EXPIRY . " MENIT!\n\nLogin di: " . BASE_URL . "/login.php";
        
        $mail->send();
        
        // Log success
        error_log("EMAIL SENT SUCCESSFULLY to: $to_email");
        
        return [
            'success' => true,
            'message' => 'Email berhasil dikirim'
        ];
        
    } catch (Exception $e) {
        // Log error
        error_log("EMAIL ERROR to $to_email: " . $mail->ErrorInfo);
        
        return [
            'success' => false,
            'message' => 'Gagal kirim email: ' . $mail->ErrorInfo
        ];
    }
}

/**
 * Alias untuk fungsi email (biar tidak perlu ubah banyak kode)
 */
function sendEmailViaEmailJS($to_email, $to_name, $username, $password) {
    return sendEmailViaSMTP($to_email, $to_name, $username, $password);
}

/**
 * Get user by ID
 */
function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get user by username
 */
function getUserByUsername($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get order by number
 */
function getOrderByNumber($pdo, $orderNumber) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    return $stmt->fetch();
}

/**
 * Get orders by admin
 */
function getOrdersByAdmin($pdo, $adminUsername, $status = null) {
    $sql = "SELECT * FROM orders WHERE assigned_to = ?";
    $params = [$adminUsername];
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get admin stats
 */
function getAdminStats($pdo, $adminUsername) {
    $stats = [
        'total' => 0,
        'process' => 0,
        'completed' => 0,
        'revenue' => 0
    ];
    
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE assigned_to = ? GROUP BY status");
    $stmt->execute([$adminUsername]);
    while ($row = $stmt->fetch()) {
        if ($row['status'] == 'process') $stats['process'] = $row['count'];
        if ($row['status'] == 'success') $stats['completed'] = $row['count'];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE assigned_to = ?");
    $stmt->execute([$adminUsername]);
    $stats['total'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT SUM(total) as revenue FROM orders WHERE assigned_to = ? AND status = 'success'");
    $stmt->execute([$adminUsername]);
    $stats['revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    return $stats;
}

/**
 * Get admin pusat stats - VERSI FIX TOTAL PENDAPATAN
 */
function getAdminPusatStats($pdo) {
    $stats = [
        'total_admins' => 0,
        'active_admins' => 0,
        'total_orders' => 0,
        'process_orders' => 0,
        'completed_orders' => 0,
        'pending_payments' => 0,
        'total_revenue' => 0,
        'total_emails' => 0
    ];
    
    // Total admin UP
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin_up'");
    $stats['total_admins'] = $stmt->fetch()['total'];
    
    // Admin UP aktif
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin_up' AND status = 'active'");
    $stats['active_admins'] = $stmt->fetch()['total'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    // Orders dalam proses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'process'");
    $stats['process_orders'] = $stmt->fetch()['total'];
    
    // Orders selesai
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'success'");
    $stats['completed_orders'] = $stmt->fetch()['total'];
    
    // Pending payments - dengan try-catch
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE verified = FALSE");
        $stats['pending_payments'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['pending_payments'] = 0;
    }
    
    // ===== FIX: TOTAL PENDAPATAN =====
    // Hitung dari orders yang sudah selesai (status success)
    $stmt = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'success'");
    $stats['total_revenue'] = $stmt->fetch()['revenue'] ?? 0;
    // =================================
    
    // Total email
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM email_logs");
        $stats['total_emails'] = $stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats['total_emails'] = 0;
    }
    
    return $stats;
}

/**
 * Create notification
 */
function createNotification($pdo, $userId, $title, $message, $type = 'info', $target = 'all', $orderNumber = null) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, target, order_number) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $title, $message, $type, $target, $orderNumber]);
}

/**
 * Format tanggal Indonesia
 */
function formatTanggalIndonesia($dateString) {
    if (empty($dateString) || $dateString == '0000-00-00 00:00:00') return '-';
    try {
        $date = new DateTime($dateString);
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $hari = $date->format('d');
        $bulanNum = (int)$date->format('m');
        $tahun = $date->format('Y');
        $jam = $date->format('H:i');
        return $hari . ' ' . $bulan[$bulanNum] . ' ' . $tahun . ' ' . $jam . ' WIB';
    } catch (\Exception $e) {
        return $dateString;
    }
}
?>