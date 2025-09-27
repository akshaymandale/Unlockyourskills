<?php
/**
 * Custom Rich Text Editor Component
 * Reusable HTML template for the custom editor
 * 
 * Usage:
 * include 'views/components/custom-editor.php';
 * echo renderCustomEditor('editorId', 'hiddenFieldName', 'charCountId', $options);
 */

function renderCustomEditor($editorId, $hiddenFieldName, $charCountId, $options = []) {
    $defaultOptions = [
        'placeholder' => 'Enter text...',
        'minHeight' => 200,
        'maxHeight' => 400,
        'toolbar' => true,
        'fontSize' => true,
        'fontColor' => true,
        'showCharCount' => true,
        'required' => false
    ];
    
    $options = array_merge($defaultOptions, $options);
    $isEdit = strpos($editorId, 'edit') === 0;
    $fontSizeId = $isEdit ? 'editFontSizeSelect' : 'fontSizeSelect';
    $fontColorId = $isEdit ? 'editFontColorSelect' : 'fontColorSelect';
    
    ob_start();
    ?>
    <div class="custom-editor-container">
        <?php if ($options['toolbar']): ?>
        <div class="editor-toolbar">
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-command="bold" title="Bold">
                    <i class="fas fa-bold"></i>
                </button>
                <button type="button" class="toolbar-btn" data-command="italic" title="Italic">
                    <i class="fas fa-italic"></i>
                </button>
                <button type="button" class="toolbar-btn" data-command="underline" title="Underline">
                    <i class="fas fa-underline"></i>
                </button>
            </div>
            
            <div class="toolbar-separator"></div>
            
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-command="insertUnorderedList" title="Bullet List">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button type="button" class="toolbar-btn" data-command="insertOrderedList" title="Numbered List">
                    <i class="fas fa-list-ol"></i>
                </button>
            </div>
            
            <div class="toolbar-separator"></div>
            
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-command="justifyLeft" title="Align Left">
                    <i class="fas fa-align-left"></i>
                </button>
                <button type="button" class="toolbar-btn" data-command="justifyCenter" title="Align Center">
                    <i class="fas fa-align-center"></i>
                </button>
                <button type="button" class="toolbar-btn" data-command="justifyRight" title="Align Right">
                    <i class="fas fa-align-right"></i>
                </button>
            </div>
            
            <div class="toolbar-separator"></div>
            
            <?php if ($options['fontSize'] || $options['fontColor']): ?>
            <div class="toolbar-group">
                <?php if ($options['fontSize']): ?>
                <select class="toolbar-select" id="<?= $fontSizeId ?>">
                    <option value="">Size</option>
                    <option value="1">Small</option>
                    <option value="3">Normal</option>
                    <option value="5">Large</option>
                    <option value="7">Extra Large</option>
                </select>
                <?php endif; ?>
                
                <?php if ($options['fontColor']): ?>
                <select class="toolbar-select" id="<?= $fontColorId ?>">
                    <option value="">Color</option>
                    <option value="#000000">Black</option>
                    <option value="#6f42c1">Purple</option>
                    <option value="#dc3545">Red</option>
                    <option value="#28a745">Green</option>
                    <option value="#007bff">Blue</option>
                    <option value="#ffc107">Yellow</option>
                    <option value="#17a2b8">Cyan</option>
                </select>
                <?php endif; ?>
            </div>
            
            <div class="toolbar-separator"></div>
            <?php endif; ?>
            
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-command="removeFormat" title="Clear Formatting">
                    <i class="fas fa-remove-format"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="editor-content" 
             id="<?= htmlspecialchars($editorId) ?>" 
             contenteditable="true" 
             data-placeholder="<?= htmlspecialchars($options['placeholder']) ?>"
             data-custom-editor="true"
             style="min-height: <?= $options['minHeight'] ?>px; <?= $options['maxHeight'] ? 'max-height: ' . $options['maxHeight'] . 'px;' : '' ?>">
        </div>
        
        <textarea class="form-control d-none" 
                  name="<?= htmlspecialchars($hiddenFieldName) ?>" 
                  id="<?= htmlspecialchars($editorId) ?>Hidden">
        </textarea>
    </div>
    
    <?php if ($options['showCharCount']): ?>
    <div class="form-text">
        <span id="<?= htmlspecialchars($charCountId) ?>">0</span> characters
    </div>
    <?php endif; ?>
    
    <?php if ($options['required']): ?>
    <div class="invalid-feedback"></div>
    <?php endif; ?>
    
    <?php
    return ob_get_clean();
}

// Helper function to initialize editor with specific options
function initializeCustomEditor($editorId, $options = []) {
    $defaultOptions = [
        'placeholder' => 'Enter text...',
        'minHeight' => 200,
        'maxHeight' => 400,
        'toolbar' => true,
        'fontSize' => true,
        'fontColor' => true
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    echo "<script>";
    echo "document.addEventListener('DOMContentLoaded', function() {";
    echo "  new CustomEditor('$editorId', " . json_encode($options) . ");";
    echo "});";
    echo "</script>";
}
?>
