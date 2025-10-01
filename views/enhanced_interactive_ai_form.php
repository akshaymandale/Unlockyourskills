<?php
// Enhanced Interactive AI Content Creation/Edit Form
// This form captures all 31 database parameters with better organization and UX
?>

<div class="enhanced-interactive-ai-form">
    <form id="enhancedInteractiveForm" method="POST" enctype="multipart/form-data">
        <!-- Hidden Fields -->
        <input type="hidden" id="interactive_id" name="interactive_id">
        <input type="hidden" id="existing_content_file" name="existing_content_file">
        <input type="hidden" id="existing_thumbnail_image" name="existing_thumbnail_image">
        <input type="hidden" id="existing_metadata_file" name="existing_metadata_file">

        <!-- Progress Indicator -->
        <div class="form-progress mb-4">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%" id="formProgressBar"></div>
            </div>
            <small class="text-muted">Complete all sections to create your Interactive AI content</small>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-pills nav-justified mb-4" id="interactiveFormTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-info-tab" data-bs-toggle="pill" data-bs-target="#basic-info" type="button" role="tab">
                    <i class="fas fa-info-circle me-2"></i>Basic Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ai-config-tab" data-bs-toggle="pill" data-bs-target="#ai-config" type="button" role="tab">
                    <i class="fas fa-brain me-2"></i>AI Configuration
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="learning-design-tab" data-bs-toggle="pill" data-bs-target="#learning-design" type="button" role="tab">
                    <i class="fas fa-graduation-cap me-2"></i>Learning Design
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tech-requirements-tab" data-bs-toggle="pill" data-bs-target="#tech-requirements" type="button" role="tab">
                    <i class="fas fa-cogs me-2"></i>Tech Requirements
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="content-delivery-tab" data-bs-toggle="pill" data-bs-target="#content-delivery" type="button" role="tab">
                    <i class="fas fa-upload me-2"></i>Content Delivery
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="interactiveFormTabContent">
            
            <!-- Basic Information Tab -->
            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Title & Content Type -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="interactive_title" class="form-label">
                                        <i class="fas fa-heading me-1"></i>Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="interactive_title" name="interactive_title" 
                                           class="form-control" placeholder="Enter Interactive AI content title" required>
                                    <div class="invalid-feedback">Please provide a title for your content.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="content_type" class="form-label">
                                        <i class="fas fa-layer-group me-1"></i>Content Type <span class="text-danger">*</span>
                                    </label>
                                    <select id="content_type" name="content_type" class="form-control" required onchange="toggleContentTypeFields()">
                                        <option value="">Select Content Type</option>
                                        <option value="adaptive_learning">Adaptive Learning</option>
                                        <option value="ai_tutoring">AI Tutoring</option>
                                        <option value="ar_vr">AR/VR</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a content type.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Version & Language -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="interactive_version" class="form-label">
                                        <i class="fas fa-code-branch me-1"></i>Version <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="interactive_version" name="version" 
                                           class="form-control" placeholder="e.g., 1.0.0" required>
                                    <div class="invalid-feedback">Please provide a version number.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="interactive_language" class="form-label">
                                        <i class="fas fa-globe me-1"></i>Language
                                    </label>
                                    <select id="interactive_language" name="language" class="form-control">
                                        <option value="">Select Language</option>
                                        <option value="en">English</option>
                                        <option value="es">Spanish</option>
                                        <option value="fr">French</option>
                                        <option value="de">German</option>
                                        <option value="hi">Hindi</option>
                                        <option value="zh">Chinese</option>
                                        <option value="ja">Japanese</option>
                                        <option value="ar">Arabic</option>
                                        <option value="pt">Portuguese</option>
                                        <option value="ru">Russian</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="difficulty_level" class="form-label">
                                        <i class="fas fa-signal me-1"></i>Difficulty Level
                                    </label>
                                    <select id="difficulty_level" name="difficulty_level" class="form-control">
                                        <option value="">Select Difficulty</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="interactive_description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Description
                                    </label>
                                    <textarea id="interactive_description" name="description" 
                                              class="form-control" rows="4" 
                                              placeholder="Describe your Interactive AI content..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="tags" class="form-label">
                                        <i class="fas fa-tags me-1"></i>Tags & Keywords <span class="text-danger">*</span>
                                    </label>
                                    <div class="tag-input-container form-control">
                                        <span id="interactiveTagDisplay"></span>
                                        <input type="text" id="interactiveTagInput" placeholder="Type and press Enter to add tags...">
                                    </div>
                                    <input type="hidden" name="tagList" id="interactiveTagList">
                                    <small class="form-text text-muted">Add relevant tags to help users find your content (e.g., AI, Machine Learning, Programming)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Configuration Tab -->
            <div class="tab-pane fade" id="ai-config" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-brain me-2"></i>AI Configuration</h5>
                    </div>
                    <div class="card-body">
                        <!-- AI Model & Interaction Type -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="ai_model" class="form-label">
                                        <i class="fas fa-robot me-1"></i>AI Model
                                    </label>
                                    <select id="ai_model" name="ai_model" class="form-control">
                                        <option value="">Select AI Model</option>
                                        <option value="GPT-4">GPT-4</option>
                                        <option value="GPT-3.5">GPT-3.5</option>
                                        <option value="Claude-3.5-Sonnet">Claude 3.5 Sonnet</option>
                                        <option value="Claude-3-Opus">Claude 3 Opus</option>
                                        <option value="Gemini-Pro">Gemini Pro</option>
                                        <option value="Custom ML Model">Custom ML Model</option>
                                        <option value="GPT-4 + Custom ML Model">GPT-4 + Custom ML Model</option>
                                        <option value="Claude-3.5-Sonnet + Codex">Claude-3.5-Sonnet + Codex</option>
                                        <option value="Custom Physics Engine + AI Safety Monitor">Custom Physics Engine + AI Safety Monitor</option>
                                        <option value="GPT-4 + Spaced Repetition Algorithm">GPT-4 + Spaced Repetition Algorithm</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="interaction_type" class="form-label">
                                        <i class="fas fa-comments me-1"></i>Interaction Type
                                    </label>
                                    <select id="interaction_type" name="interaction_type" class="form-control">
                                        <option value="">Select Interaction Type</option>
                                        <option value="text_input">Text Input</option>
                                        <option value="voice">Voice</option>
                                        <option value="gesture">Gesture</option>
                                        <option value="multimodal">Multimodal</option>
                                        <option value="chat">Chat</option>
                                        <option value="simulation">Simulation</option>
                                        <option value="game">Game</option>
                                        <option value="quiz">Quiz</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tutor Personality & Response Style -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="tutor_personality" class="form-label">
                                        <i class="fas fa-user-tie me-1"></i>Tutor Personality
                                    </label>
                                    <select id="tutor_personality" name="tutor_personality" class="form-control">
                                        <option value="">Select Tutor Personality</option>
                                        <option value="Encouraging Mentor">Encouraging Mentor</option>
                                        <option value="Patient Teacher">Patient Teacher</option>
                                        <option value="Scientific Mentor">Scientific Mentor</option>
                                        <option value="Native Speaker Guide">Native Speaker Guide</option>
                                        <option value="Friendly Helper">Friendly Helper</option>
                                        <option value="Professional Expert">Professional Expert</option>
                                        <option value="Strict Instructor">Strict Instructor</option>
                                        <option value="Creative Facilitator">Creative Facilitator</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="response_style" class="form-label">
                                        <i class="fas fa-palette me-1"></i>Response Style
                                    </label>
                                    <select id="response_style" name="response_style" class="form-control">
                                        <option value="">Select Response Style</option>
                                        <option value="encouraging">Encouraging</option>
                                        <option value="formal">Formal</option>
                                        <option value="casual">Casual</option>
                                        <option value="conversational">Conversational</option>
                                        <option value="technical">Technical</option>
                                        <option value="humorous">Humorous</option>
                                        <option value="empathetic">Empathetic</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Knowledge Domain & Adaptation Algorithm -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="knowledge_domain" class="form-label">
                                        <i class="fas fa-book me-1"></i>Knowledge Domain
                                    </label>
                                    <input type="text" id="knowledge_domain" name="knowledge_domain" 
                                           class="form-control" 
                                           placeholder="e.g., Data Science & Machine Learning">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="adaptation_algorithm" class="form-label">
                                        <i class="fas fa-cogs me-1"></i>Adaptation Algorithm
                                    </label>
                                    <select id="adaptation_algorithm" name="adaptation_algorithm" class="form-control">
                                        <option value="">Select Adaptation Algorithm</option>
                                        <option value="Bayesian Knowledge Tracing + Deep Learning">Bayesian Knowledge Tracing + Deep Learning</option>
                                        <option value="Reinforcement Learning + Code Analysis">Reinforcement Learning + Code Analysis</option>
                                        <option value="Physics Simulation + Safety AI">Physics Simulation + Safety AI</option>
                                        <option value="Spaced Repetition + Learning Style Analysis">Spaced Repetition + Learning Style Analysis</option>
                                        <option value="Item Response Theory (IRT)">Item Response Theory (IRT)</option>
                                        <option value="Machine Learning Classification">Machine Learning Classification</option>
                                        <option value="Neural Network Adaptation">Neural Network Adaptation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Learning Design Tab -->
            <div class="tab-pane fade" id="learning-design" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Learning Design</h5>
                    </div>
                    <div class="card-body">
                        <!-- Learning Objectives -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="learning_objectives" class="form-label">
                                        <i class="fas fa-target me-1"></i>Learning Objectives
                                    </label>
                                    <textarea id="learning_objectives" name="learning_objectives" 
                                              class="form-control" rows="4" 
                                              placeholder="List the learning objectives for this content (one per line)..."></textarea>
                                    <small class="form-text text-muted">Enter each learning objective on a new line</small>
                                </div>
                            </div>
                        </div>

                        <!-- Prerequisites -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="prerequisites" class="form-label">
                                        <i class="fas fa-list-check me-1"></i>Prerequisites
                                    </label>
                                    <textarea id="prerequisites" name="prerequisites" 
                                              class="form-control" rows="3" 
                                              placeholder="List any prerequisites for this content..."></textarea>
                                    <small class="form-text text-muted">What should learners know before starting this content?</small>
                                </div>
                            </div>
                        </div>

                        <!-- Time Limit -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="interactive_timeLimit" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Time Limit (minutes)
                                    </label>
                                    <input type="number" id="interactive_timeLimit" name="timeLimit" 
                                           class="form-control" min="0" step="1" 
                                           placeholder="e.g., 120">
                                    <small class="form-text text-muted">Optional: Set a time limit for completing this content</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Requirements Tab -->
            <div class="tab-pane fade" id="tech-requirements" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Technical Requirements</h5>
                    </div>
                    <div class="card-body">
                        <!-- Mobile Support -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-mobile-alt me-1"></i>Mobile Support
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_mobileSupport" 
                                               id="mobile_support_yes" value="Yes">
                                        <label class="form-check-label" for="mobile_support_yes">
                                            <i class="fas fa-check-circle text-success me-1"></i>Yes
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_mobileSupport" 
                                               id="mobile_support_no" value="No" checked>
                                        <label class="form-check-label" for="mobile_support_no">
                                            <i class="fas fa-times-circle text-danger me-1"></i>No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-chart-line me-1"></i>Progress Tracking
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_progress_tracking" 
                                               id="progress_tracking_yes" value="Yes" checked>
                                        <label class="form-check-label" for="progress_tracking_yes">
                                            <i class="fas fa-check-circle text-success me-1"></i>Yes
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_progress_tracking" 
                                               id="progress_tracking_no" value="No">
                                        <label class="form-check-label" for="progress_tracking_no">
                                            <i class="fas fa-times-circle text-danger me-1"></i>No
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VR/AR Platforms -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="vr_platform" class="form-label">
                                        <i class="fas fa-vr-cardboard me-1"></i>VR Platform
                                    </label>
                                    <select id="vr_platform" name="vr_platform" class="form-control">
                                        <option value="">Select VR Platform</option>
                                        <option value="WebXR">WebXR</option>
                                        <option value="Oculus Rift">Oculus Rift</option>
                                        <option value="HTC Vive">HTC Vive</option>
                                        <option value="Oculus Rift, HTC Vive">Oculus Rift, HTC Vive</option>
                                        <option value="PlayStation VR">PlayStation VR</option>
                                        <option value="Valve Index">Valve Index</option>
                                        <option value="Pico VR">Pico VR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="ar_platform" class="form-label">
                                        <i class="fas fa-cube me-1"></i>AR Platform
                                    </label>
                                    <select id="ar_platform" name="ar_platform" class="form-control">
                                        <option value="">Select AR Platform</option>
                                        <option value="AR.js">AR.js</option>
                                        <option value="Microsoft HoloLens">Microsoft HoloLens</option>
                                        <option value="ARCore">ARCore (Android)</option>
                                        <option value="ARKit">ARKit (iOS)</option>
                                        <option value="WebAR">WebAR</option>
                                        <option value="Magic Leap">Magic Leap</option>
                                        <option value="Meta Quest Pro">Meta Quest Pro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Device Requirements -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="device_requirements" class="form-label">
                                        <i class="fas fa-desktop me-1"></i>Device Requirements
                                    </label>
                                    <textarea id="device_requirements" name="device_requirements" 
                                              class="form-control" rows="3" 
                                              placeholder="List device requirements (one per line)..."></textarea>
                                    <small class="form-text text-muted">e.g., Modern web browser, Minimum 4GB RAM, Webcam, Microphone, VR Headset</small>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Integration -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-clipboard-check me-1"></i>Assessment Integration
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_assessment_integration" 
                                               id="assessment_yes" value="Yes">
                                        <label class="form-check-label" for="assessment_yes">
                                            <i class="fas fa-check-circle text-success me-1"></i>Yes - Integrates with assessments
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="interactive_assessment_integration" 
                                               id="assessment_no" value="No" checked>
                                        <label class="form-check-label" for="assessment_no">
                                            <i class="fas fa-times-circle text-danger me-1"></i>No - Standalone content
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Delivery Tab -->
            <div class="tab-pane fade" id="content-delivery" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Content Delivery</h5>
                    </div>
                    <div class="card-body">
                        <!-- Content URL & Embed Code -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="content_url" class="form-label">
                                        <i class="fas fa-link me-1"></i>Content URL
                                    </label>
                                    <input type="url" id="content_url" name="content_url" 
                                           class="form-control" 
                                           placeholder="https://example.com">
                                    <small class="form-text text-muted">URL for external interactive content</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="embed_code" class="form-label">
                                        <i class="fas fa-code me-1"></i>Embed Code
                                    </label>
                                    <textarea id="embed_code" name="embed_code" 
                                              class="form-control" rows="3" 
                                              placeholder="<iframe src='...'></iframe>"></textarea>
                                    <small class="form-text text-muted">HTML embed code for interactive content</small>
                                </div>
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="content_file" class="form-label">
                                        <i class="fas fa-file me-1"></i>Content File
                                    </label>
                                    <input type="file" class="form-control" id="content_file" name="content_file" 
                                           accept=".html,.htm,.zip,.unity,.json">
                                    <small class="form-text text-muted">HTML5, Unity, ZIP files (Max 50MB)</small>
                                    <div id="contentFilePreview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="interactive_thumbnail_image" class="form-label">
                                        <i class="fas fa-image me-1"></i>Thumbnail Image
                                    </label>
                                    <input type="file" class="form-control" id="interactive_thumbnail_image" 
                                           name="thumbnail_image" accept="image/*">
                                    <small class="form-text text-muted">JPG, PNG, GIF (Max 10MB)</small>
                                    <div id="interactiveThumbnailImagePreview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="metadata_file" class="form-label">
                                        <i class="fas fa-file-alt me-1"></i>Metadata File
                                    </label>
                                    <input type="file" class="form-control" id="metadata_file" 
                                           name="metadata_file" accept=".json,.xml,.txt">
                                    <small class="form-text text-muted">JSON, XML, TXT files (Max 5MB)</small>
                                    <div id="metadataFilePreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Preview -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-eye me-1"></i>Content Preview
                                    </label>
                                    <div class="content-preview border rounded p-3" style="min-height: 200px; background-color: #f8f9fa;">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-play-circle fa-3x mb-3"></i>
                                            <p>Content preview will appear here when you provide a URL or upload files</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions mt-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="clearForm">
                            <i class="fas fa-eraser me-2"></i>Clear Form
                        </button>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" id="saveDraft">
                                <i class="fas fa-save me-2"></i>Save Draft
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-rocket me-2"></i>Create Interactive AI Content
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.enhanced-interactive-ai-form {
    max-width: 1200px;
    margin: 0 auto;
}

.form-progress .progress {
    height: 8px;
}

.nav-pills .nav-link {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills .nav-link.active {
    background: linear-gradient(45deg, #007bff, #0056b3);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.nav-pills .nav-link:hover:not(.active) {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    border: none;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.tag-input-container {
    min-height: 40px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: 8px;
}

.tag-item {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 15px;
    margin: 2px;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
}

.tag-item .remove-tag {
    margin-left: 5px;
    cursor: pointer;
    font-weight: bold;
}

.content-preview {
    transition: all 0.3s ease;
}

.content-preview:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.form-actions {
    padding: 20px 0;
    border-top: 2px solid #e9ecef;
    margin-top: 30px;
}

.btn {
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
}

@media (max-width: 768px) {
    .nav-pills {
        flex-direction: column;
    }
    
    .nav-pills .nav-link {
        margin-bottom: 5px;
        text-align: center;
    }
}
</style>

<script>
// Enhanced Interactive AI Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedForm();
});

function initializeEnhancedForm() {
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize tag system
    initializeTagSystem();
    
    // Initialize file previews
    initializeFilePreviews();
    
    // Initialize progress tracking
    initializeProgressTracking();
    
    // Initialize form actions
    initializeFormActions();
}

function initializeFormValidation() {
    const form = document.getElementById('enhancedInteractiveForm');
    
    form.addEventListener('submit', function(event) {
        if (!validateForm()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
}

function validateForm() {
    let isValid = true;
    
    // Required fields validation
    const requiredFields = [
        'interactive_title',
        'content_type',
        'interactive_version'
    ];
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    // Tags validation
    const tagList = document.getElementById('interactiveTagList');
    if (!tagList.value.trim()) {
        document.getElementById('interactiveTagDisplay').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('interactiveTagDisplay').classList.remove('is-invalid');
        document.getElementById('interactiveTagDisplay').classList.add('is-valid');
    }
    
    return isValid;
}

function initializeTagSystem() {
    const tagInput = document.getElementById('interactiveTagInput');
    const tagDisplay = document.getElementById('interactiveTagDisplay');
    const tagList = document.getElementById('interactiveTagList');
    let tags = [];
    
    tagInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag(this.value.trim());
            this.value = '';
        }
    });
    
    function addTag(tag) {
        if (tag && !tags.includes(tag)) {
            tags.push(tag);
            updateTagDisplay();
            updateTagList();
        }
    }
    
    function removeTag(tag) {
        tags = tags.filter(t => t !== tag);
        updateTagDisplay();
        updateTagList();
    }
    
    function updateTagDisplay() {
        tagDisplay.innerHTML = tags.map(tag => 
            `<span class="tag-item">${tag} <span class="remove-tag" onclick="removeTag('${tag}')">&times;</span></span>`
        ).join('');
    }
    
    function updateTagList() {
        tagList.value = tags.join(',');
    }
    
    // Make removeTag globally accessible
    window.removeTag = removeTag;
}

function initializeFilePreviews() {
    // Content file preview
    document.getElementById('content_file').addEventListener('change', function(e) {
        previewFile(e.target, 'contentFilePreview');
    });
    
    // Thumbnail image preview
    document.getElementById('interactive_thumbnail_image').addEventListener('change', function(e) {
        previewImage(e.target, 'interactiveThumbnailImagePreview');
    });
    
    // Metadata file preview
    document.getElementById('metadata_file').addEventListener('change', function(e) {
        previewFile(e.target, 'metadataFilePreview');
    });
}

function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        preview.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-file me-2"></i>
                <strong>Selected:</strong> ${file.name}<br>
                <small>Size: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>
            </div>
        `;
    } else {
        preview.innerHTML = '';
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="text-center">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                    <div class="mt-2">
                        <small class="text-muted">${file.name}</small>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
}

function initializeProgressTracking() {
    const tabs = document.querySelectorAll('#interactiveFormTabs button[data-bs-toggle="pill"]');
    const progressBar = document.getElementById('formProgressBar');
    
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            updateProgress();
        });
    });
    
    function updateProgress() {
        const activeTab = document.querySelector('#interactiveFormTabs .nav-link.active');
        const tabIndex = Array.from(tabs).indexOf(activeTab);
        const progress = ((tabIndex + 1) / tabs.length) * 100;
        
        progressBar.style.width = progress + '%';
        progressBar.textContent = Math.round(progress) + '% Complete';
    }
}

function initializeFormActions() {
    // Clear form
    document.getElementById('clearForm').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear all form data?')) {
            document.getElementById('enhancedInteractiveForm').reset();
            document.getElementById('interactiveTagDisplay').innerHTML = '';
            document.getElementById('interactiveTagList').value = '';
            
            // Clear previews
            document.getElementById('contentFilePreview').innerHTML = '';
            document.getElementById('interactiveThumbnailImagePreview').innerHTML = '';
            document.getElementById('metadataFilePreview').innerHTML = '';
            
            // Reset progress
            document.getElementById('formProgressBar').style.width = '0%';
            document.getElementById('formProgressBar').textContent = '0% Complete';
            
            // Remove validation classes
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
            
            document.getElementById('enhancedInteractiveForm').classList.remove('was-validated');
        }
    });
    
    // Save draft
    document.getElementById('saveDraft').addEventListener('click', function() {
        // Implement save draft functionality
        alert('Draft saved successfully!');
    });
}

function toggleContentTypeFields() {
    const contentType = document.getElementById('content_type').value;
    
    // Hide all content type specific fields
    document.querySelectorAll('.ai-tutoring-fields, .ar-vr-fields, .adaptive-learning-fields').forEach(field => {
        field.style.display = 'none';
    });
    
    // Show relevant fields based on content type
    if (contentType === 'ai_tutoring') {
        document.querySelectorAll('.ai-tutoring-fields').forEach(field => {
            field.style.display = 'block';
        });
    } else if (contentType === 'ar_vr') {
        document.querySelectorAll('.ar-vr-fields').forEach(field => {
            field.style.display = 'block';
        });
    } else if (contentType === 'adaptive_learning') {
        document.querySelectorAll('.adaptive-learning-fields').forEach(field => {
            field.style.display = 'block';
        });
    }
}
</script>
