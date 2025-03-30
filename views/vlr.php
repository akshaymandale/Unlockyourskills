<?php
// views/vlr.php
//echo '<pre>'; print_r($_SESSION);

$clientName = $_SESSION['username'] ?? 'DEFAULT';

$vlrController = new VLRController();
$languageList = $vlrController->getLanguages();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>




<div class="main-content">
    <div class="container mt-4">
        <h1 class="page-title text-purple">
            <?= Localization::translate('vlr_title'); ?>
        </h1>

        <!-- ✅ Tabs Section -->
        <ul class="nav nav-tabs" id="vlrTabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#scorm">
                    <?= Localization::translate('scorm'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#non-scorm">
                    <?= Localization::translate('non_scorm'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#assessment">
                    <?= Localization::translate('assessment'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#audio">
                    <?= Localization::translate('audio'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#video">
                    <?= Localization::translate('video'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#document">
                    <?= Localization::translate('document'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#image">
                    <?= Localization::translate('image'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#external">
                    <?= Localization::translate('external_content'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#survey">
                    <?= Localization::translate('survey'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#feedback">
                    <?= Localization::translate('feedback'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#interactive">
                    <?= Localization::translate('interactive_ai_content'); ?>
                </a>
            </li>
        </ul>

        <!-- ✅ Tab Content Section -->
        <div class="tab-content mt-3">

            <!-- ✅ SCORM Package -->
            <div class="tab-pane show active" id="scorm">
                <!-- SCORM Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('scorm'); ?></h3>

                    <!-- ✅ SCORM "Add" Button - Opens Modal -->
                    <button class="btn btn-primary mb-3" data-toggle="modal" id="addScormBtn" data-target="#scormModal">
                        + <?= Localization::translate('add_scorm'); ?>
                    </button>

                    <!-- ✅ SCORM ADD MODAL -->
                    <div class="modal fade" id="scormModal" tabindex="-1" role="dialog"
                        aria-labelledby="scormModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="scormModalLabel">
                                        <?= Localization::translate('add_scorm_package'); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal"
                                        aria-label="<?= Localization::translate('close'); ?>">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="scormForm"
                                        action="index.php?controller=VLRController&action=addOrEditScormPackage"
                                        method="POST" enctype="multipart/form-data">
                                        <input type="hidden" id="scorm_id" name="scorm_id">
                                        <input type="hidden" id="existing_zip" name="existing_zip">
                                        <!-- Store existing file name -->

                                        <!-- ✅ Title & Upload Zip -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="scorm_title"><?= Localization::translate('title'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="text" id="scorm_title" name="scorm_title"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label
                                                        for="zipFile"><?= Localization::translate('upload_scorm_zip'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="file" id="zipFile" name="zipFile"
                                                        class="form-control-file" accept=".zip">
                                                    <p id="existingZipDisplay"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Version, Language, SCORM Category -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="version"><?= Localization::translate('version'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="text" id="version" name="version" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label
                                                        for="language"><?= Localization::translate('language_support'); ?></label>
                                                    <input type="text" id="language" name="language"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label
                                                        for="scormCategory"><?= Localization::translate('scorm_category'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <select id="scormCategory" name="scormCategory"
                                                        class="form-control">
                                                        <option value="">
                                                            <?= Localization::translate('select_scorm_type'); ?>
                                                        </option>
                                                        <option value="scorm1.2">
                                                            <?= Localization::translate('scorm_1_2'); ?>
                                                        </option>
                                                        <option value="scorm2004">
                                                            <?= Localization::translate('scorm_2004'); ?>
                                                        </option>
                                                        <option value="xapi">
                                                            <?= Localization::translate('tincan_api_xapi'); ?>
                                                        </option>
                                                        <option value="cmi5"><?= Localization::translate('cmi5'); ?>
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Description -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label
                                                        for="description"><?= Localization::translate('description'); ?></label>
                                                    <textarea id="description" name="description"
                                                        class="form-control"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="tags"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="tagDisplay"></span>
                                                        <input type="text" id="tagInput"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                                    </div>
                                                    <input type="hidden" name="tagList" id="tagList">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Time Limit & Mobile Support -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label
                                                        for="timeLimit"><?= Localization::translate('time_limit'); ?></label>
                                                    <input type="number" id="timeLimit" name="timeLimit"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('mobile_tablet_support'); ?></label><br>
                                                    <label><input type="radio" name="mobileSupport" value="Yes">
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="mobileSupport"
                                                            value="No" checked>
                                                        <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Assessment Included -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('assessment_included'); ?></label><br>
                                                    <label><input type="radio" name="assessment" value="Yes">
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="assessment" value="No"
                                                            checked> <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Submit & Cancel Buttons -->
                                        <div class="modal-footer">
                                            <button type="submit"
                                                class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                            <button type="button" class="btn btn-danger"
                                                id="clearForm"><?= Localization::translate('cancel'); ?></button>
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
                        <a class="nav-link active" data-toggle="tab" href="#scorm-1.2">
                            <?= Localization::translate('scorm_1_2'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#scorm-2004">
                            <?= Localization::translate('scorm_2004'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tin-can-api">
                            <?= Localization::translate('tincan_api_xapi'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#cmi5">
                            <?= Localization::translate('cmi5'); ?>
                        </a>
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
                    foreach ($categories as $key => $tabId):
                        ?>
                        <div class="tab-pane <?= $tabId === 'scorm-1.2' ? 'show active' : ''; ?>" id="<?= $tabId ?>">
                            <h4><?= Localization::translate($tabId . '_content'); ?></h4>
                            <div class="row">
                                <?php if (!empty($groupedScormData[$tabId])): ?>
                                    <?php foreach ($groupedScormData[$tabId] as $scorm): ?>
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
                                                            <i class="fas fa-edit edit-icon"
                                                                title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="index.php?controller=VLRController&action=delete&id=<?= $scorm['id'] ?>"
                                                            onclick="return confirm('<?= Localization::translate('delete_confirmation'); ?>');">
                                                            <i class="fas fa-trash-alt delete-icon"
                                                                title="<?= Localization::translate('delete'); ?>"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?= Localization::translate('no_scorm_found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


            </div>


            <!-- ✅ NON-SCORM -->
            <div class="tab-pane" id="non-scorm">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('non_scorm'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('NON-SCORM')">+
                        <?= Localization::translate('add'); ?></button>
                </div>
                <div id="non-scorm-items"></div>
            </div>

            <!-- ✅ Assessment -->
            <div class="tab-pane" id="assessment">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('assessment'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Assessment')">+
                        <?= Localization::translate('add'); ?></button>
                </div>
                <div id="assessment-items"></div>
            </div>

            <!-- ✅ Audio -->
            <div class="tab-pane" id="audio">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('audio'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Audio')">+
                        <?= Localization::translate('add'); ?></button>
                </div>
                <div id="audio-items"></div>
            </div>

            <!-- ✅ Video -->
            <div class="tab-pane" id="video">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('video'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Video')">+
                        <?= Localization::translate('add'); ?></button>
                </div>
                <div id="video-items"></div>
            </div>


            <!-- ✅ DOCUMENTS Tab Content -->
            <div class="tab-pane" id="document">
                <!-- Document Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('documents'); ?></h3>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal"
                        id="addDocumentBtn">
                        + <?= Localization::translate('addk'); ?>
                    </button>

                    <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="documentModalLabel">
                                        <?= Localization::translate('document.modal.add'); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal"
                                        aria-label="<?= Localization::translate('close'); ?>">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="documentForm" method="POST"
                                        action="index.php?controller=VLRController&action=addOrEditDocument"
                                        enctype="multipart/form-data">
                                        <input type="hidden" id="documentId" name="documentId">
                                        <!-- Hidden Fields for Existing Files -->
                                        <input type="hidden" id="existingDocumentWordExcelPpt" name="existingDocumentWordExcelPpt">
                                        <input type="hidden" id="existingDocumentEbookManual" name="existingDocumentEbookManual">
                                        <input type="hidden" id="existingDocumentResearch" name="existingDocumentResearch">
                                        <div class="row">
                                            <!-- Title -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="document_title" class="form-label">Title <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="document_title"
                                                    name="document_title">
                                            </div>

                                            <!-- Category -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="documentCategory" class="form-label">Category <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" id="documentCategory"
                                                    name="documentCategory">
                                                    <option value="">Select Category</option>
                                                    <option value="Word/Excel/PPT Files">Word/Excel/PPT Files</option>
                                                    <option value="E-Book & Manual">E-Book & Manual</option>
                                                    <option value="Research Paper & Case Studies">Research Paper & Case
                                                        Studies</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Dynamic Category Fields -->
                                        <div class="row">
                                            <div id="wordExcelPptFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileWordExcelPpt">Upload File (.docx, .xlsx, .pptx,
                                                    .pdf)</label>
                                                <input type="file" class="form-control" id="documentFileWordExcelPpt"
                                                    name="documentFileWordExcelPpt" accept=".docx, .xlsx, .pptx, .pdf">
                                                    <p id="existingDocumentWordExcelPptDisplay"></p>
                                            </div>

                                            <div id="ebookManualFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileEbookManual">Upload File (.pdf, .epub,
                                                    .mobi)</label>
                                                <input type="file" class="form-control" id="documentFileEbookManual"
                                                    name="documentFileEbookManual" accept=".pdf, .epub, .mobi">
                                                    <p id="existingDocumentEbookManualDisplay"></p>
                                            </div>

                                            <div id="researchFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileResearch">Upload File (.pdf, .docx)</label>
                                                <input type="file" class="form-control" id="documentFileResearch"
                                                    name="documentFileResearch" accept=".pdf, .docx">
                                                    <p id="existingDocumentResearchDisplay"></p>
                                            </div>
                                        </div>

                                        <!-- Research-Specific Fields -->
                                        <div id="researchDetails" class="row" style="display: none;">
                                            <!-- Authors -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="research_authors">Authors</label>
                                                <input type="text" class="form-control" id="research_authors"
                                                    name="research_authors">
                                            </div>

                                            <!-- Publication Date -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="research_publication_date">Publication Date</label>
                                                <input type="date" class="form-control" id="research_publication_date"
                                                    name="research_publication_date">
                                            </div>

                                            <!-- Reference Links -->
                                            <div class="col-md-12 form-group mb-3">
                                                <label for="research_references">Reference Links</label>
                                                <input type="text" class="form-control" id="research_references"
                                                    name="research_references">
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="form-group mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="document_description"
                                                name="description"></textarea>
                                        </div>

                                        <!-- Tags -->


                                        <div class="row">
  
        <div class="form-group mb-3">
            <label for="documentTags"><?= Localization::translate('tags_keywords'); ?>
                <span class="text-danger">*</span></label>
            <div class="tag-input-container form-control">
                <span id="documentTagDisplay"></span> <!-- Updated ID -->
                <input type="text" id="documentTagInput" 
                    placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
            </div>
            <input type="hidden" name="documentTagList" id="documentTagList">
        </div>
    </div>


                                        <div class="row">
                                            <!-- Language -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="language" class="form-label">Language</label>
                                                <select class="form-control" id="document_language" name="language">
                                                    <option value="">Select Language</option>
                                                    <?php
                                                    if (!empty($languageList) && is_array($languageList)) {
                                                        foreach ($languageList as $lang) {
                                                            if (isset($lang['id']) && isset($lang['language_name'])) {
                                                                $langId = htmlspecialchars($lang['id'], ENT_QUOTES, 'UTF-8');
                                                                $langName = htmlspecialchars($lang['language_name'], ENT_QUOTES, 'UTF-8');
                                                                echo "<option value=\"$langId\">$langName</option>";
                                                            }
                                                        }
                                                    } else {
                                                        echo '<option value="">No languages available</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Mobile Support -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="form-label">Mobile Support</label>
                                                <div>
                                                    <input type="radio" id="mobile_yes" name="mobile_support"
                                                        value="Yes"> Yes
                                                    <input type="radio" id="mobile_no" name="mobile_support" value="No"
                                                        checked> No
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Version -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="doc_version" class="form-label">Version Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="doc_version"
                                                    name="doc_version">
                                            </div>

                                            <!-- Time Limit -->
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="doc_time_limit" class="form-label">Time Limit
                                                    (minutes)</label>
                                                <input type="number" class="form-control" id="doc_time_limit"
                                                    name="doc_time_limit" min="1">
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">
                                                <?= Localization::translate('submit'); ?>
                                            </button>
                                            <button type="button" class="btn btn-danger" id="cancelForm">
                                                <?= Localization::translate('cancel'); ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- ✅ Document Sub-Tabs -->
                <ul class="nav nav-tabs" id="documentSubTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#word-excel-ppt">
                            <?= Localization::translate('word_excel_ppt'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#ebook-manual">
                            <?= Localization::translate('ebook_manual'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#research-case-studies">
                            <?= Localization::translate('research_case_studies'); ?>
                        </a>
                    </li>
                </ul>

                <!-- ✅ Document Sub-Tab Content -->
                <!-- ✅ Document Sub-Tab Content -->

                <?php
                // Fetch documents from the controller
                if (!isset($documents)) {
                    $documents = [];
                }

                // Define categories for Documents based on DB values
                $documentCategories = [
                    'Word/Excel/PPT Files' => [],
                    'E-Book & Manual' => [],
                    'Research Paper & Case Studies' => []
                ];

                // Distribute documents into categories
                foreach ($documents as $document) {
                    $category = $document['category'] ?? ''; // Get category from DB
                    if (isset($documentCategories[$category])) {
                        $documentCategories[$category][] = $document;
                    }
                }
                ?>

                <div class="tab-content mt-3">
                    <?php
                    // Define tab IDs and their localized titles based on DB values
                    $contentCategories = [
                        'Word/Excel/PPT Files' => ['id' => 'word-excel-ppt', 'label' => 'word_excel_ppt'],
                        'E-Book & Manual' => ['id' => 'ebook-manual', 'label' => 'ebook_manual'],
                        'Research Paper & Case Studies' => ['id' => 'research-case-studies', 'label' => 'research_case_studies']
                    ];

                    // Loop through categories and display data accordingly
                    foreach ($contentCategories as $dbCategory => $tabInfo): ?>
                        <div class="tab-pane <?= $tabInfo['id'] === 'word-excel-ppt' ? 'show active' : ''; ?>"
                            id="<?= $tabInfo['id'] ?>">
                            <h4><?= Localization::translate($tabInfo['label']) ?></h4>
                            <div class="row">
                                <?php if (!empty($documentCategories[$dbCategory])): ?>
                                    <?php foreach ($documentCategories[$dbCategory] as $document): ?>
                                        <?php
                                        // Determine the icon class based on document category
                                        $iconClass = '';
                                        switch ($dbCategory) {
                                            case 'Word/Excel/PPT Files':
                                                $iconClass = 'fas fa-file-word text-primary'; // Blue for Word/Excel/PPT
                                                break;
                                            case 'E-Book & Manual':
                                                $iconClass = 'fas fa-book text-success'; // Green for E-Books/Manuals
                                                break;
                                            case 'Research Paper & Case Studies':
                                                $iconClass = 'fas fa-scroll text-warning'; // Orange for Research/Case Studies
                                                break;
                                        }

                                        // Truncate long titles
                                        $displayTitle = strlen($document['title']) > 20 ? substr($document['title'], 0, 17) . '...' : $document['title'];
                                        ?>
                                        <div class="col-md-4">
                                            <div class="content-card">
                                                <div class="card-body">
                                                    <div class="content-icon">
                                                        <i class="<?= $iconClass; ?>"></i>
                                                    </div>
                                                    <h5 class="content-title" title="<?= htmlspecialchars($document['title']) ?>">
                                                        <?= htmlspecialchars($displayTitle) ?>
                                                    </h5>

                                                    <div class="content-actions">
                                                        <a href="#" class="edit-document"
                                                            data-document='<?= json_encode($document); ?>'>
                                                            <i class="fas fa-edit edit-icon"
                                                                title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="index.php?controller=VLRController&action=deleteDocument&id=<?= $document['id'] ?>"
                                                            onclick="return confirm('<?= Localization::translate('confirm_delete'); ?>');">
                                                            <i class="fas fa-trash-alt delete-icon"
                                                                title="<?= Localization::translate('delete'); ?>"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?= Localization::translate('no_documents_available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


            </div>

            <!-- ✅ Image -->
            <div class="tab-pane" id="image">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('image'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Image')">+
                        <?= Localization::translate('add'); ?></button>
                </div>
                <div id="image-items"></div>
            </div>



            <!-- ✅ EXTERNAL CONTENT Tab Content -->
            <div class="tab-pane" id="external">
                <!-- External Content Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('external_content'); ?></h3>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#externalContentModal">
                        + <?= Localization::translate('add_external_content'); ?>
                    </button>

                    <!-- ✅ Modal for Adding External Content -->
                    <!-- Modal Popup -->
                    <div class="modal fade" id="externalContentModal" tabindex="-1"
                        aria-labelledby="externalContentModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="externalModalLabel">
                                        <?= Localization::translate('add_external_content'); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="externalContentForm"
                                        action="index.php?controller=VLRController&action=addOrEditExternalContent"
                                        method="POST" enctype="multipart/form-data">
                                        <input type="hidden" id="external_id" name="id">

                                        <!-- Title -->
                                        <div class="form-group">
                                            <label for="title"><?= Localization::translate('title'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                            <span class="text-danger error-message"></span>
                                        </div>

                                        <!-- Version & Mobile Support -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label
                                                        for="versionNumber"><?= Localization::translate('version_number'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="versionNumber"
                                                        name="version_number" required>
                                                    <span class="text-danger error-message"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('mobile_tablet_support'); ?></label>
                                                    <div class="d-flex mt-2">
                                                        <div class="form-check mr-3">
                                                            <input class="form-check-input" type="radio"
                                                                name="mobile_support" id="mobileYes" value="Yes">
                                                            <label class="form-check-label"
                                                                for="mobileYes"><?= Localization::translate('yes'); ?></label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="mobile_support" id="mobileNo" value="No" checked>
                                                            <label class="form-check-label"
                                                                for="mobileNo"><?= Localization::translate('no'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Language & Time Limit -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label
                                                        for="languageSupport"><?= Localization::translate('language_support'); ?></label>
                                                    <select class="form-control" id="languageSupport"
                                                        name="language_support">
                                                        <option value="English">
                                                            <?= Localization::translate('english'); ?>
                                                        </option>
                                                        <option value="Hindi"><?= Localization::translate('hindi'); ?>
                                                        </option>
                                                        <option value="Marathi">
                                                            <?= Localization::translate('marathi'); ?>
                                                        </option>
                                                        <option value="Spanish">
                                                            <?= Localization::translate('spanish'); ?>
                                                        </option>
                                                        <option value="French"><?= Localization::translate('french'); ?>
                                                        </option>
                                                        <option value="German"><?= Localization::translate('german'); ?>
                                                        </option>
                                                        <option value="Chinese">
                                                            <?= Localization::translate('chinese'); ?>
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="timeLimit"><?= Localization::translate('time_limit'); ?>
                                                        (<?= Localization::translate('minutes'); ?>)</label>
                                                    <input type="number" class="form-control" id="external_timeLimit"
                                                        name="time_limit">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="form-group">
                                            <label
                                                for="description"><?= Localization::translate('description'); ?></label>
                                            <textarea class="form-control" id="external_description" name="description"
                                                rows="3"></textarea>
                                        </div>

                                        <!-- Tags/Keywords -->
                                        <div class="form-group">
                                            <label
                                                for="externalTagInput"><?= Localization::translate('tags_keywords'); ?>
                                                <span class="text-danger">*</span></label>
                                            <div class="tag-input-container form-control">
                                                <span id="externalTagDisplay"></span>
                                                <input type="text" id="externalTagInput"
                                                    placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                            </div>
                                            <input type="hidden" name="tags" id="externalTagList">
                                            <span class="text-danger error-message" id="externalTagError"></span>
                                        </div>


                                        <!-- Content Type -->
                                        <div class="form-group">
                                            <label for="contentType"><?= Localization::translate('content_type'); ?>
                                                <span class="text-danger">*</span></label>
                                            <select class="form-control" id="contentType" name="content_type"
                                                onchange="showSelectedSection()" required>
                                                <option value=""><?= Localization::translate('select'); ?></option>
                                                <option value="youtube-vimeo">
                                                    <?= Localization::translate('youtube_vimeo'); ?>
                                                </option>
                                                <option value="linkedin-udemy">
                                                    <?= Localization::translate('linkedin_udemy'); ?>
                                                </option>
                                                <option value="web-links-blogs">
                                                    <?= Localization::translate('web_links_blogs'); ?>
                                                </option>
                                                <option value="podcasts-audio">
                                                    <?= Localization::translate('podcasts_audio'); ?>
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Dynamic Content Sections -->
                                        <!-- Dynamic Fields Section -->
                                        <div id="dynamicFields">
                                            <!-- YouTube/Vimeo Fields -->
                                            <div class="content-group" id="youtubeVimeoFields">
                                                <div class="form-group">
                                                    <label for="videoUrl"><?= Localization::translate('video_url'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="videoUrl"
                                                        name="video_url">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="thumbnail"><?= Localization::translate('thumbnail_preview'); ?></label>
                                                    <input type="file" class="form-control" id="thumbnail"
                                                        name="thumbnail" accept="image/*">
                                                    <img id="thumbnailPreview" src=""
                                                        alt="<?= Localization::translate('thumbnail_preview'); ?>"
                                                        style="display:none; max-width: 100px; margin-top: 10px;">
                                                    <div id="thumbnailFileLink" style="display:none;"></div>
                                                </div>
                                            </div>

                                            <!-- LinkedIn/Udemy Fields -->
                                            <div class="content-group" id="linkedinUdemyFields">
                                                <div class="form-group">
                                                    <label for="courseUrl"><?= Localization::translate('course_url'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="courseUrl"
                                                        name="course_url">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="platformName"><?= Localization::translate('platform_name'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="platformName" name="platform_name">
                                                        <option value=""><?= Localization::translate('select'); ?>
                                                        </option>
                                                        <option value="LinkedIn Learning">LinkedIn Learning</option>
                                                        <option value="Udemy">Udemy</option>
                                                        <option value="Coursera">Coursera</option>
                                                    </select>
                                                </div>
                                            </div>


                                            <!-- Web Links/Blogs Fields -->
                                            <div class="content-group" id="webLinksBlogsFields">
                                                <div class="form-group">
                                                    <label
                                                        for="articleUrl"><?= Localization::translate('article_url'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" id="articleUrl"
                                                        name="article_url">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="author"><?= Localization::translate('author_publisher'); ?></label>
                                                    <input type="text" class="form-control" id="author" name="author">
                                                </div>
                                            </div>

                                            <!-- Podcasts/Audio Fields -->
                                            <div class="content-group" id="podcastsAudioFields">
                                                <div class="form-group">
                                                    <label
                                                        for="audioSource"><?= Localization::translate('audio_source'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="audioSource" name="audio_source">
                                                        <option value="upload">
                                                            <?= Localization::translate('upload_file'); ?>
                                                        </option>
                                                        <option value="url"><?= Localization::translate('audio_url'); ?>
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="audioFile"><?= Localization::translate('upload_audio'); ?>
                                                        (MP3/WAV)</label>
                                                    <input type="file" class="form-control" id="audioFile"
                                                        name="audio_file" accept=".mp3, .wav">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="audioUrl"><?= Localization::translate('audio_url'); ?></label>
                                                    <input type="url" class="form-control" id="audioUrl"
                                                        name="audio_url">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="speaker"><?= Localization::translate('speaker_host'); ?></label>
                                                    <input type="text" class="form-control" id="speaker" name="speaker">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                            <button type="submit" id="submit_button"
                                                class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                            <button type="button" class="btn btn-danger" data-dismiss="modal"
                                                id="clearForm"><?= Localization::translate('cancel'); ?></button>
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
                        <a class="nav-link active" data-toggle="tab" href="#youtube-vimeo">
                            <?= Localization::translate('youtube_vimeo_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#linkedin-udemy">
                            <?= Localization::translate('linkedin_udemy_coursera_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#web-links-blogs">
                            <?= Localization::translate('web_links_blogs_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#podcasts-audio">
                            <?= Localization::translate('podcasts_audio_lessons_ul'); ?>
                        </a>
                    </li>
                </ul>


                <!-- ✅ External Content Sub-Tab Content -->
                <?php
                // Validate if $externalContent is set
                if (!isset($externalContent)) {
                    $externalContent = [];
                }

                // Define categories for External Content
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
                    // Define tab IDs and their localized titles
                    $contentCategories = [
                        'youtube-vimeo' => 'youtube_vimeo',
                        'linkedin-udemy' => 'linkedin_udemy_coursera',
                        'web-links-blogs' => 'web_links_blogs',
                        'podcasts-audio' => 'podcasts_audio_lessons'
                    ];

                    // Group External Content by category
                    $groupedExternalData = [
                        'youtube-vimeo' => [],
                        'linkedin-udemy' => [],
                        'web-links-blogs' => [],
                        'podcasts-audio' => []
                    ];

                    foreach ($externalContent as $content) {
                        $categoryKey = $content['content_type'] ?? null;
                        if ($categoryKey && isset($groupedExternalData[$categoryKey])) {
                            $groupedExternalData[$categoryKey][] = $content;
                        }
                    }

                    // Loop through categories and display data accordingly
                    foreach ($contentCategories as $key => $localizationKey): ?>
                        <div class="tab-pane <?= $key === 'youtube-vimeo' ? 'show active' : ''; ?>" id="<?= $key ?>">
                            <h4><?= Localization::translate($localizationKey) ?></h4>
                            <div class="row">
                                <?php if (!empty($groupedExternalData[$key])): ?>
                                    <?php foreach ($groupedExternalData[$key] as $content): ?>
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
                                                        <a href="#" class="edit-content"
                                                            data-content='<?= json_encode($content); ?>'>
                                                            <i class="fas fa-edit edit-icon"
                                                                title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="index.php?controller=VLRController&action=deleteExternal&id=<?= $content['id'] ?>"
                                                            onclick="return confirm('<?= Localization::translate('confirm_delete'); ?>');">
                                                            <i class="fas fa-trash-alt delete-icon"
                                                                title="<?= Localization::translate('delete'); ?>"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?= Localization::translate('no_external_content'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>



            <!-- ✅ Survey -->
            <div class="tab-pane" id="survey">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('survey'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Survey')">
                        + <?= Localization::translate('add'); ?>
                    </button>
                </div>
                <div id="survey-items"></div>
            </div>

            <!-- ✅ Feedback -->
            <div class="tab-pane" id="feedback">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('feedback'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Feedback')">
                        + <?= Localization::translate('add'); ?>
                    </button>
                </div>
                <div id="feedback-items"></div>
            </div>


            <!-- ✅ INTERACTIVE & AI POWERED CONTENT Tab Content -->
            <div class="tab-pane" id="interactive">
                <!-- Interactive & AI Powered Content Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('interactive_ai_content'); ?></h3>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal('Interactive')">
                        + <?= Localization::translate('add'); ?>
                    </button>
                </div>

                <!-- ✅ Interactive & AI Powered Content Sub-Tabs -->
                <ul class="nav nav-tabs" id="interactiveSubTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#adaptive-learning">
                            <?= Localization::translate('adaptive_learning'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#chatbots-virtual-assistants">
                            <?= Localization::translate('chatbots_virtual_assistants'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#ar-vr">
                            <?= Localization::translate('ar_vr'); ?>
                        </a>
                    </li>
                </ul>

                <!-- ✅ Interactive & AI Powered Content Sub-Tab Content -->
                <div class="tab-content mt-3">
                    <div class="tab-pane show active" id="adaptive-learning">
                        <h4><?= Localization::translate('adaptive_learning'); ?></h4>
                        <p><?= Localization::translate('adaptive_learning_desc'); ?></p>
                    </div>
                    <div class="tab-pane" id="chatbots-virtual-assistants">
                        <h4><?= Localization::translate('chatbots_virtual_assistants'); ?></h4>
                        <p><?= Localization::translate('chatbots_virtual_assistants_desc'); ?></p>
                    </div>
                    <div class="tab-pane" id="ar-vr">
                        <h4><?= Localization::translate('ar_vr'); ?></h4>
                        <p><?= Localization::translate('ar_vr_desc'); ?></p>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<!-- ✅ SCORM Validation Translations -->
<script>
    const translations = <?= json_encode([
        "validation.scorm_title_required" => Localization::translate('validation.scorm_title_required'),
        "validation.scorm_zip_required" => Localization::translate('validation.scorm_zip_required'),
        "validation.version_required" => Localization::translate('validation.version_required'),
        "validation.scorm_category_required" => Localization::translate('validation.scorm_category_required'),
        "scorm.modal.edit" => Localization::translate('scorm.modal.edit'),
        "scorm.modal.add" => Localization::translate('scorm.modal.add'),
        "validation.required.title" => Localization::translate('validation.required.title'),
        "validation.required.content_type" => Localization::translate('validation.required.content_type'),
        "validation.required.version" => Localization::translate('validation.required.version'),
        "validation.required.tags" => Localization::translate('validation.required.tags'),
        "validation.required.url" => Localization::translate('validation.required.url'),
        "validation.invalid.url" => Localization::translate('validation.invalid.url'),
        "error.form_not_found" => Localization::translate('error.form_not_found'),
        "error.submit_button_missing" => Localization::translate('error.submit_button_missing'),
        "validation.required.audio_file" => Localization::translate('validation.required.audio_file'),
        "validation.invalid.audio_file" => Localization::translate('validation.invalid.audio_file'),
        "validation.required.thumbnail" => Localization::translate('validation.required.thumbnail'),
        "validation.invalid.thumbnail" => Localization::translate('validation.invalid.thumbnail'),
        "validation.file_size_exceeded" => Localization::translate('validation.file_size_exceeded')
    ]); ?>;
</script>

<script src="public/js/scorm_validation.js"></script>
<script src="public/js/scorm_package.js"></script>
<script src="public/js/document_validation.js"></script>
<script src="public/js/document_package.js"></script>
<script src="public/js/external_content_validation.js"></script>
<script src="public/js/external_package.js"></script>
<?php include 'includes/footer.php'; ?>