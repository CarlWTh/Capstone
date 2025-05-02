php
<?php
<?php
  // Database configuration
  $db_host = 'localhost';
  $db_name = 'bottle_recycling_system';
  $db_user = 'root';
  $db_pass = '';
  
  try {
      // Create PDO connection
      $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
      // Get voucher code from POST request
      if (isset($_POST['voucherCode'])) {
          $voucherCode = $_POST['voucherCode'];
  
          // Check if voucher is valid and unused
          $stmt = $pdo->prepare("SELECT * FROM Voucher WHERE code = :code AND is_used = FALSE");
          $stmt->bindParam(':code', $voucherCode);
          $stmt->execute();
          $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
  
          if ($voucher) {
              // Get minutes_per_bottle from SystemSettings
              $stmt = $pdo->prepare("SELECT minutes_per_bottle FROM SystemSettings");
              $stmt->execute();
              $settings = $stmt->fetch(PDO::FETCH_ASSOC);
              $minutesPerBottle = $settings['minutes_per_bottle'];
  
              // Mark voucher as used
              $stmt = $pdo->prepare("UPDATE Voucher SET is_used = TRUE WHERE code = :code");
              $stmt->bindParam(':code', $voucherCode);
              $stmt->execute();
  
              // Return success message
              $response = array('status' => 'success', 'message' => 'Voucher redeemed successfully.', 'minutes' => $minutesPerBottle);
              echo json_encode($response);
          } else {
              // Return failure message (invalid or used)
              $response = array('status' => 'error', 'message' => 'Invalid or already used voucher.');
              echo json_encode($response);
          }
      } else {
          // Return failure message (no voucher code provided)
          $response = array('status' => 'error', 'message' => 'No voucher code provided.');
          echo json_encode($response);
      }
  } catch (PDOException $e) {
      // Handle database errors
      $response = array('status' => 'error', 'message' => 'Database error: ' . $e->getMessage());
      echo json_encode($response);
    }
?>