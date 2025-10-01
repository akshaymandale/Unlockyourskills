# PDF Chart Spacing Fix

## Issue
The Completion Status Chart (pie chart) and Department Progress chart (bar chart) were overlapping in the PDF export.

## Root Cause
The Y-position tracking was not properly managed between chart elements:
1. Pie chart was drawn but Y-position wasn't moved below it
2. Legend was positioned using `GetY()` which returned the position before the chart
3. Bar chart labels weren't accounting for multi-line text

## Solution Applied

### 1. Fixed Pie Chart Positioning
**Before:**
```php
drawPieChart($pdf, 60, $pdf->GetY() + 25, 20, $data);
$legendY = $pdf->GetY(); // This was still at the old position!
```

**After:**
```php
$chartStartY = $pdf->GetY();
drawPieChart($pdf, 60, $chartStartY + 25, 20, $data);
$legendY = $chartStartY + 10; // Positioned relative to chart
$pdf->SetY($chartStartY + 55); // Move below both chart and legend
```

### 2. Fixed Legend Positioning
- Changed legend Cell() from `0, 1` (new line) to `0, 0` (same line)
- Manually positioned each legend item using `SetXY()`
- Set explicit Y position after all elements

### 3. Fixed Bar Chart Spacing
**Added:**
- Track maximum label lines used
- Account for multi-line labels in final Y position
- Added extra spacing after bar chart

```php
$maxLabelLines = 1;
foreach ($labels as $label) {
    $lines = ceil(strlen($truncatedLabel) / 15);
    $maxLabelLines = max($maxLabelLines, $lines);
}
$pdf->SetY($labelY + (3 * $maxLabelLines) + 8);
```

### 4. Added Spacing Between Charts
```php
$pdf->Ln(10); // Extra line break after pie chart
$pdf->Ln(10); // Extra line break after bar chart
```

## Layout Now

```
User Progress Report
====================

Summary Cards (4 colored boxes)
[50mm spacing]

Charts Section
--------------

Completion Status Distribution
┌─────────┐  Legend:
│  Pie    │  ■ Completed: XX (XX%)
│  Chart  │  ■ In Progress: XX (XX%)
│         │  ■ Not Started: XX (XX%)
└─────────┘
[65mm total height - no overlap]

Department Average Progress
┌─────────────────────────┐
│  ████ 85%               │
│  ███████ 72%            │
│  █████████ 91%          │
└─────────────────────────┘
 Dept1  Dept2  Dept3
[80-90mm total height including labels]

[New Page]
Progress Data Table
```

## Technical Details

### Positioning Values:
- **Pie Chart**: 
  - Center: X=60, Y=chartStartY+25
  - Radius: 20mm
  - Total height: ~55mm (chart + legend)
  
- **Bar Chart**:
  - Width: 170mm
  - Height: 60mm (bars only)
  - Labels: 3-10mm depending on text
  - Total: 70-80mm

### Spacing Added:
- After summary cards: 20mm
- Between charts title and chart: 5mm
- After pie chart: 10mm
- After bar chart: 10mm
- Before data table: New page

## Testing
Test with different data scenarios:
1. ✅ Only completed courses (full pie slice)
2. ✅ Mixed completion status
3. ✅ Many departments (10+)
4. ✅ Long department names
5. ✅ No department data (bar chart skipped)

## Result
✅ Charts now have proper spacing
✅ No overlapping elements
✅ Professional layout
✅ Consistent positioning
✅ Works with any data size

## Files Modified
- `/api/reports/export-user-progress.php`
  - Line 373-414: Pie chart and legend positioning
  - Line 416-438: Bar chart spacing
  - Line 208-234: Bar chart label handling

