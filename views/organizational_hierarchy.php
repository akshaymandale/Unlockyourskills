<?php
require_once __DIR__ . '/../config/Localization.php';
require_once __DIR__ . '/../core/UrlHelper.php';

// Get current user from session (already authenticated by AuthMiddleware)
$currentUser = $_SESSION['user'] ?? null;
$pageTitle = Localization::translate('organizational_hierarchy_title');

// Include header first (before any HTML output)
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Custom styles for organizational hierarchy -->
<style>
            .org-hierarchy {
            background: #f8f9fa;
            min-height: calc(100vh - 100px);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    
    .hierarchy-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0;
    }
    
    .page-header {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .page-title {
        color: #2c3e50;
        margin-bottom: 10px;
        font-size: 2rem;
        font-weight: 600;
    }
    
    .page-subtitle {
        color: #7f8c8d;
        font-size: 16px;
        margin-bottom: 0;
    }
    
    .breadcrumb-nav {
        margin-bottom: 20px;
    }
    
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }
    
    .breadcrumb-item a {
        color: #3498db;
        text-decoration: none;
    }
    
    .breadcrumb-item a:hover {
        color: #2980b9;
        text-decoration: underline;
    }
    
    .breadcrumb-item.active {
        color: #7f8c8d;
    }
    
    .org-tree {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .org-node {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 10px;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    
    .org-node:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .org-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .org-info {
        flex: 1;
        min-width: 0;
    }
    
    .org-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
        font-size: 16px;
    }
    
    .org-email {
        color: #7f8c8d;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .org-role {
        color: #3498db;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .org-status {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-locked {
        background: #fff3cd;
        color: #856404;
    }
    
    .org-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }
    
    .btn-org {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-org:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    .btn-view {
        background: #3498db;
        color: white;
    }
    
    .btn-view:hover {
        background: #2980b9;
        color: white;
    }
    
    .btn-edit {
        background: #f39c12;
        color: white;
    }
    
    .btn-edit:hover {
        background: #e67e22;
        color: white;
    }
    
    .org-expand {
        background: #27ae60;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .org-expand:hover {
        background: #229954;
        transform: translateY(-1px);
    }
    
    .org-children {
        margin-left: 40px;
        border-left: 2px solid #e9ecef;
        padding-left: 20px;
        margin-top: 10px;
    }
    
    .hierarchy-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #3498db;
        margin-bottom: 10px;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .hierarchy-controls {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        justify-content: center;
    }
    
    .btn-control {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-control:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-expand-all {
        background: #27ae60;
        color: white;
    }
    
    .btn-expand-all:hover {
        background: #229954;
        color: white;
    }
    
    .btn-collapse-all {
        background: #e74c3c;
        color: white;
    }
    
    .btn-collapse-all:hover {
        background: #c0392b;
        color: white;
    }
    
    .btn-refresh {
        background: #3498db;
        color: white;
    }
    
    .btn-refresh:hover {
        background: #2980b9;
        color: white;
    }
    
    .loading {
        text-align: center;
        padding: 50px;
        color: #7f8c8d;
    }
    
    .no-data {
        text-align: center;
        padding: 50px;
        color: #7f8c8d;
    }
    
    .org-warning {
        font-size: 11px;
        color: #f39c12;
        margin-top: 3px;
    }
    
    .org-warning i {
        margin-right: 5px;
    }
    
    @media (max-width: 768px) {
        .org-hierarchy {
            margin: 10px;
            padding: 15px;
        }
        
        .hierarchy-container {
            padding: 0 10px;
        }
        
        .page-header {
            padding: 20px;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .org-node {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .org-status {
            align-items: flex-start;
            width: 100%;
        }
        
        .org-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .hierarchy-controls {
            flex-direction: column;
        }
        
        .btn-control {
            width: 100%;
        }
        
        .hierarchy-stats {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
    }
</style>

<!-- Update page title -->
<script>
document.title = '<?= $pageTitle ?> - Unlock Your Skills';
</script>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content" data-organizational-hierarchy-page="true">
    <div class="container">
        <div class="org-hierarchy">
        <div class="hierarchy-container">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= UrlHelper::url('dashboard') ?>">
                            <i class="fas fa-home"></i> <?= Localization::translate('dashboard') ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= Localization::translate('organizational_hierarchy_title') ?>
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-sitemap me-3"></i>
                    <?= Localization::translate('organizational_hierarchy_title') ?>
                </h1>
                <p class="page-subtitle">
                    <?= Localization::translate('organizational_hierarchy_subtitle') ?>
                </p>
            </div>

            <!-- Statistics -->
            <div class="hierarchy-stats">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers">-</div>
                    <div class="stat-label"><?= Localization::translate('total_users') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeUsers">-</div>
                    <div class="stat-label"><?= Localization::translate('active_users') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalLevels">-</div>
                    <div class="stat-label"><?= Localization::translate('hierarchy_levels') ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgTeamSize">-</div>
                    <div class="stat-label"><?= Localization::translate('avg_team_size') ?></div>
                </div>
            </div>

            <!-- Controls -->
            <div class="hierarchy-controls">
                <button class="btn-control btn-expand-all" onclick="expandAll()">
                    <i class="fas fa-expand-alt me-2"></i>
                    <?= Localization::translate('expand_all') ?>
                </button>
                <button class="btn-control btn-collapse-all" onclick="collapseAll()">
                    <i class="fas fa-compress-alt me-2"></i>
                    <?= Localization::translate('collapse_all') ?>
                </button>
                <button class="btn-control btn-refresh" onclick="refreshHierarchy()">
                    <i class="fas fa-sync-alt me-2"></i>
                    <?= Localization::translate('refresh') ?>
                </button>
            </div>

            <!-- Organization Tree -->
            <div class="org-tree">
                <div id="hierarchyContent">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p><?= Localization::translate('loading_hierarchy') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Organizational Hierarchy JS -->
<script src="<?= UrlHelper::url('public/js/organizational_hierarchy.js') ?>"></script>
