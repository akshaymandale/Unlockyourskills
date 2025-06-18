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
    
    // User Management Routes
    Router::get('/users', 'UserManagementController@index');
    Router::get('/users/create', 'UserManagementController@add');
    Router::post('/users', 'UserManagementController@save');
    Router::get('/users/{id}/edit', 'UserManagementController@edit');
    Router::put('/users/{id}', 'UserManagementController@update');
    Router::delete('/users/{id}', 'UserManagementController@delete');
    
    // User AJAX operations
    Router::post('/users/ajax/search', 'UserManagementController@ajaxSearch');
    Router::post('/users/ajax/toggle-status', 'UserManagementController@toggleStatus');
    Router::post('/users/import', 'UserManagementController@import');
    
    // Custom Fields for Users
    Router::get('/users/custom-fields', 'CustomFieldController@index');
    Router::post('/users/custom-fields', 'CustomFieldController@save');
    Router::delete('/users/custom-fields/{id}', 'CustomFieldController@delete');
    
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
