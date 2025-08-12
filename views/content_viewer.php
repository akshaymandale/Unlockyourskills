<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Content') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { --purple: #6a0dad; --light: #f8f9fa; --border: #e5d6ff; }
    html, body { height: 100%; margin: 0; background: #fff; }
    .viewer-header {
      height: 48px; display: flex; align-items: center; justify-content: space-between;
      padding: 0 12px; background: #f3eaff; border-bottom: 1px solid var(--border);
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
    }
    .viewer-title { color: var(--purple); font-weight: 600; font-size: 14px; margin-left: 8px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; font-size: 12px; 
      border: 1px solid #c9c9c9; background: #fff; color: #333; border-radius: 6px; text-decoration: none; cursor: pointer; }
    .btn:hover { background: #f2f2f2; }
    .btn-primary { border-color: var(--purple); color: var(--purple); }
    .btn-primary:hover { background: #efe6f7; }
    .viewer-frame { width: 100%; height: calc(100vh - 48px); border: 0; }
  </style>
  <script>
    function closeTab() {
      try { window.close(); } catch(e) {}
      // Fallbacks if browser blocks close()
      setTimeout(function(){
        try { window.open('', '_self'); window.close(); } catch(e) {}
      }, 50);
      setTimeout(function(){
        if (document.visibilityState === 'visible') {
          history.length > 1 ? history.back() : (window.location.href = '<?= addslashes(UrlHelper::url("my-courses")) ?>');
        }
      }, 150);
    }
  </script>
</head>
<body>
  <div class="viewer-header">
    <div style="display:flex; align-items:center;">
      <button class="btn" onclick="closeTab()"><i class="fas fa-times"></i> Close</button>
      <div class="viewer-title"><?= htmlspecialchars($title ?? 'Content') ?></div>
    </div>
    <?php if (!empty($src ?? '')): ?>
      <?php if (($type ?? '') !== 'video' && ($type ?? '') !== 'audio'): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($src) ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Open source</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php if (!empty($src ?? '')): ?>
    <?php if (($type ?? '') === 'video'): ?>
      <video class="viewer-frame" controls autoplay playsinline>
        <source src="<?= htmlspecialchars($src) ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    <?php elseif (($type ?? '') === 'audio'): ?>
      <audio controls autoplay style="width:100%; height:48px;">
        <source src="<?= htmlspecialchars($src) ?>" type="audio/mpeg">
        Your browser does not support the audio element.
      </audio>
      <iframe class="viewer-frame" src="about:blank" style="display:none"></iframe>
    <?php else: ?>
      <?php if (($type ?? '') === 'scorm'): ?>
        <iframe class="viewer-frame" src="<?= htmlspecialchars($src) ?>" allow="fullscreen *; geolocation *; microphone *; camera *" referrerpolicy="no-referrer-when-downgrade"></iframe>
      <?php else: ?>
        <iframe class="viewer-frame" src="<?= htmlspecialchars($src) ?>" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
    <div style="height: calc(100vh - 48px); display:flex; align-items:center; justify-content:center; color:#777; font-family: system-ui;">No content to display.</div>
  <?php endif; ?>
</body>
</html> 