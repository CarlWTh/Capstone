php
<?php
include 'config.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

$sql = "INSERT INTO BottleDeposit () VALUES ()";
if ($conn->query($sql) === TRUE) {
    $deposit_id = $conn->insert_id;

    $voucherCode = bin2hex(random_bytes(10)); 

    $sql = "INSERT INTO Voucher (code, deposit_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $voucherCode, $deposit_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Voucher generated successfully.', 'voucherCode' => $voucherCode]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error generating voucher: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error creating deposit: ' . $conn->error]);
}

$conn->close();
?>