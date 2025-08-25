<?php
require_once 'config.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$statusCondition = '';

if ($statusFilter === 'used') {
    $statusCondition = " AND v.status = 'used'";
} elseif ($statusFilter === 'unused') {
    $statusCondition = " AND v.status = 'unused'";
} elseif ($statusFilter === 'expired') {
    $statusCondition = " AND v.status = 'expired'";
}
$transactionIdFilter = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0;
$transactionCondition = $transactionIdFilter > 0 ? " AND v.transaction_id = $transactionIdFilter" : '';
$total_vouchers_query = "SELECT COUNT(*) FROM Voucher v WHERE 1=1 $statusCondition $transactionCondition";
$total_vouchers_result = $conn->query($total_vouchers_query);
$total_vouchers = $total_vouchers_result ? (int)$total_vouchers_result->fetch_row()[0] : 0;
$total_pages = ceil($total_vouchers / $per_page);
$vouchers_query = "
    SELECT v.voucher_id, v.voucher_code AS code, v.expiration, v.status, v.redeemed_at,
           t.created_at AS deposit_time
    FROM Voucher v
    JOIN Transactions t ON v.transaction_id = t.transaction_id
    WHERE 1=1 $statusCondition $transactionCondition
    ORDER BY v.voucher_id DESC
    LIMIT $per_page OFFSET $offset
";
$vouchers = $conn->query($vouchers_query)->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        /* Modern Statistics Cards for Vouchers */
        .voucher-stats-card {
            border: none;
            border-radius: 16px;
            margin-bottom: 24px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .voucher-stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
        }

        .voucher-stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-gradient);
        }

        .voucher-stats-card-vouchers {
            --accent-gradient: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        }

        /* Modern Filter Section */
        .modern-filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            position: relative;
        }

        .modern-filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            border-radius: 16px 16px 0 0;
        }

        .filter-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
        }

        .filter-options {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-options h5 {
            margin: 0;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modern-filter-select {
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 160px;
        }

        .modern-filter-select:hover {
            border-color: #6f42c1;
            color: #6f42c1;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.15);
        }

        .modern-filter-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
            outline: none;
        }

        /* Modern Card */
        .modern-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
            margin-bottom: 24px;
        }

        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        }

        .modern-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .modern-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        }

        .modern-card-title {
            font-size: 20px;
            font-weight: 600;
            color: #495057;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modern-card-body {
            padding: 32px;
        }

        /* Modern Table */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            background: white;
            margin: 0;
            width: 100%;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            color: white;
            font-weight: 600;
            padding: 16px;
            border: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8f9fa;
        }

        .modern-table tbody td {
            padding: 16px;
            border: none;
            vertical-align: middle;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.used {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .status-badge.unused {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .status-badge.expired {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        /* Modern Pagination */
        .modern-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 32px;
            padding: 24px 0;
        }

        .modern-pagination .btn {
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modern-pagination .btn:hover {
            border-color: #6f42c1;
            color: #6f42c1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
        }

        .modern-pagination .btn.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .pagination-info {
            font-size: 0.9em;
            color: #6c757d;
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.9em;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .modern-card-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .modern-pagination {
                flex-direction: column;
                gap: 12px;
            }

            .modern-card-body {
                padding: 24px 20px;
            }

            .modern-card-header {
                padding: 20px;
            }
        }

        /* Code styling for voucher codes */
        .voucher-code {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-weight: 600;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.9em;
            color: #495057;
        }
    </style>
</head>

<body class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1><?= SITE_NAME ?></h1>
                <span class="logo-short"></span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <nav>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="bottle_deposits.php">
                        <i class="bi bi-recycle"></i>
                        <span>Bottle Deposits</span>
                    </a>
                </li>
                <li class="active">
                    <a href="vouchers.php">
                        <i class="bi bi-ticket-perforated"></i>
                        <span>Vouchers</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Sessions</span>
                    </a>
                </li>
                <li>
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>
                <li >
                        <a href="profile.php">
                            <i class="bi bi-person-circle"></i>
                            <span>My Account</span>
                        </a>
                    </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i> 
                        <span>Settings</span>
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

    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-ticket-perforated"></i>Voucher Management</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <!-- Vouchers Table Card -->
        <div class="modern-card voucher-stats-card-vouchers">
            <div class="modern-card-header">
                <h3 class="modern-card-title">
                    <i class="bi bi-ticket-perforated"></i>
                    All Vouchers
                    <?php if ($transactionIdFilter > 0): ?>
                        <small class="text-muted">(Transaction ID: #<?= $transactionIdFilter ?>)</small>
                    <?php endif; ?>
                </h3>
                <div class="filter-options">
                    <h5><i class="bi bi-funnel"></i> Filter:</h5>
                    <select id="status-filter" class="modern-filter-select">
                        <option value="">All Statuses</option>
                        <option value="used" <?php echo $statusFilter === 'used' ? 'selected' : ''; ?>>Used</option>
                        <option value="unused" <?php echo $statusFilter === 'unused' ? 'selected' : ''; ?>>Unused</option>
                        <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
            </div>
            <div class="modern-card-body">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-qr-code"></i> Voucher Code</th>
                                <th><i class="bi bi-calendar-event"></i> Expiration</th>
                                <th><i class="bi bi-check-circle"></i> Status</th>
                                <th><i class="bi bi-clock"></i> Created At</th>
                                <th><i class="bi bi-check2-circle"></i> Redeemed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vouchers)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h4>No Vouchers Found</h4>
                                            <p>No vouchers found matching your selected criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr>
                                        <td>
                                            <span class="voucher-code"><?php echo htmlspecialchars($voucher['code']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y h:i A', strtotime($voucher['expiration'])); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            if ($voucher['status'] === 'used') {
                                                $status_class = 'used';
                                                $status_text = 'Used';
                                            } elseif ($voucher['status'] === 'expired') {
                                                $status_class = 'expired';
                                                $status_text = 'Expired';
                                            } else { // 'unused'
                                                $status_class = 'unused';
                                                $status_text = 'Unused';
                                            }
                                            ?>
                                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y h:i A', strtotime($voucher['deposit_time'])); ?></td>
                                        <td><?= $voucher['redeemed_at'] ? date('M j, Y h:i A', strtotime($voucher['redeemed_at'])) : '<span class="text-muted">N/A</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modern Pagination -->
                <div class="modern-pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $statusFilter; ?><?= $transactionIdFilter > 0 ? '&transaction_id=' . $transactionIdFilter : '' ?>"
                        class="btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <div class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></div>
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&status=<?php echo $statusFilter; ?><?= $transactionIdFilter > 0 ? '&transaction_id=' . $transactionIdFilter : '' ?>"
                        class="btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Dropdown functionality
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        // Status filter functionality
        document.getElementById('status-filter').addEventListener('change', function() {
            const status = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('status', status);
            url.searchParams.set('page', 1); 
            window.location.href = url.toString();
        });

        // Add smooth animations for hover effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add intersection observer for cards animation
            const cards = document.querySelectorAll('.modern-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Add click animations to buttons
            const buttons = document.querySelectorAll('.modern-pagination .btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!this.classList.contains('disabled')) {
                        // Create ripple effect
                        const ripple = document.createElement('span');
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        const x = e.clientX - rect.left - size / 2;
                        const y = e.clientY - rect.top - size / 2;
                        
                        ripple.style.cssText = `
                            position: absolute;
                            border-radius: 50%;
                            background: rgba(255, 255, 255, 0.6);
                            transform: scale(0);
                            animation: ripple 0.6s linear;
                            width: ${size}px;
                            height: ${size}px;
                            left: ${x}px;
                            top: ${y}px;
                            pointer-events: none;
                        `;
                        
                        this.style.position = 'relative';
                        this.style.overflow = 'hidden';
                        this.appendChild(ripple);
                        
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    }
                });
            });

            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });

        // Enhanced table row interactions
        const tableRows = document.querySelectorAll('.modern-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
                this.style.boxShadow = '4px 0 12px rgba(0, 0, 0, 0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>

</html>