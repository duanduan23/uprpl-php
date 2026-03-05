    <?php
    // File: logout_pusat.php
    session_start();
    session_destroy();
    header('Location: login_pusat.php');
    exit;
    ?>