<?php
require_once 'config.php';

$voucherCodes = [];
$redeemMessage = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['redeem'])) {
        // Handle voucher redemption
        $voucherCodeToRedeem = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';
        if ($voucherCodeToRedeem === '') {
            $redeemMessage = 'Please enter a voucher code to redeem.';
            $messageType = 'error';
        } else {
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                $redeemMessage = 'Database connection failed: ' . $conn->connect_error;
                $messageType = 'error';
            } else {
                // Check if voucher exists, is not used, and not expired
                $stmt = $conn->prepare("SELECT voucher_id, is_used, expiry_time FROM Voucher WHERE code = ?");
                $stmt->bind_param("s", $voucherCodeToRedeem);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $redeemMessage = 'Invalid voucher code.';
                    $messageType = 'error';
                } else {
                    $voucher = $result->fetch_assoc();
                    $now = new DateTime();
                    $expiry = $voucher['expiry_time'] ? new DateTime($voucher['expiry_time']) : null;
                    if ($voucher['is_used']) {
                        $redeemMessage = 'Voucher has already been used.';
                        $messageType = 'error';
                    } elseif ($expiry !== null && $expiry < $now) {
                        $redeemMessage = 'Voucher has expired.';
                        $messageType = 'error';
                    } else {
                        $conn->begin_transaction();
                        try {
                            // Create new StudentSession
                            $anonymousToken = bin2hex(random_bytes(16));
                            $deviceMacAddress = null;
                            $stmtInsertSession = $conn->prepare("INSERT INTO StudentSession (anonymous_token, device_mac_address, first_access_time, last_access_time) VALUES (?, ?, NOW(), NOW())");
                            $stmtInsertSession->bind_param("ss", $anonymousToken, $deviceMacAddress);
                            if (!$stmtInsertSession->execute()) {
                                throw new Exception("Error creating student session: " . $stmtInsertSession->error);
                            }
                            $stmtInsertSession->close();

                            // Create new InternetSession linked to voucher and student session
                            $stmtInsertInternetSession = $conn->prepare("INSERT INTO InternetSession (anonymous_token, voucher_id, start_time) VALUES (?, ?, NOW())");
                            $stmtInsertInternetSession->bind_param("si", $anonymousToken, $voucher['voucher_id']);
                            if (!$stmtInsertInternetSession->execute()) {
                                throw new Exception("Error creating internet session: " . $stmtInsertInternetSession->error);
                            }
                            $stmtInsertInternetSession->close();

                            // Mark voucher as used
                            $stmtUpdateVoucher = $conn->prepare("UPDATE Voucher SET is_used = TRUE WHERE voucher_id = ?");
                            $stmtUpdateVoucher->bind_param("i", $voucher['voucher_id']);
                            if (!$stmtUpdateVoucher->execute()) {
                                throw new Exception("Error updating voucher status: " . $stmtUpdateVoucher->error);
                            }
                            $stmtUpdateVoucher->close();

                            $conn->commit();
                            $redeemMessage = 'Voucher redeemed successfully. Internet session started.';
                            $messageType = 'success';
                        } catch (Exception $e) {
                            $conn->rollback();
                            $redeemMessage = 'Error redeeming voucher: ' . $e->getMessage();
                            $messageType = 'error';
                        }
                    }
                }
                $stmt->close();
                $conn->close();
            }
        }
    } else {
        // Handle deposit simulation
        $numBottles = isset($_POST['numBottles']) ? intval($_POST['numBottles']) : 0;

        if ($numBottles <= 0) {
            $redeemMessage = "Number of bottles must be greater than zero.";
            $messageType = 'error';
        } else {
            if (!defined('DB_HOST') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
                $redeemMessage = "Error: Could not find database credentials.";
                $messageType = 'error';
            } else {
                $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                if ($conn->connect_error) {
                    $redeemMessage = "Connection failed: " . $conn->connect_error;
                    $messageType = 'error';
                } else {
                    $conn->begin_transaction();

                    try {
                        // Insert new StudentSession
                        $anonymousToken = bin2hex(random_bytes(16));
                        $deviceMacAddress = null;
                        $stmt = $conn->prepare("INSERT INTO StudentSession (anonymous_token, device_mac_address, first_access_time, last_access_time) VALUES (?, ?, NOW(), NOW())");
                        $stmt->bind_param("ss", $anonymousToken, $deviceMacAddress);
                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting into StudentSession: " . $stmt->error);
                        }
                        $sessionId = $conn->insert_id;
                        $stmt->close();

                        // Insert BottleDeposit with session_id
                        $stmt = $conn->prepare("INSERT INTO BottleDeposit (session_id, timestamp, bottle_count) VALUES (?, NOW(), ?)");
                        $stmt->bind_param("ii", $sessionId, $numBottles);
                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting into BottleDeposit: " . $stmt->error);
                        }
                        $depositId = $conn->insert_id;
                        $stmt->close();

                        $voucherCodes = [];
$voucherCode = generateVoucherCode();

$stmt = $conn->prepare("INSERT INTO Voucher (code, deposit_id, is_used) VALUES (?, ?, FALSE)");
$stmt->bind_param("si", $voucherCode, $depositId);

if (!$stmt->execute()) {
    throw new Exception("Error inserting into Voucher: " . $stmt->error);
}

$stmt->close();
$voucherCodes[] = $voucherCode;

                        $conn->commit();
                        $redeemMessage = "Successfully processed {$numBottles} bottle(s)";
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $redeemMessage = "Error: " . $e->getMessage();
                        $messageType = 'error';
                    }

                    $conn->close();
                }
            }
        }
    }
} else {
    $voucherCodes = [];
    $redeemMessage = '';
    $messageType = '';
}

function generateVoucherCode($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recycle for Connectivity - Simulation</title>
    <style>
        :root {
            --primary-color: #ff5722;
            --secondary-color: #00bcd0;
            --card-bg: #ffffff;
            --bg-color: #f5f5f5;
            --text-color: #333333;
            --light-text: #777777;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: center;
        }
        
        .logo {
            width: 60px;
            height: 60px;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .title-card {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .title-card h1 {
            margin: 0;
            font-size: 1.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .title-card p {
            margin: 0.5rem 0 0;
            font-style: italic;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .status-section {
            margin-bottom: 1rem;
        }
        
        .status-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .button-row {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .primary-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .secondary-button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .connect-button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: 500;
            text-align: center;
        }
        
        .message.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .footer {
            text-align: center;
            padding: 1rem;
            color: var(--light-text);
            font-size: 0.8rem;
        }
        
        /* Modal Styles */
        .voucher-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        
        .voucher-modal.show {
            display: flex;
        }
        
        .voucher-modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
        }
        
        .voucher-modal-header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .voucher-codes-list {
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.25rem;
            color: var(--text-color);
            word-break: break-all;
            background-color: #f5f5f5;
            padding: 1rem;
            border-radius: 8px;
            text-align: left;
        }
        
        .voucher-modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.75rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
            transition: color 0.3s ease;
        }
        
        .voucher-modal-close:hover {
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="header">
        <svg class="logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path fill="white" d="M21.82,15.42L19.32,19.75C18.83,20.61 17.92,21.06 17,21H15V23L12.5,18.5L15,14V16H17.82L15.6,12.15L19.93,9.65L21.73,12.77C22.25,13.54 22.32,14.57 21.82,15.42M9.21,3.06H14.21C15.19,3.06 16.04,3.63 16.45,4.45L17.45,6.19L19.18,5.19L16.54,9.6L11.39,9.69L13.12,8.69L11.71,6.24L9.5,10.09L5.16,7.59L6.96,4.47C7.37,3.64 8.22,3.06 9.21,3.06M5.05,19.76L2.55,15.43C2.06,14.58 2.13,13.56 2.64,12.79L3.64,11.06L1.91,10.06L7.05,10.14L9.7,14.56L7.97,13.56L6.56,16H11V21H7.4C6.47,21.07 5.55,20.61 5.05,19.76Z"/>
        </svg>
    </div>

    <div class="container">
        <div class="title-card card">
            <h1>Recycle for Connectivity</h1>
            <p>"Exchange plastic bottles for internet access!"</p>
        </div>

        <div class="card">
            <div class="status-section">
                <div class="status-label">Status:</div>
                <div class="status-value">Ready to process bottles</div>
            </div>

            <form method="post" class="simulate-form">
                <label class="form-label" for="numBottles">Number of Bottles:</label>
                <input class="form-input" type="number" id="numBottles" name="numBottles" min="1" required>
                
                <div class="button-row">
                    <button type="submit" name="simulate" class="primary-button">INSERT BOTTLE</button>
                    <a href="#" class="secondary-button">HOW TO CONNECT</a>
                </div>
            </form>
        </div>


        <!-- Status message area -->
        <?php if (!empty($redeemMessage)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($redeemMessage); ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            Powered by: Recycle for Connectivity System © All rights reserved 2025
        </div>
    </div>

    <!-- Voucher Modal - will be shown when vouchers are generated -->
    <?php if (!empty($voucherCodes)): ?>
        <div class="voucher-modal show" id="voucherModal">
            <div class="voucher-modal-content">
                <button class="voucher-modal-close" id="closeModal">&times;</button>
                <div class="voucher-modal-header">Generated Voucher Codes</div>
                <div class="voucher-codes-list">
                    <?php foreach ($voucherCodes as $code): ?>
                        <?php echo htmlspecialchars($code); ?><br>
                    <?php endforeach; ?>
                </div>
                <form method="post" class="redeem-form">
                    <label class="form-label" for="modal_voucher_code">Enter Voucher Code to Redeem:</label>
                    <input class="form-input" type="text" id="modal_voucher_code" name="voucher_code" required>
                    <button type="submit" name="redeem" class="connect-button">REDEEM VOUCHER</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Modal close functionality
        const modal = document.getElementById('voucherModal');
        const closeModalBtn = document.getElementById('closeModal');

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                modal.classList.remove('show');
            });
        }

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        });
    </script>
</body>
</html>