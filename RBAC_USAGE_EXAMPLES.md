# Centralized RBAC Usage Examples

## Overview
This guide shows how to use the centralized Role-Based Access Control (RBAC) system **without modifying existing UI cards**. The system works alongside your current interface.

## Backend Usage

### 1. Include Permission Helper

Add this to any file where you need permissions:

```php
<?php
// Include the permission helper
require_once 'includes/permission_helper.php';
?>
```

### 2. In Controllers - Check Permissions Before Access

```php
<?php
// Example: CourseManagementController.php
class CourseManagementController {
    public function index() {
        // Check if user can access course management
        if (!canAccess('course_management')) {
            // Redirect to dashboard or show error
            header('Location: /dashboard');
            exit;
        }
        
        // Continue with normal logic
        $courses = $this->loadCourses();
        require 'views/course_management.php';
    }
    
    public function create() {
        // Check if user can create courses
        if (!canCreate('course_management')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Create course logic
        $this->saveCourse($_POST);
    }
    
    public function edit($id) {
        // Check if user can edit courses
        if (!canEdit('course_management')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Edit course logic
        $this->updateCourse($id, $_POST);
    }
    
    public function delete($id) {
        // Check if user can delete courses
        if (!canDelete('course_management')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Delete course logic
        $this->removeCourse($id);
    }
}
?>
```

### 3. In Views - Show/Hide Elements Based on Permissions

```php
<!-- Example: course_management.php -->
<div class="container">
    <h1>Course Management</h1>
    
    <!-- Show create button only if user has permission -->
    <?php if (canCreate('course_management')): ?>
        <button class="btn btn-success" onclick="createCourse()">
            <i class="fas fa-plus"></i> Create Course
        </button>
    <?php endif; ?>
    
    <div class="courses-list">
        <?php foreach ($courses as $course): ?>
            <div class="course-item">
                <h3><?= htmlspecialchars($course['title']) ?></h3>
                
                <!-- Action buttons based on permissions -->
                <div class="actions">
                    <?php if (canEdit('course_management')): ?>
                        <button class="btn btn-primary btn-sm" onclick="editCourse(<?= $course['id'] ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    <?php endif; ?>
                    
                    <?php if (canDelete('course_management')): ?>
                        <button class="btn btn-danger btn-sm" onclick="deleteCourse(<?= $course['id'] ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

## Frontend Usage

### 1. Include Permission Manager Script

Add this to your header or before closing body tag:

```html
<script src="public/js/permission_manager.js"></script>
```

### 2. JavaScript Permission Checks

```javascript
// Example: course_management.js

// Check permission before creating course
function createCourse() {
    checkPermission('course_management', 'create', function() {
        // This will only execute if user has permission
        showCreateCourseModal();
    });
}

// Check permission before editing course
function editCourse(courseId) {
    checkPermission('course_management', 'edit', function() {
        // This will only execute if user has permission
        showEditCourseModal(courseId);
    });
}

// Check permission before deleting course
function deleteCourse(courseId) {
    checkPermission('course_management', 'delete', function() {
        // This will only execute if user has permission
        showDeleteConfirmation(courseId);
    });
}

// Update permissions after AJAX content loads
function loadCourses() {
    fetch('/api/courses')
        .then(response => response.json())
        .then(data => {
            renderCourses(data);
            updatePermissions(); // Re-apply permission checks
        });
}
```

### 3. Optional: Add Data Attributes to Existing Elements

If you want to automatically hide elements based on permissions, you can add data attributes to existing HTML:

```html
<!-- This will automatically hide if user doesn't have access -->
<div data-permission="course_management:access">
    <h5>Course Management</h5>
    <!-- Your existing content -->
</div>

<!-- This button will be disabled if user doesn't have create permission -->
<button data-action-permission="course_management:create" class="btn btn-success">
    Create Course
</button>
```

## Available Modules

Use these module names in your permission checks:

- `user_management` - User Management
- `user_roles_permissions` - User Roles & Permissions  
- `course_management` - Course Management
- `course_creation` - Course Creation
- `course_categories` - Course Categories
- `course_subcategories` - Course Subcategories
- `course_applicability` - Course Applicability
- `vlr` - Virtual Learning Repository
- `assessments` - Assessments
- `announcements` - Announcements
- `events` - Events
- `opinion_polls` - Opinion Polls
- `social_feed` - Social Feed
- `reports` - Reports
- `custom_fields` - Custom Fields
- `client_management` - Client Management
- `settings` - Settings

## Permission Actions

Each module supports these actions:
- `access` - Can view/access the module
- `create` - Can create new items
- `edit` - Can edit existing items
- `delete` - Can delete items

## Example Implementation for Different Modules

### Announcements Module

```php
// AnnouncementController.php
class AnnouncementController {
    public function index() {
        if (!canAccess('announcements')) {
            header('Location: /dashboard');
            exit;
        }
        
        $announcements = $this->loadAnnouncements();
        require 'views/announcements.php';
    }
    
    public function create() {
        if (!canCreate('announcements')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Create announcement logic
    }
}
```

### Events Module

```php
// EventController.php
class EventController {
    public function index() {
        if (!canAccess('events')) {
            header('Location: /dashboard');
            exit;
        }
        
        $events = $this->loadEvents();
        require 'views/events.php';
    }
    
    public function edit($id) {
        if (!canEdit('events')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Edit event logic
    }
}
```

### Reports Module

```php
// ReportController.php
class ReportController {
    public function index() {
        if (!canAccess('reports')) {
            header('Location: /dashboard');
            exit;
        }
        
        $reports = $this->loadReports();
        require 'views/reports.php';
    }
}
```

## Debugging

### Backend Debugging

```php
// Check permission status for debugging
$status = PermissionHelper::getInstance()->getPermissionStatus('course_management');
print_r($status);

// Check specific permissions
echo "Can access: " . (canAccess('course_management') ? 'Yes' : 'No') . "\n";
echo "Can create: " . (canCreate('course_management') ? 'Yes' : 'No') . "\n";
echo "Can edit: " . (canEdit('course_management') ? 'Yes' : 'No') . "\n";
echo "Can delete: " . (canDelete('course_management') ? 'Yes' : 'No') . "\n";
```

### Frontend Debugging

```javascript
// Show all permissions in console
showPermissions();

// Check specific permissions
console.log('Can access course management:', canAccess('course_management'));
console.log('Can create courses:', canCreate('course_management'));
console.log('Can edit courses:', canEdit('course_management'));
console.log('Can delete courses:', canDelete('course_management'));
```

## Integration with Existing Code

### 1. Add to Header (Optional)

If you want to include the permission helper globally, add this to your header:

```php
// In header.php
require_once 'includes/permission_helper.php';
outputFrontendPermissions(); // Outputs JavaScript variables
```

### 2. Use in Existing Controllers

Simply add permission checks to your existing controller methods:

```php
// In your existing controller
public function someAction() {
    // Add this line at the beginning
    if (!canAccess('your_module_name')) {
        header('Location: /dashboard');
        exit;
    }
    
    // Your existing code continues here...
}
```

### 3. Use in Existing Views

Add permission checks to your existing view files:

```php
<!-- In your existing view -->
<?php if (canCreate('your_module_name')): ?>
    <!-- Your existing create button -->
    <button class="btn btn-success">Create</button>
<?php endif; ?>
```

This approach allows you to implement centralized RBAC without changing your existing UI structure! 