php
<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $numBottles = isset($_POST['numBottles']) ? intval($_POST['numBottles']) : 0;

    if ($numBottles <= 0) {
        echo "Number of bottles must be greater than zero.";
        exit;
    }

    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
        echo "Error: Could not find database credentials.";
        exit;
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO BottleDeposit (deposit_date, bottle_count) VALUES (NOW(), ?)");
        $stmt->bind_param("i", $numBottles);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting into BottleDeposit: " . $stmt->error);
        }

        $depositId = $conn->insert_id;
        $stmt->close();

        for ($i = 0; $i < $numBottles; $i++) {
            $voucherCode = generateVoucherCode(); 

            $stmt = $conn->prepare("INSERT INTO Voucher (code, deposit_id, is_used) VALUES (?, ?, FALSE)");
            $stmt->bind_param("si", $voucherCode, $depositId);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting into Voucher: " . $stmt->error);
            }

            $stmt->close();
        }

        $conn->commit();
        echo "Deposit successful. " . $numBottles . " bottle(s) deposited and voucher(s) generated.";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Simulate Deposit</title>
    </head>
    <body>
        <h2>Simulate Bottle Deposit</h2>
        <form method="post">
            <label for="numBottles">Number of Bottles:</label>
            <input type="number" id="numBottles" name="numBottles" min="1" required><br><br>
            <input type="submit" value="Simulate Deposit">
        </form>
    </body>
    </html>
    <?php
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