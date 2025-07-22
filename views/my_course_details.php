<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4">
        <!-- Banner -->
        <div class="course-banner position-relative mb-4" style="height: 260px; border-radius: 18px; overflow: hidden; background: #f8f9fa;">
            <?php if (!empty($course['thumbnail_image'])): ?>
                <img src="/Unlockyourskills/<?= htmlspecialchars($course['thumbnail_image']) ?>" alt="<?= htmlspecialchars($course['name']) ?>" class="w-100 h-100" style="object-fit: cover;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 w-100">
                    <i class="fas fa-book-open fa-5x text-secondary"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Course Info -->
        <div class="card mb-4 p-4 shadow-sm">
            <h2 class="text-purple fw-bold mb-3"><?= htmlspecialchars($course['name']) ?></h2>
            <p class="text-muted"><?= nl2br(htmlspecialchars($course['description'] ?? 'No description available.')) ?></p>
        </div>

        <!-- Prerequisites -->
        <?php if (!empty($course['prerequisites'])): ?>
        <div class="card mb-4 p-4 shadow-sm">
            <h5 class="text-purple fw-bold mb-3">Prerequisites</h5>
            <ul>
                <?php foreach ($course['prerequisites'] as $pre): ?>
                    <li><?= htmlspecialchars($pre['title']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Modules & Content -->
        <div class="card mb-4 p-4 shadow-sm">
            <h5 class="text-purple fw-bold mb-3">Course Content</h5>
            <?php if (!empty($course['modules'])): ?>
                <div class="accordion" id="modulesAccordion">
                    <?php foreach ($course['modules'] as $idx => $module): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $idx ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                                    <?= htmlspecialchars($module['title']) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $idx ?>" data-bs-parent="#modulesAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($module['content'])): ?>
                                        <ul class="list-group">
                                            <?php foreach ($module['content'] as $content): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= htmlspecialchars(ucfirst($content['type'])) ?>:</strong> <?= htmlspecialchars($content['title']) ?>
                                                    </div>
                                                    <div>
                                                        <?php
                                                            $urlBase = '/Unlockyourskills/';
                                                            switch ($content['type']) {
                                                                case 'scorm':
                                                                    $launchUrl = $content['scorm_launch_path'] ?? '';
                                                                    if ($launchUrl) {
                                                                        echo "<button class='btn btn-sm theme-btn-primary launch-content-btn' data-type='iframe' data-url='{$urlBase}" . htmlspecialchars($launchUrl) . "' data-title='" . htmlspecialchars($content['title']) . "'>Launch</button>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No SCORM launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'non_scorm':
                                                                case 'interactive':
                                                                    $launchUrl = $content['non_scorm_launch_path'] ?? $content['interactive_launch_url'] ?? '';
                                                                    if ($launchUrl) {
                                                                        echo "<button class='btn btn-sm theme-btn-primary launch-content-btn' data-type='iframe' data-url='{$urlBase}" . htmlspecialchars($launchUrl) . "' data-title='" . htmlspecialchars($content['title']) . "'>Launch</button>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'video':
                                                                    $videoUrl = $content['video_file_path'] ?? '';
                                                                    if ($videoUrl) {
                                                                        echo "<button class='btn btn-sm theme-btn-primary launch-content-btn' data-type='video' data-url='{$urlBase}" . htmlspecialchars($videoUrl) . "' data-title='" . htmlspecialchars($content['title']) . "'>Play</button>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No video file</span>";
                                                                    }
                                                                    break;
                                                                case 'audio':
                                                                    $audioUrl = $content['audio_file_path'] ?? '';
                                                                    if ($audioUrl) {
                                                                        echo "<audio controls preload='none' style='height: 30px;'><source src='{$urlBase}" . htmlspecialchars($audioUrl) . "' type='audio/mpeg'></audio>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No audio file</span>";
                                                                    }
                                                                    break;
                                                                case 'document':
                                                                    $docUrl = $content['document_file_path'] ?? '';
                                                                    if ($docUrl) {
                                                                        echo "<a href='{$urlBase}" . htmlspecialchars($docUrl) . "' target='_blank' class='btn btn-sm btn-outline-secondary'>View</a>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No document file</span>";
                                                                    }
                                                                    break;
                                                                case 'image':
                                                                    $imgUrl = $content['image_file_path'] ?? '';
                                                                    if ($imgUrl) {
                                                                        echo "<a href='{$urlBase}" . htmlspecialchars($imgUrl) . "' target='_blank' class='btn btn-sm btn-outline-secondary'>View</a>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No image file</span>";
                                                                    }
                                                                    break;
                                                                case 'external':
                                                                    $extUrl = $content['external_content_url'] ?? '';
                                                                    if ($extUrl) {
                                                                        echo "<a href='" . htmlspecialchars($extUrl) . "' target='_blank' class='btn btn-sm btn-outline-info'>Open Link</a>";
                                                                    } else {
                                                                        echo "<span class='text-danger'>No external link</span>";
                                                                    }
                                                                    break;
                                                                case 'assessment':
                                                                case 'survey':
                                                                case 'feedback':
                                                                case 'assignment':
                                                                    // For demo, just show a Start button (link to actual package if needed)
                                                                    echo "<button class='btn btn-sm btn-outline-success'>Start</button>";
                                                                    break;
                                                                default:
                                                                    echo "<button class='btn btn-sm btn-outline-secondary' disabled>Not available</button>";
                                                                    break;
                                                            }
                                                        ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">No content in this module.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No modules available for this course.</p>
            <?php endif; ?>
        </div>

        <!-- Post-requisites -->
        <?php if (!empty($course['post_requisites'])): ?>
        <div class="card mb-4 p-4 shadow-sm">
            <h5 class="text-purple fw-bold mb-3">Post-requisites</h5>
            <ul>
                <?php foreach ($course['post_requisites'] as $post): ?>
                    <li><?= htmlspecialchars($post['title'] ?? 'N/A') ?> (<?= htmlspecialchars($post['content_type']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Content Player Modal -->
<div class="modal fade" id="contentPlayerModal" tabindex="-1" aria-labelledby="contentPlayerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contentPlayerModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="content-player-container" class="w-100 h-100"></div>
      </div>
    </div>
  </div>
</div>

<script src="/Unlockyourskills/public/js/course_player.js"></script>

<?php include 'includes/footer.php'; ?> 