/**
 * PDFShift Export Utility
 * Handles server-side PDF generation and download
 */

class PDFExporter {
    constructor(options = {}) {
        this.timeout = options.timeout || 60000; // 60 seconds
        this.retries = options.retries || 3;
        this.retryDelay = options.retryDelay || 1000;
    }

    /**
     * Export presentation to PDF
     * @param {number} presentationId - Presentation ID
     * @param {object} options - Export options
     * @returns {Promise<void>}
     */
    async exportPDF(presentationId, options = {}) {
        const {
            showLoading = true,
            onProgress = null,
            onError = null,
            onSuccess = null
        } = options;

        let loadingIndicator = null;

        try {
            // Show loading indicator
            if (showLoading) {
                loadingIndicator = this._showLoadingIndicator('Генерирование PDF...');
            }

            // Call export API
            const blob = await this._fetchPDF(presentationId, onProgress);

            // Download file
            this._downloadBlob(blob, `presentation_${presentationId}.pdf`);

            // Call success callback
            if (onSuccess) onSuccess();

        } catch (error) {
            console.error('PDF Export Error:', error);
            
            if (onError) {
                onError(error);
            } else {
                this._showError(error.message);
            }
        } finally {
            // Hide loading indicator
            if (loadingIndicator) {
                this._hideLoadingIndicator(loadingIndicator);
            }
        }
    }

    /**
     * Fetch PDF from server with retry logic
     * @private
     */
    async _fetchPDF(presentationId, onProgress) {
        let lastError = null;

        for (let attempt = 1; attempt <= this.retries; attempt++) {
            try {
                const response = await fetch(
                    `/api.php?action=export_pdf&id=${presentationId}`,
                    {
                        method: 'GET',
                        credentials: 'same-origin', // Include session cookies
                        timeout: this.timeout
                    }
                );

                // Check for authentication errors
                if (response.status === 401) {
                    throw new Error('Требуется авторизация. Пожалуйста, войдите в систему.');
                }

                // Check for access denied
                if (response.status === 403) {
                    const error = await response.json();
                    throw new Error(error.message || 'Доступ запрещён');
                }

                // Check for not found
                if (response.status === 404) {
                    throw new Error('Презентация не найдена');
                }

                // Check for server errors
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(
                        error.message || `Ошибка сервера: HTTP ${response.status}`
                    );
                }

                // Track download progress
                const contentLength = response.headers.get('content-length');
                const total = parseInt(contentLength, 10);

                if (!response.body) {
                    throw new Error('Response body is empty');
                }

                const reader = response.body.getReader();
                const chunks = [];
                let loaded = 0;

                while (true) {
                    const { done, value } = await reader.read();

                    if (done) break;

                    chunks.push(value);
                    loaded += value.length;

                    // Call progress callback
                    if (onProgress && total) {
                        const progress = Math.round((loaded / total) * 100);
                        onProgress(progress);
                    }
                }

                return new Blob(chunks, { type: 'application/pdf' });

            } catch (error) {
                lastError = error;
                console.warn(`Attempt ${attempt}/${this.retries} failed:`, error.message);

                // Don't retry on client-side errors
                if (error.message.includes('Требуется авторизация') ||
                    error.message.includes('Доступ запрещён') ||
                    error.message.includes('не найдена')) {
                    throw error;
                }

                // Retry with delay
                if (attempt < this.retries) {
                    await this._delay(this.retryDelay * attempt);
                }
            }
        }

        throw lastError || new Error('Failed to fetch PDF after multiple attempts');
    }

    /**
     * Download blob as file
     * @private
     */
    _downloadBlob(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Cleanup
        window.URL.revokeObjectURL(url);
    }

    /**
     * Show loading indicator
     * @private
     */
    _showLoadingIndicator(message = 'Загрузка...') {
        const overlay = document.createElement('div');
        overlay.className = 'pdf-loading-overlay';
        overlay.innerHTML = `
            <div class="pdf-loading-content">
                <div class="pdf-loading-spinner"></div>
                <p class="pdf-loading-text">${message}</p>
            </div>
        `;

        // Add styles if not already present
        if (!document.getElementById('pdf-export-styles')) {
            const style = document.createElement('style');
            style.id = 'pdf-export-styles';
            style.textContent = `
                .pdf-loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                }

                .pdf-loading-content {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                }

                .pdf-loading-spinner {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #2c7f8d;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: pdf-spin 1s linear infinite;
                    margin: 0 auto 15px;
                }

                @keyframes pdf-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .pdf-loading-text {
                    margin: 0;
                    color: #333;
                    font-size: 16px;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(overlay);
        return overlay;
    }

    /**
     * Hide loading indicator
     * @private
     */
    _hideLoadingIndicator(overlay) {
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    }

    /**
     * Show error message
     * @private
     */
    _showError(message) {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'pdf-error-message';
        errorContainer.innerHTML = `
            <div class="pdf-error-content">
                <svg class="pdf-error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>${message}</p>
                <button onclick="this.parentElement.parentElement.remove()" class="pdf-error-close">
                    Закрыть
                </button>
            </div>
        `;

        // Add styles if not already present
        if (!document.getElementById('pdf-error-styles')) {
            const style = document.createElement('style');
            style.id = 'pdf-error-styles';
            style.textContent = `
                .pdf-error-message {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    animation: pdf-slide-in 0.3s ease-out;
                }

                @keyframes pdf-slide-in {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                .pdf-error-content {
                    background: white;
                    border-left: 4px solid #e74c3c;
                    padding: 16px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    max-width: 400px;
                }

                .pdf-error-icon {
                    width: 24px;
                    height: 24px;
                    color: #e74c3c;
                    margin-bottom: 10px;
                    display: inline-block;
                }

                .pdf-error-content p {
                    margin: 0 0 12px 0;
                    color: #333;
                    font-size: 14px;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }

                .pdf-error-close {
                    background: #f5f5f5;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                    color: #666;
                    transition: background 0.2s;
                }

                .pdf-error-close:hover {
                    background: #e8e8e8;
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(errorContainer);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorContainer.parentNode) {
                errorContainer.parentNode.removeChild(errorContainer);
            }
        }, 5000);
    }

    /**
     * Delay utility
     * @private
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Global instance
window.pdfExporter = new PDFExporter();

// Convenience functions
function exportPresentationPDF(presentationId) {
    return window.pdfExporter.exportPDF(presentationId);
}

function exportPresentationWithProgress(presentationId) {
    return window.pdfExporter.exportPDF(presentationId, {
        onProgress: (progress) => {
            console.log(`PDF Download Progress: ${progress}%`);
            // Update progress bar if available
            const progressBar = document.querySelector('.pdf-progress-bar');
            if (progressBar) {
                progressBar.style.width = progress + '%';
            }
        }
    });
}
