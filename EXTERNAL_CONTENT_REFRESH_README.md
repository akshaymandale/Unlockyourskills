# External Content Refresh System

## Overview

This system automatically refreshes the course details page when external content tabs are closed, ensuring users always see the most up-to-date progress information. It uses the same reliable localStorage-based polling mechanism as the document refresh system.

## How It Works

### 1. External Content Launch
- User clicks "View External Content" on course details page
- External content opens in new tab via `content_viewer.php`
- Parent page starts monitoring for close events

### 2. External Content Closure Detection
When external content tab is closed (either via close button or tab close), **multiple event listeners** ensure the close is detected:

```javascript
// 1. User clicks close button
function closeTab() {
    // Sets localStorage flag for external content
    localStorage.setItem('external_closed_' + contentId, Date.now().toString());
    // Attempts to close window
}

// 2. Multiple event listeners for reliability
window.addEventListener('beforeunload', setDocumentCloseFlag);
document.addEventListener('pagehide', setDocumentCloseFlag);
window.addEventListener('unload', setDocumentCloseFlag);
```

### 3. Parent Page Monitoring
The parent page (`my_course_details.php`) runs a **polling system** that checks localStorage every 2 seconds:

```javascript
function startDocumentCloseMonitoring() {
    // Get all external content IDs from the page
    const externalButtons = document.querySelectorAll('.launch-content-btn[data-type="external"]');
    const externalIds = Array.from(externalButtons).map(btn => btn.dataset.contentId);
    
    // Check every 2 seconds for external content close flags
    const checkInterval = setInterval(() => {
        // Look for localStorage flags like 'external_closed_123'
        const closeFlag = localStorage.getItem('external_closed_' + contentId);
        if (closeFlag) {
            // Trigger page refresh
            window.location.reload();
        }
    }, 2000);
}
```

## Implementation Details

### Files Modified

#### 1. `views/content_viewer.php`
- **Enhanced `setDocumentCloseFlag()` function**: Now handles external content type
- **Event listeners**: `beforeunload`, `pagehide`, `unload` for automatic detection
- **localStorage flags**: Sets flags like `external_closed_[contentId]` for external content

#### 2. `views/my_course_details.php`
- **Enhanced `startDocumentCloseMonitoring()` function**: Now includes external content monitoring
- **External content detection**: Monitors `.launch-content-btn[data-type="external"]` buttons
- **Close flag checking**: Checks for `external_closed_[contentId]` flags
- **Auto-refresh**: Triggers `window.location.reload()` when external content close detected

#### 3. `test_external_content_refresh.php`
- **Test file**: Verifies the external content refresh system works
- **Simulates course details page**: Shows localStorage monitoring in action
- **Multiple content types**: Tests external, document, and audio content

## Key Features

### ✅ **Reliability**
- Multiple event listeners catch all close scenarios
- localStorage works even if postMessage fails
- No complex window communication required

### ✅ **Performance**
- Lightweight polling (every 2 seconds)
- Minimal impact on page performance
- Automatic cleanup when page refreshes

### ✅ **User Experience**
- Clear notification when refresh is happening
- Automatic refresh without user intervention
- No broken functionality

### ✅ **Browser Compatibility**
- Works in all modern browsers
- No special browser features required
- Fallback mechanisms built-in

## Content Type Handling

The system now handles all content types with specific logic:

- **External Content**: Simple refresh after 2 seconds
- **Documents**: Simple refresh after 2 seconds  
- **Audio**: Update progress first, then refresh after 1 second
- **Video**: Update progress first, then refresh after 1 second
- **Images**: Simple refresh after 2 seconds

## Testing

### Test File Usage
1. Open `test_external_content_refresh.php` in your browser
2. Click "Open External Content" to simulate opening external content
3. Close the external content tab (close button or tab close)
4. Watch the test page detect the close and simulate refresh
5. Use "Check localStorage" to see what flags are set

### Real Implementation Testing
1. Navigate to a course details page
2. Click "View External Content" on any external content
3. Close the external content tab
4. Verify the course details page refreshes automatically

## Technical Details

### Event Listeners Used
- `closeTab()` - User clicks close button
- `beforeunload` - Page is being unloaded
- `pagehide` - Page is being hidden (more reliable in some browsers)
- `unload` - Final event before window closes

### localStorage Keys
- Format: `external_closed_[contentId]`
- Value: Unix timestamp when external content was closed
- Example: `external_closed_123 = 1703123456789`

### Polling Configuration
- **Check Interval**: Every 2 seconds
- **Refresh Delay**: 2 seconds after close detection
- **Notification Duration**: 3 seconds
- **Cleanup**: Automatic when page refreshes

## Integration with Existing System

The external content refresh system integrates seamlessly with the existing document refresh system:

- **Same monitoring function**: `startDocumentCloseMonitoring()`
- **Same polling interval**: 2 seconds
- **Same refresh mechanism**: `window.location.reload()`
- **Same notification system**: Bootstrap alerts
- **Same safety mechanisms**: Duplicate prevention, processed flags

## Advantages Over Complex Solutions

### ❌ **Problems with postMessage**
- Can fail if parent window is unavailable
- Complex error handling required
- Browser compatibility issues
- Security concerns with cross-origin

### ✅ **Benefits of localStorage**
- Always works if both pages are from same origin
- Simple key-value storage
- No complex messaging protocols
- Reliable across all browsers

## Troubleshooting

### Common Issues

#### 1. Page Not Refreshing
- Check browser console for errors
- Verify external content buttons have correct `data-type="external"` attribute
- Check if localStorage flags are being set correctly
- Ensure monitoring function is started

#### 2. Multiple Refreshes
- Check for duplicate event listeners
- Verify processed flags are working correctly
- Check if multiple monitoring instances are running

#### 3. External Content Not Detected
- Verify external content buttons have correct `data-content-id` attribute
- Check if content type is correctly set to "external"
- Ensure monitoring function includes external content IDs

### Debug Information
The system provides comprehensive logging:
```javascript
console.log('Monitoring external close events for:', externalIds);
console.log('External content close detected for:', contentId);
console.log('External content was closed - refreshing page to show updated progress');
```

## Summary

The external content refresh system provides a **robust, reliable way** to automatically refresh the course details page when external content tabs are closed. It:

1. **Detects tab closure** through multiple event listeners
2. **Sets flags in localStorage** when external content is closed
3. **Polls for close events** every 2 seconds on the parent page
4. **Automatically refreshes** the parent page to show updated progress
5. **Prevents duplicate refreshes** and provides user feedback

This ensures that users always see the most up-to-date progress information after closing external content tabs, without requiring manual page refreshes.
