<?php
// views/vlr.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
<div class="container mt-4">
    <h1 class="page-title text-purple">Virtual Learning Repository (VLR)</h1>

    <!-- ✅ Tabs Section -->
    <ul class="nav nav-tabs" id="vlrTabs">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#scorm">SCORM</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#non-scorm">NON-SCORM</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#assessment">Assessment</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#audio">Audio</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#video">Video</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#document">Document</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#image">Image</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#external">External Content</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#survey">Survey</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#feedback">Feedback</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#interactive">Interactive & AI Content</a>
        </li>
    </ul>

    <!-- ✅ Tab Content Section -->
    <div class="tab-content mt-3">

        <!-- ✅ SCORM Package -->
        <div class="tab-pane show active" id="scorm">
                    <!-- SCORM Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>SCORM</h3>
                <!-- ✅ SCORM "Add" Button - Opens Modal -->
                <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#scormModal">+ Add SCORM</button>

                <!-- ✅ SCORM ADD MODAL -->
                <div class="modal fade" id="scormModal" tabindex="-1" role="dialog" aria-labelledby="scormModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="scormModalLabel">Add SCORM Package</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="scormForm" enctype="multipart/form-data">
                                    
                                    <!-- ✅ Title & Upload Zip (Side by Side) -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="scorm_title">Title <span class="text-danger">*</span></label>
                                                <input type="text" id="scorm_title" name="scorm_title" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="zipFile">Upload SCORM Zip File <span class="text-danger">*</span></label>
                                                <input type="file" id="zipFile" name="zipFile" class="form-control-file" accept=".zip" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Version, Language, SCORM Category (Side by Side) -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="version">Version <span class="text-danger">*</span></label>
                                                <input type="text" id="version" name="version" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="language">Language Support</label>
                                                <input type="text" id="language" name="language" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="scormCategory">SCORM Category <span class="text-danger">*</span></label>
                                                <select id="scormCategory" name="scormCategory" class="form-control" required>
                                                    <option value="">Select SCORM Type</option>
                                                    <option value="scorm1.2">SCORM 1.2</option>
                                                    <option value="scorm2004">SCORM 2004</option>
                                                    <option value="xapi">Tin Can API (xAPI)</option>
                                                    <option value="cmi5">CMI5</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Description (Full Width) -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea id="description" name="description" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Tags (Full Width) -->
                                    <div class="row">
                                        <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="tags">Tags/Keywords <span class="text-danger">*</span></label>
                                            <div class="tag-input-container form-control">
                                                <span id="tagDisplay"></span>
                                                <input type="text" id="tagInput" placeholder="Add a tag and press Enter">
                                            </div>
                                            <input type="hidden" name="tagList" id="tagList">
                                        </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Time Limit & Mobile Support (Side by Side) -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="timeLimit">Time Limit (in minutes)</label>
                                                <input type="number" id="timeLimit" name="timeLimit" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Mobile & Tablet Support</label><br>
                                                <label><input type="radio" name="mobileSupport" value="Yes"> Yes</label>
                                                <label class="ml-3"><input type="radio" name="mobileSupport" value="No" checked> No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Assessment Included (Full Width) -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Assessment Included</label><br>
                                                <label><input type="radio" name="assessment" value="Yes"> Yes</label>
                                                <label class="ml-3"><input type="radio" name="assessment" value="No" checked> No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ Submit & Cancel Buttons -->
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <button type="button" class="btn btn-danger" id="clearForm">Cancel</button>  
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>



            </div>
            <!-- ✅ SCORM Sub-Tabs -->
            <ul class="nav nav-tabs" id="scormSubTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#scorm-1.2">SCORM 1.2</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#scorm-2004">SCORM 2004</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tin-can-api">Tin Can API (xAPI)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#cmi5">CMI5</a>
                </li>
            </ul>

            <!-- ✅ SCORM Sub-Tab Content -->
            <div class="tab-content mt-3">
                <div class="tab-pane show active" id="scorm-1.2"> <!-- Ensure "show active" is set -->
                    <h4>SCORM 1.2 Content</h4>
                    <p>Details about SCORM 1.2...</p>
                </div>
                <div class="tab-pane" id="scorm-2004">
                    <h4>SCORM 2004 Content</h4>
                    <p>Details about SCORM 2004...</p>
                </div>
                <div class="tab-pane" id="tin-can-api">
                    <h4>Tin Can API (xAPI) Content</h4>
                    <p>Details about Tin Can API...</p>
                </div>
                <div class="tab-pane" id="cmi5">
                    <h4>CMI5 Content</h4>
                    <p>Details about CMI5...</p>
                </div>
            </div>
        </div>


        <!-- ✅ NON-SCORM -->
        <div class="tab-pane" id="non-scorm">
            <div class="d-flex justify-content-between align-items-center">
                <h3>NON-SCORM</h3>
                <button class="btn btn-sm btn-primary" onclick="openAddModal('NON-SCORM')">+ Add</button>
            </div>
            <div id="non-scorm-items"></div>
        </div>

        <!-- ✅ Assessment -->
        <div class="tab-pane" id="assessment">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Assessment</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Assessment')">+ Add</button>
        </div>    
            <div id="assessment-items"></div>
        </div>

        <!-- ✅ Audio -->
        <div class="tab-pane" id="audio">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Audio</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Audio')">+ Add</button>
        </div>
            <div id="audio-items"></div>
        </div>

        <!-- ✅ Video -->
        <div class="tab-pane" id="video">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Video</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Video')">+ Add</button>
        </div>
            <div id="video-items"></div>
        </div>

        <!-- ✅ DOCUMENTS Tab Content -->
        <div class="tab-pane" id="document">
            <!-- Document Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Documents</h3>
                <button class="btn btn-sm btn-primary" onclick="openAddModal('Document')">+ Add</button>
            </div>

            <!-- ✅ Document Sub-Tabs -->
            <ul class="nav nav-tabs" id="documentSubTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#word-excel-ppt">Word/Excel/PPT files</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#ebook-manual">E-book & Manual</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#research-case-studies">Research Paper & Case Studies</a>
                </li>
            </ul>

            <!-- ✅ Document Sub-Tab Content -->
            <div class="tab-content mt-3">
                <div class="tab-pane show active" id="word-excel-ppt">
                    <h4>Word/Excel/PPT Files</h4>
                    <p>Upload and manage Word, Excel, and PowerPoint files.</p>
                </div>
                <div class="tab-pane" id="ebook-manual">
                    <h4>E-Book & Manual</h4>
                    <p>Upload and manage e-books and manuals.</p>
                </div>
                <div class="tab-pane" id="research-case-studies">
                    <h4>Research Paper & Case Studies</h4>
                    <p>Upload and manage research papers and case studies.</p>
                </div>
            </div>
        </div>




         <!-- ✅ Image -->
         <div class="tab-pane" id="image">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Image</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Image')">+ Add</button>
        </div>
            <div id="image-items"></div>
        </div>

       
        <!-- ✅ EXTERNAL CONTENT Tab Content -->
        <div class="tab-pane" id="external">
            <!-- External Content Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>External Content</h3>
                <button class="btn btn-sm btn-primary" onclick="openAddModal('External')">+ Add</button>
            </div>

            <!-- ✅ External Content Sub-Tabs -->
            <ul class="nav nav-tabs" id="externalSubTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#youtube-vimeo">YouTube & Vimeo Integration</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#linkedin-udemy">LinkedIn Learning, Udemy, Coursera</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#web-links-blogs">Web Links & Blogs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#podcasts-audio">Podcasts & Audio Lessons</a>
                </li>
            </ul>

            <!-- ✅ External Content Sub-Tab Content -->
            <div class="tab-content mt-3">
                <div class="tab-pane show active" id="youtube-vimeo">
                    <h4>YouTube & Vimeo Integration</h4>
                    <p>Manage embedded YouTube & Vimeo videos.</p>
                </div>
                <div class="tab-pane" id="linkedin-udemy">
                    <h4>LinkedIn Learning, Udemy, Coursera</h4>
                    <p>Manage integrations with LinkedIn Learning, Udemy, and Coursera.</p>
                </div>
                <div class="tab-pane" id="web-links-blogs">
                    <h4>Web Links & Blogs</h4>
                    <p>Manage external web links and blogs.</p>
                </div>
                <div class="tab-pane" id="podcasts-audio">
                    <h4>Podcasts & Audio Lessons</h4>
                    <p>Manage podcasts and audio learning materials.</p>
                </div>
            </div>
        </div>



        <!-- ✅ Survey -->
        <div class="tab-pane" id="survey">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Survey</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Survey')">+ Add</button>
        </div>
            <div id="survey-items"></div>
        </div>

        <!-- ✅ Feedback -->
        <div class="tab-pane" id="feedback">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Feedback</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Feedback')">+ Add</button>
        </div>
            <div id="feedback-items"></div>
        </div>

        <!-- ✅ INTERACTIVE & AI POWERED CONTENT Tab Content -->
        <div class="tab-pane" id="interactive">
            <!-- Interactive & AI Powered Content Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Interactive & AI Powered Content</h3>
                <button class="btn btn-sm btn-primary" onclick="openAddModal('Interactive')">+ Add</button>
            </div>

            <!-- ✅ Interactive & AI Powered Content Sub-Tabs -->
            <ul class="nav nav-tabs" id="interactiveSubTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#adaptive-learning">Adaptive Learning Content</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chatbots-virtual-assistants">Chatbots & Virtual Assistants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#ar-vr">Augmented Reality (AR) / Virtual Reality (VR)</a>
                </li>
            </ul>

            <!-- ✅ Interactive & AI Powered Content Sub-Tab Content -->
            <div class="tab-content mt-3">
                <div class="tab-pane show active" id="adaptive-learning">
                    <h4>Adaptive Learning Content</h4>
                    <p>Manage and customize adaptive learning content.</p>
                </div>
                <div class="tab-pane" id="chatbots-virtual-assistants">
                    <h4>Chatbots & Virtual Assistants</h4>
                    <p>Manage AI-powered chatbots and virtual assistants.</p>
                </div>
                <div class="tab-pane" id="ar-vr">
                    <h4>Augmented Reality (AR) / Virtual Reality (VR)</h4>
                    <p>Manage AR and VR-based interactive learning experiences.</p>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
<script src="public/js/scorm_validation.js"></script>
<?php include 'includes/footer.php'; ?>
