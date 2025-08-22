/**
 * Content Preview Functionality
 * Handles preview for Audio, Video, Image, Document, and External Content
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize preview functionality
    initializePreviewHandlers();
});

function initializePreviewHandlers() {
    // Audio Preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-audio')) {
            e.preventDefault();
            const audioData = JSON.parse(e.target.closest('.preview-audio').getAttribute('data-audio'));
            showAudioPreview(audioData);
        }
    });

    // Video Preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-video')) {
            e.preventDefault();
            const videoData = JSON.parse(e.target.closest('.preview-video').getAttribute('data-video'));
            showVideoPreview(videoData);
        }
    });

    // Image Preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-image')) {
            e.preventDefault();
            const imageData = JSON.parse(e.target.closest('.preview-image').getAttribute('data-image'));
            showImagePreview(imageData);
        }
    });

    // Document Preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-document')) {
            e.preventDefault();
            const documentData = JSON.parse(e.target.closest('.preview-document').getAttribute('data-document'));
            showDocumentPreview(documentData);
        }
    });

    // External Content Preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-external')) {
            e.preventDefault();
            const externalData = JSON.parse(e.target.closest('.preview-external').getAttribute('data-content'));
            showExternalPreview(externalData);
        }
    });
}

function showAudioPreview(audioData) {
    const modalTitle = document.getElementById('previewModalLabel');
    const modalBody = document.getElementById('previewModalBody');
    
    modalTitle.textContent = `Audio Preview: ${audioData.title}`;
    
    const audioPath = audioData.audio_file_path || audioData.audio_file;
    const fullAudioPath = audioPath.startsWith('uploads/') ? audioPath : `uploads/audio/${audioPath}`;
    
    modalBody.innerHTML = `
        <div class="preview-content">
            <div class="content-info mb-3">
                <h5>${audioData.title}</h5>
                <p class="text-muted">${audioData.description || 'No description available'}</p>
                <div class="content-details">
                    <span class="badge bg-primary">Version: ${audioData.version || 'N/A'}</span>
                    <span class="badge bg-info">Language: ${audioData.language_name || 'N/A'}</span>
                    <span class="badge bg-secondary">Mobile Support: ${audioData.mobile_support || 'No'}</span>
                </div>
            </div>
            <div class="audio-player">
                <audio controls style="width: 100%;">
                    <source src="${fullAudioPath}" type="audio/mpeg">
                    <source src="${fullAudioPath}" type="audio/wav">
                    Your browser does not support the audio element.
                </audio>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function showVideoPreview(videoData) {
    const modalTitle = document.getElementById('previewModalLabel');
    const modalBody = document.getElementById('previewModalBody');
    
    modalTitle.textContent = `Video Preview: ${videoData.title}`;
    
    const videoPath = videoData.video_file_path || videoData.video_file;
    const fullVideoPath = videoPath.startsWith('uploads/') ? videoPath : `uploads/video/${videoPath}`;
    
    modalBody.innerHTML = `
        <div class="preview-content">
            <div class="content-info mb-3">
                <h5>${videoData.title}</h5>
                <p class="text-muted">${videoData.description || 'No description available'}</p>
                <div class="content-details">
                    <span class="badge bg-primary">Version: ${videoData.version || 'N/A'}</span>
                    <span class="badge bg-info">Language: ${videoData.language_name || 'N/A'}</span>
                    <span class="badge bg-secondary">Mobile Support: ${videoData.mobile_support || 'No'}</span>
                </div>
            </div>
            <div class="video-player">
                <video controls style="width: 100%; max-height: 500px;">
                    <source src="${fullVideoPath}" type="video/mp4">
                    <source src="${fullVideoPath}" type="video/webm">
                    <source src="${fullVideoPath}" type="video/ogg">
                    Your browser does not support the video element.
                </video>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function showImagePreview(imageData) {
    const modalTitle = document.getElementById('previewModalLabel');
    const modalBody = document.getElementById('previewModalBody');
    
    modalTitle.textContent = `Image Preview: ${imageData.title}`;
    
    const imagePath = imageData.image_file_path || imageData.image_file;
    const fullImagePath = imagePath.startsWith('uploads/') ? imagePath : `uploads/image/${imagePath}`;
    
    modalBody.innerHTML = `
        <div class="preview-content">
            <div class="content-info mb-3">
                <h5>${imageData.title}</h5>
                <p class="text-muted">${imageData.description || 'No description available'}</p>
                <div class="content-details">
                    <span class="badge bg-primary">Version: ${imageData.version || 'N/A'}</span>
                    <span class="badge bg-info">Language: ${imageData.language_name || 'N/A'}</span>
                    <span class="badge bg-secondary">Mobile Support: ${imageData.mobile_support || 'No'}</span>
                </div>
            </div>
            <div class="image-viewer text-center">
                <img src="${fullImagePath}" alt="${imageData.title}" 
                     style="max-width: 100%; max-height: 600px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';">
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function showDocumentPreview(documentData) {
    const modalTitle = document.getElementById('previewModalLabel');
    const modalBody = document.getElementById('previewModalBody');

    modalTitle.textContent = `Document Preview: ${documentData.title}`;

    // Documents have different file fields based on category
    const documentPath = documentData.word_excel_ppt_file ||
                        documentData.ebook_manual_file ||
                        documentData.research_file;

    if (!documentPath) {
        modalBody.innerHTML = `
            <div class="preview-content">
                <div class="content-info mb-3">
                    <h5>${documentData.title}</h5>
                    <p class="text-muted">${documentData.description || 'No description available'}</p>
                    <div class="content-details">
                        <span class="badge bg-primary">Version: ${documentData.version_number || 'N/A'}</span>
                        <span class="badge bg-info">Language: ${documentData.language_name || 'N/A'}</span>
                        <span class="badge bg-secondary">Mobile Support: ${documentData.mobile_support || 'No'}</span>
                    </div>
                </div>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>No File Available</h5>
                    <p>This document does not have an associated file.</p>
                </div>
            </div>
        `;
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
        return;
    }

    const fullDocumentPath = documentPath.startsWith('uploads/') ? documentPath : `uploads/documents/${documentPath}`;
    const fileExtension = documentPath.split('.').pop().toLowerCase();
    
    let previewContent = '';
    
    if (['pdf'].includes(fileExtension)) {
        previewContent = `
            <div class="document-viewer">
                <embed src="${fullDocumentPath}" type="application/pdf" width="100%" height="600px">
                <p class="mt-2 text-center">
                    <a href="${fullDocumentPath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Open in New Tab
                    </a>
                </p>
            </div>
        `;
    } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileExtension)) {
        previewContent = `
            <div class="document-viewer text-center">
                <div class="alert alert-info">
                    <i class="fas fa-file-${getFileIcon(fileExtension)} fa-3x mb-3"></i>
                    <h5>Document Preview Not Available</h5>
                    <p>This document type (${fileExtension.toUpperCase()}) cannot be previewed directly in the browser.</p>
                    <a href="${fullDocumentPath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download Document
                    </a>
                </div>
            </div>
        `;
    } else {
        previewContent = `
            <div class="document-viewer text-center">
                <div class="alert alert-warning">
                    <i class="fas fa-file fa-3x mb-3"></i>
                    <h5>Unsupported File Type</h5>
                    <p>Preview not available for this file type (${fileExtension.toUpperCase()}).</p>
                    <a href="${fullDocumentPath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
            </div>
        `;
    }
    
    modalBody.innerHTML = `
        <div class="preview-content">
            <div class="content-info mb-3">
                <h5>${documentData.title}</h5>
                <p class="text-muted">${documentData.description || 'No description available'}</p>
                <div class="content-details">
                    <span class="badge bg-primary">Version: ${documentData.version_number || 'N/A'}</span>
                    <span class="badge bg-info">Language: ${documentData.language_name || 'N/A'}</span>
                    <span class="badge bg-secondary">Mobile Support: ${documentData.mobile_support || 'No'}</span>
                    <span class="badge bg-warning">Category: ${documentData.category || 'N/A'}</span>
                    <span class="badge bg-dark">Type: ${fileExtension.toUpperCase()}</span>
                </div>
            </div>
            ${previewContent}
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function showExternalPreview(externalData) {
    const modalTitle = document.getElementById('previewModalLabel');
    const modalBody = document.getElementById('previewModalBody');

    modalTitle.textContent = `External Content Preview: ${externalData.title}`;

    let previewContent = '';
    const contentType = externalData.content_type;
    const contentTypeName = getContentTypeName(contentType);
    
    switch(contentType) {
        case 'youtube-vimeo':
            const videoUrl = externalData.video_url;
            const embedUrl = getEmbedUrl(videoUrl);
            previewContent = `
                <div class="external-viewer">
                    ${embedUrl ? 
                        `<iframe width="100%" height="400" src="${embedUrl}" frameborder="0" allowfullscreen></iframe>` :
                        `<div class="alert alert-warning">
                            <p>Video preview not available. <a href="${videoUrl}" target="_blank">Open Video</a></p>
                        </div>`
                    }
                </div>
            `;
            break;
            
        case 'linkedin-udemy':
            previewContent = `
                <div class="external-viewer">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h5>Course Content</h5>
                        <p>Platform: ${externalData.platform_name}</p>
                        <a href="${externalData.course_url}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Open Course
                        </a>
                    </div>
                </div>
            `;
            break;
            
        case 'web-links-blogs':
            previewContent = `
                <div class="external-viewer">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-globe fa-3x mb-3"></i>
                        <h5>Web Article/Blog</h5>
                        <p>Author: ${externalData.author || 'Unknown'}</p>
                        <a href="${externalData.article_url}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Read Article
                        </a>
                    </div>
                </div>
            `;
            break;
            
        case 'podcasts-audio':
            if (externalData.audio_file) {
                // Construct the correct audio file path
                let audioPath;
                if (externalData.audio_file.startsWith('http')) {
                    // External URL
                    audioPath = externalData.audio_file;
                } else if (externalData.audio_file.startsWith('/')) {
                    // Absolute path
                    audioPath = externalData.audio_file;
                } else if (externalData.audio_file.startsWith('uploads/')) {
                    // Relative path starting with uploads/
                    audioPath = `/${window.location.pathname.split('/')[1]}/${externalData.audio_file}`;
                } else {
                    // Just filename, construct full path
                    audioPath = `/${window.location.pathname.split('/')[1]}/uploads/external/audio/${externalData.audio_file}`;
                }
                
                // Debug logging
                console.log('🔊 Audio Preview Debug:', {
                    originalFile: externalData.audio_file,
                    constructedPath: audioPath,
                    pathname: window.location.pathname,
                    pathParts: window.location.pathname.split('/')
                });
                
                previewContent = `
                    <div class="external-viewer">
                        <div class="audio-player">
                            <audio controls style="width: 100%;" preload="metadata" onerror="handleAudioError(this)">
                                <source src="${audioPath}" type="audio/mpeg">
                                <source src="${audioPath}" type="audio/mp3">
                                <source src="${audioPath}" type="audio/wav">
                                <source src="${audioPath}" type="audio/ogg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        <p class="mt-2"><strong>Speaker:</strong> ${externalData.speaker || 'Unknown'}</p>
                        <div class="mt-2">
                            <a href="${audioPath}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> Download Audio
                            </a>
                        </div>
                    </div>
                `;
            } else if (externalData.audio_url) {
                previewContent = `
                    <div class="external-viewer">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-podcast fa-3x mb-3"></i>
                            <h5>Podcast/Audio Content</h5>
                            <p>Speaker: ${externalData.speaker || 'Unknown'}</p>
                            <a href="${externalData.audio_url}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Listen to Audio
                            </a>
                        </div>
                    </div>
                `;
            }
            break;
            
        default:
            previewContent = `
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>Preview Not Available</h5>
                    <p>This content type cannot be previewed.</p>
                </div>
            `;
    }
    
    modalBody.innerHTML = `
        <div class="preview-content">
            <div class="content-info mb-3">
                <h5>${externalData.title}</h5>
                <p class="text-muted">${externalData.description || 'No description available'}</p>
                <div class="content-details">
                    <span class="badge bg-primary">Version: ${externalData.version_number || 'N/A'}</span>
                    <span class="badge bg-info">Language: ${externalData.language_name || 'N/A'}</span>
                    <span class="badge bg-secondary">Mobile Support: ${externalData.mobile_support || 'No'}</span>
                    <span class="badge bg-warning">Type: ${contentTypeName}</span>
                </div>
            </div>
            ${previewContent}
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

// Helper Functions
function getFileIcon(extension) {
    const iconMap = {
        'pdf': 'pdf',
        'doc': 'word',
        'docx': 'word',
        'xls': 'excel',
        'xlsx': 'excel',
        'ppt': 'powerpoint',
        'pptx': 'powerpoint'
    };
    return iconMap[extension] || 'alt';
}

function getEmbedUrl(url) {
    // YouTube
    const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/);
    if (youtubeMatch) {
        return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
    }

    // Vimeo
    const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
    if (vimeoMatch) {
        return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
    }

    return null;
}

function getContentTypeName(contentType) {
    const contentTypeMap = {
        'youtube-vimeo': 'YouTube/Vimeo Video',
        'linkedin-udemy': 'Online Course',
        'web-links-blogs': 'Web Article/Blog',
        'podcasts-audio': 'Podcast/Audio Content'
    };

    return contentTypeMap[contentType] || contentType || 'Unknown';
}

// Audio error handling function
function handleAudioError(audioElement) {
    console.error('🔊 Audio playback error:', audioElement.error);
    const audioContainer = audioElement.closest('.audio-player');
    if (audioContainer) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-2';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Audio Playback Error:</strong> 
            ${audioElement.error ? audioElement.error.message : 'Unknown error occurred'}
            <br>
            <small>Please check the audio file path or try downloading the file.</small>
        `;
        audioContainer.appendChild(errorDiv);
    }
}


