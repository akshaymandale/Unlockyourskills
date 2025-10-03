<?php
// api/reports/export-course-completion.php

// Set session save path to application sessions directory
$sessionPath = __DIR__ . '/../../sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

// Set session cookie params
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'secure' => false,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    error_log('[EXPORT COURSE COMPLETION] Unauthorized access');
    die('Unauthorized access');
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/CourseCompletionReportModel.php';

// Get current user
$currentUser = $_SESSION['user'];
$clientId = $currentUser['client_id'];

// Get export format
$format = $_GET['format'] ?? $_POST['format'] ?? 'pdf';

// Get filters from GET or POST data
$filterSource = !empty($_POST) ? $_POST : $_GET;
$filters = [
    'client_id' => $clientId,
    'start_date' => $filterSource['start_date'] ?? null,
    'end_date' => $filterSource['end_date'] ?? null,
    'course_ids' => !empty($filterSource['course_ids']) ? (is_array($filterSource['course_ids']) ? $filterSource['course_ids'] : explode(',', $filterSource['course_ids'])) : null,
    'status' => !empty($filterSource['status']) ? (is_array($filterSource['status']) ? $filterSource['status'] : explode(',', $filterSource['status'])) : null,
    'custom_field_id' => $filterSource['custom_field_id'] ?? null,
    'custom_field_value' => !empty($filterSource['custom_field_value']) ? (is_array($filterSource['custom_field_value']) ? $filterSource['custom_field_value'] : explode(',', $filterSource['custom_field_value'])) : null
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '';
});

// Get data from model
$model = new CourseCompletionReportModel();
$result = $model->getCourseCompletionData($filters, 1, PHP_INT_MAX);
$reportData = $result['data'];
$summary = $model->getSummaryStats($filters);

// Prepare charts data
$charts = [
    'completion_rate' => [
        'labels' => [],
        'data' => []
    ],
    'enrollment_status' => [
        'completed' => 0,
        'in_progress' => 0,
        'not_started' => 0
    ]
];

// Sort courses by completion rate (highest first) for chart
usort($reportData, function($a, $b) {
    return $b['completion_rate'] <=> $a['completion_rate'];
});

// Process course completion rates for bar chart (top 10 by completion rate)
$chartCount = 0;
foreach ($reportData as $row) {
    if ($chartCount < 10) { // Top 10 courses by completion rate
        $charts['completion_rate']['labels'][] = $row['course_name'];
        $charts['completion_rate']['data'][] = $row['completion_rate'];
        $chartCount++;
    }
    
    // Aggregate enrollment status from all data
    $charts['enrollment_status']['completed'] += $row['completed_count'];
    $charts['enrollment_status']['in_progress'] += $row['in_progress_count'];
    $charts['enrollment_status']['not_started'] += $row['not_started_count'];
}

// Export based on format
switch ($format) {
    case 'pdf':
        exportPDF($reportData, $summary, $charts, $filters);
        break;
    case 'excel':
        exportExcel($reportData, $summary, $filters);
        break;
    case 'csv':
        exportCSV($reportData, $summary, $filters);
        break;
    default:
        exportCSV($reportData, $summary, $filters);
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
    $pdf->SetFillColor(139, 92, 246); // Purple theme color
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
    
    if (!file_exists($tcpdfPath)) {
        error_log('[PDF EXPORT] TCPDF not found, using alternative export');
        exportPDFAlternative($reportData, $summary, $charts, $filters);
        return;
    }
    
    require_once $tcpdfPath;
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('UnlockYourSkills LMS');
    $pdf->SetAuthor('UnlockYourSkills LMS');
    $pdf->SetTitle('Course Completion Report');
    $pdf->SetSubject('Course Completion Report');
    
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
    $pdf->Cell(0, 10, 'Course Completion Report', 0, 1, 'C');
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
    
    // Total Courses
    $pdf->SetFillColor(139, 92, 246);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['total_courses'], 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Total Courses', 0, 1, 'C');
    
    // Total Enrollments
    $x += $cardWidth + 5;
    $pdf->SetFillColor(13, 202, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, $summary['total_enrollments'], 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Total Enrollments', 0, 1, 'C');
    
    // Overall Completion Rate
    $x += $cardWidth + 5;
    $pdf->SetFillColor(25, 135, 84);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, round($summary['overall_completion_rate'], 1) . '%', 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Completion Rate', 0, 1, 'C');
    
    // Avg Completion
    $x += $cardWidth + 5;
    $pdf->SetFillColor(255, 193, 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Rect($x, $y, $cardWidth, 15, 'F');
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell($cardWidth - 4, 6, round($summary['avg_completion_percentage'], 1) . '%', 0, 1, 'C');
    $pdf->SetXY($x + 2, $y + 8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($cardWidth - 4, 5, 'Avg Completion', 0, 1, 'C');
    
    $pdf->Ln(20);
    
    // Charts Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Charts', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Enrollment Status Chart (Visual Pie Chart)
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Enrollment Status Distribution', 0, 1, 'L');
    $pdf->Ln(3);
    
    $total = $charts['enrollment_status']['completed'] + 
             $charts['enrollment_status']['in_progress'] + 
             $charts['enrollment_status']['not_started'];
    
    if ($total > 0) {
        $completedPct = round(($charts['enrollment_status']['completed'] / $total) * 100, 1);
        $inProgressPct = round(($charts['enrollment_status']['in_progress'] / $total) * 100, 1);
        $notStartedPct = round(($charts['enrollment_status']['not_started'] / $total) * 100, 1);
        
        // Draw pie chart
        $chartStartY = $pdf->GetY();
        try {
            drawPieChart($pdf, 60, $chartStartY + 25, 20, [
                ['label' => 'Completed', 'value' => $completedPct, 'color' => [25, 135, 84]],
                ['label' => 'In Progress', 'value' => $inProgressPct, 'color' => [255, 193, 7]],
                ['label' => 'Not Started', 'value' => $notStartedPct, 'color' => [108, 117, 125]]
            ]);
        } catch (Exception $e) {
            error_log('[PDF EXPORT] Error drawing pie chart: ' . $e->getMessage());
        }
        
        // Legend (position to the right of pie chart)
        $legendX = 100;
        $legendY = $chartStartY + 10;
        
        $pdf->SetFont('helvetica', '', 9);
        
        // Completed legend
        $pdf->SetFillColor(25, 135, 84);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'Completed: ' . $charts['enrollment_status']['completed'] . ' (' . $completedPct . '%)', 0, 0, 'L');
        
        // In Progress legend
        $legendY += 6;
        $pdf->SetFillColor(255, 193, 7);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'In Progress: ' . $charts['enrollment_status']['in_progress'] . ' (' . $inProgressPct . '%)', 0, 0, 'L');
        
        // Not Started legend
        $legendY += 6;
        $pdf->SetFillColor(108, 117, 125);
        $pdf->Rect($legendX, $legendY, 4, 4, 'F');
        $pdf->SetXY($legendX + 6, $legendY);
        $pdf->Cell(60, 4, 'Not Started: ' . $charts['enrollment_status']['not_started'] . ' (' . $notStartedPct . '%)', 0, 0, 'L');
        
        // Move Y position below both chart and legend
        $pdf->SetY($chartStartY + 55);
        $pdf->Ln(10);
    }
    
    // Course Completion Rate Bar Chart (if available)
    if (!empty($charts['completion_rate']['labels'])) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Top 10 Courses by Completion Rate', 0, 1, 'L');
        $pdf->Ln(3);
        
        $barChartStartY = $pdf->GetY();
        
        try {
            drawBarChart($pdf, $charts['completion_rate']['labels'], $charts['completion_rate']['data']);
        } catch (Exception $e) {
            error_log('[PDF EXPORT] Error drawing bar chart: ' . $e->getMessage());
        }
        
        // Ensure we're below the bar chart
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Ln(10);
    }
    
    // Data Table
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Course Completion Data', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 58, 64);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(50, 7, 'Course Name', 1, 0, 'L', true);
    $pdf->Cell(20, 7, 'Enrolled', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Completed', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'In Progress', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Not Started', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Completion %', 1, 1, 'C', true);
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($reportData as $row) {
        // Check if we need a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            
            // Repeat header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(52, 58, 64);
            $pdf->SetTextColor(255, 255, 255);
            
            $pdf->Cell(50, 7, 'Course Name', 1, 0, 'L', true);
            $pdf->Cell(20, 7, 'Enrolled', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Completed', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'In Progress', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Not Started', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Completion %', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
        }
        
        $pdf->Cell(50, 6, substr($row['course_name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(20, 6, $row['total_enrollments'], 1, 0, 'C');
        $pdf->Cell(20, 6, $row['completed_count'], 1, 0, 'C');
        $pdf->Cell(25, 6, $row['in_progress_count'], 1, 0, 'C');
        $pdf->Cell(25, 6, $row['not_started_count'], 1, 0, 'C');
        $pdf->Cell(30, 6, round($row['completion_rate'], 1) . '%', 1, 1, 'C');
    }
    
    // Output PDF
    $filename = 'course_completion_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

/**
 * Fallback PDF export using HTML
 */
function exportPDFAlternative($reportData, $summary, $charts, $filters) {
    // Output HTML that can be printed to PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="course_completion_report_' . date('Y-m-d_H-i-s') . '.html"');
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Course Completion Report</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #8b5cf6; }
        .summary { display: flex; gap: 10px; margin: 20px 0; }
        .summary-card { flex: 1; padding: 15px; text-align: center; border: 2px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #343a40; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        @media print { body { margin: 0; } }
    </style></head><body>';
    
    echo '<h1>Course Completion Report</h1>';
    echo '<p style="text-align: center;">Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo '<div class="summary">';
    echo '<div class="summary-card"><h3>' . $summary['total_courses'] . '</h3><p>Total Courses</p></div>';
    echo '<div class="summary-card"><h3>' . $summary['total_enrollments'] . '</h3><p>Total Enrollments</p></div>';
    echo '<div class="summary-card"><h3>' . round($summary['overall_completion_rate'], 1) . '%</h3><p>Completion Rate</p></div>';
    echo '<div class="summary-card"><h3>' . round($summary['avg_completion_percentage'], 1) . '%</h3><p>Avg Completion</p></div>';
    echo '</div>';
    
    echo '<table>';
    echo '<tr><th>Course Name</th><th>Enrolled</th><th>Completed</th><th>In Progress</th><th>Not Started</th><th>Completion Rate</th></tr>';
    
    foreach ($reportData as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['course_name']) . '</td>';
        echo '<td>' . $row['total_enrollments'] . '</td>';
        echo '<td>' . $row['completed_count'] . '</td>';
        echo '<td>' . $row['in_progress_count'] . '</td>';
        echo '<td>' . $row['not_started_count'] . '</td>';
        echo '<td>' . round($row['completion_rate'], 1) . '%</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export report as Excel
 */
function exportExcel($reportData, $summary, $filters) {
    // Check if PhpSpreadsheet is available
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    
    if (!file_exists($autoloadPath)) {
        exportCSV($reportData, $summary, $filters);
        return;
    }
    
    require_once $autoloadPath;
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('UnlockYourSkills LMS')
        ->setTitle('Course Completion Report')
        ->setSubject('Course Completion Report');
    
    // Title
    $sheet->setCellValue('A1', 'Course Completion Report');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Date
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
    $sheet->mergeCells('A2:F2');
    
    // Summary
    $row = 4;
    $sheet->setCellValue('A' . $row, 'Summary');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Total Courses:');
    $sheet->setCellValue('B' . $row, $summary['total_courses']);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Total Enrollments:');
    $sheet->setCellValue('B' . $row, $summary['total_enrollments']);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Overall Completion Rate:');
    $sheet->setCellValue('B' . $row, round($summary['overall_completion_rate'], 1) . '%');
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Avg Completion:');
    $sheet->setCellValue('B' . $row, round($summary['avg_completion_percentage'], 1) . '%');
    $row += 2;
    
    // Table header
    $sheet->setCellValue('A' . $row, 'Course Name');
    $sheet->setCellValue('B' . $row, 'Enrolled');
    $sheet->setCellValue('C' . $row, 'Completed');
    $sheet->setCellValue('D' . $row, 'In Progress');
    $sheet->setCellValue('E' . $row, 'Not Started');
    $sheet->setCellValue('F' . $row, 'Completion Rate');
    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF343A40');
    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
    $row++;
    
    // Table data
    foreach ($reportData as $data) {
        $sheet->setCellValue('A' . $row, $data['course_name']);
        $sheet->setCellValue('B' . $row, $data['total_enrollments']);
        $sheet->setCellValue('C' . $row, $data['completed_count']);
        $sheet->setCellValue('D' . $row, $data['in_progress_count']);
        $sheet->setCellValue('E' . $row, $data['not_started_count']);
        $sheet->setCellValue('F' . $row, round($data['completion_rate'], 1) . '%');
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output Excel file
    $filename = 'course_completion_report_' . date('Y-m-d_H-i-s') . '.xlsx';
    
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
    $filename = 'course_completion_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Title
    fputcsv($output, ['Course Completion Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Total Courses', $summary['total_courses']]);
    fputcsv($output, ['Total Enrollments', $summary['total_enrollments']]);
    fputcsv($output, ['Overall Completion Rate', round($summary['overall_completion_rate'], 1) . '%']);
    fputcsv($output, ['Avg Completion', round($summary['avg_completion_percentage'], 1) . '%']);
    fputcsv($output, []);
    
    // Table header
    fputcsv($output, ['Course Name', 'Enrolled', 'Completed', 'In Progress', 'Not Started', 'Completion Rate']);
    
    // Table data
    foreach ($reportData as $row) {
        fputcsv($output, [
            $row['course_name'],
            $row['total_enrollments'],
            $row['completed_count'],
            $row['in_progress_count'],
            $row['not_started_count'],
            round($row['completion_rate'], 1) . '%'
        ]);
    }
    
    fclose($output);
    exit;
}

