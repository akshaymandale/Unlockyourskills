<?php
// views/vlr.php
//echo '<pre>'; print_r($_SESSION);

$clientName = $_SESSION['username'] ?? 'DEFAULT';
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
                <button class="btn btn-primary mb-3" data-toggle="modal" id="addScormBtn" data-target="#scormModal">+ Add SCORM</button>

                <!-- ✅ SCORM ADD MODAL -->
                <!-- SCORM Modal -->
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
                        <form id="scormForm" action="index.php?controller=VLRController&action=addOrEditScormPackage" method="POST" enctype="multipart/form-data">
                            <input type="hidden" id="scorm_id" name="scorm_id">
                            <input type="hidden" id="existing_zip" name="existing_zip"> <!-- Store existing file name -->
                                <!-- ✅ Title & Upload Zip (Side by Side) -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="scorm_title">Title <span class="text-danger">*</span></label>
                                            <input type="text" id="scorm_title" name="scorm_title" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="zipFile">Upload SCORM Zip File <span class="text-danger">*</span></label>
                                            <input type="file" id="zipFile" name="zipFile" class="form-control-file" accept=".zip">
                                            <p id="existingZipDisplay"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- ✅ Version, Language, SCORM Category (Side by Side) -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="version">Version <span class="text-danger">*</span></label>
                                            <input type="text" id="version" name="version" class="form-control">
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
                                            <select id="scormCategory" name="scormCategory" class="form-control">
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
                 
                <?php
// Validate if $scormPackages is set
if (!isset($scormPackages)) {
    $scormPackages = [];
}


// Categorize SCORM packages
$scormCategories = [
    'scorm-1.2' => [],
    'scorm-2004' => [],
    'tin-can-api' => [],
    'cmi5' => []
];

// Distribute packages into categories
foreach ($scormPackages as $package) {
    $category = strtolower(str_replace(' ', '-', $package['scorm_category']));
    if (isset($scormCategories[$category])) {
        $scormCategories[$category][] = $package;
    }
}
?>
<div class="tab-content mt-3">
    <?php
    // Define tab IDs corresponding to scorm_category
    $categories = [
        'scorm1.2' => 'scorm-1.2',
        'scorm2004' => 'scorm-2004',
        'xapi' => 'tin-can-api',
        'cmi5' => 'cmi5'
    ];

    // Initialize empty arrays to group data by category
    $groupedScormData = [
        'scorm-1.2' => [],
        'scorm-2004' => [],
        'tin-can-api' => [],
        'cmi5' => []
    ];

    // Group SCORM packages by category
    foreach ($scormPackages as $package) {
        $categoryKey = $categories[$package['scorm_category']] ?? null;
        if ($categoryKey) {
            $groupedScormData[$categoryKey][] = $package;
        }
    }

    // Loop through categories and display data accordingly
    foreach ($categories as $key => $tabId) :
    ?>
        <div class="tab-pane <?= $tabId === 'scorm-1.2' ? 'show active' : ''; ?>" id="<?= $tabId ?>">
            <h4><?= strtoupper(str_replace('-', ' ', $tabId)) ?> Content</h4>
            <div class="row">
                <?php if (!empty($groupedScormData[$tabId])) : ?>
                    <?php foreach ($groupedScormData[$tabId] as $scorm) : ?>
                        <div class="col-md-4">
                            <div class="scorm-card">
                                <div class="card-body">
                                    <div class="scorm-icon">
                                        <i class="fas fa-file-archive"></i>
                                    </div>
                                    <?php
                                    $displayTitle = strlen($scorm['title']) > 20 ? substr($scorm['title'], 0, 17) . '...' : $scorm['title'];
                                    ?>
                                    <h5 class="scorm-title" title="<?= htmlspecialchars($scorm['title']) ?>">
                                        <?= htmlspecialchars($displayTitle) ?>
                                    </h5>
                                    <div class="scorm-actions">
                                    <a href="#" class="edit-scorm" data-scorm='<?= json_encode($scorm); ?>'>
                                    <i class="fas fa-edit edit-icon" title="Edit"></i>
                                </a>
                                        <a href="index.php?controller=VLRController&action=delete&id=<?= $scorm['id'] ?>" onclick="return confirm('Are you sure you want to delete this SCORM package?');"> <i class="fas fa-trash-alt delete-icon" title="Delete"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No SCORM packages found for this category.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
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
                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#externalContentModal"> + Add External Content </button>

                    <!-- ✅ Modal for Adding External Content -->
                    <!-- Modal Popup -->
                    <div class="modal fade" id="externalContentModal" tabindex="-1" aria-labelledby="externalContentModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="externalModalLabel">Add External Content</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="externalContentForm" action="index.php?controller=VLRController&action=addOrEditExternalContent" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" id="external_id" name="id">

                                        <!-- Title -->
                                        <div class="form-group">
                                            <label for="title">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                            <span class="text-danger error-message"></span>
                                        </div>

                                        <!-- Version & Mobile Support -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="versionNumber">Version Number <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="versionNumber" name="version_number" required>
                                                    <span class="text-danger error-message"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Mobile & Tablet Support</label>
                                                    <div class="d-flex mt-2">
                                                        <div class="form-check mr-3">
                                                            <input class="form-check-input" type="radio" name="mobile_support" id="mobileYes" value="Yes">
                                                            <label class="form-check-label" for="mobileYes">Yes</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="mobile_support" id="mobileNo" value="No" checked>
                                                            <label class="form-check-label" for="mobileNo">No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Language & Time Limit -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="languageSupport">Language Support</label>
                                                    <select class="form-control" id="languageSupport" name="language_support">
                                                        <option value="English">English</option>
                                                        <option value="Hindi">Hindi</option>
                                                        <option value="Marathi">Marathi</option>
                                                        <option value="Spanish">Spanish</option>
                                                        <option value="French">French</option>
                                                        <option value="German">German</option>
                                                        <option value="Chinese">Chinese</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="timeLimit">Time Limit (in minutes)</label>
                                                    <input type="number" class="form-control" id="external_timeLimit" name="time_limit">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="external_description" name="description" rows="3"></textarea>
                                        </div>

                                        <!-- Tags/Keywords -->
                                        <div class="form-group">
                                            <label for="externalTagInput">Tags/Keywords <span class="text-danger">*</span></label>
                                            <div class="tag-input-container form-control">
                                                <span id="externalTagDisplay"></span>
                                                <input type="text"  id="externalTagInput" placeholder="Add a tag and press Enter">
                                            </div>
                                            <input type="hidden" name="tags" id="externalTagList">
                                            <span class="text-danger error-message" id="externalTagError"></span>
                                        </div>

                                        <!-- Content Type -->
                                        <div class="form-group">
                                            <label for="contentType">Content Type <span class="text-danger">*</span></label>
                                            <select class="form-control" id="contentType" name="content_type" onchange="showSelectedSection()" required>
                                                <option value="">Select</option>
                                                <option value="youtube-vimeo">YouTube & Vimeo</option>
                                                <option value="linkedin-udemy">LinkedIn Learning, Udemy, Coursera</option>
                                                <option value="web-links-blogs">Web Links & Blogs</option>
                                                <option value="podcasts-audio">Podcasts & Audio Lessons</option>
                                            </select>
                                        </div>

                                        <!-- Dynamic Content Sections -->
                                        <!-- Dynamic Fields Section -->
                                        <div id="dynamicFields">
                                            <!-- YouTube/Vimeo Fields -->
                                            <div class="content-group" id="youtubeVimeoFields">
                                                <div class="form-group">
                                                    <label for="videoUrl">Video URL <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="videoUrl" name="video_url">
                                                </div>
                                                <div class="form-group">
                                                    <label for="thumbnail">Thumbnail Preview</label>
                                                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                                    <img id="thumbnailPreview" src="" alt="Thumbnail Preview" style="display:none; max-width: 100px; margin-top: 10px;">
                                                    <div id="thumbnailFileLink" style="display:none;"></div>
                                                </div>
                                            </div>

                                            <!-- LinkedIn/Udemy Fields -->
                                            <div class="content-group" id="linkedinUdemyFields">
                                                <div class="form-group">
                                                    <label for="courseUrl">Course URL <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="courseUrl" name="course_url">
                                                </div>
                                                <div class="form-group">
                                                    <label for="platformName">Platform Name <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="platformName" name="platform_name">
                                                        <option value="">Select</option>
                                                        <option value="LinkedIn Learning">LinkedIn Learning</option>
                                                        <option value="Udemy">Udemy</option>
                                                        <option value="Coursera">Coursera</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Web Links/Blogs Fields -->
                                            <div class="content-group" id="webLinksBlogsFields">
                                                <div class="form-group">
                                                    <label for="articleUrl">URL <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="articleUrl" name="article_url">
                                                </div>
                                                <div class="form-group">
                                                    <label for="author">Author/Publisher</label>
                                                    <input type="text" class="form-control" id="author" name="author">
                                                </div>
                                            </div>

                                            <!-- Podcasts/Audio Fields -->
                                            <div class="content-group" id="podcastsAudioFields">
                                                <div class="form-group">
                                                    <label for="audioSource">Audio Source <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="audioSource" name="audio_source">
                                                        <option value="upload">Upload File</option>
                                                        <option value="url">Audio URL</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="audioFile">Upload Audio (MP3/WAV)</label>
                                                    <input type="file" class="form-control" id="audioFile" name="audio_file" accept=".mp3, .wav">
                                                </div>
                                                <div class="form-group">
                                                    <label for="audioUrl">Audio URL</label>
                                                    <input type="url" class="form-control" id="audioUrl" name="audio_url">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                            <button type="submit" id="submit_button" class="btn btn-primary">Submit</button>
                                            <button type="button" class="btn btn-danger" data-dismiss="modal" id="clearForm">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>





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
<!-- ✅ External Content Sub-Tab Content -->

<?php
// Validate if $externalContent is set
if (!isset($externalContent)) {
    $externalContent = [];
}

// Categorize External Content by type
$externalCategories = [
    'youtube-vimeo' => [],
    'linkedin-udemy' => [],
    'web-links-blogs' => [],
    'podcasts-audio' => []
];

// Distribute content into categories
foreach ($externalContent as $content) {
    $category = strtolower(str_replace(' ', '-', $content['content_type']));
    if (isset($externalCategories[$category])) {
        $externalCategories[$category][] = $content;
    }
}
?>

<div class="tab-content mt-3">
    <?php
    // Define tab IDs for external content
    $contentCategories = [
        'youtube-vimeo' => 'youtube-vimeo',
        'linkedin-udemy' => 'linkedin-udemy',
        'web-links-blogs' => 'web-links-blogs',
        'podcasts-audio' => 'podcasts-audio'
    ];

    // Initialize empty arrays to group data by category
    $groupedExternalData = [
        'youtube-vimeo' => [],
        'linkedin-udemy' => [],
        'web-links-blogs' => [],
        'podcasts-audio' => []
    ];

    // Group External Content by category
    foreach ($externalContent as $content) {
        $categoryKey = $contentCategories[$content['content_type']] ?? null;
        if ($categoryKey) {
            $groupedExternalData[$categoryKey][] = $content;
        }
    }

    // Loop through categories and display data accordingly
    foreach ($contentCategories as $key => $tabId) :
    ?>
        <div class="tab-pane <?= $tabId === 'youtube-vimeo' ? 'show active' : ''; ?>" id="<?= $tabId ?>">
            <h4><?= strtoupper(str_replace('-', ' ', $tabId)) ?> Content</h4>
            <div class="row">
                <?php if (!empty($groupedExternalData[$tabId])) : ?>
                    <?php foreach ($groupedExternalData[$tabId] as $content) : ?>
                        <?php
                        // Determine the icon class based on content type
                        $iconClass = '';
                        switch ($content['content_type']) {
                            case 'youtube-vimeo':
                                $iconClass = 'fas fa-video text-danger'; // Red for YouTube/Vimeo
                                break;
                            case 'linkedin-udemy':
                                $iconClass = 'fas fa-chalkboard-teacher text-primary'; // Blue for LinkedIn/Udemy
                                break;
                            case 'web-links-blogs':
                                $iconClass = 'fas fa-newspaper text-dark'; // Gray for Web Articles/Blogs
                                break;
                            case 'podcasts-audio':
                                $iconClass = 'fas fa-podcast text-warning'; // Orange for Podcasts
                                break;
                        }

                        // Truncate long titles
                        $displayTitle = strlen($content['title']) > 20 ? substr($content['title'], 0, 17) . '...' : $content['title'];
                        ?>
                        <div class="col-md-4">
                            <div class="content-card">
                                <div class="card-body">
                                    <div class="content-icon">
                                        <i class="<?= $iconClass; ?>"></i>
                                    </div>
                                    <h5 class="content-title" title="<?= htmlspecialchars($content['title']) ?>">
                                        <?= htmlspecialchars($displayTitle) ?>
                                    </h5>
                                    <div class="content-actions">
                                        <a href="#" class="edit-content" data-content='<?= json_encode($content); ?>'>
                                            <i class="fas fa-edit edit-icon" title="Edit"></i>
                                        </a>
                                        <a href="index.php?controller=VLRController&action=deleteExternal&id=<?= $content['id'] ?>" onclick="return confirm('Are you sure you want to delete this external content?');">
                                            <i class="fas fa-trash-alt delete-icon" title="Delete"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No External Content found for this category.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
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
<script src="public/js/scorm_package.js"></script>
<script src="public/js/external_content_validation.js"></script>
<script src="public/js/external_package.js"></script>
<?php include 'includes/footer.php'; ?>
