<?php
require_once 'config.php';
checkAdminAuth();

// Handle new deposit addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deposit']))
{
    $bottleCount = (int) $_POST['bottle_count'];

    if ($bottleCount > 0)
    {
        $stmt = $conn->prepare("INSERT INTO BottleDeposit (bottle_count) VALUES (?)"); // Insert the deposit
        $stmt->bind_param("i", $bottleCount); // Bind parameters
        if ($stmt->execute()) // Execute the statement
        {
            logAdminActivity('Deposit Added', "Added a new bottle deposit of $bottleCount bottles"); // Log activity
            $depositId = $conn->insert_id; // Get the last inserted ID (deposit_id)
            for ($i = 0; $i < $bottleCount; $i++)
            {
                $voucherCode = generateUniqueVoucherCode($conn); // Generate a unique voucher code
                $voucherStmt = $conn->prepare("INSERT INTO Voucher (code, deposit_id) VALUES (?, ?)"); // Insert the voucher
                $voucherStmt->bind_param("si", $voucherCode, $depositId); // Bind parameters
                $voucherStmt->execute(); // Execute the statement
            }
            redirectWithMessage('bins.php', 'success', 'Deposit added and vouchers created successfully!'); // Redirect with success message
        }
        else
        {
            redirectWithMessage('bins.php', 'error', 'Failed to add deposit.'); // Redirect with error message
        }
    }
}

$deposits = $conn->query("
    SELECT deposit_id, bottle_count, timestamp  
    FROM BottleDeposit
    ORDER BY timestamp DESC  
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Bottle Deposits Access', 'Viewed bottle deposits list');
function generateUniqueVoucherCode($conn)
{
    do {
        $voucherCode = substr(md5(uniqid(rand(), true)), 0, 10);
        $stmt = $conn->prepare("SELECT 1 FROM Voucher WHERE code = ?");
        $stmt->bind_param("s", $voucherCode);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    return $voucherCode; // Return the voucher code
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>Bottle Deposits - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1><?php echo SITE_NAME; ?></h1>
                    <span class="logo-short"></span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <nav>
                <ul>
                    <li class="">
                        <a href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="deposits.php">
                            <i class="bi bi-recycle"></i>
                            <span>Bottle Deposits</span>
                        </a>
                    </li>
                    <li>
                        <a href="vouchers.php">
                            <i class="bi bi-ticket-perforated"></i>
                            <span>Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="bins.php">
                            <i class="bi bi-trash"></i>
                            <span>Trash Bins</span>
                        </a>
                    </li>
                    <li>
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
                        </a>
                    </li>
                    <li>
                        <a href="sessions.php">
                            <i class="bi bi-wifi"></i>
                            <span>Internet Sessions</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="activity_logs.php">
                            <i class="bi bi-clock-history"></i>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">

            <h2>Bottle Deposits</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="avatar-img">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    <a href="#"><i class="bi bi-person"></i> Profile</a>
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage();?>

        <div class="card">

            <div class="card-header">
                <h3>Add New Deposit</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepositModal">
                    <i class="bi bi-plus"></i> Add Deposit

                </button>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Deposit ID</th>
                            <th>Number of Bottles</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposits as $deposit):?>

                            <tr>

                                <td>
                                    <?php echo $deposit['deposit_id']; ?>
                                </td>
                                <td>
                                    <?php echo $deposit['bottle_count']; ?>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d H:i:s', strtotime($deposit['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Deposit Modal -->
        <div class="modal fade" id="addDepositModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">

                        <h5 class="modal-title">Add New Deposit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="bins.php">

                        <input type="hidden" name="add_deposit" value="1">
                        <div class="modal-body">
                            <div class="form-group mb-3">

                                <label for="bottle_count" class="form-label">Number of Bottles</label>
                                <input type="number" class="form-control" id="bottle_count" name="bottle_count"
                                    required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> 
                            <button type="submit" class="btn btn-primary">Add Deposit</button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Profile dropdown
        document.querySelector('.dropdown-header').addEventListener('click', function () {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });
    </script>
</body> 
</html>