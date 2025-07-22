# Session Timeout Feature

## Overview

The session timeout feature automatically logs out users after 1 hour of inactivity to enhance security. The system includes both server-side and client-side timeout management with user-friendly warnings.

## Features

### Server-Side Timeout Management
- **Automatic Logout**: Users are automatically logged out after 1 hour of inactivity
- **Session Tracking**: Tracks user activity using `$_SESSION['last_activity']` timestamp
- **Middleware Integration**: Integrated into `AuthMiddleware` for seamless operation
- **AJAX Support**: Handles AJAX requests with appropriate JSON responses
- **Logging**: Logs timeout events for audit purposes

### Client-Side Timeout Management
- **Activity Tracking**: Monitors user activity (mouse, keyboard, scroll, touch)
- **Warning System**: Shows warning modal 5 minutes before timeout
- **Session Extension**: Users can extend their session with one click
- **Automatic Redirect**: Redirects to login page with timeout message
- **Real-time Updates**: Sends activity pings to server

## Implementation Details

### Files Modified/Created

#### Core Files
- `core/middleware/AuthMiddleware.php` - Enhanced with timeout checking
- `core/middleware/SessionTimeoutMiddleware.php` - Dedicated timeout middleware (created)
- `controllers/LoginController.php` - Added timeout message handling
- `controllers/SessionController.php` - API endpoint for session activity (created)

#### Views
- `views/login.php` - Added timeout message display
- `views/includes/header.php` - Added session timeout JavaScript

#### JavaScript
- `public/js/session-timeout.js` - Client-side timeout management (created)

#### Configuration
- `config/session.php` - Session configuration (created)
- `routes/web.php` - Added session activity API route

### Configuration Options

The session timeout can be configured through environment variables or by modifying `config/session.php`:

```php
// Timeout duration (default: 60 minutes)
SESSION_TIMEOUT_MINUTES=60

// Warning time before timeout (default: 5 minutes)
SESSION_WARNING_MINUTES=5

// Check interval in milliseconds (default: 60000 = 1 minute)
SESSION_CHECK_INTERVAL=60000

// Enable/disable logging
SESSION_LOGGING_ENABLED=true
SESSION_LOG_TIMEOUTS=true
SESSION_LOG_ACTIVITY=false
```

## How It Works

### 1. Server-Side Process
1. **Session Initialization**: When user logs in, `$_SESSION['last_activity']` is set
2. **Activity Tracking**: Each request updates the timestamp
3. **Timeout Check**: `AuthMiddleware` checks if session has timed out
4. **Automatic Logout**: If timeout exceeded, session is destroyed and user redirected

### 2. Client-Side Process
1. **Activity Monitoring**: JavaScript tracks user interactions
2. **Periodic Checks**: Checks session status every minute
3. **Warning Display**: Shows warning modal 5 minutes before timeout
4. **Session Extension**: User can extend session or logout immediately
5. **Automatic Redirect**: Redirects to login if timeout reached

### 3. API Integration
- **Activity Pings**: Client sends activity updates to `/api/session/activity`
- **Session Status**: API provides current session status
- **Session Extension**: API handles session extension requests

## User Experience

### Normal Flow
1. User logs in and starts using the system
2. System tracks activity automatically
3. 5 minutes before timeout, warning modal appears
4. User can extend session or logout
5. If no action taken, automatic logout occurs

### Timeout Flow
1. User becomes inactive for 1 hour
2. System automatically logs out user
3. User redirected to login page with timeout message
4. User must log in again to continue

## Security Benefits

- **Prevents Unauthorized Access**: Reduces risk of session hijacking
- **Compliance**: Meets security requirements for sensitive applications
- **Audit Trail**: Logs timeout events for security monitoring
- **User Control**: Users can extend sessions when needed

## Testing

### Manual Testing
1. Log in to the system
2. Leave the browser idle for 1 hour
3. Verify automatic logout occurs
4. Check timeout message on login page

### Quick Testing (Development)
To test quickly, temporarily reduce timeout duration:

```php
// In AuthMiddleware.php, change timeout to 1 minute for testing
private $timeoutMinutes = 1; // 1 minute for testing
```

### API Testing
Test the session activity API:

```bash
# Activity ping
curl -X POST http://localhost/unlockyourskills/api/session/activity \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"action":"ping","timestamp":1234567890}'

# Session status
curl -X POST http://localhost/unlockyourskills/api/session/activity \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"action":"status"}'
```

## Troubleshooting

### Common Issues

1. **Session not timing out**
   - Check if `$_SESSION['last_activity']` is being set
   - Verify middleware is being called
   - Check server logs for timeout events

2. **Warning not showing**
   - Ensure JavaScript is loaded
   - Check browser console for errors
   - Verify API endpoint is accessible

3. **API errors**
   - Check session is active
   - Verify request headers
   - Check server error logs

### Debug Mode

Enable debug logging by setting:

```php
// In config/session.php
'logging' => [
    'enabled' => true,
    'log_timeouts' => true,
    'log_activity' => true, // Enable for debugging
],
```

## Future Enhancements

- **Configurable Timeouts**: Per-user or per-role timeout settings
- **Remember Me**: Option to extend session duration
- **Idle Detection**: More sophisticated idle detection
- **Session Analytics**: Dashboard for session statistics
- **Mobile Optimization**: Better mobile experience for warnings 