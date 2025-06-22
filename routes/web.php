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
    
    // Custom Fields for Users (legacy routes)
    Router::get('/users/custom-fields', 'CustomFieldController@index');
    Router::post('/users/custom-fields', 'CustomFieldController@save');
    Router::delete('/users/custom-fields/{id}', 'CustomFieldController@delete');

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
    Router::post('/vlr/scorm', 'VLRController@scormSave');
    Router::delete('/vlr/scorm/{id}', 'VLRController@scormDelete');
    
    // External Content
    Router::get('/vlr/external', 'VLRController@externalIndex');
    Router::post('/vlr/external', 'VLRController@externalSave');
    Router::delete('/vlr/external/{id}', 'VLRController@externalDelete');
    
    // Documents
    Router::get('/vlr/documents', 'VLRController@documentsIndex');
    Router::post('/vlr/documents', 'VLRController@documentsSave');
    Router::delete('/vlr/documents/{id}', 'VLRController@documentsDelete');
    
    // Audio Packages
    Router::get('/vlr/audio', 'VLRController@audioIndex');
    Router::post('/vlr/audio', 'VLRController@audioSave');
    Router::delete('/vlr/audio/{id}', 'VLRController@audioDelete');
    
    // Video Packages
    Router::get('/vlr/video', 'VLRController@videoIndex');
    Router::post('/vlr/video', 'VLRController@videoSave');
    Router::delete('/vlr/video/{id}', 'VLRController@videoDelete');
    
    // Image Packages
    Router::get('/vlr/images', 'VLRController@imageIndex');
    Router::post('/vlr/images', 'VLRController@imageSave');
    Router::delete('/vlr/images/{id}', 'VLRController@imageDelete');
    
    // Interactive Content
    Router::get('/vlr/interactive', 'VLRController@interactiveIndex');
    Router::post('/vlr/interactive', 'VLRController@interactiveSave');
    Router::delete('/vlr/interactive/{id}', 'VLRController@interactiveDelete');
    
    // Assessment Packages
    Router::get('/vlr/assessment-packages', 'VLRController@assessmentIndex');
    Router::post('/vlr/assessment-packages', 'VLRController@assessmentSave');
    Router::delete('/vlr/assessment-packages/{id}', 'VLRController@assessmentDelete');
    
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
    Router::post('/feed/poll-vote', 'SocialFeedController@pollVote');
    Router::get('/feed/notifications', 'SocialFeedController@notifications');

});

// ===================================
// SUPER ADMIN ROUTES
// ===================================

Router::middleware(['Auth'])->group(function() {
    
    // Client Management (Super Admin Only)
    Router::get('/clients', 'ClientController@index');
    Router::get('/clients/create', 'ClientController@add');
    Router::post('/clients', 'ClientController@save');
    Router::get('/clients/{id}/edit', 'ClientController@edit');
    Router::put('/clients/{id}', 'ClientController@update');
    Router::delete('/clients/{id}', 'ClientController@delete');
    
    // Client AJAX operations
    Router::post('/clients/ajax/search', 'ClientController@ajaxSearch');
    
    // Navigate to client-specific user management
    Router::get('/clients/{id}/users', 'UserManagementController@clientUsers');
    
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
    
});

// ===================================
// FALLBACK ROUTES
// ===================================

// Note: Fallback routes removed to prevent regex issues
