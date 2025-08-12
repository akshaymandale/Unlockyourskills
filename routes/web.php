<?php

/**
 * Web Routes for UnlockYourSkills Application
 * Laravel-style routing implementation
 */

// ===================================
// PUBLIC ROUTES (No Authentication Required)
// ===================================

// Login & Authentication
Router::get('/', 'LoginController@index');
Router::get('/login', 'LoginController@index');
Router::post('/login', 'LoginController@login');
Router::get('/logout', 'LoginController@logout');
Router::post('/logout', 'LoginController@logout');

// Language switching (public)
Router::get('/lang/{language}', 'LanguageController@switch');

// Location API (for dynamic dropdowns)
Router::post('/api/locations/states', 'LocationController@getStatesByCountry');
Router::post('/api/locations/cities', 'LocationController@getCitiesByState');
Router::post('/api/locations/timezones', 'LocationController@getTimezonesByCountry');

// Session Activity API (for timeout management)
Router::post('/api/session/activity', 'SessionController@activity');

// Debug logging endpoint
Router::post('/debug-log', function() {
    require_once 'debug-log.php';
});

// ===================================
// AUTHENTICATED ROUTES
// ===================================

Router::middleware(['Auth'])->group(function() {

    // Dashboard
    Router::get('/dashboard', 'DashboardController@index');

    // Manage Portal
    Router::get('/manage-portal', 'ManagePortalController@index');
    
    // ===================================
    // USER MANAGEMENT
    // ===================================
    
    // Modal Content Routes (MUST come before parameterized routes)
    Router::get('/users/modal/add', 'UserManagementController@loadAddUserModal');
    Router::get('/users/modal/edit', 'UserManagementController@loadEditUserModal');
    Router::post('/users/modal/add', 'UserManagementController@submitAddUserModal');
    Router::post('/users/modal/edit', 'UserManagementController@submitEditUserModal');

    // User AJAX operations
    Router::post('/users/ajax/search', 'UserManagementController@ajaxSearch');
    Router::post('/users/ajax/toggle-status', 'UserManagementController@toggleStatus');
    Router::post('/users/import', 'UserManagementController@import');

    // User Management Routes
    Router::get('/users', 'UserManagementController@index');
    Router::get('/users/create', 'UserManagementController@add');
    Router::post('/users', 'UserManagementController@save');
    Router::get('/users/{id}/edit', 'UserManagementController@edit');
    Router::put('/users/{id}', 'UserManagementController@update');
    Router::post('/users/{id}', 'UserManagementController@update'); // For form submissions
    Router::delete('/users/{id}', 'UserManagementController@delete');
    Router::get('/users/{id}/delete', 'UserManagementController@delete'); // For GET delete links

    // User lock/unlock operations
    Router::post('/users/{id}/lock', 'UserManagementController@lock');
    Router::post('/users/{id}/unlock', 'UserManagementController@unlock');
    Router::get('/users/{id}/lock', 'UserManagementController@lock'); // For GET lock links
    Router::get('/users/{id}/unlock', 'UserManagementController@unlock'); // For GET unlock links
    
    // ===================================
    // MY COURSES & PORTAL
    // ===================================
    Router::get('/my-courses', 'MyCoursesController@index');
    Router::get('/my-courses/list', 'MyCoursesController@getUserCourses');
    Router::get('/manage-portal', 'ManagePortalController@index');
    Router::get('/my-courses/details/{id}', 'MyCoursesController@details');
    Router::get('/my-courses/view-content', 'MyCoursesController@viewContent');
    Router::get('/my-courses/start', 'MyCoursesController@start');

    // ===================================
    // ASSESSMENT PLAYER
    // ===================================
    Router::get('/assessment-player', 'AssessmentPlayerController@start');
    Router::get('/assessment-player/start', 'AssessmentPlayerController@start');
    Router::post('/assessment-player/save-answer', 'AssessmentPlayerController@saveAnswer');
    Router::post('/assessment-player/submit-assessment', 'AssessmentPlayerController@submitAssessment');
    Router::get('/assessment-player/progress', 'AssessmentPlayerController@getProgress');
    Router::post('/assessment-player/update-time', 'AssessmentPlayerController@updateTime');
    Router::get('/assessment-player/health-check', 'AssessmentPlayerController@healthCheck');

    // ===================================
    // COURSE CATEGORIES
    // ===================================
    
    // Course Categories
    Router::get('/course-categories', 'CourseCategoryController@index');
    
    // Course Categories AJAX operations
    Router::post('/course-categories/ajax/search', 'CourseCategoryController@ajaxSearch');
    Router::post('/course-categories/ajax/toggle-status', 'CourseCategoryController@toggleStatus');
    Router::post('/course-categories/submit', 'CourseCategoryController@submitCategory');
    
    // Course Categories CRUD operations
    Router::get('/course-categories/create', 'CourseCategoryController@create');
    Router::post('/course-categories', 'CourseCategoryController@create');
    Router::get('/course-categories/{id}/edit', 'CourseCategoryController@edit');
    Router::post('/course-categories/{id}', 'CourseCategoryController@edit');
    Router::delete('/course-categories/{id}', 'CourseCategoryController@delete');
    Router::post('/course-categories/{id}/delete', 'CourseCategoryController@delete'); // POST fallback for delete
    Router::get('/course-categories/{id}/delete', 'CourseCategoryController@delete'); // For GET delete links
    
    // Course Categories API (more specific routes after general ones)
    Router::get('/course-categories/get/{id}', 'CourseCategoryController@get');
    Router::get('/api/course-categories/dropdown', 'CourseCategoryController@getCategoriesForDropdown');
    
    // Course Categories Modal operations (more specific routes after general ones)
    Router::post('/course-categories/modal/add', 'CourseCategoryController@loadAddModal');
    Router::post('/course-categories/modal/edit', 'CourseCategoryController@loadEditModal');
    
    // Custom Fields for Users (legacy routes)
    Router::get('/users/custom-fields', 'CustomFieldController@index');
    Router::post('/users/custom-fields', 'CustomFieldController@save');
    Router::delete('/users/custom-fields/{id}', 'CustomFieldController@delete');

    // ===================================
    // COURSE SUBCATEGORIES
    // ===================================
    
    // Course Subcategories
    Router::get('/course-subcategories', 'CourseSubcategoryController@index');
    
    // Course Subcategories AJAX operations
    Router::post('/course-subcategories/ajax/search', 'CourseSubcategoryController@ajaxSearch');
    Router::post('/course-subcategories/ajax/toggle-status', 'CourseSubcategoryController@toggleStatus');
    Router::post('/course-subcategories/submit', 'CourseSubcategoryController@submitSubcategory');
    
    // Course Subcategories CRUD operations
    Router::get('/course-subcategories/create', 'CourseSubcategoryController@create');
    Router::post('/course-subcategories', 'CourseSubcategoryController@create');
    Router::get('/course-subcategories/{id}/edit', 'CourseSubcategoryController@edit');
    Router::post('/course-subcategories/{id}', 'CourseSubcategoryController@edit');
    Router::delete('/course-subcategories/{id}', 'CourseSubcategoryController@delete');
    Router::post('/course-subcategories/{id}/delete', 'CourseSubcategoryController@delete'); // POST fallback for delete
    Router::get('/course-subcategories/{id}/delete', 'CourseSubcategoryController@delete'); // For GET delete links
    
    // Course Subcategories API (more specific routes after general ones)
    Router::get('/course-subcategories/get/{id}', 'CourseSubcategoryController@get');
    Router::get('/api/course-subcategories/dropdown', 'CourseSubcategoryController@getSubcategoriesForDropdown');
    
    // Course Subcategories Modal operations (more specific routes after general ones)
    Router::post('/course-subcategories/modal/add', 'CourseSubcategoryController@loadAddModal');
    Router::post('/course-subcategories/modal/edit', 'CourseSubcategoryController@loadEditModal');

    // ===================================
    // COURSE CREATION
    // ===================================
    
    // Course Creation
    Router::get('/course-creation', 'CourseCreationController@index');
    Router::post('/course-creation', 'CourseCreationController@createCourse');
    // Modal route for Add Course
    Router::get('/course-creation/modal/add', 'CourseCreationController@loadAddCourseModal');
    
    // Course Creation AJAX operations
    Router::post('/course-creation/subcategories', 'CourseCreationController@getSubcategories');
    Router::post('/course-creation/vlr-content', 'CourseCreationController@getVLRContent');

    // ===================================
    // SETTINGS ROUTES
    // ===================================

    // Settings Main
    Router::get('/settings', 'SettingsController@index');

    // Custom Fields Management
    Router::get('/settings/custom-fields', 'SettingsController@customFields');
    Router::post('/settings/custom-fields', 'SettingsController@storeCustomField');
    Router::post('/settings/custom-fields/update-modal', 'SettingsController@updateCustomFieldModal');
    Router::post('/settings/custom-fields/activate', 'SettingsController@activateCustomFieldPost');
    Router::post('/settings/custom-fields/deactivate', 'SettingsController@deactivateCustomFieldPost');
    Router::post('/settings/custom-fields/delete', 'SettingsController@deleteCustomFieldPost');
    Router::get('/settings/custom-fields/{id}/edit', 'SettingsController@editCustomField');
    Router::put('/settings/custom-fields/{id}', 'SettingsController@updateCustomField');
    Router::get('/settings/custom-fields/{id}/activate', 'SettingsController@activateCustomField');
    Router::get('/settings/custom-fields/{id}/deactivate', 'SettingsController@deactivateCustomField');
    Router::get('/settings/custom-fields/{id}/delete', 'SettingsController@deleteCustomField');

    // Custom Fields API for validation
    Router::post('/api/custom-fields/check-name', 'SettingsController@checkFieldName');
    Router::post('/api/custom-fields/check-label', 'SettingsController@checkFieldLabel');
    
    // ===================================
    // USER ROLES & PERMISSIONS
    // ===================================
    
    // User Roles & Permissions
    Router::get('/user-roles', 'UserRolesController@index');
    Router::post('/user-roles/create', 'UserRolesController@create');
    Router::post('/user-roles/update', 'UserRolesController@update');
    Router::post('/user-roles/delete', 'UserRolesController@delete');
    Router::post('/user-roles/toggle-status', 'UserRolesController@toggleStatus');
    Router::post('/user-roles/save-permissions', 'UserRolesController@savePermissions');
    Router::post('/user-roles/get-role', 'UserRolesController@getRole');
    
    // ===================================
    // ASSESSMENT QUESTIONS
    // ===================================
    
    // Assessment Questions
    Router::get('/assessments', 'QuestionController@index');
    Router::get('/assessments/create', 'QuestionController@add');
    Router::post('/assessments', 'QuestionController@save');
    Router::get('/assessments/{id}/edit', 'QuestionController@edit');
    Router::put('/assessments/{id}', 'QuestionController@save');
    Router::delete('/assessments/{id}', 'QuestionController@delete');
    
    // Assessment AJAX operations
    Router::post('/assessments/ajax/search', 'QuestionController@ajaxSearch');
    Router::post('/assessments/import', 'QuestionController@import');
    
    // ===================================
    // VLR (VIRTUAL LEARNING RESOURCES)
    // ===================================
    
    // VLR Main Routes
    Router::get('/vlr', 'VLRController@index');
    Router::get('/vlr/create', 'VLRController@add');
    Router::post('/vlr', 'VLRController@save');
    Router::get('/vlr/{id}/edit', 'VLRController@edit');
    Router::put('/vlr/{id}', 'VLRController@update');
    Router::delete('/vlr/{id}', 'VLRController@delete');
    
    // VLR AJAX operations
    Router::post('/vlr/ajax/search', 'VLRController@ajaxSearch');
    
    // SCORM Packages
    Router::get('/vlr/scorm', 'VLRController@scormIndex');
    Router::post('/vlr/scorm', 'VLRController@addOrEditScormPackage');
    Router::delete('/vlr/scorm/{id}', 'VLRController@delete');
    
    // External Content
    Router::get('/vlr/external', 'VLRController@externalIndex');
    Router::post('/vlr/external', 'VLRController@addOrEditExternalContent');
    Router::delete('/vlr/external/{id}', 'VLRController@deleteExternal');
    
    // Documents
    Router::get('/vlr/documents', 'VLRController@documentsIndex');
    Router::post('/vlr/documents', 'VLRController@addOrEditDocument');
    Router::delete('/vlr/documents/{id}', 'VLRController@deleteDocument');
    
    // Audio Packages
    Router::get('/vlr/audio', 'VLRController@audioIndex');
    Router::post('/vlr/audio', 'VLRController@addOrEditAudioPackage');
    Router::delete('/vlr/audio/{id}', 'VLRController@deleteAudioPackage');
    
    // Assignment Packages
    Router::get('/vlr/assignment', 'VLRController@assignmentIndex');
    Router::post('/vlr/assignment', 'VLRController@addOrEditAssignmentPackage');
    Router::delete('/vlr/assignment/{id}', 'VLRController@deleteAssignmentPackage');
    
    // Video Packages
    Router::get('/vlr/video', 'VLRController@videoIndex');
    Router::post('/vlr/video', 'VLRController@addOrEditVideoPackage');
    Router::delete('/vlr/video/{id}', 'VLRController@deleteVideoPackage');
    
    // Image Packages
    Router::get('/vlr/images', 'VLRController@imageIndex');
    Router::post('/vlr/images', 'VLRController@addOrEditImagePackage');
    Router::delete('/vlr/images/{id}', 'VLRController@deleteImagePackage');
    
    // Interactive Content
    Router::get('/vlr/interactive', 'VLRController@interactiveIndex');
    Router::post('/vlr/interactive', 'VLRController@addOrEditInteractiveContent');
    Router::delete('/vlr/interactive/{id}', 'VLRController@deleteInteractiveContent');
    
    // Assessment Packages
    Router::get('/vlr/assessment-packages', 'VLRController@assessmentIndex');
    Router::post('/vlr/assessment-packages', 'VLRController@addOrEditAssessment');
    // Add Assessment modal AJAX endpoints (MUST come before parameterized routes)
    Router::get('/vlr/assessment-packages/filter-options', 'AssessmentController@getFilterOptions');
    Router::get('/vlr/assessment-packages/questions', 'AssessmentController@getQuestions');
    Router::post('/vlr/assessment-packages/selected-questions', 'AssessmentController@getSelectedQuestions');
    // Parameterized routes (MUST come after specific routes)
    Router::get('/vlr/assessment-packages/{id}', 'VLRController@getAssessmentById');
    Router::delete('/vlr/assessment-packages/{id}', 'VLRController@deleteAssessment');
    
    // Non-SCORM Content
    Router::get('/vlr/non-scorm', 'VLRController@nonScormIndex');
    Router::post('/vlr/non-scorm', 'VLRController@addOrEditNonScormPackage');
    Router::delete('/vlr/non-scorm/{id}', 'VLRController@deleteNonScormPackage');
    
    // Survey Packages
    Router::get('/vlr/surveys', 'VLRController@surveyIndex');
    Router::post('/vlr/surveys', 'VLRController@addOrEditSurvey');
    Router::delete('/vlr/surveys/{id}', 'VLRController@deleteSurvey');
    
    // Add Survey modal AJAX endpoints (MUST come before parameterized routes)
    Router::get('/vlr/surveys/filter-options', 'SurveyQuestionController@getFilterOptions');
    Router::get('/vlr/surveys/questions', 'SurveyQuestionController@getQuestions');
    Router::post('/vlr/surveys/selected-questions', 'SurveyQuestionController@getSelectedQuestions');
    // Parameterized routes (MUST come after specific routes)
    Router::get('/vlr/surveys/{id}', 'VLRController@getSurveyById');
    
    // Feedback Packages
    Router::get('/vlr/feedback', 'VLRController@feedbackIndex');
    Router::post('/vlr/feedback', 'VLRController@addOrEditFeedback');
    Router::delete('/vlr/feedback/{id}', 'VLRController@deleteFeedback');

    // Add Feedback modal AJAX endpoints (MUST come before parameterized routes)
    Router::get('/vlr/feedback/filter-options', 'FeedbackQuestionController@getFilterOptions');
    Router::get('/vlr/feedback/questions', 'FeedbackQuestionController@getQuestions');
    Router::post('/vlr/feedback/selected-questions', 'FeedbackQuestionController@getSelectedQuestions');
    
    // Parameterized routes (MUST come after specific routes)
    Router::get('/vlr/feedback/{id}', 'VLRController@getFeedbackById');
    Router::delete('/vlr/feedback/{id}', 'VLRController@deleteFeedback');
    
    // VLR Assessment Question Management
    Router::get('/vlr/questions', 'QuestionController@index');
    Router::get('/vlr/questions/create', 'QuestionController@add');
    Router::post('/vlr/questions', 'QuestionController@save');
    Router::get('/vlr/questions/{id}/edit', 'QuestionController@edit');
    Router::put('/vlr/questions/{id}', 'QuestionController@save');
    Router::delete('/vlr/questions/{id}', 'QuestionController@delete');
    Router::get('/vlr/questions/{id}/delete', 'QuestionController@delete'); // For GET delete links
    Router::get('/vlr/questions/{id}', 'QuestionController@getQuestionById');
    
    // ===================================
    // SURVEY QUESTIONS
    // ===================================
    
    // Survey Questions
    Router::get('/surveys', 'SurveyQuestionController@index');
    Router::get('/surveys/create', 'SurveyQuestionController@add');
    Router::post('/surveys', 'SurveyQuestionController@save');
    Router::get('/surveys/{id}/edit', 'SurveyQuestionController@edit');
    Router::put('/surveys/{id}', 'SurveyQuestionController@update');
    Router::delete('/surveys/{id}', 'SurveyQuestionController@delete');
    Router::get('/surveys/{id}/delete', 'SurveyQuestionController@delete'); // For GET delete links
    
    // Survey AJAX operations
    Router::post('/surveys/ajax/search', 'SurveyQuestionController@ajaxSearch');
    Router::post('/surveys/import', 'SurveyQuestionController@import');
    
    // ===================================
    // FEEDBACK QUESTIONS
    // ===================================

    // Feedback Questions
    Router::get('/feedback', 'FeedbackQuestionController@index');
    Router::get('/feedback/create', 'FeedbackQuestionController@add');
    Router::post('/feedback', 'FeedbackQuestionController@save');
    Router::get('/feedback/{id}/edit', 'FeedbackQuestionController@edit');
    Router::put('/feedback/{id}', 'FeedbackQuestionController@update');
    Router::delete('/feedback/{id}', 'FeedbackQuestionController@delete');
    Router::get('/feedback/{id}', 'FeedbackQuestionController@getQuestionById');

    // Feedback AJAX operations
    Router::post('/feedback/ajax/search', 'FeedbackQuestionController@ajaxSearch');
    Router::post('/feedback/import', 'FeedbackQuestionController@import');

    // ===================================
    // OPINION POLLS
    // ===================================

    // Opinion Poll Management
    Router::get('/opinion-polls', 'OpinionPollController@index');
    Router::post('/opinion-polls', 'OpinionPollController@create');
    Router::get('/opinion-polls/{id}/edit', 'OpinionPollController@edit');
    Router::put('/opinion-polls/{id}', 'OpinionPollController@update');
    Router::delete('/opinion-polls/{id}', 'OpinionPollController@delete');

    // Opinion Poll AJAX operations
    Router::post('/opinion-polls/ajax/search', 'OpinionPollController@ajaxSearch');
    Router::post('/opinion-polls/status', 'OpinionPollController@updateStatus');
    Router::get('/opinion-polls/{id}/results', 'OpinionPollController@getResults');

    // Opinion Poll Voting (for learners)
    Router::get('/polls', 'OpinionPollController@viewPolls');
    Router::post('/polls/{id}/vote', 'OpinionPollController@submitVote');
    Router::get('/polls/{id}/results', 'OpinionPollController@viewResults');

    // ===================================
    // ANNOUNCEMENTS
    // ===================================

    // Announcement Management
    Router::get('/announcements', 'AnnouncementController@index');
    Router::post('/announcements', 'AnnouncementController@create');
    Router::get('/announcements/{id}/edit', 'AnnouncementController@edit');
    Router::put('/announcements/{id}', 'AnnouncementController@update');
    Router::delete('/announcements/{id}', 'AnnouncementController@delete');

    // Announcement AJAX operations
    Router::get('/announcements/ajax/list', 'AnnouncementController@getAnnouncements');
    Router::post('/announcements/status', 'AnnouncementController@updateStatus');
    Router::get('/announcements/{id}/stats', 'AnnouncementController@getStats');
    Router::get('/announcements/{id}/acknowledgments', 'AnnouncementController@getAcknowledgments');
    Router::post('/announcements/{id}/acknowledge', 'AnnouncementController@acknowledge');

    // Announcement Viewing (for learners)
    Router::get('/my-announcements', 'AnnouncementController@viewAnnouncements');
    Router::get('/announcements/{id}/view', 'AnnouncementController@viewAnnouncement');
    Router::post('/announcements/{id}/mark-read', 'AnnouncementController@markAsRead');

    // ===================================
    // EVENTS
    // ===================================

    // Event Management
    Router::get('/events', 'EventController@index');
    Router::post('/events', 'EventController@create');
    Router::get('/events/{id}/edit', 'EventController@edit');
    Router::put('/events/{id}', 'EventController@update');
    Router::delete('/events/{id}', 'EventController@delete');

    // Event AJAX operations
    Router::get('/events/ajax/list', 'EventController@getEvents');
    Router::post('/events/status', 'EventController@updateStatus');
    Router::get('/events/{id}/attendees', 'EventController@attendees');
    Router::post('/events/{id}/rsvp', 'EventController@rsvp');

    // Event Viewing (for learners)
    Router::get('/my-events', 'EventController@viewEvents');
    Router::get('/events/{id}/view', 'EventController@viewEvent');

    // ===================================
    // SOCIAL FEED (NEWS WALL)
    // ===================================
    Router::get('/feed', 'SocialFeedController@index');
    Router::get('/feed/list', 'SocialFeedController@list');
    Router::post('/feed', 'SocialFeedController@create');
    Router::get('/feed/{id}', 'SocialFeedController@show');
    Router::post('/feed/edit', 'SocialFeedController@edit');
    Router::post('/feed/delete', 'SocialFeedController@delete');
    Router::get('/feed/comment', 'SocialFeedController@comments');
    Router::post('/feed/comment', 'SocialFeedController@comment');
    Router::post('/feed/reaction', 'SocialFeedController@reaction');
    Router::post('/feed/report', 'SocialFeedController@report');
    Router::post('/feed/pin', 'SocialFeedController@pin');
    Router::post('/feed/status', 'SocialFeedController@updateStatus');
    Router::get('/feed/statistics', 'SocialFeedController@statistics');
    Router::post('/feed/poll-vote', 'SocialFeedController@pollVote');
    Router::get('/feed/notifications', 'SocialFeedController@notifications');

    // Course Management Routes
    Router::get('/course-management', 'CourseCreationController@courseManagement');
    Router::get('/api/courses', 'CourseCreationController@getCourses');
    Router::get('/courses/ajax/get', 'CourseCreationController@getCourses');
    Router::post('/api/courses/{id}/publish', 'CourseCreationController@publishCourse');
    Router::post('/api/courses/{id}/unpublish', 'CourseCreationController@unpublishCourse');
    Router::delete('/api/courses/{id}', 'CourseCreationController@deleteCourse');
    Router::get('/course-edit/{id}', 'CourseCreationController@editCourse');
    Router::post('/course-edit/{id}', 'CourseCreationController@updateCourse');
    Router::put('/course-edit/{id}', 'CourseCreationController@updateCourse');
    Router::get('/course-preview/{id}', 'CourseCreationController@previewCourse');
    Router::get('/course-analytics/{id}', 'CourseCreationController@courseAnalytics');

    // New route for loading the add course modal content via AJAX
    Router::get('/courses/modal/add', 'CourseCreationController@loadAddCourseModal');

});

// ===================================
// SUPER ADMIN ROUTES
// ===================================

Router::middleware(['Auth'])->group(function() {
    
    // Client Management (Super Admin Only)
    Router::get('/clients', 'ClientController@index');
    Router::get('/clients/create', 'ClientController@create');
    Router::post('/clients', 'ClientController@store');
    Router::get('/clients/{id}/edit', 'ClientController@edit');
    Router::put('/clients/{id}', 'ClientController@update');
    Router::get('/clients/{id}/can-delete', 'ClientController@canDelete');
    Router::get('/clients/{id}/delete', 'ClientController@delete');
    Router::delete('/clients/{id}', 'ClientController@delete');
    
    // Client AJAX operations
    Router::post('/clients/ajax/search', 'ClientController@ajaxSearch');
    
    // Navigate to client-specific user management
    Router::get('/clients/{id}/users', 'UserManagementController@clientUsers');
    
    // Client users AJAX operations
    Router::post('/clients/{id}/users/ajax/search', 'UserManagementController@ajaxSearch');

});

// ===================================
// API ROUTES (for AJAX calls)
// ===================================

Router::prefix('api')->middleware(['Auth'])->group(function() {
    
    // Generic AJAX endpoints
    Router::post('/upload', 'FileController@upload');
    Router::delete('/files/{id}', 'FileController@delete');
    
    // Data export endpoints
    Router::get('/export/users', 'ExportController@users');
    Router::get('/export/assessments', 'ExportController@assessments');
    Router::get('/export/surveys', 'ExportController@surveys');
    Router::get('/export/feedback', 'ExportController@feedback');
    
    // API endpoint for fetching a single assessment question by ID (for AJAX edit)
    Router::get('/vlr/questions/{id}', 'QuestionController@getQuestionById');
    
});

// ===================================
// FALLBACK ROUTES
// ===================================

// Note: Fallback routes removed to prevent regex issues
Router::post('/users/modal/submit-add', 'UserManagementController@submitAddUserModal');

// Course Applicability routes
Router::get('/course-applicability', 'CourseApplicabilityController@index');
Router::get('/course-applicability/getApplicability', 'CourseApplicabilityController@getApplicability');
Router::post('/course-applicability/assign', 'CourseApplicabilityController@assign');
Router::post('/course-applicability/remove', 'CourseApplicabilityController@remove');
Router::get('/course-applicability/getUsersByCustomField', 'CourseApplicabilityController@getUsersByCustomField');
// Course Applicability AJAX user search
Router::get('/course-applicability/search-users', 'CourseApplicabilityController@searchUsers');
Router::get('/course-applicability/getApplicableUsers', 'CourseApplicabilityController@getApplicableUsers');
// My Courses
Router::get('/my-courses', 'MyCoursesController@index');
Router::get('/my-courses/list', 'MyCoursesController@getUserCourses');

