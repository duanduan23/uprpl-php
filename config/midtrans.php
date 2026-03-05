<?php
// ==================== KONFIGURASI MIDTRANS ====================
// File: config/midtrans.php

// ==== GANTI DENGAN PUNYA LO ====
define('MIDTRANS_SERVER_KEY', 'Mid-server-tOX-ANDRrgZY7EUef-dkuM-x');
define('MIDTRANS_CLIENT_KEY', 'Mid-client-1ReZBYV2yaTuE9Z_');
define('MIDTRANS_IS_PRODUCTION', false); // false = mode sandbox

// URL callback (ganti kalo udah online)
define('MIDTRANS_SUCCESS_URL', 'http://localhost/uprpl-php/payment_success.php');
define('MIDTRANS_NOTIFICATION_URL', 'http://localhost/uprpl-php/payment_notify.php');
?>