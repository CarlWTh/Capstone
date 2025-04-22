<?php
require_once 'config.php';
require_once 'vendor/autoload.php'; // Path to TCPDF autoload
checkAdminAuth();

use TCPDF as TCPDF;

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'month';

// Set PDF document properties
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Bottle Recycling System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Recycling Report');
$pdf->SetSubject('Monthly Recycling Report');
$pdf->SetKeywords('Recycling, Report, PDF');

// Set default header data
$pdf->SetHeaderData('', 0, 'Bottle Recycling System Report', 'Generated on '.date('Y-m-d H:i:s'));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Recycling Report Summary', 0, 1, 'C');
$pdf->Ln(10);

// Get report data based on filter
$title = '';
$where = '';
$period = '';

switch ($filter) {
    case 'quarter':
        $title = 'Quarterly Report';
        $period = date('Y') . ' Q' . ceil(date('n') / 3);
        $where = "WHERE generated_on >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        break;
    case 'year':
        $title = 'Annual Report';
        $period = date('Y');
        $where = "WHERE YEAR(generated_on) = YEAR(CURRENT_DATE())";
        break;
    default: // month
        $title = 'Monthly Report';
        $period = date('F Y');
        $where = "WHERE MONTH(generated_on) = MONTH(CURRENT_DATE()) AND YEAR(generated_on) = YEAR(CURRENT_DATE())";
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $title . ' - ' . $period, 0, 1);
$pdf->Ln(5);

// Get summary data
$summary = [];
$result = $conn->query("
    SELECT 
        SUM(bottle_count) as total_bottles,
        SUM(credits_earned) as total_credits,
        COUNT(DISTINCT user_id) as active_users
    FROM transactions
    $where
");
if ($result) {
    $summary = $result->fetch_assoc();
}

// Summary table
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$html = '<table border="1" cellpadding="4">
    <tr>
        <th width="50%">Metric</th>
        <th width="50%">Value</th>
    </tr>
    <tr>
        <td>Total Bottles Recycled</td>
        <td>'.number_format($summary['total_bottles'] ?? 0).'</td>
    </tr>
    <tr>
        <td>Total Credits Issued</td>
        <td>'.number_format($summary['total_credits'] ?? 0).'</td>
    </tr>
    <tr>
        <td>Active Recyclers</td>
        <td>'.number_format($summary['active_users'] ?? 0).'</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Monthly trends chart (we'll use a simple table since we can't embed dynamic charts in PDF)
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Monthly Trends', 0, 1);

// Get monthly data
$monthly_data = [];
$result = $conn->query("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(bottle_count) as bottles,
        SUM(credits_earned) as credits
    FROM transactions
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
if ($result) {
    $monthly_data = $result->fetch_all(MYSQLI_ASSOC);
}

if (!empty($monthly_data)) {
    $html = '<table border="1" cellpadding="4">
        <tr>
            <th width="30%">Month</th>
            <th width="35%">Bottles Recycled</th>
            <th width="35%">Credits Issued</th>
        </tr>';
    
    foreach ($monthly_data as $row) {
        $html .= '<tr>
            <td>'.date('F Y', strtotime($row['month'].'-01')).'</td>
            <td>'.number_format($row['bottles']).'</td>
            <td>'.number_format($row['credits']).'</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
} else {
    $pdf->Cell(0, 10, 'No data available for monthly trends', 0, 1);
}

// Recent transactions
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Recent Transactions', 0, 1);

$transactions = [];
$result = $conn->query("
    SELECT 
        t.transaction_date,
        u.username,
        t.bottle_count,
        t.credits_earned
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    $where
    ORDER BY t.transaction_date DESC
    LIMIT 15
");
if ($result) {
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
}

if (!empty($transactions)) {
    $html = '<table border="1" cellpadding="4">
        <tr>
            <th width="25%">Date/Time</th>
            <th width="25%">User</th>
            <th width="25%">Bottles</th>
            <th width="25%">Credits</th>
        </tr>';
    
    foreach ($transactions as $txn) {
        $html .= '<tr>
            <td>'.$txn['transaction_date'].'</td>
            <td>'.htmlspecialchars($txn['username'] ?? 'Guest').'</td>
            <td>'.$txn['bottle_count'].'</td>
            <td>'.$txn['credits_earned'].'</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
} else {
    $pdf->Cell(0, 10, 'No transactions found for this period', 0, 1);
}

// Footer
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated by Bottle Recycling System Admin Dashboard', 0, 1, 'C');

// Close and output PDF document
$pdf->Output('recycling_report_'.date('Y-m-d').'.pdf', 'D');
exit();