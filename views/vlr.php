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
        <?php
        // Get the active tab from URL parameter
        $activeTab = $_GET['tab'] ?? 'scorm'; // Default to scorm if no tab parameter
        ?>
        <ul class="nav nav-tabs" id="vlrTabs">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'scorm' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#scorm">
                    <?= Localization::translate('scorm'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'non-scorm' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#non-scorm">
                    <?= Localization::translate('non_scorm'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'assessment' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#assessment">
                    <?= Localization::translate('assessment'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'audio' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#audio">
                    <?= Localization::translate('audio'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'video' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#video">
                    <?= Localization::translate('video'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'document' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#document">
                    <?= Localization::translate('document'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'image' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#image">
                    <?= Localization::translate('image'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'external' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#external">
                    <?= Localization::translate('external_content'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'survey' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#survey">
                    <?= Localization::translate('survey'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'feedback' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#feedback">
                    <?= Localization::translate('feedback'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'interactive' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#interactive">
                    <?= Localization::translate('interactive_ai_content'); ?>
                </a>
            </li>
        </ul>

        <!-- ✅ Tab Content Section -->
        <div class="tab-content mt-3">

            <!-- ✅ SCORM Package -->
            <div class="tab-pane <?= $activeTab === 'scorm' ? 'show active' : ''; ?>" id="scorm">
                <!-- SCORM Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('scorm'); ?></h3>

                    <!-- ✅ SCORM "Add" Button - Opens Modal -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addScormBtn"
                        data-bs-target="#scormModal">
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

                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="<?= Localization::translate('close'); ?>"></button>


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
                                                    <label for="zipFile" class="form-label">
                                                        <?= Localization::translate('upload_scorm_zip'); ?>
                                                        <span class="text-danger" id="zipRequired">*</span>
                                                        <span class="text-muted" id="zipOptional" style="display: none;">(Optional - leave empty to keep existing file)</span>
                                                    </label>
                                                    <input type="file" class="form-control" id="zipFile" name="zipFile" accept=".zip,.rar,.7z">
                                                    <small class="text-muted">Max size: 50MB. Formats: ZIP, RAR, 7Z files</small>
                                                    <div id="scormZipPreview" class="mt-2"></div>
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
                                                    <select class="form-control" id="language" name="language">
                                                        <option value=""><?= Localization::translate('select_language'); ?>
                                                        </option>
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
                                                            echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                        }
                                                        ?>
                                                    </select>
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#scorm-1.2">
                            <?= Localization::translate('scorm_1_2'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#scorm-2004">
                            <?= Localization::translate('scorm_2004'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tin-can-api">
                            <?= Localization::translate('tincan_api_xapi'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#cmi5">
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
                                                        <a href="#" class="delete-scorm" data-id="<?= $scorm['id'] ?>" data-title="<?= htmlspecialchars($scorm['title']) ?>">
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
            <div class="tab-pane <?= $activeTab === 'non-scorm' ? 'show active' : ''; ?>" id="non-scorm">


                <!-- Non-SCORM Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('non_scorm'); ?></h3>

                    <!-- ✅ Non-SCORM "Add" Button - Opens Modal -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addNonScormBtn"
                        data-bs-target="#nonScormModal">
                        + <?= Localization::translate('add_non_scorm'); ?>
                    </button>

                    <!-- ✅ NON-SCORM ADD MODAL -->
                    <div class="modal fade" id="nonScormModal" tabindex="-1" role="dialog"
                        aria-labelledby="nonScormModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="nonScormModalLabel">
                                        <?= Localization::translate('add_non_scorm_package'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="<?= Localization::translate('close'); ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="nonScormForm"
                                        action="index.php?controller=VLRController&action=addOrEditNonScormPackage"
                                        method="POST" enctype="multipart/form-data">
                                        <input type="hidden" id="non_scorm_id" name="non_scorm_id">
                                        <input type="hidden" id="existing_content_package" name="existing_content_package">
                                        <input type="hidden" id="existing_launch_file" name="existing_launch_file">
                                        <input type="hidden" id="existing_thumbnail_image" name="existing_thumbnail_image">
                                        <input type="hidden" id="existing_manifest_file" name="existing_manifest_file">

                                        <!-- ✅ Title & Content Type -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="non_scorm_title"><?= Localization::translate('nonscorm.field.title'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" id="non_scorm_title" name="non_scorm_title"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_content_type"><?= Localization::translate('nonscorm.field.content_type'); ?> <span class="text-danger">*</span></label>
                                                    <select id="nonscorm_content_type" name="content_type" class="form-control">
                                                        <option value=""><?= Localization::translate('nonscorm.placeholder.select_content_type'); ?></option>
                                                        <option value="html5"><?= Localization::translate('html5_content'); ?></option>
                                                        <option value="flash"><?= Localization::translate('flash_content'); ?></option>
                                                        <option value="unity"><?= Localization::translate('unity_content'); ?></option>
                                                        <option value="custom_web"><?= Localization::translate('custom_web_app'); ?></option>
                                                        <option value="mobile_app"><?= Localization::translate('mobile_app'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Version, Language -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_version"><?= Localization::translate('nonscorm.field.version'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" id="nonscorm_version" name="version" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_language"><?= Localization::translate('nonscorm.field.language'); ?></label>
                                                    <select class="form-control" id="nonscorm_language" name="language">
                                                        <option value=""><?= Localization::translate('select_language'); ?></option>
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
                                                            echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Description -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="nonscorm_description"><?= Localization::translate('nonscorm.field.description'); ?></label>
                                                    <textarea id="nonscorm_description" name="description" class="form-control"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="nonscormTags"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="nonscormTagDisplay"></span>
                                                        <input type="text" id="nonscormTagInput"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                                    </div>
                                                    <input type="hidden" name="tagList" id="nonscormTagList">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Content URL & Launch File -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_content_url" class="form-label"><?= Localization::translate('nonscorm.field.content_url'); ?> (https://example.com)</label>
                                                    <input type="text" id="nonscorm_content_url" name="content_url" class="form-control">
                                                    <small class="text-muted">Format: https://example.com or http://example.com</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="launch_file" class="form-label"><?= Localization::translate('nonscorm.field.launch_file'); ?></label>
                                                    <input type="file" id="launch_file" name="launch_file" class="form-control" accept=".html,.htm,.swf,.unity3d">
                                                    <small class="text-muted">Max size: 10MB. Formats: HTML, SWF, Unity3D files</small>
                                                    <div id="launchFilePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ File Uploads -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="content_package" class="form-label"><?= Localization::translate('nonscorm.field.content_package'); ?></label>
                                                    <input type="file" id="content_package" name="content_package" class="form-control" accept=".zip,.rar,.7z">
                                                    <small class="text-muted">Max size: 100MB. Formats: ZIP, RAR, 7Z files</small>
                                                    <div id="contentPackagePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_thumbnail_image" class="form-label"><?= Localization::translate('nonscorm.field.thumbnail_image'); ?></label>
                                                    <input type="file" id="nonscorm_thumbnail_image" name="thumbnail_image" class="form-control" accept="image/*">
                                                    <small class="text-muted">Max size: 10MB. Formats: All image types</small>
                                                    <div id="thumbnailImagePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Manifest File -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="manifest_file" class="form-label"><?= Localization::translate('nonscorm.field.manifest_file'); ?></label>
                                                    <input type="file" id="manifest_file" name="manifest_file" class="form-control" accept=".xml,.json,.txt">
                                                    <small class="text-muted">Max size: 10MB. Formats: XML, JSON, TXT files</small>
                                                    <div id="manifestFilePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Time Limit & Mobile Support -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nonscorm_timeLimit" class="form-label"><?= Localization::translate('nonscorm.field.time_limit'); ?></label>
                                                    <input type="number" id="nonscorm_timeLimit" name="timeLimit" class="form-control" min="0" step="1">
                                                    <small class="text-muted">Time limit in minutes (optional)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('nonscorm.field.mobile_support'); ?></label><br>
                                                    <label><input type="radio" name="nonscorm_mobileSupport" value="Yes">
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="nonscorm_mobileSupport" value="No" checked>
                                                        <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ HTML5 Specific Fields - All in One Row -->
                                        <div class="row html5-fields" style="display: none;">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="html5_framework" class="form-label"><?= Localization::translate('nonscorm.field.html5_framework'); ?></label>
                                                    <select id="html5_framework" name="html5_framework" class="form-control form-select">
                                                        <option value=""><?= Localization::translate('nonscorm.placeholder.select_framework'); ?></option>
                                                        <option value="react"><?= Localization::translate('nonscorm.framework.react'); ?></option>
                                                        <option value="angular"><?= Localization::translate('nonscorm.framework.angular'); ?></option>
                                                        <option value="vue"><?= Localization::translate('nonscorm.framework.vue'); ?></option>
                                                        <option value="vanilla"><?= Localization::translate('nonscorm.framework.vanilla'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label class="form-label"><?= Localization::translate('nonscorm.field.responsive_design'); ?></label>
                                                    <div class="mt-2">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_responsive_design" id="responsive_yes" value="Yes" checked>
                                                            <label class="form-check-label" for="responsive_yes"><?= Localization::translate('yes'); ?></label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_responsive_design" id="responsive_no" value="No">
                                                            <label class="form-check-label" for="responsive_no"><?= Localization::translate('no'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label class="form-label"><?= Localization::translate('nonscorm.field.offline_support'); ?></label>
                                                    <div class="mt-2">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_offline_support" id="offline_yes" value="Yes">
                                                            <label class="form-check-label" for="offline_yes"><?= Localization::translate('yes'); ?></label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_offline_support" id="offline_no" value="No" checked>
                                                            <label class="form-check-label" for="offline_no"><?= Localization::translate('no'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Flash Specific Fields - All in One Row -->
                                        <div class="row flash-fields" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="flash_version" class="form-label"><?= Localization::translate('nonscorm.field.flash_version'); ?></label>
                                                    <input type="text" id="flash_version" name="flash_version" class="form-control" placeholder="e.g., 11.2.0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="flash_security" class="form-label"><?= Localization::translate('nonscorm.field.flash_security'); ?></label>
                                                    <select id="flash_security" name="flash_security" class="form-control form-select">
                                                        <option value="Local"><?= Localization::translate('nonscorm.flash.local'); ?></option>
                                                        <option value="Network"><?= Localization::translate('nonscorm.flash.network'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Unity Specific Fields - All in One Row -->
                                        <div class="row unity-fields" style="display: none;">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="unity_version" class="form-label"><?= Localization::translate('nonscorm.field.unity_version'); ?></label>
                                                    <input type="text" id="unity_version" name="unity_version" class="form-control" placeholder="e.g., 2022.3.0f1">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="unity_platform" class="form-label"><?= Localization::translate('nonscorm.field.unity_platform'); ?></label>
                                                    <select id="unity_platform" name="unity_platform" class="form-control form-select">
                                                        <option value="WebGL"><?= Localization::translate('nonscorm.unity.webgl'); ?></option>
                                                        <option value="WebPlayer"><?= Localization::translate('nonscorm.unity.webplayer'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="unity_compression" class="form-label"><?= Localization::translate('nonscorm.field.unity_compression'); ?></label>
                                                    <select id="unity_compression" name="unity_compression" class="form-control form-select">
                                                        <option value="Gzip"><?= Localization::translate('nonscorm.compression.gzip'); ?></option>
                                                        <option value="Brotli"><?= Localization::translate('nonscorm.compression.brotli'); ?></option>
                                                        <option value="None"><?= Localization::translate('nonscorm.compression.none'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Custom Web App Fields - All in One Row -->
                                        <div class="row custom-web-fields" style="display: none;">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="web_technologies" class="form-label"><?= Localization::translate('nonscorm.field.web_technologies'); ?></label>
                                                    <textarea id="web_technologies" name="web_technologies" class="form-control" rows="2" placeholder="e.g., JavaScript, CSS3, WebGL"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="browser_requirements" class="form-label"><?= Localization::translate('nonscorm.field.browser_requirements'); ?></label>
                                                    <textarea id="browser_requirements" name="browser_requirements" class="form-control" rows="2" placeholder="e.g., Chrome 90+, Firefox 88+"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="external_dependencies" class="form-label"><?= Localization::translate('nonscorm.field.external_dependencies'); ?></label>
                                                    <textarea id="external_dependencies" name="external_dependencies" class="form-control" rows="2" placeholder="e.g., jQuery, Bootstrap"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Mobile App Fields - All in One Row -->
                                        <div class="row mobile-app-fields" style="display: none;">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="mobile_platform" class="form-label"><?= Localization::translate('nonscorm.field.mobile_platform'); ?></label>
                                                    <select id="mobile_platform" name="mobile_platform" class="form-control form-select">
                                                        <option value="Cross-Platform"><?= Localization::translate('nonscorm.mobile.cross_platform'); ?></option>
                                                        <option value="iOS"><?= Localization::translate('nonscorm.mobile.ios'); ?></option>
                                                        <option value="Android"><?= Localization::translate('nonscorm.mobile.android'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="app_store_url" class="form-label"><?= Localization::translate('nonscorm.field.app_store_url'); ?></label>
                                                    <input type="url" id="app_store_url" name="app_store_url" class="form-control" placeholder="https://apps.apple.com/...">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="minimum_os_version" class="form-label"><?= Localization::translate('nonscorm.field.minimum_os_version'); ?></label>
                                                    <input type="text" id="minimum_os_version" name="minimum_os_version" class="form-control" placeholder="e.g., iOS 14.0, Android 8.0">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Progress Tracking & Assessment Integration - All in One Row -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="form-label"><?= Localization::translate('nonscorm.field.progress_tracking'); ?></label>
                                                    <div class="mt-2">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_progress_tracking" id="progress_yes" value="Yes" checked>
                                                            <label class="form-check-label" for="progress_yes"><?= Localization::translate('yes'); ?></label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_progress_tracking" id="progress_no" value="No">
                                                            <label class="form-check-label" for="progress_no"><?= Localization::translate('no'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="form-label"><?= Localization::translate('nonscorm.field.assessment_integration'); ?></label>
                                                    <div class="mt-2">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_assessment_integration" id="assessment_yes" value="Yes">
                                                            <label class="form-check-label" for="assessment_yes"><?= Localization::translate('yes'); ?></label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="nonscorm_assessment_integration" id="assessment_no" value="No" checked>
                                                            <label class="form-check-label" for="assessment_no"><?= Localization::translate('no'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Completion Criteria & Scoring Method - All in One Row -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="completion_criteria" class="form-label"><?= Localization::translate('nonscorm.field.completion_criteria'); ?></label>
                                                    <textarea id="completion_criteria" name="completion_criteria" class="form-control" rows="2" placeholder="e.g., Complete all sections, Pass final quiz"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="scoring_method" class="form-label"><?= Localization::translate('nonscorm.field.scoring_method'); ?></label>
                                                    <select id="scoring_method" name="scoring_method" class="form-control form-select">
                                                        <option value="None"><?= Localization::translate('nonscorm.scoring.none'); ?></option>
                                                        <option value="Points"><?= Localization::translate('nonscorm.scoring.points'); ?></option>
                                                        <option value="Percentage"><?= Localization::translate('nonscorm.scoring.percentage'); ?></option>
                                                        <option value="Pass/Fail"><?= Localization::translate('nonscorm.scoring.pass_fail'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Submit & Cancel Buttons -->
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                            <button type="button" class="btn btn-danger" id="clearNonScormForm"><?= Localization::translate('cancel'); ?></button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Non-SCORM Sub-Tabs -->
                <ul class="nav nav-tabs" id="nonScormSubTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#html5-content" onclick="console.log('HTML5 tab clicked')">
                            <?= Localization::translate('html5_content'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#flash-content" onclick="console.log('Flash tab clicked')">
                            <?= Localization::translate('flash_content'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#unity-content" onclick="console.log('Unity tab clicked')">
                            <?= Localization::translate('unity_content'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#custom-web" onclick="console.log('Custom Web tab clicked')">
                            <?= Localization::translate('custom_web_app'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mobile-app" onclick="console.log('Mobile App tab clicked')">
                            <?= Localization::translate('mobile_app'); ?>
                        </a>
                    </li>
                </ul>

                <!-- ✅ Non-SCORM Sub-Tab Content -->
                <div class="tab-content mt-3">
                    <?php
                    // Validate if $nonScormPackages is set
                    if (!isset($nonScormPackages)) {
                        $nonScormPackages = [];
                    }

                    // Define tab IDs corresponding to content_type
                    $categories = [
                        'html5' => 'html5-content',
                        'flash' => 'flash-content',
                        'unity' => 'unity-content',
                        'custom_web' => 'custom-web',
                        'mobile_app' => 'mobile-app'
                    ];


                    // Define content for each tab
                    $tabContents = [
                        'html5-content' => [
                            'title' => 'HTML5 Content',
                            'description' => 'This section displays HTML5-based non-SCORM packages. HTML5 content includes interactive web-based learning materials that run directly in modern browsers without requiring additional plugins.',
                            'icon' => 'fab fa-html5',
                            'color' => '#e34c26'
                        ],
                        'flash-content' => [
                            'title' => 'Flash Content',
                            'description' => 'This section displays Flash-based non-SCORM packages. Flash content includes legacy interactive learning materials created with Adobe Flash technology.',
                            'icon' => 'fas fa-bolt',
                            'color' => '#cc0000'
                        ],
                        'unity-content' => [
                            'title' => 'Unity Content',
                            'description' => 'This section displays Unity-based non-SCORM packages. Unity content includes 3D interactive learning experiences and simulations built with Unity engine.',
                            'icon' => 'fab fa-unity',
                            'color' => '#000000'
                        ],
                        'custom-web' => [
                            'title' => 'Custom Web Content',
                            'description' => 'This section displays custom web-based non-SCORM packages. Custom web content includes specialized interactive learning materials built with various web technologies.',
                            'icon' => 'fas fa-globe',
                            'color' => '#007bff'
                        ],
                        'mobile-app' => [
                            'title' => 'Mobile App Content',
                            'description' => 'This section displays mobile application-based non-SCORM packages. Mobile app content includes learning materials designed specifically for mobile devices and tablets.',
                            'icon' => 'fas fa-mobile-alt',
                            'color' => '#28a745'
                        ]
                    ];

                    // Group packages by content type
                    $groupedPackages = [];
                    foreach ($nonScormPackages as $package) {
                        $contentType = $package['content_type'];
                        $categoryKey = $categories[$contentType] ?? null;
                        if ($categoryKey) {
                            $groupedPackages[$categoryKey][] = $package;
                        }
                    }

                    // Loop through categories and display content
                    foreach ($categories as $key => $tabId):
                        $content = $tabContents[$tabId] ?? [
                            'title' => ucfirst(str_replace('-', ' ', $tabId)),
                            'description' => 'Content for ' . $tabId,
                            'icon' => 'fas fa-code',
                            'color' => '#6a0dad'
                        ];

                        $packagesForTab = $groupedPackages[$tabId] ?? [];
                        ?>
                        <div class="tab-pane <?= $tabId === 'html5-content' ? 'show active' : ''; ?>" id="<?= $tabId ?>">
                            <h4><?= $content['title'] ?></h4>
                            <div class="row">
                                <?php if (!empty($packagesForTab)): ?>
                                    <?php foreach ($packagesForTab as $package): ?>
                                        <div class="col-md-4">
                                            <div class="nonscorm-card">
                                                <div class="card-body">
                                                    <div class="nonscorm-icon">
                                                        <i class="<?= $content['icon'] ?>" style="color: <?= $content['color'] ?>;"></i>
                                                    </div>
                                                    <?php
                                                    $displayTitle = strlen($package['title']) > 20 ? substr($package['title'], 0, 17) . '...' : $package['title'];
                                                    ?>
                                                    <h5 class="nonscorm-title" title="<?= htmlspecialchars($package['title']) ?>">
                                                        <?= htmlspecialchars($displayTitle) ?>
                                                    </h5>
                                                    <div class="nonscorm-actions">
                                                        <a href="#" class="edit-non-scorm" data-package='<?= json_encode($package); ?>'>
                                                            <i class="fas fa-edit edit-icon" title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="delete-non-scorm" data-id="<?= $package['id'] ?>" data-title="<?= htmlspecialchars($package['title']) ?>">
                                                            <i class="fas fa-trash-alt delete-icon" title="<?= Localization::translate('delete'); ?>"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?= Localization::translate('no_' . str_replace('-', '_', $tabId) . '_found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            
            <!-- ✅ Assessment -->
            <div class="tab-pane <?= $activeTab === 'assessment' ? 'show active' : ''; ?>" id="assessment">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('assessment'); ?></h3>
                    <div class="d-flex gap-2">
                        <!-- Add Assessment Button -->
                        <button class="btn btn-sm btn-primary" id="addAssessmentBtn" data-bs-toggle="modal"
                            data-bs-target="#assessment_assessmentModal">
                            + <?= Localization::translate('add_assessment'); ?>
                        </button>

                        <!-- ✅ Assessment Modal -->
                        <div class="modal fade" id="assessment_assessmentModal" tabindex="-1"
                            aria-labelledby="assessment_assessmentModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg"> <!-- WIDER modal -->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="assessment_assessmentModalLabel">
                                            <?= Localization::translate('assessment.modal.add_title'); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="<?= Localization::translate('close'); ?>"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="assessment_assessmentForm"
                                            action="index.php?controller=VLRController&action=addOrEditAssessment"
                                            method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="selected_question_ids"
                                                id="assessment_selectedQuestionIds">

                                            <!-- Assessment Title -->
                                            <div class="form-group mb-3">
                                                <label for="assessment_assessmentTitle" class="form-label">
                                                    <?= Localization::translate('assessment.field.title'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="assessment_assessmentTitle"
                                                    name="title">
                                            </div>

                                            <div class="row">
                                                <!-- Tags and Keywords -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_tags">
                                                            <?= Localization::translate('tags_keywords'); ?>
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="tag-input-container form-control">
                                                            <span id="assessment_tagDisplay"></span>
                                                            <input type="text" id="assessment_assessment_tagInput"
                                                                placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                                                class="form-control border-0">
                                                        </div>
                                                        <input type="hidden" id="assessment_tagList" name="tags">
                                                    </div>
                                                </div>

                                                <!-- Number of Attempts -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_numAttempts" class="form-label">
                                                            <?= Localization::translate('assessment.field.num_attempts'); ?>
                                                        </label>
                                                        <select class="form-control" id="assessment_numAttempts"
                                                            name="num_attempts">
                                                            <?php for ($i = 1; $i <= 100; $i++): ?>
                                                                <option value="<?= $i ?>"><?= $i ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Passing Percentage -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_passingPercentage" class="form-label">
                                                            <?= Localization::translate('assessment.field.passing_percentage'); ?>
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control"
                                                            id="assessment_passingPercentage" name="passing_percentage">
                                                    </div>
                                                </div>

                                                <!-- Time Limit -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_timeLimit" class="form-label">
                                                            <?= Localization::translate('assessment.field.time_limit'); ?>
                                                        </label>
                                                        <input type="text" class="form-control"
                                                            id="assessment_timeLimit" name="time_limit">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Negative Marking -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">
                                                            <?= Localization::translate('assessment.field.negative_marking'); ?>
                                                        </label><br>
                                                        <div>
                                                            <input type="radio" id="assessment_negativeMarkingNo"
                                                                name="assessment_negativeMarking" value="No" checked>
                                                            <?= Localization::translate('no'); ?>
                                                            <input type="radio" id="assessment_negativeMarkingYes"
                                                                name="assessment_negativeMarking" value="Yes">
                                                            <?= Localization::translate('yes'); ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Negative Marking Percentage -->
                                                <div class="col-md-6" id="assessment_negativeMarkingPercentageWrapper"
                                                    style="display: none;">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_negativeMarkingPercentage"
                                                            class="form-label">
                                                            <?= Localization::translate('assessment.field.negative_percentage'); ?>
                                                        </label>
                                                        <select class="form-control"
                                                            id="assessment_negativeMarkingPercentage"
                                                            name="negative_marking_percentage">
                                                            <option value="">
                                                                <?= Localization::translate('assessment.placeholder.select_negative_percentage'); ?>
                                                            </option>
                                                            <option value="25">25%</option>
                                                            <option value="50">50%</option>
                                                            <option value="75">75%</option>
                                                            <option value="100">100%</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Assessment Type -->
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">
                                                            <?= Localization::translate('assessment.field.type'); ?>
                                                        </label><br>
                                                        <div>
                                                            <input type="radio" id="assessment_assessmentTypeFixed"
                                                                name="assessment_assessmentType" value="Fixed" checked>
                                                            <?= Localization::translate('assessment.type.fixed'); ?>
                                                            <input type="radio" id="assessment_assessmentTypeDynamic"
                                                                name="assessment_assessmentType" value="Dynamic">
                                                            <?= Localization::translate('assessment.type.dynamic'); ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Number of Questions to Display -->
                                                <div class="col-md-6" id="assessment_numberOfQuestionsWrapper"
                                                    style="display: none;">
                                                    <div class="form-group mb-3">
                                                        <label for="assessment_numberOfQuestions" class="form-label">
                                                            <?= Localization::translate('assessment.field.num_questions_to_display'); ?>
                                                        </label>
                                                        <input type="text" class="form-control"
                                                            id="assessment_numberOfQuestions"
                                                            name="num_questions_to_display">
                                                        <input type="hidden" id="assessment_selectedQuestionCount"
                                                            name="selected_question_count" value="0">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Add Question Button -->
                                            <div class="form-group mb-3">
                                                <button type="button" class="btn btn-primary"
                                                    id="assessment_addQuestionBtn">
                                                    <?= Localization::translate('assessment.button.add_question'); ?>
                                                </button>
                                            </div>

                                            <!-- Selected Questions Grid -->
                                            <div class="table-responsive mt-3" id="assessment_selectedQuestionsWrapper"
                                                style="display: none;">
                                                <table class="table table-bordered table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th><?= Localization::translate('assessment.table.question_title'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('assessment.table.tags'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('assessment.table.marks'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('assessment.table.type'); ?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="assessment_selectedQuestionsBody">
                                                        <!-- JS will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Submit and Cancel Buttons -->
                                            <div class="form-group mb-3">
                                                <button type="submit" class="btn btn-success">
                                                    <?= Localization::translate('submit'); ?>
                                                </button>
                                                <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <?= Localization::translate('cancel'); ?>
                        </button> -->
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Question Selection Modal -->

                        <div class="modal fade" id="assessment_questionModal" tabindex="-1"
                            aria-labelledby="assessment_questionModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="assessment_questionModalLabel">
                                            <?= Localization::translate('select_questions'); ?>
                                        </h5>
                                        <button type="button" class="btn-close"
                                            aria-label="<?= Localization::translate('close'); ?>"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Filter Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <input type="text" id="assessment_questionSearch" class="form-control"
                                                    placeholder="<?= Localization::translate('search_questions'); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <select id="assessment_filterMarks" class="form-select">
                                                    <option value=""><?= Localization::translate('loading'); ?>...
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select id="assessment_filterType" class="form-select">
                                                    <option value=""><?= Localization::translate('loading'); ?>...
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <select id="assessment_showEntries" class="form-select">
                                                    <option value="10" selected>
                                                        <?= Localization::translate('show_10'); ?>
                                                    </option>
                                                    <option value="25"><?= Localization::translate('show_25'); ?>
                                                    </option>
                                                    <option value="50"><?= Localization::translate('show_50'); ?>
                                                    </option>
                                                    <option value="75"><?= Localization::translate('show_75'); ?>
                                                    </option>
                                                    <option value="100"><?= Localization::translate('show_100'); ?>
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-md-3 text-end">
                                                <button class="btn btn-outline-secondary me-2"
                                                    id="assessment_clearFiltersBtn">
                                                    <i class="bi bi-x-circle"></i>
                                                    <?= Localization::translate('clear_filters'); ?>
                                                </button>
                                                <button class="btn btn-outline-secondary" id="assessment_refreshBtn">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                    <?= Localization::translate('refresh'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Question Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><input type="checkbox" id="assessment_selectAllQuestions">
                                                        </th>
                                                        <th><?= Localization::translate('question_title'); ?></th>
                                                        <th><?= Localization::translate('tags_keywords'); ?></th>
                                                        <th><?= Localization::translate('marks'); ?></th>
                                                        <th><?= Localization::translate('type'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="assessment_questionTableBody">
                                                    <!-- JavaScript inserts rows here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination (Optional Placeholder) -->
                                        <nav>
                                            <ul class="pagination justify-content-center" id="assessment_pagination">
                                                <!-- JS can optionally update this if server-side paging is added -->
                                            </ul>
                                        </nav>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-success" id="assessment_loopQuestionsBtn">
                                            <?= Localization::translate('loop_selected_questions'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <a href="index.php?controller=QuestionController&action=index" class="btn btn-sm btn-primary">
                            + <?= Localization::translate('add_questions'); ?>
                        </a>
                    </div>
                </div>

                <?php
                // Validate if $assessmentPackages is set
                if (!isset($assessmentPackages)) {
                    $assessmentPackages = [];
                }
                ?>

                <!-- ✅ Assessment Display -->
                <div class="assessment-wrapper">
                    <div class="assessment-wrapper-border">
                        <div class="row">
                            <?php if (!empty($assessmentPackages)): ?>
                                <?php foreach ($assessmentPackages as $assessment): ?>
                                    <div class="col-md-4">
                                        <div class="assessment-card">
                                            <div class="card-body">
                                                <div class="assessment-icon">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <h5 class="assessment-title"
                                                    title="<?= htmlspecialchars($assessment['title']) ?>">
                                                    <?= htmlspecialchars(strlen($assessment['title']) > 20 ? substr($assessment['title'], 0, 17) . '...' : $assessment['title']) ?>
                                                </h5>
                                                <div class="assessment-actions">
                                                    <a href="#" class="edit-assessment"
                                                        data-assessment='<?= json_encode($assessment); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-assessment" data-id="<?= $assessment['id'] ?>" data-title="<?= htmlspecialchars($assessment['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_assessment_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>




            </div>



            <!-- ✅ Audio -->
            <div class="tab-pane <?= $activeTab === 'audio' ? 'show active' : ''; ?>" id="audio">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('audio'); ?></h3>

                    <!-- ✅ Audio "Add" Button -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addAudioBtn"
                        data-bs-target="#audioModal">
                        + <?= Localization::translate('add_audio'); ?>
                    </button>
                </div>

                <!-- ✅ Audio Modal -->
                <div class="modal fade" id="audioModal" tabindex="-1" aria-labelledby="audioModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form id="audioForm" action="index.php?controller=VLRController&action=addOrEditAudioPackage"
                            method="POST" enctype="multipart/form-data">
                            <input type="hidden" id="audio_idaudio" name="audio_idaudio">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="audioModalLabel">
                                        <?= Localization::translate('add_audio_package'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">
                                        <!-- Title -->
                                        <div class="col-md-6">
                                            <label for="audio_titleaudio" class="form-label"><?= Localization::translate('audio.field.title'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="audio_titleaudio"
                                                name="audio_titleaudio">
                                        </div>

                                        <!-- Upload Audio -->
                                        <div class="col-md-6">
                                            <label for="audioFileaudio" class="form-label"><?= Localization::translate('audio.upload_audio_file'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="audioFileaudio"
                                                name="audioFileaudio" accept="audio/*">
                                            <small class="text-muted">Max size: 10MB. Formats: All audio types.</small>
                                            <input type="hidden" id="existing_audioaudio" name="existing_audio">
                                            <div id="existingAudioDisplayaudio" class="mt-2"></div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label
                                                        for="tagsaudio"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="tagDisplayaudio"></span>
                                                        <input type="text" id="tagInputaudio"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                                    </div>
                                                    <input type="hidden" name="tagListaudio" id="tagListaudio">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="col-md-12">
                                            <label for="descriptionaudio" class="form-label"><?= Localization::translate('description'); ?></label>
                                            <textarea class="form-control" id="descriptionaudio" name="descriptionaudio"
                                                rows="3"></textarea>
                                        </div>

                                        <!-- Version -->
                                        <div class="col-md-4">
                                            <label for="versionaudio" class="form-label"><?= Localization::translate('version_number'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="versionaudio"
                                                name="versionaudio" min="0" step="any" pattern="\d*">
                                        </div>

                                        <!-- Language Support -->
                                        <div class="col-md-4">
                                            <label for="languageaudio" class="form-label"><?= Localization::translate('language'); ?></label>
                                            <select class="form-select" id="languageaudio" name="languageaudio">
                                                <option value=""><?= Localization::translate('select_language'); ?></option>
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
                                                    echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Time Limit -->
                                        <div class="col-md-4">
                                            <label for="timeLimitaudio" class="form-label"><?= Localization::translate('time_limit'); ?></label>
                                            <input type="number" class="form-control" id="timeLimitaudio"
                                                name="timeLimitaudio" min="1" pattern="\d*">
                                        </div>

                                        <!-- Mobile & Tablet Support -->
                                        <div class="col-md-12">
                                            <label class="form-label"><?= Localization::translate('mobile_tablet_support'); ?></label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportaudio" id="mobileYesaudio" value="Yes">
                                                    <label class="form-check-label" for="mobileYesaudio"><?= Localization::translate('yes'); ?></label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportaudio" id="mobileNoaudio" value="No" checked>
                                                    <label class="form-check-label" for="mobileNoaudio"><?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                    <button type="button" class="btn btn-danger"
                                        id="clearFormaudio"><?= Localization::translate('cancel'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ✅ Audio Display -->
                <div class="audio-wrapper mt-4">
                    <div class="audio-wrapper-border">
                        <div class="row">
                            <?php if (!empty($audioPackages)): ?>
                                <?php foreach ($audioPackages as $audio): ?>
                                    <div class="col-md-4">
                                        <div class="audio-card">
                                            <div class="card-body">
                                                <div class="audio-icon">
                                                    <i class="fas fa-music"></i>
                                                </div>
                                                <h5 class="audio-title" title="<?= htmlspecialchars($audio['title']) ?>">
                                                    <?= htmlspecialchars(strlen($audio['title']) > 20 ? substr($audio['title'], 0, 17) . '...' : $audio['title']) ?>
                                                </h5>
                                                <div class="audio-actions">
                                                    <a href="#" class="preview-audio" data-audio='<?= json_encode($audio); ?>'>
                                                        <i class="fas fa-eye preview-icon"
                                                            title="<?= Localization::translate('preview'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="edit-audio" data-audio='<?= json_encode($audio); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-audio" data-id="<?= $audio['id'] ?>" data-title="<?= htmlspecialchars($audio['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_audio_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ✅ Video -->
            <div class="tab-pane <?= $activeTab === 'video' ? 'show active' : ''; ?>" id="video">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('video'); ?></h3>
                    <!-- ✅ Video "Add" Button -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addVideoBtn"
                        data-bs-target="#videoModal">
                        + <?= Localization::translate('add_video'); ?>
                    </button>
                </div>
                <!-- ✅ Video Modal -->
                <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form id="videoForm" action="index.php?controller=VLRController&action=addOrEditVideoPackage"
                            method="POST" enctype="multipart/form-data">
                            <input type="hidden" id="video_idvideo" name="video_idvideo">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="videoModalLabel">
                                        <?= Localization::translate('add_video_package'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">
                                        <!-- Title -->
                                        <div class="col-md-6">
                                            <label for="video_titlevideo" class="form-label"><?= Localization::translate('video.field.title'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="video_titlevideo"
                                                name="video_titlevideo">
                                        </div>

                                        <!-- Upload Video -->
                                        <div class="col-md-6">
                                            <label for="videoFilevideo" class="form-label"><?= Localization::translate('video.upload_video_file'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="videoFilevideo"
                                                name="videoFilevideo" accept="video/*">
                                            <small class="text-muted">Max size: 500MB. Formats: All video types.</small>
                                            <input type="hidden" id="existing_videovideo" name="existing_video">
                                            <div id="existingVideoDisplayvideo" class="mt-2"></div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label
                                                        for="tagsvideo"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="tagDisplayvideo"></span>
                                                        <input type="text" id="tagInputvideo"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                                            name="tagInputvideo">
                                                    </div>
                                                    <input type="hidden" name="tagListvideo" id="tagListvideo">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="col-md-12">
                                            <label for="descriptionvideo" class="form-label"><?= Localization::translate('description'); ?></label>
                                            <textarea class="form-control" id="descriptionvideo" name="descriptionvideo"
                                                rows="3"></textarea>
                                        </div>

                                        <!-- Version -->
                                        <div class="col-md-4">
                                            <label for="versionvideo" class="form-label"><?= Localization::translate('version_number'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="versionvideo"
                                                name="versionvideo" min="0" step="any" pattern="\d*">
                                        </div>

                                        <!-- Language Support -->
                                        <div class="col-md-4">
                                            <label for="languagevideo" class="form-label"><?= Localization::translate('language'); ?></label>
                                            <select class="form-select" id="languagevideo" name="languagevideo">
                                                <option value=""><?= Localization::translate('select_language'); ?></option>
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
                                                    echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Time Limit -->
                                        <div class="col-md-4">
                                            <label for="timeLimitvideo" class="form-label"><?= Localization::translate('time_limit'); ?></label>
                                            <input type="number" class="form-control" id="timeLimitvideo"
                                                name="timeLimitvideo" min="1" pattern="\d*">
                                        </div>

                                        <!-- Mobile & Tablet Support -->
                                        <div class="col-md-12">
                                            <label class="form-label"><?= Localization::translate('mobile_tablet_support'); ?></label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportvideo" id="mobileYesvideo" value="Yes">
                                                    <label class="form-check-label" for="mobileYesvideo"><?= Localization::translate('yes'); ?></label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportvideo" id="mobileNovideo" value="No" checked>
                                                    <label class="form-check-label" for="mobileNovideo"><?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                    <button type="button" class="btn btn-danger"
                                        id="clearFormvideo"><?= Localization::translate('cancel'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ✅ Video Display -->
                <div class="video-wrapper mt-4">
                    <div class="video-wrapper-border">
                        <div class="row">
                            <?php if (!empty($videoPackages)): ?>
                                <?php foreach ($videoPackages as $video): ?>
                                    <div class="col-md-4">
                                        <div class="video-card">
                                            <div class="card-body">
                                                <div class="video-icon">
                                                    <i class="fas fa-video"></i>
                                                </div>
                                                <h5 class="video-title" title="<?= htmlspecialchars($video['title']) ?>">
                                                    <?= htmlspecialchars(strlen($video['title']) > 20 ? substr($video['title'], 0, 17) . '...' : $video['title']) ?>
                                                </h5>
                                                <div class="video-actions">
                                                    <a href="#" class="preview-video" data-video='<?= json_encode($video); ?>'>
                                                        <i class="fas fa-eye preview-icon"
                                                            title="<?= Localization::translate('preview'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="edit-video" data-video='<?= json_encode($video); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-video" data-id="<?= $video['id'] ?>" data-title="<?= htmlspecialchars($video['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_video_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>



            </div>


            <!-- ✅ DOCUMENTS Tab Content -->
            <div class="tab-pane <?= $activeTab === 'document' ? 'show active' : ''; ?>" id="document">
                <!-- Document Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('documents'); ?></h3>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal"
                        id="addDocumentBtn">
                        + <?= Localization::translate('add_document'); ?>
                    </button>

                    <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="documentModalLabel">
                                        <?= Localization::translate('document.modal.add'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>

                                </div>
                                <div class="modal-body">
                                    <form id="documentForm" method="POST"
                                        action="index.php?controller=VLRController&action=addOrEditDocument"
                                        enctype="multipart/form-data">
                                        <input type="hidden" id="documentId" name="documentId">
                                        <input type="hidden" id="existingDocumentWordExcelPpt"
                                            name="existingDocumentWordExcelPpt">
                                        <input type="hidden" id="existingDocumentEbookManual"
                                            name="existingDocumentEbookManual">
                                        <input type="hidden" id="existingDocumentResearch"
                                            name="existingDocumentResearch">

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="document_title" class="form-label">
                                                    <?= Localization::translate('title'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="document_title"
                                                    name="document_title">
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label for="documentCategory" class="form-label">
                                                    <?= Localization::translate('category'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" id="documentCategory"
                                                    name="documentCategory">
                                                    <option value=""><?= Localization::translate('select_category'); ?>
                                                    </option>
                                                    <option value="Word/Excel/PPT Files">
                                                        <?= Localization::translate('category.word_excel_ppt'); ?>
                                                    </option>
                                                    <option value="E-Book & Manual">
                                                        <?= Localization::translate('category.ebook_manual'); ?>
                                                    </option>
                                                    <option value="Research Paper & Case Studies">
                                                        <?= Localization::translate('category.research_paper'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div id="wordExcelPptFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileWordExcelPpt" class="form-label">
                                                    <?= Localization::translate('upload_file.word_excel_ppt'); ?>
                                                </label>
                                                <input type="file" class="form-control" id="documentFileWordExcelPpt"
                                                    name="documentFileWordExcelPpt" accept=".docx, .xlsx, .pptx, .pdf">
                                                <small class="text-muted">Max size: 50MB. Formats: DOCX, XLSX, PPTX, PDF.</small>
                                                <div id="existingDocumentWordExcelPptDisplay" class="mt-2"></div>
                                            </div>

                                            <div id="ebookManualFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileEbookManual" class="form-label">
                                                    <?= Localization::translate('upload_file.ebook_manual'); ?>
                                                </label>
                                                <input type="file" class="form-control" id="documentFileEbookManual"
                                                    name="documentFileEbookManual" accept=".pdf, .epub, .mobi">
                                                <small class="text-muted">Max size: 50MB. Formats: PDF, EPUB, MOBI.</small>
                                                <div id="existingDocumentEbookManualDisplay" class="mt-2"></div>
                                            </div>

                                            <div id="researchFields" class="col-md-12 form-group mb-3"
                                                style="display: none;">
                                                <label for="documentFileResearch" class="form-label">
                                                    <?= Localization::translate('upload_file.research'); ?>
                                                </label>
                                                <input type="file" class="form-control" id="documentFileResearch"
                                                    name="documentFileResearch" accept=".pdf, .docx">
                                                <small class="text-muted">Max size: 50MB. Formats: PDF, DOCX.</small>
                                                <div id="existingDocumentResearchDisplay" class="mt-2"></div>
                                            </div>
                                        </div>

                                        <div id="researchDetails" class="row" style="display: none;">
                                            <div class="col-md-6 form-group mb-3">
                                                <label
                                                    for="research_authors"><?= Localization::translate('authors'); ?></label>
                                                <input type="text" class="form-control" id="research_authors"
                                                    name="research_authors">
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label
                                                    for="research_publication_date"><?= Localization::translate('publication_date'); ?></label>
                                                <input type="date" class="form-control" id="research_publication_date"
                                                    name="research_publication_date">
                                            </div>

                                            <div class="col-md-12 form-group mb-3">
                                                <label
                                                    for="research_references"><?= Localization::translate('reference_links'); ?></label>
                                                <input type="text" class="form-control" id="research_references"
                                                    name="research_references">
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="description"
                                                class="form-label"><?= Localization::translate('description'); ?></label>
                                            <textarea class="form-control" id="document_description"
                                                name="description"></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="documentTags"><?= Localization::translate('tags_keywords'); ?>
                                                <span class="text-danger">*</span></label>
                                            <div class="tag-input-container form-control">
                                                <span id="documentTagDisplay"></span>
                                                <input type="text" id="documentTagInput"
                                                    placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                            </div>
                                            <input type="hidden" name="documentTagList" id="documentTagList">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="language"
                                                    class="form-label"><?= Localization::translate('language'); ?></label>
                                                <select class="form-control" id="document_language" name="language">
                                                    <option value=""><?= Localization::translate('select_language'); ?>
                                                    </option>
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
                                                        echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label
                                                    class="form-label"><?= Localization::translate('mobile_support'); ?></label>
                                                <div>
                                                    <input type="radio" id="mobile_yes" name="mobile_support"
                                                        value="Yes">
                                                    <?= Localization::translate('yes'); ?>
                                                    <input type="radio" id="mobile_no" name="mobile_support" value="No"
                                                        checked>
                                                    <?= Localization::translate('no'); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label for="doc_version" class="form-label">
                                                    <?= Localization::translate('version_number'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control" id="doc_version"
                                                    name="doc_version">
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label for="doc_time_limit"
                                                    class="form-label"><?= Localization::translate('time_limit'); ?>
                                                    (<?= Localization::translate('minutes'); ?>)</label>
                                                <input type="number" class="form-control" id="doc_time_limit"
                                                    name="doc_time_limit" min="1">
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="submit"
                                                class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                            <button type="button" class="btn btn-danger"
                                                id="cancelForm"><?= Localization::translate('cancel'); ?></button>
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#word-excel-ppt">
                            <?= Localization::translate('word_excel_ppt'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#ebook-manual">
                            <?= Localization::translate('ebook_manual'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#research-case-studies">
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
                                                        <a href="#" class="preview-document"
                                                            data-document='<?= json_encode($document); ?>'>
                                                            <i class="fas fa-eye preview-icon"
                                                                title="<?= Localization::translate('preview'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="edit-document"
                                                            data-document='<?= json_encode($document); ?>'>
                                                            <i class="fas fa-edit edit-icon"
                                                                title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="delete-document" data-id="<?= $document['id'] ?>" data-title="<?= htmlspecialchars($document['title']) ?>">
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
            <div class="tab-pane <?= $activeTab === 'image' ? 'show active' : ''; ?>" id="image">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('image'); ?></h3>
                    <!-- ✅ Image "Add" Button -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addImageBtn"
                        data-bs-target="#imageModal">
                        + <?= Localization::translate('add_image'); ?>
                    </button>
                </div>
                <!-- ✅ Image Modal -->
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form id="imageForm" action="index.php?controller=VLRController&action=addOrEditImagePackage"
                            method="POST" enctype="multipart/form-data">
                            <input type="hidden" id="image_idimage" name="image_idimage">

                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imageModalLabel">
                                        <?= Localization::translate('add_image_package'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">
                                        <!-- Title -->
                                        <div class="col-md-6">
                                            <label for="image_titleimage" class="form-label"><?= Localization::translate('image.field.title'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="image_titleimage"
                                                name="image_titleimage">
                                        </div>

                                        <!-- Upload Image -->
                                        <div class="col-md-6">
                                            <label for="imageFileimage" class="form-label"><?= Localization::translate('image.upload_image_file'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="imageFileimage"
                                                name="imageFileimage" accept="image/*">
                                            <small class="text-muted">Max size: 10MB. Formats: JPG, PNG, GIF,
                                                etc.</small>
                                            <input type="hidden" id="existing_imageimage" name="existing_image">
                                            <div id="existingImageDisplayimage" class="mt-2"></div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label
                                                        for="tagsimage"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="tagDisplayimage"></span>
                                                        <input type="text" id="tagInputimage"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                                            name="tagInputimage">
                                                    </div>
                                                    <input type="hidden" name="tagListimage" id="tagListimage">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="col-md-12">
                                            <label for="descriptionimage" class="form-label"><?= Localization::translate('description'); ?></label>
                                            <textarea class="form-control" id="descriptionimage" name="descriptionimage"
                                                rows="3"></textarea>
                                        </div>

                                        <!-- Version -->
                                        <div class="col-md-6">
                                            <label for="versionimage" class="form-label"><?= Localization::translate('version_number'); ?> <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="versionimage"
                                                name="versionimage" min="0" step="any" pattern="\d*">
                                        </div>

                                        <!-- Language Support -->
                                        <div class="col-md-6">
                                            <label for="languageimage" class="form-label"><?= Localization::translate('language'); ?></label>
                                            <select class="form-select" id="languageimage" name="languageimage">
                                                <option value=""><?= Localization::translate('select_language'); ?></option>
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
                                                    echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Mobile & Tablet Support -->
                                        <div class="col-md-12">
                                            <label class="form-label"><?= Localization::translate('mobile_tablet_support'); ?></label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportimage" id="mobileYesimage" value="Yes">
                                                    <label class="form-check-label" for="mobileYesimage"><?= Localization::translate('yes'); ?></label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="mobileSupportimage" id="mobileNoimage" value="No" checked>
                                                    <label class="form-check-label" for="mobileNoimage"><?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                    <button type="button" class="btn btn-danger"
                                        id="clearFormimage"><?= Localization::translate('cancel'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ✅ Image Display -->
                <div class="image-wrapper mt-4">
                    <div class="image-wrapper-border">
                        <div class="row">
                            <?php if (!empty($imagePackages)): ?>
                                <?php foreach ($imagePackages as $image): ?>
                                    <div class="col-md-4">
                                        <div class="image-card">
                                            <div class="card-body">
                                                <div class="image-icon">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                                <h5 class="image-title" title="<?= htmlspecialchars($image['title']) ?>">
                                                    <?= htmlspecialchars(strlen($image['title']) > 20 ? substr($image['title'], 0, 17) . '...' : $image['title']) ?>
                                                </h5>
                                                <div class="image-actions">
                                                    <a href="#" class="preview-image" data-image='<?= json_encode($image); ?>'>
                                                        <i class="fas fa-eye preview-icon"
                                                            title="<?= Localization::translate('preview'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="edit-image" data-image='<?= json_encode($image); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-image" data-id="<?= $image['id'] ?>" data-title="<?= htmlspecialchars($image['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_image_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>




            <!-- ✅ EXTERNAL CONTENT Tab Content -->
            <div class="tab-pane <?= $activeTab === 'external' ? 'show active' : ''; ?>" id="external">
                <!-- External Content Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('external_content'); ?></h3>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#externalContentModal">
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
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
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
                                            <input type="text" class="form-control" id="title" name="title">
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
                                                        name="version_number">
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
                                                        <option value=""><?= Localization::translate('select_language'); ?>
                                                        </option>
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
                                                            echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                        }
                                                        ?>
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
                                                onchange="showSelectedSection()">
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
                                                        (MP3/WAV) <span class="text-danger">*</span></label>
                                                    <input type="file" class="form-control" id="audioFile"
                                                        name="audio_file" accept=".mp3, .wav">
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="audioUrl"><?= Localization::translate('audio_url'); ?> <span class="text-danger">*</span></label>
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
                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#youtube-vimeo">
                            <?= Localization::translate('youtube_vimeo_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#linkedin-udemy">
                            <?= Localization::translate('linkedin_udemy_coursera_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#web-links-blogs">
                            <?= Localization::translate('web_links_blogs_ul'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#podcasts-audio">
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
                                                        <a href="#" class="preview-external"
                                                            data-content='<?= json_encode($content); ?>'>
                                                            <i class="fas fa-eye preview-icon"
                                                                title="<?= Localization::translate('preview'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="edit-content"
                                                            data-content='<?= json_encode($content); ?>'>
                                                            <i class="fas fa-edit edit-icon"
                                                                title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="delete-external" data-id="<?= $content['id'] ?>" data-title="<?= htmlspecialchars($content['title']) ?>">
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
            <div class="tab-pane <?= $activeTab === 'survey' ? 'show active' : ''; ?>" id="survey">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('survey'); ?></h3>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" id="addSurveyBtn" data-bs-toggle="modal"
                            data-bs-target="#survey_surveyModal">
                            + <?= Localization::translate('add_survey'); ?>
                        </button>

                        <!-- ✅ Survey Modal -->
                        <div class="modal fade" id="survey_surveyModal" tabindex="-1"
                            aria-labelledby="survey_surveyModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg"> <!-- WIDER modal -->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="survey_surveyModalLabel">
                                            <?= Localization::translate('survey.modal.add_title'); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="<?= Localization::translate('close'); ?>"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="survey_surveyForm"
                                            action="index.php?controller=VLRController&action=addOrEditSurvey"
                                            method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="selected_survey_question_ids"
                                                id="survey_selectedSurveyQuestionIds">


                                            <!-- Survey Title -->
                                            <div class="form-group mb-3">
                                                <label for="survey_surveyTitle" class="form-label">
                                                    <?= Localization::translate('survey.field.title'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="survey_surveyTitle"
                                                    name="title">
                                            </div>

                                            <!-- Tags/Keywords -->
                                            <div class="form-group mb-3">
                                                <label for="survey_tags">
                                                    <?= Localization::translate('tags_keywords'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <div class="tag-input-container form-control">
                                                    <span id="survey_tagDisplay"></span>
                                                    <input type="text" id="survey_survey_tagInput"
                                                        placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                                        class="form-control border-0">
                                                </div>
                                                <input type="hidden" id="survey_tagList" name="tags">
                                            </div>

                                            <!-- Add Survey Question Button -->
                                            <div class="form-group mb-3">
                                                <button type="button" class="btn btn-primary"
                                                    id="survey_addSurveyQuestionBtn">
                                                    <?= Localization::translate('survey.button.add_question'); ?>
                                                </button>
                                            </div>

                                            <!-- Selected Questions Grid -->
                                            <div class="table-responsive mt-3"
                                                id="survey_selectedSurveyQuestionsWrapper" style="display: none;">


                                                <input type="hidden" name="survey_selectedQuestionCount"
                                                id="survey_selectedQuestionCount">

                                                <input type="hidden" name="survey_selectedQuestionIds"
                                                id="survey_selectedQuestionIds">

                                                <table class="table table-bordered table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th><?= Localization::translate('survey.table.question_title'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('survey.table.tags'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('survey.table.type'); ?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="survey_selectedQuestionsBody">
                                                        <!-- JS will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Submit and Cancel Buttons -->
                                            <div class="form-group mb-3">
                                                <button type="submit" class="btn btn-success">
                                                    <?= Localization::translate('submit'); ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Survey Question Selection Modal -->
                        <div class="modal fade" id="survey_questionModal" tabindex="-1"
                            aria-labelledby="survey_questionModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="survey_questionModalLabel">
                                            <?= Localization::translate('survey.select_questions'); ?>
                                        </h5>
                                        <button type="button" class="btn-close"
                                            aria-label="<?= Localization::translate('close'); ?>"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Filter Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <input type="text" id="survey_questionSearch" class="form-control"
                                                    placeholder="<?= Localization::translate('search_questions'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <select id="survey_filterType" class="form-select">
                                                    <option value=""><?= Localization::translate('loading'); ?>...
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select id="survey_showEntries" class="form-select">
                                                    <option value="10" selected>
                                                        <?= Localization::translate('show_10'); ?>
                                                    </option>
                                                    <option value="25"><?= Localization::translate('show_25'); ?>
                                                    </option>
                                                    <option value="50"><?= Localization::translate('show_50'); ?>
                                                    </option>
                                                    <option value="75"><?= Localization::translate('show_75'); ?>
                                                    </option>
                                                    <option value="100"><?= Localization::translate('show_100'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <button class="btn btn-outline-secondary me-2"
                                                    id="survey_clearFiltersBtn">
                                                    <i class="bi bi-x-circle"></i>
                                                    <?= Localization::translate('clear_filters'); ?>
                                                </button>
                                                <button class="btn btn-outline-secondary" id="survey_refreshBtn">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                    <?= Localization::translate('refresh'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Survey Question Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><input type="checkbox" id="survey_selectAllQuestions"></th>
                                                        <th><?= Localization::translate('survey.question_title'); ?>
                                                        </th>
                                                        <th><?= Localization::translate('tags_keywords'); ?></th>
                                                        <th><?= Localization::translate('type'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="survey_questionTableBody">
                                                    <!-- JavaScript inserts rows here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <nav>
                                            <ul class="pagination justify-content-center" id="survey_pagination">
                                                <!-- JS inserts pagination -->
                                            </ul>
                                        </nav>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-success" id="survey_loopQuestionsBtn">
                                            <?= Localization::translate('loop_selected_questions'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <a href="index.php?controller=SurveyQuestionController&action=index"
                            class="btn btn-sm btn-primary">
                            + <?= Localization::translate('add_survey_questions'); ?>
                        </a>
                    </div>
                </div>

                <!-- ✅ Survey Display -->
                <div class="survey-wrapper">
                    <div class="survey-wrapper-border">
                        <div class="row">
                            <?php if (!empty($surveyPackages)): ?>
                                <?php foreach ($surveyPackages as $survey): ?>
                                    <div class="col-md-4">
                                        <div class="survey-card">
                                            <div class="card-body">
                                                <div class="survey-icon">
                                                    <i class="fas fa-poll"></i>
                                                </div>
                                                <h5 class="survey-title"
                                                    title="<?= htmlspecialchars($survey['title']) ?>">
                                                    <?= htmlspecialchars(strlen($survey['title']) > 20 ? substr($survey['title'], 0, 17) . '...' : $survey['title']) ?>
                                                </h5>
                                                <div class="survey-actions">
                                                    <a href="#" class="edit-survey"
                                                        data-survey='<?= json_encode($survey); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-survey" data-id="<?= $survey['id'] ?>" data-title="<?= htmlspecialchars($survey['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_surveys_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ✅ Feedback -->
            <!-- ✅ Feedback -->
            <div class="tab-pane <?= $activeTab === 'feedback' ? 'show active' : ''; ?>" id="feedback">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= Localization::translate('feedback'); ?></h3>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" id="addFeedbackBtn" data-bs-toggle="modal"
                            data-bs-target="#feedback_feedbackModal">
                            + <?= Localization::translate('add_feedback'); ?>
                        </button>

                        <!-- ✅ Feedback Modal -->
                        <div class="modal fade" id="feedback_feedbackModal" tabindex="-1"
                            aria-labelledby="feedback_feedbackModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="feedback_feedbackModalLabel">
                                            <?= Localization::translate('feedback.modal.add_title'); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="<?= Localization::translate('close'); ?>"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="feedback_feedbackForm"
                                            action="index.php?controller=VLRController&action=addOrEditFeedback"
                                            method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="selected_feedback_question_ids"
                                                id="feedback_selectedFeedbackQuestionIds">

                                            <!-- Feedback Title -->
                                            <div class="form-group mb-3">
                                                <label for="feedback_feedbackTitle" class="form-label">
                                                    <?= Localization::translate('feedback.field.title'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="feedback_feedbackTitle"
                                                    name="title">
                                            </div>

                                            <!-- Tags/Keywords -->
                                            <div class="form-group mb-3">
                                                <label for="feedback_tags">
                                                    <?= Localization::translate('tags_keywords'); ?> <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <div class="tag-input-container form-control">
                                                    <span id="feedback_tagDisplay"></span>
                                                    <input type="text" id="feedback_feedback_tagInput"
                                                        placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                                        class="form-control border-0">
                                                </div>
                                                <input type="hidden" id="feedback_tagList" name="feedbackTagList">
                                            </div>

                                            <!-- Add Feedback Question Button -->
                                            <div class="form-group mb-3">
                                                <button type="button" class="btn btn-primary"
                                                    id="feedback_addFeedbackQuestionBtn">
                                                    <?= Localization::translate('feedback.button.add_question'); ?>
                                                </button>
                                            </div>

                                            <!-- Selected Questions Grid -->
                                            <div class="table-responsive mt-3"
                                                id="feedback_selectedFeedbackQuestionsWrapper" style="display: none;">

                                                <input type="hidden" name="feedback_selectedQuestionCount"
                                                id="feedback_selectedQuestionCount">

                                                <input type="hidden" name="feedback_selectedQuestionIds"
                                                id="feedback_selectedQuestionIds">
                                                <table class="table table-bordered table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th><?= Localization::translate('feedback.table.question_title'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('feedback.table.tags'); ?>
                                                            </th>
                                                            <th><?= Localization::translate('feedback.table.type'); ?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="feedback_selectedQuestionsBody">
                                                        <!-- JS will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Submit and Cancel Buttons -->
                                            <div class="form-group mb-3">
                                                <button type="submit" class="btn btn-success">
                                                    <?= Localization::translate('submit'); ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feedback Question Selection Modal -->
                        <div class="modal fade" id="feedback_questionModal" tabindex="-1"
                            aria-labelledby="feedback_questionModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="feedback_questionModalLabel">
                                            <?= Localization::translate('feedback.select_questions'); ?>
                                        </h5>
                                        <button type="button" class="btn-close"
                                            aria-label="<?= Localization::translate('close'); ?>"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Filter Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <input type="text" id="feedback_questionSearch" class="form-control"
                                                    placeholder="<?= Localization::translate('search_questions'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <select id="feedback_filterType" class="form-select">
                                                    <option value=""><?= Localization::translate('loading'); ?>...
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select id="feedback_showEntries" class="form-select">
                                                    <option value="10" selected>
                                                        <?= Localization::translate('show_10'); ?></option>
                                                    <option value="25"><?= Localization::translate('show_25'); ?>
                                                    </option>
                                                    <option value="50"><?= Localization::translate('show_50'); ?>
                                                    </option>
                                                    <option value="75"><?= Localization::translate('show_75'); ?>
                                                    </option>
                                                    <option value="100"><?= Localization::translate('show_100'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <button class="btn btn-outline-secondary me-2"
                                                    id="feedback_clearFiltersBtn">
                                                    <i class="bi bi-x-circle"></i>
                                                    <?= Localization::translate('clear_filters'); ?>
                                                </button>
                                                <button class="btn btn-outline-secondary" id="feedback_refreshBtn">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                    <?= Localization::translate('refresh'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Feedback Question Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><input type="checkbox" id="feedback_selectAllQuestions">
                                                        </th>
                                                        <th><?= Localization::translate('feedback.question_title'); ?>
                                                        </th>
                                                        <th><?= Localization::translate('tags_keywords'); ?></th>
                                                        <th><?= Localization::translate('type'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="feedback_questionTableBody">
                                                    <!-- JavaScript inserts rows here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <nav>
                                            <ul class="pagination justify-content-center" id="feedback_pagination">
                                                <!-- JS inserts pagination -->
                                            </ul>
                                        </nav>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-success" id="feedback_loopQuestionsBtn">
                                            <?= Localization::translate('loop_selected_questions'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="index.php?controller=FeedbackQuestionController&action=index"
                            class="btn btn-sm btn-primary">
                            + <?= Localization::translate('add_feedback_questions'); ?>
                        </a>
                    </div>
                </div>

                <!-- ✅ Feedback Display -->
                <div class="feedback-wrapper">
                    <div class="feedback-wrapper-border">
                        <div class="row">
                            <?php if (!empty($feedbackPackages)): ?>
                                <?php foreach ($feedbackPackages as $feedback): ?>
                                    <div class="col-md-4">
                                        <div class="feedback-card">
                                            <div class="card-body">
                                                <div class="feedback-icon">
                                                    <i class="fas fa-comments"></i>
                                                </div>
                                                <h5 class="feedback-title"
                                                    title="<?= htmlspecialchars($feedback['title']) ?>">
                                                    <?= htmlspecialchars(strlen($feedback['title']) > 20 ? substr($feedback['title'], 0, 17) . '...' : $feedback['title']) ?>
                                                </h5>
                                                <div class="feedback-actions">
                                                    <a href="#" class="edit-feedback"
                                                        data-feedback='<?= json_encode($feedback); ?>'>
                                                        <i class="fas fa-edit edit-icon"
                                                            title="<?= Localization::translate('edit'); ?>"></i>
                                                    </a>
                                                    <a href="#" class="delete-feedback" data-id="<?= $feedback['id'] ?>" data-title="<?= htmlspecialchars($feedback['title']) ?>">
                                                        <i class="fas fa-trash-alt delete-icon"
                                                            title="<?= Localization::translate('delete'); ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= Localization::translate('no_feedback_found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>



            <!-- ✅ INTERACTIVE & AI POWERED CONTENT Tab Content -->
            <div class="tab-pane <?= $activeTab === 'interactive' ? 'show active' : ''; ?>" id="interactive">
                <!-- Interactive & AI Powered Content Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><?= Localization::translate('interactive_ai_content'); ?></h3>

                    <!-- ✅ Interactive "Add" Button - Opens Modal -->
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" id="addInteractiveBtn"
                        data-bs-target="#interactiveModal">
                        + <?= Localization::translate('add_interactive_package'); ?>
                    </button>

                    <!-- ✅ INTERACTIVE ADD MODAL -->
                    <div class="modal fade" id="interactiveModal" tabindex="-1" role="dialog"
                        aria-labelledby="interactiveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="interactiveModalLabel">
                                        <?= Localization::translate('add_interactive_package'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="<?= Localization::translate('close'); ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="interactiveForm"
                                        action="index.php?controller=VLRController&action=addOrEditInteractiveContent"
                                        method="POST" enctype="multipart/form-data">
                                        <input type="hidden" id="interactive_id" name="interactive_id">
                                        <input type="hidden" id="existing_content_file" name="existing_content_file">
                                        <input type="hidden" id="existing_interactive_thumbnail_image" name="existing_thumbnail_image">
                                        <input type="hidden" id="existing_metadata_file" name="existing_metadata_file">

                                        <!-- ✅ Title & Content Type -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="interactive_title"><?= Localization::translate('interactive.field.title'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" id="interactive_title" name="interactive_title"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="content_type"><?= Localization::translate('interactive.field.content_type'); ?> <span class="text-danger">*</span></label>
                                                    <select id="content_type" name="content_type" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_content_type'); ?></option>
                                                        <option value="adaptive_learning"><?= Localization::translate('adaptive_learning'); ?></option>
                                                        <option value="ai_tutoring"><?= Localization::translate('interactive_ai_tutoring'); ?></option>
                                                        <option value="ar_vr"><?= Localization::translate('ar_vr'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Version, Language, Difficulty Level -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="interactive_version"><?= Localization::translate('interactive.field.version'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" id="interactive_version" name="version" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="interactive_language"><?= Localization::translate('interactive.field.language'); ?></label>
                                                    <select class="form-control" id="interactive_language" name="language">
                                                        <option value=""><?= Localization::translate('select_language'); ?></option>
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
                                                            echo '<option value="">' . Localization::translate('no_languages_available') . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="difficulty_level"><?= Localization::translate('interactive.field.difficulty_level'); ?></label>
                                                    <select id="difficulty_level" name="difficulty_level" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_difficulty'); ?></option>
                                                        <option value="Beginner"><?= Localization::translate('interactive.difficulty.beginner'); ?></option>
                                                        <option value="Intermediate"><?= Localization::translate('interactive.difficulty.intermediate'); ?></option>
                                                        <option value="Advanced"><?= Localization::translate('interactive.difficulty.advanced'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Description -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="interactive_description"><?= Localization::translate('interactive.field.description'); ?></label>
                                                    <textarea id="interactive_description" name="description" class="form-control"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="tags"><?= Localization::translate('tags_keywords'); ?> <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="interactiveTagDisplay"></span>
                                                        <input type="text" id="interactiveTagInput"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                                    </div>
                                                    <input type="hidden" name="tagList" id="interactiveTagList">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Content URL & Embed Code -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="content_url" class="form-label"><?= Localization::translate('interactive.field.content_url'); ?> (https://example.com)</label>
                                                    <input type="text" id="content_url" name="content_url" class="form-control">
                                                    <small class="text-muted">Format: https://example.com or http://example.com</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="embed_code"><?= Localization::translate('interactive.field.embed_code'); ?></label>
                                                    <textarea id="embed_code" name="embed_code" class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ File Uploads -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="content_file" class="form-label"><?= Localization::translate('interactive.field.content_file'); ?></label>
                                                    <input type="file" class="form-control" id="content_file" name="content_file">
                                                    <small class="text-muted">Max size: 50MB. Formats: HTML5, Unity, ZIP, etc.</small>
                                                    <div id="contentFilePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="interactive_thumbnail_image" class="form-label"><?= Localization::translate('interactive.field.thumbnail_image'); ?></label>
                                                    <input type="file" class="form-control" id="interactive_thumbnail_image" name="thumbnail_image" accept="image/*">
                                                    <small class="text-muted">Max size: 10MB. Formats: JPG, PNG, GIF.</small>
                                                    <div id="interactiveThumbnailImagePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="metadata_file" class="form-label">Metadata File</label>
                                                    <input type="file" class="form-control" id="metadata_file" name="metadata_file">
                                                    <small class="text-muted">Max size: 5MB. Formats: JSON, XML, TXT.</small>
                                                    <div id="metadataFilePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ AI Model & Interaction Type (for AI Tutoring) -->
                                        <div class="row ai-tutoring-fields" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="ai_model"><?= Localization::translate('interactive.field.ai_model'); ?></label>
                                                    <select id="ai_model" name="ai_model" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_ai_model'); ?></option>
                                                        <option value="gpt4"><?= Localization::translate('interactive.ai_model.gpt4'); ?></option>
                                                        <option value="claude"><?= Localization::translate('interactive.ai_model.claude'); ?></option>
                                                        <option value="gemini"><?= Localization::translate('interactive.ai_model.gemini'); ?></option>
                                                        <option value="custom"><?= Localization::translate('interactive.ai_model.custom'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="interaction_type"><?= Localization::translate('interactive.field.interaction_type'); ?></label>
                                                    <select id="interaction_type" name="interaction_type" class="form-control">
                                                        <option value=""><?= Localization::translate('select'); ?></option>
                                                        <option value="chat"><?= Localization::translate('interactive.interaction_type.chat'); ?></option>
                                                        <option value="simulation"><?= Localization::translate('interactive.interaction_type.simulation'); ?></option>
                                                        <option value="game"><?= Localization::translate('interactive.interaction_type.game'); ?></option>
                                                        <option value="quiz"><?= Localization::translate('interactive.interaction_type.quiz'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ AR/VR Platform Fields (for AR/VR) -->
                                        <div class="row ar-vr-fields" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="vr_platform"><?= Localization::translate('interactive.field.vr_platform'); ?></label>
                                                    <select id="vr_platform" name="vr_platform" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_vr_platform'); ?></option>
                                                        <option value="oculus"><?= Localization::translate('interactive.vr_platform.oculus'); ?></option>
                                                        <option value="htc_vive"><?= Localization::translate('interactive.vr_platform.htc_vive'); ?></option>
                                                        <option value="playstation_vr"><?= Localization::translate('interactive.vr_platform.playstation_vr'); ?></option>
                                                        <option value="web_vr"><?= Localization::translate('interactive.vr_platform.web_vr'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="ar_platform"><?= Localization::translate('interactive.field.ar_platform'); ?></label>
                                                    <select id="ar_platform" name="ar_platform" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_ar_platform'); ?></option>
                                                        <option value="arcore"><?= Localization::translate('interactive.ar_platform.arcore'); ?></option>
                                                        <option value="arkit"><?= Localization::translate('interactive.ar_platform.arkit'); ?></option>
                                                        <option value="web_ar"><?= Localization::translate('interactive.ar_platform.web_ar'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ AI Tutoring Specific Fields -->
                                        <div class="row ai-tutoring-fields" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tutor_personality"><?= Localization::translate('interactive.field.tutor_personality'); ?></label>
                                                    <select id="tutor_personality" name="tutor_personality" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_tutor_personality'); ?></option>
                                                        <option value="friendly"><?= Localization::translate('interactive.tutor_personality.friendly'); ?></option>
                                                        <option value="professional"><?= Localization::translate('interactive.tutor_personality.professional'); ?></option>
                                                        <option value="encouraging"><?= Localization::translate('interactive.tutor_personality.encouraging'); ?></option>
                                                        <option value="strict"><?= Localization::translate('interactive.tutor_personality.strict'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="response_style"><?= Localization::translate('interactive.field.response_style'); ?></label>
                                                    <select id="response_style" name="response_style" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_response_style'); ?></option>
                                                        <option value="formal"><?= Localization::translate('interactive.response_style.formal'); ?></option>
                                                        <option value="casual"><?= Localization::translate('interactive.response_style.casual'); ?></option>
                                                        <option value="conversational"><?= Localization::translate('interactive.response_style.conversational'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Adaptive Learning Specific Fields -->
                                        <div class="row adaptive-learning-fields" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="adaptation_algorithm"><?= Localization::translate('interactive.field.adaptation_algorithm'); ?></label>
                                                    <select id="adaptation_algorithm" name="adaptation_algorithm" class="form-control">
                                                        <option value=""><?= Localization::translate('interactive.placeholder.select_adaptation_algorithm'); ?></option>
                                                        <option value="bayesian"><?= Localization::translate('interactive.adaptation_algorithm.bayesian'); ?></option>
                                                        <option value="irt"><?= Localization::translate('interactive.adaptation_algorithm.irt'); ?></option>
                                                        <option value="ml"><?= Localization::translate('interactive.adaptation_algorithm.ml'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('interactive.field.assessment_integration'); ?></label><br>
                                                    <label><input type="radio" name="interactive_assessment_integration" value="Yes">
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="interactive_assessment_integration" value="No" checked>
                                                        <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Learning Objectives & Prerequisites -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="learning_objectives"><?= Localization::translate('interactive.field.learning_objectives'); ?></label>
                                                    <textarea id="learning_objectives" name="learning_objectives" class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="prerequisites"><?= Localization::translate('interactive.field.prerequisites'); ?></label>
                                                    <textarea id="prerequisites" name="prerequisites" class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Knowledge Domain & Device Requirements -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="knowledge_domain"><?= Localization::translate('interactive.field.knowledge_domain'); ?></label>
                                                    <input type="text" id="knowledge_domain" name="knowledge_domain" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="device_requirements"><?= Localization::translate('interactive.field.device_requirements'); ?></label>
                                                    <textarea id="device_requirements" name="device_requirements" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Time Limit & Mobile Support -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="interactive_timeLimit" class="form-label"><?= Localization::translate('interactive.field.time_limit'); ?></label>
                                                    <input type="number" id="interactive_timeLimit" name="timeLimit" class="form-control" min="0" step="1">
                                                    <small class="text-muted">Time limit in minutes (optional)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('interactive.field.mobile_support'); ?></label><br>
                                                    <label><input type="radio" name="interactive_mobileSupport" value="Yes">
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="interactive_mobileSupport" value="No" checked>
                                                        <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Progress Tracking -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?= Localization::translate('interactive.field.progress_tracking'); ?></label><br>
                                                    <label><input type="radio" name="interactive_progress_tracking" value="Yes" checked>
                                                        <?= Localization::translate('yes'); ?></label>
                                                    <label class="ml-3"><input type="radio" name="interactive_progress_tracking" value="No">
                                                        <?= Localization::translate('no'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ Submit & Cancel Buttons -->
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                                            <button type="button" class="btn btn-danger" id="clearInteractiveForm"><?= Localization::translate('cancel'); ?></button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Interactive & AI Powered Content Sub-Tabs -->
                <ul class="nav nav-tabs" id="interactiveSubTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#adaptive-learning">
                            <?= Localization::translate('adaptive_learning'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#ai-tutoring">
                            <?= Localization::translate('interactive_ai_tutoring'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#ar-vr">
                            <?= Localization::translate('ar_vr'); ?>
                        </a>
                    </li>
                </ul>

                <!-- ✅ Interactive & AI Powered Content Sub-Tab Content -->
                <div class="tab-content mt-3">
                    <?php
                    // Validate if $interactiveContent is set
                    if (!isset($interactiveContent)) {
                        $interactiveContent = [];
                    }

                    // Categorize Interactive content
                    $interactiveCategories = [
                        'adaptive-learning' => [],
                        'ai-tutoring' => [],
                        'ar-vr' => []
                    ];

                    // Distribute content into categories
                    foreach ($interactiveContent as $content) {
                        $category = str_replace('_', '-', $content['content_type']);
                        if (isset($interactiveCategories[$category])) {
                            $interactiveCategories[$category][] = $content;
                        }
                    }
                    ?>

                    <?php foreach ($interactiveCategories as $categoryKey => $categoryContent): ?>
                        <div class="tab-pane <?= $categoryKey === 'adaptive-learning' ? 'show active' : ''; ?>" id="<?= $categoryKey ?>">
                            <?php
                            // Get proper translation key for tab header
                            $tabTranslationKey = '';
                            switch ($categoryKey) {
                                case 'ai-tutoring':
                                    $tabTranslationKey = 'interactive_ai_tutoring';
                                    break;
                                case 'adaptive-learning':
                                    $tabTranslationKey = 'adaptive_learning';
                                    break;
                                case 'ar-vr':
                                    $tabTranslationKey = 'ar_vr';
                                    break;
                                default:
                                    $tabTranslationKey = str_replace('-', '_', $categoryKey);
                                    break;
                            }
                            ?>
                            <h4><?= Localization::translate($tabTranslationKey); ?></h4>
                            <div class="row">
                                <?php if (!empty($categoryContent)): ?>
                                    <?php foreach ($categoryContent as $interactive): ?>
                                        <div class="col-md-4">
                                            <div class="interactive-card">
                                                <div class="card-body">
                                                    <div class="interactive-icon">
                                                        <i class="fas fa-brain"></i>
                                                    </div>
                                                    <?php
                                                    $displayTitle = strlen($interactive['title']) > 20 ? substr($interactive['title'], 0, 17) . '...' : $interactive['title'];
                                                    ?>
                                                    <h5 class="interactive-title" title="<?= htmlspecialchars($interactive['title']) ?>">
                                                        <?= htmlspecialchars($displayTitle) ?>
                                                    </h5>
                                                    <div class="interactive-actions">
                                                        <a href="#" class="edit-interactive" data-interactive='<?= json_encode($interactive); ?>'>
                                                            <i class="fas fa-edit edit-icon" title="<?= Localization::translate('edit'); ?>"></i>
                                                        </a>
                                                        <a href="#" class="delete-interactive" data-id="<?= $interactive['id'] ?>" data-title="<?= htmlspecialchars($interactive['title']) ?>">
                                                            <i class="fas fa-trash-alt delete-icon" title="<?= Localization::translate('delete'); ?>"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p><?= Localization::translate('no_interactive_content_found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


        </div>
    </div>
</div>



<script src="public/js/scorm_validation.js"></script>
<script src="public/js/scorm_package.js"></script>
<script src="public/js/assessment_validation.js"></script>
<script src="public/js/assessment_package.js"></script>
<script src="public/js/audio_validation.js"></script>
<script src="public/js/audio_package.js"></script>
<script src="public/js/video_validation.js"></script>
<script src="public/js/video_package.js"></script>
<script src="public/js/add_question_on_assessment.js"></script>
<script src="public/js/document_validation.js"></script>
<script src="public/js/document_package.js"></script>
<script src="public/js/image_validation.js"></script>
<script src="public/js/image_package.js"></script>
<script src="public/js/external_content_validation.js"></script>
<script src="public/js/external_package.js"></script>
<script src="public/js/content_preview.js"></script>
<script src="public/js/survey_validation.js"></script>
<script src="public/js/survey_package.js"></script>
<script src="public/js/add_survey_question_on_survey.js"></script>
<script src="public/js/feedback_validation.js"></script>
<script src="public/js/feedback_package.js"></script>
<script src="public/js/add_feedback_question_on_feedback.js"></script>
<script src="public/js/interactive_validation.js"></script>
<script src="public/js/interactive_package.js"></script>
<script src="public/js/non_scorm_validation.js"></script>
<script src="public/js/non_scorm_package.js"></script>
<script src="public/js/confirmation_modal.js"></script>

<!-- ✅ Tab Management Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the active tab from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'scorm';

    // Ensure the correct tab is active and shown
    const tabLink = document.querySelector(`a[href="#${activeTab}"]`);
    const tabPane = document.querySelector(`#${activeTab}`);

    if (tabLink && tabPane) {
        // Remove active classes from all tabs and panes
        document.querySelectorAll('#vlrTabs .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelectorAll('.tab-content > .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        // Add active classes to the correct tab and pane
        tabLink.classList.add('active');
        tabPane.classList.add('show', 'active');

        // Trigger Bootstrap tab shown event to ensure proper initialization
        const tabTrigger = new bootstrap.Tab(tabLink);
        tabTrigger.show();

        // ✅ Initialize sub-tabs after a delay
        setTimeout(() => {
            initializeSubTabsForActiveTab(activeTab);
        }, 300);
    }

    // Add event listeners to all tab links to remove tab parameter from URL when manually clicked
    document.querySelectorAll('#vlrTabs .nav-link').forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', function(e) {
            // Remove tab parameter from URL when user manually clicks tabs
            const url = new URL(window.location);
            if (url.searchParams.has('tab')) {
                url.searchParams.delete('tab');
                window.history.replaceState({}, '', url);
            }
        });
    });

    // ✅ Initialize sub-tabs for modules that have them
    initializeSubTabs();

    // ✅ Initialize sub-tab click handlers for Document, External, and Interactive modules
    initializeSubTabClickHandlers();
});

// ✅ Function to initialize sub-tabs for Document, External Content, and Interactive modules
function initializeSubTabs() {
    // Document Sub-tabs
    const documentTabLinks = document.querySelectorAll('#documentSubTabs .nav-link');
    documentTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all document sub-tabs
            documentTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all document sub-tab panes
            const documentTabContent = document.querySelector('#document .tab-content');
            if (documentTabContent) {
                documentTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    // External Content Sub-tabs
    const externalTabLinks = document.querySelectorAll('#externalSubTabs .nav-link');
    externalTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all external sub-tabs
            externalTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all external sub-tab panes
            const externalTabContent = document.querySelector('#external .tab-content');
            if (externalTabContent) {
                externalTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    // Interactive Content Sub-tabs
    const interactiveTabLinks = document.querySelectorAll('#interactiveSubTabs .nav-link');
    interactiveTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all interactive sub-tabs
            interactiveTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all interactive sub-tab panes
            const interactiveTabContent = document.querySelector('#interactive .tab-content');
            if (interactiveTabContent) {
                interactiveTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
}

// ✅ Function to initialize sub-tabs for the active main tab
function initializeSubTabsForActiveTab(activeTab) {
    // Only handle tabs that have sub-tabs
    if (!['document', 'external', 'interactive'].includes(activeTab)) {
        return;
    }

    let subTabsContainer, firstSubTabId;

    switch(activeTab) {
        case 'document':
            subTabsContainer = '#documentSubTabs';
            firstSubTabId = '#word-excel-ppt';
            break;
        case 'external':
            subTabsContainer = '#externalSubTabs';
            firstSubTabId = '#youtube-vimeo';
            break;
        case 'interactive':
            subTabsContainer = '#interactiveSubTabs';
            firstSubTabId = '#adaptive-learning';
            break;
    }

    // Get sub-tab elements
    const subTabLinks = document.querySelectorAll(`${subTabsContainer} .nav-link`);
    const firstSubTabLink = document.querySelector(`${subTabsContainer} .nav-link[href="${firstSubTabId}"]`);
    const firstSubTabPane = document.querySelector(firstSubTabId);

    if (subTabLinks.length > 0 && firstSubTabLink && firstSubTabPane) {
        // Remove active from all sub-tabs
        subTabLinks.forEach(link => link.classList.remove('active'));

        // Remove active from all sub-tab panes in this section
        const mainTabPane = document.querySelector(`#${activeTab}`);
        if (mainTabPane) {
            const subTabPanes = mainTabPane.querySelectorAll('.tab-content .tab-pane');
            subTabPanes.forEach(pane => pane.classList.remove('show', 'active'));
        }

        // Activate first sub-tab and its pane
        firstSubTabLink.classList.add('active');
        firstSubTabPane.classList.add('show', 'active');

        // ✅ Force display and trigger reflow to ensure content is visible
        firstSubTabPane.style.display = 'block';
        firstSubTabPane.offsetHeight; // Force reflow

        // Trigger Bootstrap tab to ensure proper initialization
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            const subTabTrigger = new bootstrap.Tab(firstSubTabLink);
            subTabTrigger.show();
        }

        // ✅ Additional check to ensure content is visible
        setTimeout(() => {
            if (firstSubTabPane && !firstSubTabPane.classList.contains('show')) {
                firstSubTabPane.classList.add('show', 'active');
            }

            // ✅ Force visibility with inline styles as backup
            if (firstSubTabPane) {
                firstSubTabPane.style.display = 'block';
                firstSubTabPane.style.opacity = '1';
                firstSubTabPane.style.visibility = 'visible';
            }
        }, 100);
    }
}

// ✅ Function to initialize sub-tab click handlers for Document, External, and Interactive modules
function initializeSubTabClickHandlers() {
    // Document Sub-tab Click Handlers
    const documentTabLinks = document.querySelectorAll('#documentSubTabs .nav-link');
    documentTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all document sub-tabs
            documentTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all document sub-tab panes
            const documentTabContent = document.querySelector('#document .tab-content');
            if (documentTabContent) {
                documentTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                targetPane.style.display = 'block';
            }
        });
    });

    // External Content Sub-tab Click Handlers
    const externalTabLinks = document.querySelectorAll('#externalSubTabs .nav-link');
    externalTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all external sub-tabs
            externalTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all external sub-tab panes
            const externalTabContent = document.querySelector('#external .tab-content');
            if (externalTabContent) {
                externalTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                targetPane.style.display = 'block';
            }
        });
    });

    // Interactive Content Sub-tab Click Handlers
    const interactiveTabLinks = document.querySelectorAll('#interactiveSubTabs .nav-link');
    interactiveTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all interactive sub-tabs
            interactiveTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all interactive sub-tab panes
            const interactiveTabContent = document.querySelector('#interactive .tab-content');
            if (interactiveTabContent) {
                interactiveTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                targetPane.style.display = 'block';
            }
        });
    });

    // ✅ Professional Delete Confirmations using Modal
    document.addEventListener('click', function(e) {
        // SCORM Package Delete
        if (e.target.closest('.delete-scorm')) {
            e.preventDefault();
            const link = e.target.closest('.delete-scorm');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('SCORM package "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=delete&id=' + id;
            });
        }

        // Document Delete
        if (e.target.closest('.delete-document')) {
            e.preventDefault();
            const link = e.target.closest('.delete-document');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('document "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteDocument&id=' + id;
            });
        }

        // External Content Delete
        if (e.target.closest('.delete-external')) {
            e.preventDefault();
            const link = e.target.closest('.delete-external');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('external content "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteExternal&id=' + id;
            });
        }

        // Survey Delete
        if (e.target.closest('.delete-survey')) {
            e.preventDefault();
            const link = e.target.closest('.delete-survey');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('survey "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteSurvey&id=' + id;
            });
        }

        // Interactive Content Delete
        if (e.target.closest('.delete-interactive')) {
            e.preventDefault();
            const link = e.target.closest('.delete-interactive');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('interactive content "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteInteractiveContent&id=' + id;
            });
        }

        // Non-SCORM Package Delete
        if (e.target.closest('.delete-non-scorm')) {
            e.preventDefault();
            const link = e.target.closest('.delete-non-scorm');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('non-SCORM package "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteNonScormPackage&id=' + id;
            });
        }

        // Assessment Package Delete
        if (e.target.closest('.delete-assessment')) {
            e.preventDefault();
            const link = e.target.closest('.delete-assessment');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('assessment "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteAssessment&id=' + id;
            });
        }

        // Audio Package Delete
        if (e.target.closest('.delete-audio')) {
            e.preventDefault();
            const link = e.target.closest('.delete-audio');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('audio package "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteAudioPackage&id=' + id;
            });
        }

        // Video Package Delete
        if (e.target.closest('.delete-video')) {
            e.preventDefault();
            const link = e.target.closest('.delete-video');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('video package "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteVideoPackage&id=' + id;
            });
        }

        // Image Package Delete
        if (e.target.closest('.delete-image')) {
            e.preventDefault();
            const link = e.target.closest('.delete-image');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('image package "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteImagePackage&id=' + id;
            });
        }

        // Feedback Package Delete
        if (e.target.closest('.delete-feedback')) {
            e.preventDefault();
            const link = e.target.closest('.delete-feedback');
            const id = link.dataset.id;
            const title = link.dataset.title;

            confirmDelete('feedback "' + title + '"', function() {
                window.location.href = 'index.php?controller=VLRController&action=deleteFeedback&id=' + id;
            });
        }
    });
}
</script>

<!-- ✅ Universal Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Content Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewModalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>