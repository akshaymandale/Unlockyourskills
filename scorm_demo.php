<?php
/**
 * SCORM Integration Demo Page
 * Test the SCORM launcher and integration system
 */

session_start();

// Set demo user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Demo user ID
    $_SESSION['client_id'] = 1; // Demo client ID
}

$baseUrl = 'http://localhost/Unlockyourskills';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCORM Integration Demo - Unlock Your Skills</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .demo-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 800px;
            overflow: hidden;
        }
        
        .demo-header {
            background: linear-gradient(135deg, #6a0dad, #8a2be2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .demo-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .demo-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 1rem 0 0 0;
        }
        
        .demo-content {
            padding: 2rem;
        }
        
        .demo-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .demo-section h3 {
            color: #6a0dad;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .demo-button {
            background: linear-gradient(135deg, #6a0dad, #8a2be2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
        }
        
        .demo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .demo-button.secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .demo-button.secondary:hover {
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        
        .demo-button.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .demo-button.success:hover {
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .demo-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .demo-info h4 {
            color: #1976d2;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .demo-info p {
            margin: 0;
            color: #0d47a1;
            font-size: 0.9rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list .feature-icon {
            color: #28a745;
            font-size: 1.2rem;
        }
        
        .demo-footer {
            background: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .demo-footer p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .demo-container {
                margin: 1rem;
                border-radius: 10px;
            }
            
            .demo-header {
                padding: 1.5rem;
            }
            
            .demo-title {
                font-size: 2rem;
            }
            
            .demo-content {
                padding: 1.5rem;
            }
            
            .demo-button {
                display: block;
                margin: 0.5rem 0;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1 class="demo-title">
                <i class="fas fa-graduation-cap me-3"></i>
                SCORM Integration Demo
            </h1>
            <p class="demo-subtitle">Professional SCORM Content Player with Progress Tracking</p>
        </div>
        
        <div class="demo-content">
            <div class="demo-section">
                <h3><i class="fas fa-rocket me-2"></i>Quick Launch</h3>
                <p>Test the SCORM launcher with sample content:</p>
                
                <a href="<?= $baseUrl ?>/scorm/launch?course_id=1&module_id=1&content_id=58&title=Sample%20SCORM%20Content" 
                   class="demo-button" target="_blank">
                    <i class="fas fa-play me-2"></i>Launch SCORM Player
                </a>
                
                <a href="<?= $baseUrl ?>/scorm/launch?course_id=1&module_id=1&content_id=59&title=Advanced%20SCORM%20Demo" 
                   class="demo-button secondary" target="_blank">
                    <i class="fas fa-cog me-2"></i>Advanced Demo
                </a>
            </div>
            
            <div class="demo-section">
                <h3><i class="fas fa-cogs me-2"></i>System Components</h3>
                <p>The SCORM integration system includes:</p>
                
                <ul class="feature-list">
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>SCORM Wrapper:</strong> Full SCORM 1.2/2004 API compliance
                    </li>
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>SCORM Player:</strong> Professional content player interface
                    </li>
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>Integration Manager:</strong> Unified SCORM system management
                    </li>
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>Progress Tracking:</strong> Seamless integration with existing system
                    </li>
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>Resume Functionality:</strong> Continue from where you left off
                    </li>
                    <li>
                        <i class="fas fa-check-circle feature-icon"></i>
                        <strong>Auto-save:</strong> Automatic progress saving every 30 seconds
                    </li>
                </ul>
            </div>
            
            <div class="demo-section">
                <h3><i class="fas fa-info-circle me-2"></i>How to Use</h3>
                
                <div class="demo-info">
                    <h4><i class="fas fa-lightbulb me-2"></i>For Content Creators</h4>
                    <p>Upload SCORM packages through the VLR system. The system automatically extracts launch paths and manages content.</p>
                </div>
                
                <div class="demo-info">
                    <h4><i class="fas fa-users me-2"></i>For Learners</h4>
                    <p>Access SCORM content through your courses. Progress is automatically tracked and saved. Resume functionality works seamlessly.</p>
                </div>
                
                <div class="demo-info">
                    <h4><i class="fas fa-tools me-2"></i>For Developers</h4>
                    <p>Use the SCORM launcher URL: <code>/scorm/launch?course_id=X&module_id=Y&content_id=Z</code></p>
                </div>
            </div>
            
            <div class="demo-section">
                <h3><i class="fas fa-code me-2"></i>Technical Details</h3>
                <p>Integration points and API endpoints:</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>SCORM Endpoints:</h5>
                        <ul class="feature-list">
                            <li><code>GET /scorm/launch</code> - Launch SCORM content</li>
                            <li><code>POST /scorm/update</code> - Update progress</li>
                            <li><code>POST /scorm/complete</code> - Mark complete</li>
                            <li><code>GET /scorm/resume</code> - Get resume data</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>JavaScript Classes:</h5>
                        <ul class="feature-list">
                            <li><code>SCORMWrapper</code> - SCORM API implementation</li>
                            <li><code>SCORMPlayer</code> - Content player interface</li>
                            <li><code>SCORMIntegrationManager</code> - System coordination</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="demo-section">
                <h3><i class="fas fa-external-link-alt me-2"></i>Additional Resources</h3>
                <p>Explore the SCORM integration system:</p>
                
                <a href="<?= $baseUrl ?>/public/js/scorm-wrapper.js" class="demo-button secondary" target="_blank">
                    <i class="fas fa-file-code me-2"></i>View SCORM Wrapper
                </a>
                
                <a href="<?= $baseUrl ?>/public/js/scorm-player.js" class="demo-button secondary" target="_blank">
                    <i class="fas fa-file-code me-2"></i>View SCORM Player
                </a>
                
                <a href="<?= $baseUrl ?>/SCORM_INTEGRATION_README.md" class="demo-button secondary" target="_blank">
                    <i class="fas fa-book me-2"></i>Read Documentation
                </a>
                
                <a href="<?= $baseUrl ?>/vlr?tab=scorm" class="demo-button success" target="_blank">
                    <i class="fas fa-upload me-2"></i>Manage SCORM Packages
                </a>
            </div>
        </div>
        
        <div class="demo-footer">
            <p>
                <i class="fas fa-heart text-danger"></i>
                SCORM Integration System - Built for Unlock Your Skills Platform
            </p>
            <p>
                <small>
                    Features: SCORM 1.2/2004 Support | Progress Tracking | Resume Functionality | Auto-save | Professional UI
                </small>
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Demo page functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('SCORM Integration Demo loaded');
            
            // Add click tracking for demo buttons
            const demoButtons = document.querySelectorAll('.demo-button');
            demoButtons.forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Demo button clicked:', this.textContent.trim());
                });
            });
            
            // Show demo info
            console.log('SCORM Integration Demo Page');
            console.log('This page demonstrates the SCORM integration system');
            console.log('Click "Launch SCORM Player" to test the system');
        });
    </script>
</body>
</html>
