document.addEventListener('DOMContentLoaded', function() {
    const contentPlayerModal = new bootstrap.Modal(document.getElementById('contentPlayerModal'));
    const modalTitle = document.getElementById('contentPlayerModalLabel');
    const playerContainer = document.getElementById('content-player-container');

    // Only handle launch-content-btn buttons that have data-url (for modal display)
    // Content viewer links (without data-url) will open in new tabs
    document.querySelectorAll('.launch-content-btn[data-url]').forEach(button => {
        button.addEventListener('click', function(e) {
            // Only handle if this button has a URL for modal display
            const url = this.dataset.url;
            if (!url) {
                return; // Let the default link behavior happen
            }
            
            e.preventDefault(); // Prevent default only for modal display
            
            const type = this.dataset.type;
            const title = this.dataset.title;

            modalTitle.textContent = title;
            playerContainer.innerHTML = '';

            if (type === 'video') {
                const videoElement = document.createElement('video');
                videoElement.setAttribute('controls', '');
                videoElement.setAttribute('autoplay', '');
                videoElement.style.width = '100%';
                videoElement.style.height = '100%';
                const sourceElement = document.createElement('source');
                sourceElement.setAttribute('src', url);
                sourceElement.setAttribute('type', 'video/mp4');
                videoElement.appendChild(sourceElement);
                playerContainer.appendChild(videoElement);
            } else if (type === 'iframe') {
                const iframeElement = document.createElement('iframe');
                iframeElement.setAttribute('src', url);
                iframeElement.setAttribute('frameborder', '0');
                iframeElement.style.width = '100%';
                iframeElement.style.height = '100%';
                iframeElement.setAttribute('allowfullscreen', '');
                playerContainer.appendChild(iframeElement);
            }

            contentPlayerModal.show();
        });
    });

    document.getElementById('contentPlayerModal').addEventListener('hidden.bs.modal', function () {
        playerContainer.innerHTML = '';
        modalTitle.textContent = '';
    });
}); 