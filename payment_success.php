<?php
// payment_success.php
require_once 'config/database.php';

$orderNumber = $_GET['order'] ?? '';
if ($orderNumber) {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'verified' WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
}

header('Location: index.php?payment=success');