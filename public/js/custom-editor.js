/**
 * Custom Rich Text Editor
 * A reusable rich text editor component with no external dependencies
 */

class CustomEditor {
    constructor(editorId, options = {}) {
        this.editorId = editorId;
        this.options = {
            minHeight: 200,
            maxHeight: 400,
            placeholder: 'Enter text...',
            toolbar: true,
            fontSize: true,
            fontColor: true,
            ...options
        };
        
        this.editor = null;
        this.hidden = null;
        this.charCount = null;
        this.toolbar = null;
        
        this.init();
    }
    
    init() {
        this.editor = document.getElementById(this.editorId);
        if (!this.editor) {
            console.error(`CustomEditor: Element with ID '${this.editorId}' not found`);
            return;
        }
        
        this.hidden = document.getElementById(this.editorId + 'Hidden');
        this.charCount = document.getElementById(this.editorId.replace('Body', 'CharCount'));
        this.toolbar = this.editor.closest('.custom-editor-container')?.querySelector('.editor-toolbar');
        
        this.setupEditor();
        this.setupEventListeners();
        this.updateCharCount();
    }
    
    setupEditor() {
        // Set placeholder
        this.editor.setAttribute('data-placeholder', this.options.placeholder);
        
        // Set minimum height
        this.editor.style.minHeight = this.options.minHeight + 'px';
        
        // Set maximum height
        if (this.options.maxHeight) {
            this.editor.style.maxHeight = this.options.maxHeight + 'px';
        }
    }
    
    setupEventListeners() {
        // Content change events
        this.editor.addEventListener('input', () => this.updateHiddenField());
        this.editor.addEventListener('paste', (e) => {
            // Allow paste but clean up content
            setTimeout(() => this.updateHiddenField(), 10);
        });
        
        // Toolbar events
        if (this.toolbar) {
            this.toolbar.addEventListener('click', (e) => this.handleToolbarClick(e));
        }
        
        // Font controls
        this.setupFontControls();
        
        // Selection change events
        this.editor.addEventListener('keyup', () => this.updateButtonStates());
        this.editor.addEventListener('mouseup', () => this.updateButtonStates());
        
        // Keyboard shortcuts
        this.editor.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }
    
    setupFontControls() {
        const container = this.editor.closest('.custom-editor-container');
        if (!container) return;
        
        // Font size
        const fontSizeSelect = container.querySelector('#fontSizeSelect, #editFontSizeSelect');
        if (fontSizeSelect) {
            fontSizeSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.executeCommand('fontSize', e.target.value);
                }
            });
        }
        
        // Font color
        const fontColorSelect = container.querySelector('#fontColorSelect, #editFontColorSelect');
        if (fontColorSelect) {
            fontColorSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.executeCommand('foreColor', e.target.value);
                }
            });
        }
    }
    
    handleToolbarClick(e) {
        const btn = e.target.closest('.toolbar-btn');
        if (btn) {
            e.preventDefault();
            const command = btn.dataset.command;
            if (command) {
                this.executeCommand(command);
                this.updateButtonStates();
            }
        }
    }
    
    handleKeyboardShortcuts(e) {
        if (!e.ctrlKey) return;
        
        switch (e.key) {
            case 'b':
                e.preventDefault();
                this.executeCommand('bold');
                break;
            case 'i':
                e.preventDefault();
                this.executeCommand('italic');
                break;
            case 'u':
                e.preventDefault();
                this.executeCommand('underline');
                break;
        }
    }
    
    executeCommand(command, value = null) {
        this.editor.focus();
        document.execCommand(command, false, value);
        this.updateHiddenField();
    }
    
    updateButtonStates() {
        if (!this.toolbar) return;
        
        const buttons = this.toolbar.querySelectorAll('.toolbar-btn[data-command]');
        buttons.forEach(btn => {
            const command = btn.dataset.command;
            const isActive = document.queryCommandState(command);
            btn.classList.toggle('active', isActive);
        });
    }
    
    updateHiddenField() {
        if (this.hidden) {
            this.hidden.value = this.editor.innerHTML;
        }
        this.updateCharCount();
    }
    
    updateCharCount() {
        if (this.charCount) {
            const text = this.editor.textContent || this.editor.innerText || '';
            this.charCount.textContent = text.length;
        }
    }
    
    // Public methods
    setContent(content) {
        this.editor.innerHTML = content;
        this.updateHiddenField();
    }
    
    getContent() {
        return this.editor.innerHTML;
    }
    
    getTextContent() {
        return this.editor.textContent || this.editor.innerText || '';
    }
    
    clear() {
        this.editor.innerHTML = '';
        this.updateHiddenField();
    }
    
    focus() {
        this.editor.focus();
    }
    
    destroy() {
        // Remove event listeners and clean up
        this.editor.removeEventListener('input', this.updateHiddenField);
        this.editor.removeEventListener('paste', this.updateHiddenField);
        this.editor.removeEventListener('keyup', this.updateButtonStates);
        this.editor.removeEventListener('mouseup', this.updateButtonStates);
        this.editor.removeEventListener('keydown', this.handleKeyboardShortcuts);
        
        if (this.toolbar) {
            this.toolbar.removeEventListener('click', this.handleToolbarClick);
        }
    }
}

// Global functions for backward compatibility
window.CustomEditor = CustomEditor;

// Initialize all custom editors on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize editors with data-custom-editor attribute
    const editors = document.querySelectorAll('[data-custom-editor]');
    editors.forEach(editor => {
        const editorId = editor.id;
        const options = {
            placeholder: editor.dataset.placeholder || 'Enter text...',
            minHeight: parseInt(editor.dataset.minHeight) || 200,
            maxHeight: parseInt(editor.dataset.maxHeight) || 400
        };
        
        new CustomEditor(editorId, options);
    });
});

// Utility functions
window.setEditorContent = function(editorId, content) {
    const editor = window.customEditors?.[editorId];
    if (editor) {
        editor.setContent(content);
    } else {
        const element = document.getElementById(editorId);
        if (element && element.contentEditable === 'true') {
            element.innerHTML = content;
            const hidden = document.getElementById(editorId + 'Hidden');
            if (hidden) {
                hidden.value = content;
            }
        }
    }
};

window.getEditorContent = function(editorId) {
    const editor = window.customEditors?.[editorId];
    if (editor) {
        return editor.getContent();
    } else {
        const element = document.getElementById(editorId);
        return element ? element.innerHTML : '';
    }
};

window.clearEditorContent = function(editorId) {
    const editor = window.customEditors?.[editorId];
    if (editor) {
        editor.clear();
    } else {
        const element = document.getElementById(editorId);
        if (element) {
            element.innerHTML = '';
            const hidden = document.getElementById(editorId + 'Hidden');
            if (hidden) {
                hidden.value = '';
            }
        }
    }
};

// Store editor instances globally for easy access
window.customEditors = window.customEditors || {};
