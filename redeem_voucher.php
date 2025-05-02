<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucherCode = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';

    if (empty($voucherCode)) {
        echo json_encode(['status' => 'error', 'message' => 'Voucher code is required.']);
        exit;
    }

    // Check if voucher exists and is not used
    $stmt = $conn->prepare("SELECT voucher_id, deposit_id, is_used FROM Voucher WHERE code = ?");
    $stmt->bind_param("s", $voucherCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid voucher code.']);
        exit;
    }
    $voucher = $result->fetch_assoc();
    $stmt->close();

    if ($voucher['is_used']) {
        echo json_encode(['status' => 'error', 'message' => 'Voucher has already been used.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Create new StudentSession
        $anonymousToken = bin2hex(random_bytes(16));
        $deviceMacAddress = null; // Could be passed from client if available
        $stmt = $conn->prepare("INSERT INTO StudentSession (anonymous_token, device_mac_address, first_access_time, last_access_time) VALUES (?, ?, NOW(), NOW())");
        $stmt->bind_param("ss", $anonymousToken, $deviceMacAddress);
        if (!$stmt->execute()) {
            throw new Exception("Error creating student session: " . $stmt->error);
        }
        $stmt->close();

        // Create new InternetSession linked to voucher and student session
        $stmt = $conn->prepare("INSERT INTO InternetSession (anonymous_token, voucher_id, start_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $anonymousToken, $voucher['voucher_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error creating internet session: " . $stmt->error);
        }
        $stmt->close();

        // Mark voucher as used
        $stmt = $conn->prepare("UPDATE Voucher SET is_used = TRUE WHERE voucher_id = ?");
        $stmt->bind_param("i", $voucher['voucher_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error updating voucher status: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Voucher redeemed successfully. Internet session started.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Redeem Voucher</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" />
    </head>
    <body class="container mt-5">
        <h2>Redeem Voucher</h2>
        <form method="POST" action="redeem_voucher.php" class="mt-3">
            <div class="mb-3">
                <label for="voucher_code" class="form-label">Voucher Code</label>
                <input type="text" class="form-control" id="voucher_code" name="voucher_code" required />
            </div>
            <button type="submit" class="btn btn-primary">Redeem</button>
        </form>
    </body>
    </html>
    <?php
}
?>
