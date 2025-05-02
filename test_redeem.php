php
<!DOCTYPE html>
<html>
<head>
    <title>Test Voucher Redemption</title>
</head>
<body>

<h2>Test Voucher Redemption</h2>

<form method="post" action="redeem_voucher.php">
    <label for="voucherCode">Voucher Code:</label>
    <input type="text" name="voucherCode" id="voucherCode" required>
    <button type="submit">Redeem Voucher</button>
</form>

<div id="result">
    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $voucherCode = $_POST["voucherCode"];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "redeem_voucher.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "voucherCode=" . urlencode($voucherCode));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode == 200) {
                echo $response;
            } else {
                echo "<p style='color: red;'>Error: An error occurred during the redemption process.</p>";
            }
        }
    ?>
</div>

</body>
</html>