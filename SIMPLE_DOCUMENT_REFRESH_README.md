# Simple Document Refresh System

## Overview

This is a simplified, reliable document refresh system that automatically refreshes the course details page when documents are closed. It uses localStorage for communication instead of complex window messaging, making it more reliable across different browsers and scenarios.

## How It Works

### 1. Document Launch
- User clicks "View Document" on course details page
- Document opens in new tab
- No special handling needed

### 2. Document Closure Detection
- When document tab is closed (close button or tab close), a flag is set in localStorage
- The flag format is: `document_closed_[contentId] = [timestamp]`
- Multiple event listeners ensure the flag is set even if the close button isn't used

### 3. Parent Page Monitoring
- Course details page polls localStorage every 2 seconds
- When a close flag is detected, the page refreshes automatically
- User sees a notification that the page is refreshing

## Implementation Details

### Files Modified

#### 1. `views/content_viewer.php`
- Enhanced `closeTab()` function to set localStorage flag
- Added multiple event listeners for reliable detection:
  - `beforeunload` - Page being unloaded
  - `pagehide` - Page being hidden
  - `unload` - Window being unloaded
- Helper function `setDocumentCloseFlag()` for consistent flag setting

#### 2. `views/my_course_details.php`
- Added `startDocumentCloseMonitoring()` function
- Polls localStorage every 2 seconds for document close flags
- Automatically refreshes page when flags are detected
- Shows user notification during refresh process

#### 3. `test_simple_document_refresh.php`
- Test file to verify the system works
- Simulates the course details page
- Shows localStorage monitoring in action

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

## Testing

### Test File Usage
1. Open `test_simple_document_refresh.php` in your browser
2. Click "Open Document" to simulate opening a document
3. Close the document tab (close button or tab close)
4. Watch the test page detect the close and simulate refresh
5. Use "Check localStorage" to see what flags are set

### Real Implementation Testing
1. Navigate to a course details page
2. Click "View Document" on any document
3. Close the document tab
4. Verify the course details page refreshes automatically

## Technical Details

### Event Listeners Used
- `closeTab()` - User clicks close button
- `beforeunload` - Page is being unloaded
- `pagehide` - Page is being hidden (more reliable in some browsers)
- `unload` - Final event before window closes

### localStorage Keys
- Format: `document_closed_[contentId]`
- Value: Unix timestamp when document was closed
- Example: `document_closed_123 = 1703123456789`

### Polling Configuration
- **Check Interval**: Every 2 seconds
- **Refresh Delay**: 2 seconds after close detection
- **Notification Duration**: 5 seconds
- **Cleanup**: Automatic when page refreshes

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
- Verify localStorage is available
- Check if content IDs are being passed correctly

#### 2. Multiple Refreshes
- System automatically prevents multiple refreshes
- Each close flag is removed after processing
- Page refresh stops monitoring

#### 3. Performance Issues
- Polling interval is only 2 seconds
- Minimal CPU usage
- Automatic cleanup prevents memory leaks

### Debug Information
- Console logging shows all events
- localStorage keys can be inspected manually
- Test file provides visual feedback

## Security Considerations

### Origin Validation
- localStorage only works within same origin
- No cross-site communication possible
- Secure by design

### Data Privacy
- Only stores document close timestamps
- No personal information stored
- Data is local to user's browser

## Conclusion

This simple localStorage-based approach provides reliable document refresh functionality without the complexity and potential failures of window messaging systems. It's lightweight, browser-compatible, and won't disturb any existing functionality in your application.

The system automatically detects when documents are closed and refreshes the course details page to show updated progress, providing a seamless user experience with minimal code changes.
