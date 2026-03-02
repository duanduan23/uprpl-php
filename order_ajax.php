<?php
// ==================== ORDER AJAX ====================
// File: order_ajax.php - VERSI DENGAN BATASAN AMBIL PESANAN

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_up_id']) && !isset($_SESSION['admin_pusat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Tentukan user yang login
if (isset($_SESSION['admin_up_id'])) {
    $userId = $_SESSION['admin_up_id'];
    $username = $_SESSION['admin_up_username'];
    $role = 'admin_up';
} else {
    $userId = $_SESSION['admin_pusat_id'];
    $username = $_SESSION['admin_pusat_username'];
    $role = 'super_admin';
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'complete_order':
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Cek apakah order ini milik admin yang login (jika admin UP)
            if ($role === 'admin_up') {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND assigned_to = ?");
                $stmt->execute([$orderNumber, $username]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
                $stmt->execute([$orderNumber]);
            }
            
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE orders SET status = 'success', completed_at = NOW() WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            
            logActivity($pdo, $userId, $username, 'complete_order', "Menyelesaikan order: $orderNumber");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Order selesai']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
        
    case 'take_order':
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // ========== CEK BATASAN PESANAN PER ADMIN ==========
            // Cek apakah user ini udah pegang berapa pesanan yang masih proses
            $stmt_cek = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE assigned_to = ? AND status = 'process'");
            $stmt_cek->execute([$username]);
            $jumlah_pegang = $stmt_cek->fetch()['total'];
            
            // Batasi maksimal 3 pesanan per admin (bisa diubah angkanya)
            $MAX_PESANAN = 3; // Ganti angka ini sesuai keinginan
            
            if ($jumlah_pegang >= $MAX_PESANAN) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Kamu sudah memegang $jumlah_pegang pesanan. Selesaikan dulu sebelum mengambil yang baru! (Maksimal $MAX_PESANAN)"
                ]);
                exit;
            }
            // ===================================================
            
            // Cek apakah order masih available (belum diassign)
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND assigned_to IS NULL");
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan sudah diambil orang lain']);
                exit;
            }
            
            // Update assigned_to dan status
            $stmt = $pdo->prepare("UPDATE orders SET assigned_to = ?, status = 'process' WHERE order_number = ?");
            $stmt->execute([$username, $orderNumber]);
            
            // Log aktivitas
            logActivity($pdo, $userId, $username, 'take_order', "Mengambil pesanan: $orderNumber");
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pesanan berhasil diambil',
                'sisa_kuota' => $MAX_PESANAN - ($jumlah_pegang + 1)
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
}
?>