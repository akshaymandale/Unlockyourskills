<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(ucfirst($activityType)) ?> - <?= htmlspecialchars($activity['title'] ?? 'Activity') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f7f7fb; }
    .header { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#fff; border-bottom:1px solid #eee; position:sticky; top:0; z-index:10; }
    .title { font-weight:700; color:#2d2a34; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; border:1px solid #ddd; background:#fff; color:#333; text-decoration:none; cursor:pointer; }
    .btn-primary { background:#6a0dad; color:#fff; border-color:#6a0dad; }
    .container { max-width: 960px; margin: 18px auto; padding: 0 14px; }
    .card { background:#fff; border:1px solid #eee; border-radius:12px; padding:18px; }
    .q { margin-bottom: 14px; }
    .q h4 { margin: 0 0 8px; font-size: 16px; }
  </style>
</head>
<body>
  <div class="header">
    <div class="title"><i class="fas fa-clipboard-check me-2"></i><?= htmlspecialchars(ucfirst($activityType)) ?> • <?= htmlspecialchars($activity['title'] ?? 'Activity') ?></div>
    <div>
      <button class="btn" onclick="window.close()"><i class="fas fa-times"></i> Close</button>
    </div>
  </div>
  <div class="container">
    <div class="card">
      <?php if ($activityType === 'assessment' && !empty($activity['selected_questions'])): ?>
        <?php foreach ($activity['selected_questions'] as $idx => $q): ?>
          <div class="q">
            <h4>Q<?= $idx+1 ?>. <?= htmlspecialchars($q['title'] ?? '') ?></h4>
            <small class="text-muted">Type: <?= htmlspecialchars($q['type'] ?? '') ?> • Marks: <?= htmlspecialchars($q['marks'] ?? '') ?></small>
          </div>
        <?php endforeach; ?>
      <?php elseif ($activityType === 'survey' && !empty($activity['selected_questions'])): ?>
        <?php foreach ($activity['selected_questions'] as $idx => $q): ?>
          <div class="q">
            <h4>Q<?= $idx+1 ?>. <?= htmlspecialchars($q['title'] ?? '') ?></h4>
          </div>
        <?php endforeach; ?>
      <?php elseif ($activityType === 'feedback' && !empty($activity['selected_questions'])): ?>
        <?php foreach ($activity['selected_questions'] as $idx => $q): ?>
          <div class="q">
            <h4>Q<?= $idx+1 ?>. <?= htmlspecialchars($q['title'] ?? '') ?></h4>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No content found for this activity.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html> 