<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 search-courses" id="searchCoursesPage">
        <h1 class="page-title text-purple mb-4">
            <i class="fas fa-search me-2"></i> <?= Localization::translate('search_courses'); ?>
        </h1>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <div></div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <input type="text" id="searchCoursesInput" class="form-control" style="min-width:220px;" placeholder="<?= Localization::translate('search_courses_placeholder'); ?>">
                <button id="toggleViewBtn" class="btn btn-outline-secondary" title="Toggle Grid/List">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
        </div>
        <div id="searchCoursesList" class="row g-3"></div>
        <div id="searchCoursesPagination" class="mt-4"></div>
    </div>
</div>
<script src="/Unlockyourskills/public/js/search_courses.js"></script>
<?php include 'includes/footer.php'; ?>
