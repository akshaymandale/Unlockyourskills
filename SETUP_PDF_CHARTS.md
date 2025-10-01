# Setup PDF Charts - Installation Guide

## Problem
Charts are not appearing in PDF downloads because the TCPDF library is not installed.

## Solution: Install TCPDF via Composer

### Step 1: Check if Composer is installed
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills
composer --version
```

If Composer is not installed, install it first:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Create composer.json file
Create a file named `composer.json` in your project root with this content:
```json
{
    "require": {
        "tecnickcom/tcpdf": "^6.6",
        "phpoffice/phpspreadsheet": "^1.29"
    }
}
```

### Step 3: Install dependencies
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills
composer install
```

This will create a `vendor` directory with TCPDF and PhpSpreadsheet libraries.

### Step 4: Test PDF export
1. Go to `http://localhost/Unlockyourskills/reports/user-progress`
2. Click the **PDF** button
3. You should now see visual charts in your PDF!

## Current Status

**Without TCPDF**: The system falls back to HTML export with CSS-based charts
- ✅ Still functional
- ✅ Charts visible in browser
- ✅ Can be printed to PDF from browser
- ❌ Not a true PDF file
- ❌ Requires browser to view

**With TCPDF**: True PDF with embedded vector charts
- ✅ Professional PDF document
- ✅ Embedded visual charts (pie & bar)
- ✅ Can be opened in any PDF reader
- ✅ Perfect for emailing/sharing
- ✅ No browser required

## Alternative: Quick HTML to PDF

If you can't install Composer right now, you can still use the HTML export and convert it to PDF:

1. Click the PDF button (it downloads an HTML file)
2. Open the HTML file in your browser
3. Press `Cmd+P` (Mac) or `Ctrl+P` (Windows)
4. Choose "Save as PDF"
5. You'll have a PDF with visual charts!

The HTML export includes:
- ✅ Visual pie chart (using CSS conic-gradient)
- ✅ Visual bar charts
- ✅ All data tables
- ✅ Print-optimized styles

## Troubleshooting

### Charts still not showing after installing TCPDF?
Check the error log at:
```bash
tail -f /Applications/XAMPP/xamppfiles/logs/error_log
```

Look for lines starting with `[PDF EXPORT]` to see what's happening.

### Permission errors?
```bash
sudo chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills/vendor
```

### Want to verify TCPDF is installed?
```bash
ls -la /Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills/vendor/tecnickcom/tcpdf/
```

If you see files, TCPDF is installed!

## What You Get With Visual Charts

### Pie Chart Features:
- 🟢 Green slice for Completed courses
- 🟡 Yellow slice for In Progress courses
- ⚫ Gray slice for Not Started courses  
- Color-coded legend with exact counts and percentages

### Bar Chart Features:
- Purple bars matching your #6a0dad theme
- Progress percentage on top of each bar
- Grid lines for easy reading
- Y-axis from 0-100%
- Department names labeled

## Need Help?

Check the logs:
```bash
tail -n 50 /Applications/XAMPP/xamppfiles/logs/error_log | grep "PDF EXPORT"
```

This will show you exactly what's happening during PDF generation.

