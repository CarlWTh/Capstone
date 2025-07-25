<?php
require_once 'config.php'; // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucherCode = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';

    if (empty($voucherCode)) {
        echo json_encode(['status' => 'error', 'message' => 'Voucher code is required.']);
        exit;
    }

    // Use the global $conn from config.php
    global $conn;

    $conn->begin_transaction(); // Start a transaction

    try {
        // Check if voucher exists, is not used, and not expired
        // Changed 'code' to 'voucher_code' and 'is_used' to 'status'
        $stmt = $conn->prepare("SELECT voucher_id, transaction_id, status, duration_minutes FROM Voucher WHERE voucher_code = ?");
        $stmt->bind_param("s", $voucherCode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Invalid voucher code.');
        }
        $voucher = $result->fetch_assoc();
        $stmt->close();

        if ($voucher['status'] === 'used') {
            throw new Exception('Voucher has already been used.');
        } elseif ($voucher['status'] === 'expired') {
            throw new Exception('Voucher has expired.');
        }

        // Simulate getting or creating a user based on a MAC address
        // In a real scenario, the device's MAC address would be detected.
        $deviceMacAddress = $_POST['device_mac_address'] ?? '00:00:00:00:00:' . sprintf('%02X', mt_rand(0, 255)); // Get from POST or simulate
        $user_id = null;

        $stmtUser = $conn->prepare("SELECT user_id FROM User WHERE mac_address = ?");
        $stmtUser->bind_param("s", $deviceMacAddress);
        $stmtUser->execute();
        $userResult = $stmtUser->get_result();

        if ($userResult->num_rows > 0) {
            $user_id = $userResult->fetch_assoc()['user_id'];
            // Update last_active time for existing user
            $stmtUpdateUser = $conn->prepare("UPDATE User SET last_active = NOW() WHERE user_id = ?");
            $stmtUpdateUser->bind_param("i", $user_id);
            $stmtUpdateUser->execute();
            $stmtUpdateUser->close();
        } else {
            // Create new User
            $stmtInsertUser = $conn->prepare("INSERT INTO User (mac_address, time_credits, last_active) VALUES (?, 0.00, NOW())");
            $stmtInsertUser->bind_param("s", $deviceMacAddress);
            if (!$stmtInsertUser->execute()) {
                throw new Exception("Error creating user: " . $stmtInsertUser->error);
            }
            $user_id = $conn->insert_id;
            $stmtInsertUser->close();
        }
        $stmtUser->close();

        // Create new UserSession
        // Assuming 'ip_address' is also passed or simulated
        $ipAddress = $_POST['ip_address'] ?? '192.168.1.' . mt_rand(1, 254);
        $stmtInsertUserSession = $conn->prepare("INSERT INTO UserSessions (user_id, ip_address, start_time, voucher_id) VALUES (?, ?, NOW(), ?)");
        $stmtInsertUserSession->bind_param("isi", $user_id, $ipAddress, $voucher['voucher_id']);
        if (!$stmtInsertUserSession->execute()) {
            throw new Exception("Error creating user session: " . $stmtInsertUserSession->error);
        }
        $userSessionId = $conn->insert_id;
        $stmtInsertUserSession->close();

        // Mark voucher as used and link to user
        $stmtUpdateVoucher = $conn->prepare("UPDATE Voucher SET status = 'used', redeemed_by = ?, redeemed_at = NOW() WHERE voucher_id = ?");
        $stmtUpdateVoucher->bind_param("ii", $user_id, $voucher['voucher_id']);
        if (!$stmtUpdateVoucher->execute()) {
            throw new Exception("Error updating voucher status: " . $stmtUpdateVoucher->error);
        }
        $stmtUpdateVoucher->close();

        // Update user's time credits
        $newTimeCredits = $voucher['duration_minutes']; // Voucher's duration is the credit
        $stmtUpdateTimeCredits = $conn->prepare("UPDATE User SET time_credits = time_credits + ? WHERE user_id = ?");
        $stmtUpdateTimeCredits->bind_param("di", $newTimeCredits, $user_id);
        if (!$stmtUpdateTimeCredits->execute()) {
            throw new Exception("Error updating user time credits: " . $stmtUpdateTimeCredits->error);
        }
        $stmtUpdateTimeCredits->close();

        // Log the activity
        logAdminActivity('Voucher Redemption', "Voucher '{$voucherCode}' redeemed by User ID: {$user_id} for {$newTimeCredits} minutes.");

        $conn->commit(); // Commit the transaction

        echo json_encode(['status' => 'success', 'message' => 'Voucher redeemed successfully. Internet session started for ' . number_format($voucher['duration_minutes'], 0) . ' minutes.']);
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        error_log("Voucher redemption error: " . $e->getMessage()); // Log error for debugging
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Display the form if not a POST request
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Redeem Voucher</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" />
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background-color: #f4f4f4;
                margin: 0;
            }
            .container {
                background: #fff;
                width: 400px;
                padding: 30px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            h2 {
                text-align: center;
                color: #333;
                margin-bottom: 25px;
            }
            .form-label {
                display: block;
                margin-bottom: 8px;
                font-weight: bold;
                color: #555;
            }
            .form-control {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-sizing: border-box;
                font-size: 16px;
            }
            .btn-primary {
                background-color: #28a745; /* Green for redeem */
                color: white;
                padding: 12px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                width: 100%;
                font-size: 18px;
                font-weight: bold;
                transition: background-color 0.3s ease;
            }
            .btn-primary:hover {
                background-color: #218838;
            }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <h2>Redeem Voucher</h2>
            <form method="POST" action="redeem_voucher.php" class="mt-3">
                <div class="mb-3">
                    <label for="voucher_code" class="form-label">Voucher Code</label>
                    <input type="text" class="form-control" id="voucher_code" name="voucher_code" required />
                </div>
                <!-- Optional: Add fields for device MAC address and IP if you want to capture them directly -->
                <!-- <div class="mb-3">
                    <label for="device_mac_address" class="form-label">Device MAC Address</label>
                    <input type="text" class="form-control" id="device_mac_address" name="device_mac_address" placeholder="Optional: AA:BB:CC:DD:EE:FF" />
                </div>
                <div class="mb-3">
                    <label for="ip_address" class="form-label">IP Address</label>
                    <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="Optional: 192.168.1.100" />
                </div> -->
                <button type="submit" class="btn btn-primary">Redeem</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
