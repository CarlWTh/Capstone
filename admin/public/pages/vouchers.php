<?php
require_once '../config.php';
checkAdminAuth();

require_once '../helpers/devices_backend.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/voucher.css">

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
                    <a href="devices.php">
                        <i class="bi bi-phone"></i>
                            <span>Devices</span>
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

// Status filter functionality
document.getElementById('status-filter').addEventListener('change', function() {
    const status = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.set('page', 1); 
    window.location.href = url.toString();
});

// Document ready functionality
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

    // Enhanced table row interactions - UPDATED to match Activity Logs behavior
    const tableRows = document.querySelectorAll('.modern-table tbody tr');
    tableRows.forEach(row => {
        // Check if it's not an empty state row
        if (!row.querySelector('.empty-state')) {
            row.addEventListener('mouseenter', function() {
                // Match the Activity Logs hover style exactly
                this.style.backgroundColor = '#f8f9fa';
                this.style.transition = 'background-color 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        }
    });
});
    </script>
</body>

</html>