<?php
// api/reports/export-user-progress.php

// Set session save path to application sessions directory (matching index.php)
$sessionPath = __DIR__ . '/../../sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

// Set session cookie path to match the application path
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'secure' => false, // Set to true if using HTTPS
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session state
error_log('[EXPORT] Session ID: ' . session_id());
error_log('[EXPORT] Session status: ' . session_status());
error_log('[EXPORT] Session data exists: ' . (isset($_SESSION['id']) ? 'yes' : 'no'));
error_log('[EXPORT] User data exists: ' . (isset($_SESSION['user']) ? 'yes' : 'no'));

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    error_log('[EXPORT] Unauthorized access - Session ID: ' . session_id());
    error_log('[EXPORT] $_SESSION contents: ' . json_encode($_SESSION));
    die('Unauthorized access');
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/UserProgressReportModel.php';

// Get current user
$currentUser = $_SESSION['user'];
$clientId = $currentUser['client_id'];

// Get export format
$format = $_POST['format'] ?? 'pdf';

// Get filters from POST data
$filters = [
    'client_id' => $clientId,
    'start_date' => $_POST['start_date'] ?? null,
    'end_date' => $_POST['end_date'] ?? null,
    'user_ids' => !empty($_POST['user_ids']) ? (is_array($_POST['user_ids']) ? $_POST['user_ids'] : explode(',', $_POST['user_ids'])) : null,
    'course_ids' => !empty($_POST['course_ids']) ? (is_array($_POST['course_ids']) ? $_POST['course_ids'] : explode(',', $_POST['course_ids'])) : null,
    'status' => !empty($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'] : explode(',', $_POST['status'])) : null,
    'custom_field_id' => $_POST['custom_field_id'] ?? null,
    'custom_field_value' => !empty($_POST['custom_field_value']) ? (is_array($_POST['custom_field_value']) ? $_POST['custom_field_value'] : [$_POST['custom_field_value']]) : null
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '';
});

// Get data from model
$model = new UserProgressReportModel();
$result = $model->getUserProgressData($filters, 1, PHP_INT_MAX);
$reportData = $result['data'];
$summary = $model->getSummaryStats($filters);

// Get summary and charts from POST if available
$summaryFromPost = isset($_POST['summary']) ? json_decode($_POST['summary'], true) : null;
$chartsFromPost = isset($_POST['charts']) ? json_decode($_POST['charts'], true) : null;

// Use POST data if available, otherwise use calculated data
if ($summaryFromPost) {
    $summary = $summaryFromPost;
}

$charts = $chartsFromPost ?: [
    'completion_status' => [
        'completed' => 0,
        'in_progress' => 0,
        'not_started' => 0
    ],
    'department_progress' => [
        'labels' => [],
        'data' => []
    ]
];

// Calculate charts if not provided
if (!$chartsFromPost) {
    foreach ($reportData as $row) {
        switch ($row['progress_status']) {
            case 'completed':
                $charts['completion_status']['completed']++;
                break;
            case 'in_progress':
                $charts['completion_status']['in_progress']++;
                break;
            case 'not_started':
                $charts['completion_status']['not_started']++;
                break;
        }
    }
}

// Export based on format
if ($format === 'pdf') {
    exportPDF($reportData, $summary, $charts, $filters);
} elseif ($format === 'excel') {
    exportExcel($reportData, $summary, $filters);
} else {
    die('Invalid export format');
}

/**
 * Draw a pie chart on PDF using simple shapes
 */
function drawPieChart($pdf, $centerX, $centerY, $radius, $data) {
    $total = array_sum(array_column($data, 'value'));
    if ($total == 0) return;
    
    $startAngle = 0;
    
    foreach ($data as $slice) {
        if ($slice['value'] == 0) continue;
        
        $angle = ($slice['value'] / $total) * 360;
        $endAngle = $startAngle + $angle;
        
        // Set slice color
        $pdf->SetFillColor($slice['color'][0], $slice['color'][1], $slice['color'][2]);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(1);
        
        // Draw the slice using PieSector (available in TCPDF)
        if (method_exists($pdf, 'PieSector')) {
            $pdf->PieSector($centerX, $centerY, $radius, $startAngle, $endAngle, 
                           'FD', false, 0, 2);
        } else {
            // Fallback: Draw a simple colored rectangle as indicator
            $rectX = $centerX - $radius + (($startAngle / 360) * ($radius * 2));
            $rectWidth = (($angle / 360) * ($radius * 2));
            $pdf->Rect($rectX, $centerY, $rectWidth, 5, 'F');
        }
        
        $startAngle = $endAngle;
    }
    
    // Reset draw color
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);
}

/**
 * Draw a bar chart on PDF
 */
function drawBarChart($pdf, $labels, $values) {
    if (empty($labels) || empty($values)) return;
    
    $startX = 15;
    $startY = $pdf->GetY();
    $chartWidth = 170;
    $chartHeight = 60;
    $maxValue = 100; // Progress is always 0-100%
    
    $barCount = count($labels);
    $barWidth = min(20, ($chartWidth - 10) / $barCount - 5);
    $spacing = ($chartWidth - ($barWidth * $barCount)) / ($barCount + 1);
    
    // Draw chart background
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Rect($startX, $startY, $chartWidth, $chartHeight, 'F');
    
    // Draw grid lines
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);
    for ($i = 0; $i <= 4; $i++) {
        $y = $startY + ($chartHeight * $i / 4);
        $pdf->Line($startX, $y, $startX + $chartWidth, $y);
    }
    
    // Draw bars
    $pdf->SetFillColor(106, 13, 173); // Purple theme color
    $x = $startX + $spacing;
    
    foreach ($values as $index => $value) {
        $barHeight = ($value / $maxValue) * $chartHeight;
        $barY = $startY + $chartHeight - $barHeight;
        
        // Draw bar
        $pdf->Rect($x, $barY, $barWidth, $barHeight, 'F');
        
        // Draw value on top of bar
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $barY - 5);
        $pdf->Cell($barWidth, 4, round($value, 1) . '%', 0, 0, 'C');
        
        $x += $barWidth + $spacing;
    }
    
    // Draw labels below chart
    $pdf->SetFont('helvetica', '', 7);
    $x = $startX + $spacing;
    $labelY = $startY + $chartHeight + 2;
    
    $maxLabelLines = 1;
    foreach ($labels as $label) {
        $pdf->SetXY($x, $labelY);
        // Truncate long labels
        $truncatedLabel = strlen($label) > 15 ? substr($label, 0, 12) . '...' : $label;
        $pdf->MultiCell($barWidth, 3, $truncatedLabel, 0, 'C');
        // Track max lines used for labels
        $lines = ceil(strlen($truncatedLabel) / 15);
        $maxLabelLines = max($maxLabelLines, $lines);
        $x += $barWidth + $spacing;
    }
    
    // Draw Y-axis labels
    $pdf->SetFont('helvetica', '', 7);
    for ($i = 0; $i <= 4; $i++) {
        $value = $maxValue - ($maxValue * $i / 4);
        $y = $startY + ($chartHeight * $i / 4);
        $pdf->SetXY($startX - 10, $y - 2);
        $pdf->Cell(8, 4, round($value) . '%', 0, 0, 'R');
    }
    
    // Reset text color and font
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    
    // Move Y position after chart and labels (accounting for multi-line labels)
    $pdf->SetY($labelY + (3 * $maxLabelLines) + 8);
}

/**
 * Export report as PDF
 */
function exportPDF($reportData, $summary, $charts, $filters) {
    // Check if TCPDF is available
    $tcpdfPath = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    error_log('[PDF EXPORT] Checking for TCPDF at: ' . $tcpdfPath);
    error_log('[PDF EXPORT] TCPDF exists: ' . (file_exists($tcpdfPath) ? 'yes' : 'no'));
    
    if (!file_exists($tcpdfPath)) {
        error_log('[PDF EXPORT] TCPDF not found, using alternative export');
        // Use alternative FPDF-based implementation
        exportPDFAlternative($reportData, $summary, $charts, $filters);
        return;
    }
    
    error_log('[PDF EXPORT] Loading TCPDF library');
    require_once $tcpdfPath;
    
    error_log('[PDF EXPORT] Creating PDF document with charts');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('UnlockYourSkills LMS');
    $pdf->SetAuthor('UnlockYourSkills LMS');
    $pdf->SetTitle('User Progress Report');
    $pdf->SetSubject('User Progress Report');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 20);
    
    // Title
    $pdf->Cell(0, 10, 'User Progress Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Summary Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    
    // Summary cards in a grid
    $cardWidth = 45;
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    
    // Not Started
    $pdf->SetFillColor(108, 117, 125);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['not_started_courses'], 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Not Started', 0, 1, 'C');
    
    // In Progress
    $x += $cardWidth + 5;
    $pdf->SetFillColor(255, 193, 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['in_progress_courses'], 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'In Progress', 0, 1, 'C');
    
    // Completed
    $x += $cardWidth + 5;
    $pdf->SetFillColor(40, 167, 69);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['completed_courses'], 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Completed', 0, 1, 'C');
    
    // Average Completion
    $x += $cardWidth + 5;
    $pdf->SetFillColor(13, 202, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['avg_completion'] . '%', 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Avg Completion', 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(20);
    
    // Charts Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Charts', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Completion Status Chart (Visual Pie Chart)
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Completion Status Distribution', 0, 1, 'L');
    $pdf->Ln(3);
    
    $total = $charts['completion_status']['completed'] + 
             $charts['completion_status']['in_progress'] + 
             $charts['completion_status']['not_started'];
    
    error_log('[PDF EXPORT] Drawing pie chart. Total: ' . $total);
    
    if ($total > 0) {
        $completedPct = round(($charts['completion_status']['completed'] / $total) * 100, 1);
        $inProgressPct = round(($charts['completion_status']['in_progress'] / $total) * 100, 1);
        $notStartedPct = round(($charts['completion_status']['not_started'] / $total) * 100, 1);
        
        error_log('[PDF EXPORT] Percentages - Completed: ' . $completedPct . ', In Progress: ' . $inProgressPct . ', Not Started: ' . $notStartedPct);
        
        // Draw pie chart
        $chartStartY = $pdf->GetY();
        try {
            drawPieChart($pdf, 60, $chartStartY + 25, 20, [
                ['label' => 'Completed', 'value' => $completedPct, 'color' => [40, 167, 69]],
                ['label' => 'In Progress', 'value' => $inProgressPct, 'color' => [255, 193, 7]],
                ['label' => 'Not Started', 'value' => $notStartedPct, 'color' => [108, 117, 125]]
            ]);
            error_log('[PDF EXPORT] Pie chart drawn successfully');
        } catch (Exception $e) {
            error_log('[PDF EXPORT] Error drawing pie chart: ' . $e->getMessage());
        }
        
        // Legend (position to the right of pie chart)
        $legendX = 100;
        $legendY = $chartStartY + 10;
        
        $pdf->SetFont('helvetica', '', 9);
        
        // Completed legend
        $pdf->SetFillColor(40, 167, 69);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'Completed: ' . $charts['completion_status']['completed'] . ' (' . $completedPct . '%)', 0, 0, 'L');
        
        // In Progress legend
        $legendY += 6;
        $pdf->SetFillColor(255, 193, 7);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'In Progress: ' . $charts['completion_status']['in_progress'] . ' (' . $inProgressPct . '%)', 0, 0, 'L');
        
        // Not Started legend
        $legendY += 6;
        $pdf->SetFillColor(108, 117, 125);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'Not Started: ' . $charts['completion_status']['not_started'] . ' (' . $notStartedPct . '%)', 0, 0, 'L');
        
        // Move Y position below both chart and legend
        $pdf->SetY($chartStartY + 55);
        $pdf->Ln(10);
    }
    
    // Department Progress Bar Chart (if available)
    if (!empty($charts['department_progress']['labels'])) {
        error_log('[PDF EXPORT] Drawing bar chart with ' . count($charts['department_progress']['labels']) . ' departments');
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Department Average Progress', 0, 1, 'L');
        $pdf->Ln(3);
        
        $barChartStartY = $pdf->GetY();
        
        try {
            drawBarChart($pdf, $charts['department_progress']['labels'], $charts['department_progress']['data']);
            error_log('[PDF EXPORT] Bar chart drawn successfully');
        } catch (Exception $e) {
            error_log('[PDF EXPORT] Error drawing bar chart: ' . $e->getMessage());
        }
        
        // Ensure we're below the bar chart (bar chart height is ~80-90mm including labels)
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Ln(10);
    } else {
        error_log('[PDF EXPORT] No department data for bar chart');
    }
    
    // Progress Data Table
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Progress Data', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 58, 64);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(35, 7, 'User Name', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Email', 1, 0, 'C', true);
    $pdf->Cell(45, 7, 'Course', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Progress', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Status', 1, 0, 'C', true);
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($reportData as $row) {
        $pdf->Cell(35, 6, substr($row['user_name'], 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell(50, 6, substr($row['user_email'], 0, 30), 1, 0, 'L', $fill);
        $pdf->Cell(45, 6, substr($row['course_name'], 0, 25), 1, 0, 'L', $fill);
        $pdf->Cell(20, 6, $row['completion_percentage'] . '%', 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, ucwords(str_replace('_', ' ', $row['progress_status'])), 1, 0, 'C', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
        
        // Check if we need a new page
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            
            // Re-print header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(52, 58, 64);
            $pdf->SetTextColor(255, 255, 255);
            
            $pdf->Cell(35, 7, 'User Name', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Email', 1, 0, 'C', true);
            $pdf->Cell(45, 7, 'Course', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Progress', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Status', 1, 0, 'C', true);
            $pdf->Ln();
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    
    // Output PDF
    $filename = 'user_progress_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D');
}

/**
 * Alternative PDF export using basic PHP (no external library)
 */
function exportPDFAlternative($reportData, $summary, $charts, $filters) {
    // If TCPDF is not available, fallback to HTML-based PDF or simple text output
    // For now, we'll use a simple HTML approach
    
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="user_progress_report_' . date('Y-m-d_H-i-s') . '.html"');
    
    echo '<html><head><title>User Progress Report</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #6a0dad; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .card { border: 2px solid #ccc; padding: 15px; text-align: center; border-radius: 5px; width: 150px; }
        .card h3 { margin: 0; font-size: 24px; }
        .card p { margin: 5px 0 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #6a0dad; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style></head><body>';
    
    echo '<h1>User Progress Report</h1>';
    echo '<p style="text-align:center;">Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    // Summary
    echo '<h2>Summary</h2>';
    echo '<div class="summary">';
    echo '<div class="card"><h3>' . $summary['not_started_courses'] . '</h3><p>Not Started</p></div>';
    echo '<div class="card"><h3>' . $summary['in_progress_courses'] . '</h3><p>In Progress</p></div>';
    echo '<div class="card"><h3>' . $summary['completed_courses'] . '</h3><p>Completed</p></div>';
    echo '<div class="card"><h3>' . $summary['avg_completion'] . '%</h3><p>Avg Completion</p></div>';
    echo '</div>';
    
    // Data table
    echo '<h2>Progress Data</h2>';
    echo '<table>';
    echo '<tr><th>User Name</th><th>Email</th><th>Course</th><th>Progress</th><th>Status</th><th>Last Accessed</th></tr>';
    
    foreach ($reportData as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['course_name']) . '</td>';
        echo '<td>' . $row['completion_percentage'] . '%</td>';
        echo '<td>' . ucwords(str_replace('_', ' ', $row['progress_status'])) . '</td>';
        echo '<td>' . ($row['last_accessed_at'] ? date('Y-m-d', strtotime($row['last_accessed_at'])) : 'N/A') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
}

/**
 * Export report as Excel
 */
function exportExcel($reportData, $summary, $filters) {
    // Check if PhpSpreadsheet is available
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    
    if (!file_exists($autoloadPath)) {
        // Fallback to CSV
        exportCSV($reportData, $summary, $filters);
        return;
    }
    
    require_once $autoloadPath;
    
    // Use fully qualified class names instead of use statements
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('UnlockYourSkills LMS')
        ->setTitle('User Progress Report')
        ->setSubject('User Progress Report')
        ->setDescription('User progress report generated from UnlockYourSkills LMS');
    
    // Title
    $sheet->setCellValue('A1', 'User Progress Report');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Date
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Summary Section
    $row = 4;
    $sheet->setCellValue('A' . $row, 'Summary');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Not Started Courses:');
    $sheet->setCellValue('B' . $row, $summary['not_started_courses']);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'In Progress Courses:');
    $sheet->setCellValue('B' . $row, $summary['in_progress_courses']);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Completed Courses:');
    $sheet->setCellValue('B' . $row, $summary['completed_courses']);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Average Completion:');
    $sheet->setCellValue('B' . $row, $summary['avg_completion'] . '%');
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Total Records:');
    $sheet->setCellValue('B' . $row, $summary['total_progress_records']);
    $row += 2;
    
    // Progress Data Table
    $sheet->setCellValue('A' . $row, 'Progress Data');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $row++;
    
    // Table headers
    $headers = ['User Name', 'Email', 'Course Name', 'Progress', 'Status', 'Last Accessed', 'Time Spent'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF6a0dad');
        $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $col++;
    }
    $row++;
    
    // Table data
    foreach ($reportData as $data) {
        $sheet->setCellValue('A' . $row, $data['user_name']);
        $sheet->setCellValue('B' . $row, $data['user_email']);
        $sheet->setCellValue('C' . $row, $data['course_name']);
        $sheet->setCellValue('D' . $row, $data['completion_percentage'] . '%');
        $sheet->setCellValue('E' . $row, ucwords(str_replace('_', ' ', $data['progress_status'])));
        $sheet->setCellValue('F' . $row, $data['last_accessed_at'] ? date('Y-m-d H:i', strtotime($data['last_accessed_at'])) : 'N/A');
        
        // Format time spent
        $seconds = $data['total_time_spent'] ?? 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $timeSpent = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
        $sheet->setCellValue('G' . $row, $timeSpent);
        
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Add borders to data table
    $dataRange = 'A' . ($row - count($reportData) - 1) . ':G' . ($row - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    // Output Excel file
    $filename = 'user_progress_report_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Fallback CSV export
 */
function exportCSV($reportData, $summary, $filters) {
    $filename = 'user_progress_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Title and date
    fputcsv($output, ['User Progress Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Not Started Courses', $summary['not_started_courses']]);
    fputcsv($output, ['In Progress Courses', $summary['in_progress_courses']]);
    fputcsv($output, ['Completed Courses', $summary['completed_courses']]);
    fputcsv($output, ['Average Completion', $summary['avg_completion'] . '%']);
    fputcsv($output, ['Total Records', $summary['total_progress_records']]);
    fputcsv($output, []);
    
    // Data table
    fputcsv($output, ['Progress Data']);
    fputcsv($output, ['User Name', 'Email', 'Course Name', 'Progress', 'Status', 'Last Accessed', 'Time Spent']);
    
    foreach ($reportData as $row) {
        $seconds = $row['total_time_spent'] ?? 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $timeSpent = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
        
        fputcsv($output, [
            $row['user_name'],
            $row['user_email'],
            $row['course_name'],
            $row['completion_percentage'] . '%',
            ucwords(str_replace('_', ' ', $row['progress_status'])),
            $row['last_accessed_at'] ? date('Y-m-d H:i', strtotime($row['last_accessed_at'])) : 'N/A',
            $timeSpent
        ]);
    }
    
    fclose($output);
    exit;
}

