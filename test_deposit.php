php
<?php
include 'config.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bottle_recycling_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT bin_id FROM bins";
$result = $conn->query($sql);

$bins = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bins[] = $row['bin_id'];
    }
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bottle_count = $_POST['bottle_count'];
    $bin_id = $_POST['bin_id'];
    $status = $_POST['status'];
    $anonymous_token = $_POST['anonymous_token'];
    $session_id = $_POST['session_id'];

    $sql = "INSERT INTO deposits (bottle_count, bin_id, status, anonymous_token, session_id, timestamp) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("iissi", $bottle_count, $bin_id, $status, $anonymous_token, $session_id);

        if ($stmt->execute()) {
            $successMessage = "Deposit added successfully!";
        } else {
            $errorMessage = "Error adding deposit: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $errorMessage = "Error preparing statement: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Deposit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        form {
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="number"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php if ($successMessage): ?>
    <div class="success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="error"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<form method="post">
    <label for="bottle_count">Number of Bottles:</label>
    <input type="number" name="bottle_count" id="bottle_count" required>

    <label for="bin_id">Bin ID:</label>
    <select name="bin_id" id="bin_id" required>
        <?php foreach ($bins as $bin): ?>
            <option value="<?php echo $bin; ?>"><?php echo $bin; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="status">Status:</label>
    <select name="status" id="status" required>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
    </select>

    <label for="anonymous_token">Anonymous Token:</label>
    <input type="text" name="anonymous_token" id="anonymous_token" required>
    
    <label for="session_id">Session ID:</label>
    <input type="number" name="session_id" id="session_id" required>
    
    <input type="submit" value="Add Deposit">
</form>
</body>
</html>
<?php
$conn->close();
?>