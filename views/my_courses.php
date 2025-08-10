<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 my-courses" id="myCoursesPage">
        <h1 class="page-title text-purple mb-4">
            <i class="fas fa-book me-2"></i> <?= Localization::translate('my_courses'); ?>
        </h1>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <ul class="nav nav-pills mb-2" id="myCoursesTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-status="not_started" href="#"> <?= Localization::translate('not_started'); ?> </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-status="in_progress" href="#"> <?= Localization::translate('in_progress'); ?> </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-status="completed" href="#"> <?= Localization::translate('completed'); ?> </a>
                </li>
            </ul>
            <div class="d-flex gap-2 align-items-center mb-2">
                <input type="text" id="myCoursesSearch" class="form-control" style="min-width:220px;" placeholder="<?= Localization::translate('search_courses'); ?>">
                <button id="toggleViewBtn" class="btn btn-outline-secondary" title="Toggle Grid/List">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
        </div>
        <div id="myCoursesList" class="row g-3"></div>
        <div id="myCoursesPagination" class="mt-4"></div>
    </div>
</div>
<script src="/Unlockyourskills/public/js/my_courses.js"></script>
<?php include 'includes/footer.php'; ?> 