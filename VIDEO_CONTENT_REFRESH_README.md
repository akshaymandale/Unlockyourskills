# Video Content Refresh System

## Overview

This system automatically refreshes the course details page when video content tabs are closed, ensuring users always see the most up-to-date progress information. It uses the same reliable localStorage-based polling mechanism as the document and external content refresh systems.

## How It Works

### 1. Video Content Launch
- User clicks "View Video Content" on course details page
- Video content opens in new tab via `content_viewer.php`
- Parent page starts monitoring for close events

### 2. Video Content Closure Detection
When video content tab is closed (either via close button or tab close), **multiple event listeners** ensure the close is detected:

```javascript
// 1. User clicks close button
function closeTab() {
    // Sets localStorage flag for video content
    localStorage.setItem('video_closed_' + contentId, Date.now().toString());
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
    // Get all video content IDs from the page
    const videoButtons = document.querySelectorAll('.launch-content-btn[data-type="video"]');
    const videoIds = Array.from(videoButtons).map(btn => btn.dataset.contentId);
    
    // Check every 2 seconds for video content close flags
    const checkInterval = setInterval(() => {
        // Look for localStorage flags like 'video_closed_123'
        const closeFlag = localStorage.getItem('video_closed_' + contentId);
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
- **Enhanced `setDocumentCloseFlag()` function**: Now handles video content type
- **Event listeners**: `beforeunload`, `pagehide`, `unload` for automatic detection
- **localStorage flags**: Sets flags like `video_closed_[contentId]` for video content
- **Content type detection**: Properly identifies video content and sets appropriate flags

#### 2. `views/my_course_details.php`
- **Enhanced `startDocumentCloseMonitoring()` function**: Already includes video content monitoring
- **Video content detection**: Monitors `.launch-content-btn[data-type="video"]` buttons
- **Close flag checking**: Checks for `video_closed_[contentId]` flags every 2 seconds
- **Auto-refresh**: Triggers `window.location.reload()` when video content close detected
- **Progress update**: Updates video progress before refresh for better user experience

#### 3. `test_video_content_refresh.php`
- **Test file**: Verifies the video content refresh system works
- **Simulates course details page**: Shows localStorage monitoring in action
- **Multiple content types**: Tests video, document, audio, and external content
- **Video simulation**: Includes interactive video player simulation

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
- Progress updates before refresh for video content
- No broken functionality

### ✅ **Browser Compatibility**
- Works in all modern browsers
- No special browser features required
- Fallback mechanisms built-in

## Content Type Handling

The system now handles all content types with optimized refresh timing:

- **Video Content**: Update progress first, then refresh after 1 second
- **Audio Content**: Update progress first, then refresh after 1 second
- **External Content**: Simple refresh after 2 seconds
- **Documents**: Simple refresh after 2 seconds  
- **Images**: Simple refresh after 2 seconds

## Video-Specific Features

### **Progress Tracking**
- Video content updates progress before refresh
- Ensures latest progress is saved to database
- User sees updated progress immediately after refresh

### **Enhanced Monitoring**
- Video content gets priority in monitoring system
- Faster refresh timing (1 second vs 2 seconds for other content)
- Progress update integration for seamless experience

### **Event Handling**
- Multiple close detection methods for reliability
- Handles both manual close button and tab close
- Automatic flag cleanup to prevent duplicates

## Testing

### Test File Usage
1. Open `test_video_content_refresh.php` in your browser
2. Click "Open Video Content" to simulate opening video content
3. Close the video content tab (close button or tab close)
4. Watch the test page detect the close and simulate refresh
5. Use "Check localStorage" to see what flags are set
6. Test video simulation controls (play, pause, complete)

### Real Implementation Testing
1. Navigate to a course details page
2. Click "View Video Content" on any video content
3. Close the video content tab
4. Verify the course details page refreshes automatically
5. Check that updated progress is displayed

## Technical Details

### Event Listeners Used
- `closeTab()` - User clicks close button
- `beforeunload` - Page is being unloaded
- `pagehide` - Page is being hidden (more reliable in some browsers)
- `unload` - Final event before window closes

### localStorage Keys
- Format: `video_closed_[contentId]`
- Value: Unix timestamp when video content was closed
- Example: `video_closed_123 = 1703123456789`

### Polling Configuration
- **Check Interval**: Every 2 seconds
- **Refresh Delay**: 1 second after close detection (for video content)
- **Progress Update**: Before refresh for video content
- **Notification Duration**: 3 seconds
- **Cleanup**: Automatic when page refreshes

## Integration with Existing System

The video content refresh system integrates seamlessly with the existing refresh infrastructure:

- **Same monitoring function**: `startDocumentCloseMonitoring()`
- **Same polling interval**: 2 seconds
- **Same refresh mechanism**: `window.location.reload()`
- **Same notification system**: Bootstrap alerts
- **Same safety mechanisms**: Duplicate prevention, processed flags
- **Enhanced timing**: Faster refresh for video content (1 second)

## Video Progress Update Process

### **Before Refresh**
1. Detect video content close
2. Call `refreshVideoProgress(contentId)` function
3. Update progress in database
4. Wait for progress update to complete

### **After Progress Update**
1. Show success/error notification
2. Refresh page after 1 second delay
3. Display updated progress information
4. Resume monitoring for new content

### **Error Handling**
- If progress update fails, still refresh after 2 seconds
- Fallback mechanism ensures page always refreshes
- User sees updated content even if progress update fails

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
- Verify video content buttons have correct `data-type="video"` attribute
- Check if localStorage flags are being set correctly
- Ensure monitoring function is started

#### 2. Multiple Refreshes
- Check for duplicate event listeners
- Verify processed flags are working correctly
- Check if multiple monitoring instances are running

#### 3. Video Content Not Detected
- Verify video content buttons have correct `data-content-id` attribute
- Check if content type is correctly set to "video"
- Ensure monitoring function includes video content IDs

#### 4. Progress Not Updating
- Check if `refreshVideoProgress` function is available
- Verify video progress API endpoints are working
- Check browser console for progress update errors

### Debug Information
The system provides comprehensive logging:
```javascript
console.log('Monitoring video close events for:', videoIds);
console.log('Video close detected for:', contentId);
console.log('Video was closed - updating progress before refresh');
console.log('Video progress updated, refreshing page...');
```

## Performance Considerations

### **Polling Optimization**
- 2-second interval balances responsiveness with performance
- Monitoring stops when page refreshes
- Automatic cleanup prevents memory leaks

### **Progress Update Efficiency**
- Progress updates happen asynchronously
- Non-blocking refresh process
- Fallback timing ensures user experience

### **Memory Management**
- Event listeners are properly cleaned up
- localStorage flags are removed after processing
- Session storage prevents duplicate processing

## Summary

The video content refresh system provides a **robust, reliable way** to automatically refresh the course details page when video content tabs are closed. It:

1. **Detects tab closure** through multiple event listeners
2. **Sets flags in localStorage** when video content is closed
3. **Updates progress** before refreshing for better user experience
4. **Polls for close events** every 2 seconds on the parent page
5. **Automatically refreshes** the parent page to show updated progress
6. **Prevents duplicate refreshes** and provides user feedback

This ensures that users always see the most up-to-date progress information after closing video content tabs, with enhanced progress tracking and faster refresh timing compared to other content types.

The system is now **fully integrated** with your existing refresh infrastructure and provides the same reliable, automatic refresh functionality for video content that users already enjoy with documents, audio, external content, and images.
