    <?php
    // ==================== TEST SESSION ====================
    // File: test_session.php - FIX VERSION

    session_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Test Session - Unit Produksi RPL</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <style>
            body {
                background: linear-gradient(135deg, #667eea, #764ba2);
                min-height: 100vh;
                padding: 30px 20px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .test-card {
                background: white;
                border-radius: 20px;
                padding: 30px;
                max-width: 800px;
                margin: 0 auto;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            }
            h2 {
                color: #2c3e50;
                font-weight: 800;
                margin-bottom: 20px;
            }
            .info-box {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                border-left: 5px solid #667eea;
            }
            .info-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #e9ecef;
            }
            .info-item:last-child {
                border-bottom: none;
            }
            .info-label {
                font-weight: 600;
                color: #495057;
            }
            .info-value {
                color: #667eea;
                font-family: monospace;
            }
            pre {
                background: #1e293b;
                color: #e2e8f0;
                padding: 15px;
                border-radius: 10px;
                font-size: 14px;
                max-height: 300px;
                overflow: auto;
            }
            .btn-test {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s;
                margin-right: 10px;
            }
            .btn-test:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(102,126,234,0.4);
            }
            .result-box {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 20px;
                margin-top: 20px;
                display: none;
            }
            .result-box.active {
                display: block;
            }
        </style>
    </head>
    <body>
        <div class="test-card">
            <div class="text-center mb-4">
                <i class="bi bi-database display-1" style="color: #667eea;"></i>
                <h2 class="mt-3">TEST SESSION</h2>
                <p class="text-muted">Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
            </div>

            <div class="info-box">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Session</h5>
                <div class="info-item">
                    <span class="info-label">Session ID:</span>
                    <span class="info-value"><?php echo session_id(); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Session Name:</span>
                    <span class="info-value"><?php echo session_name(); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Session Status:</span>
                    <span class="info-value">
                        <?php
                        $status = session_status();
                        if ($status === PHP_SESSION_ACTIVE) {
                            echo '<span class="badge bg-success">ACTIVE</span>';
                        } else {
                            echo '<span class="badge bg-danger">INACTIVE</span>';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Save Path:</span>
                    <span class="info-value"><?php echo session_save_path(); ?></span>
                </div>
            </div>

            <h5 class="fw-bold mb-3"><i class="bi bi-database me-2"></i>Session Data</h5>
            <pre><?php print_r($_SESSION); ?></pre>

            <div class="mt-4">
                <button class="btn-test" onclick="testAjax()">
                    <i class="bi bi-arrow-repeat me-2"></i>Test AJAX Session
                </button>
                <button class="btn-test" onclick="window.location.href='?clear=1'">
                    <i class="bi bi-trash me-2"></i>Clear Session
                </button>
                <button class="btn-test" onclick="window.location.href='debug_session.php'">
                    <i class="bi bi-bug me-2"></i>Debug Session
                </button>
            </div>

            <div id="ajaxResult" class="result-box">
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-check me-2"></i>AJAX Result</h5>
                <div id="ajaxContent"></div>
            </div>
        </div>

        <?php
        if (isset($_GET['clear'])) {
            session_destroy();
            echo '<script>window.location.href = "test_session.php";</script>';
        }
        ?>

        <script>
            function testAjax() {
                const resultDiv = document.getElementById('ajaxResult');
                const contentDiv = document.getElementById('ajaxContent');
                
                resultDiv.classList.add('active');
                contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading...</p></div>';
                
                $.ajax({
                    url: 'debug_session.php',
                    method: 'GET',
                    dataType: 'json',
                    xhrFields: {
                        withCredentials: true
                    },
                    success: function(response) {
                        contentDiv.innerHTML = '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    },
                    error: function(xhr, status, error) {
                        contentDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error + '<br>' + xhr.responseText + '</div>';
                    }
                });
            }
        </script>
    </body>
    </html>