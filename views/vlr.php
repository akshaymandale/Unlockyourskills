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
            <h3>SCORM</h3>
            <div class="accordion" id="scormAccordion">
                <div class="card">
                    <div class="card-header">
                        <button class="btn btn-link text-purple" data-toggle="collapse" data-target="#scormSub">
                            SCORM Sub-packages
                        </button>
                        <button class="btn btn-sm btn-primary float-right" onclick="openAddModal('SCORM')">+ Add</button>
                    </div>
                    <div id="scormSub" class="collapse show" data-parent="#scormAccordion">
                        <div class="card-body">
                            <ul>
                                <li>SCORM 1.2</li>
                                <li>SCORM 2004</li>
                                <li>Tin Can API (xAPI)</li>
                                <li>CMI5</li>
                            </ul>
                            <div id="scorm-items"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ NON-SCORM -->
        <div class="tab-pane" id="non-scorm">
            <h3>NON-SCORM</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('NON-SCORM')">+ Add</button>
            <div id="non-scorm-items"></div>
        </div>

        <!-- ✅ Assessment -->
        <div class="tab-pane" id="assessment">
            <h3>Assessment</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Assessment')">+ Add</button>
            <div id="assessment-items"></div>
        </div>

        <!-- ✅ Audio -->
        <div class="tab-pane" id="audio">
            <h3>Audio</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Audio')">+ Add</button>
            <div id="audio-items"></div>
        </div>

        <!-- ✅ Video -->
        <div class="tab-pane" id="video">
            <h3>Video</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Video')">+ Add</button>
            <div id="video-items"></div>
        </div>

        <!-- ✅ Documents -->
        <div class="tab-pane" id="document">
            <h3>Document</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Document')">+ Add</button>
            <ul>
                <li>Word/Excel/PPT files</li>
                <li>E-book & Manual</li>
                <li>Research Paper & Case Studies</li>
            </ul>
            <div id="document-items"></div>
        </div>

        <!-- ✅ External Content -->
        <div class="tab-pane" id="external">
            <h3>External Content</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('External')">+ Add</button>
            <ul>
                <li>YouTube & Vimeo Integration</li>
                <li>LinkedIn Learning, Udemy, Coursera</li>
                <li>Web Links & Blogs</li>
                <li>Podcasts & Audio Lessons</li>
            </ul>
            <div id="external-items"></div>
        </div>

        <!-- ✅ Survey -->
        <div class="tab-pane" id="survey">
            <h3>Survey</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Survey')">+ Add</button>
            <div id="survey-items"></div>
        </div>

        <!-- ✅ Feedback -->
        <div class="tab-pane" id="feedback">
            <h3>Feedback</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Feedback')">+ Add</button>
            <div id="feedback-items"></div>
        </div>

        <!-- ✅ Interactive & AI Powered Content -->
        <div class="tab-pane" id="interactive">
            <h3>Interactive & AI Powered Content</h3>
            <button class="btn btn-sm btn-primary" onclick="openAddModal('Interactive')">+ Add</button>
            <ul>
                <li>Adaptive Learning Content</li>
                <li>Chatbots & Virtual Assistants</li>
                <li>Augmented Reality (AR) / Virtual Reality (VR)</li>
            </ul>
            <div id="interactive-items"></div>
        </div>

    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
