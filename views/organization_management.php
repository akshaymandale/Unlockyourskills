<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-building me-2"></i>
                    <?= Localization::translate('organization_management'); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php?controller=OrganizationController&action=create" class="btn theme-btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        <?= Localization::translate('add_organization'); ?>
                    </a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="index.php" class="row g-3">
                        <input type="hidden" name="controller" value="OrganizationController">
                        
                        <div class="col-md-4">
                            <label for="search" class="form-label"><?= Localization::translate('search'); ?></label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search); ?>" 
                                   placeholder="<?= Localization::translate('search_organizations'); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label"><?= Localization::translate('status'); ?></label>
                            <select class="form-select" id="status" name="status">
                                <option value=""><?= Localization::translate('all_statuses'); ?></option>
                                <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : ''; ?>>
                                    <?= Localization::translate('active'); ?>
                                </option>
                                <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : ''; ?>>
                                    <?= Localization::translate('inactive'); ?>
                                </option>
                                <option value="suspended" <?= ($filters['status'] === 'suspended') ? 'selected' : ''; ?>>
                                    <?= Localization::translate('suspended'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn theme-btn-secondary me-2">
                                <i class="fas fa-search me-1"></i>
                                <?= Localization::translate('search'); ?>
                            </button>
                            <a href="index.php?controller=OrganizationController" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                <?= Localization::translate('clear'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Organizations Grid -->
            <div class="row">
                <?php if (empty($organizations)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= Localization::translate('no_organizations_found'); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($organizations as $org): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 organization-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <?php if ($org['logo_path']): ?>
                                            <img src="<?= htmlspecialchars($org['logo_path']); ?>" 
                                                 alt="<?= htmlspecialchars($org['name']); ?>" 
                                                 class="organization-logo me-2">
                                        <?php else: ?>
                                            <div class="organization-logo-placeholder me-2">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>
                                        <h6 class="mb-0"><?= htmlspecialchars($org['name']); ?></h6>
                                    </div>
                                    <span class="badge bg-<?= $org['status'] === 'active' ? 'success' : ($org['status'] === 'suspended' ? 'danger' : 'secondary'); ?>">
                                        <?= ucfirst($org['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <div class="organization-info">
                                        <div class="info-item">
                                            <strong><?= Localization::translate('client_code'); ?>:</strong>
                                            <span class="text-muted"><?= htmlspecialchars($org['client_code']); ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <strong><?= Localization::translate('users'); ?>:</strong>
                                            <span class="text-muted">
                                                <?= $org['active_users']; ?> / <?= $org['max_users']; ?>
                                            </span>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?= $org['max_users'] > 0 ? ($org['active_users'] / $org['max_users'] * 100) : 0; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <strong><?= Localization::translate('plan'); ?>:</strong>
                                            <span class="text-muted"><?= ucfirst($org['subscription_plan']); ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <strong><?= Localization::translate('created'); ?>:</strong>
                                            <span class="text-muted"><?= date('M j, Y', strtotime($org['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="index.php?controller=OrganizationController&action=edit&id=<?= $org['id']; ?>" 
                                           class="btn btn-sm theme-btn-secondary" title="<?= Localization::translate('edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?controller=UserManagementController&organization_id=<?= $org['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="<?= Localization::translate('manage_users'); ?>">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="index.php?controller=OrganizationController&action=analytics&id=<?= $org['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="<?= Localization::translate('analytics'); ?>">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm theme-btn-danger delete-organization" 
                                           data-id="<?= $org['id']; ?>" 
                                           data-name="<?= htmlspecialchars($org['name']); ?>"
                                           title="<?= Localization::translate('delete'); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Organizations pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?controller=OrganizationController&page=<?= $i; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($filters['status']); ?>">
                                    <?= $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.organization-card {
    transition: transform 0.2s ease-in-out;
}

.organization-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.organization-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 4px;
}

.organization-logo-placeholder {
    width: 40px;
    height: 40px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.organization-info .info-item {
    margin-bottom: 8px;
}

.organization-info .info-item:last-child {
    margin-bottom: 0;
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    background-color: var(--theme-primary-color, #6f42c1);
}
</style>

<!-- Organization Delete Confirmations -->
<script src="public/js/modules/organization_confirmations.js"></script>

<?php include 'includes/footer.php'; ?>
