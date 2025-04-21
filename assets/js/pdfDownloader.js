/**
 * PDF Downloader Helper
 * A utility script to facilitate PDF downloads on mobile devices
 */

const PDFDownloader = {
    /**
     * Detects if the user is on a mobile device
     * @return {boolean} True if on mobile device
     */
    isMobileDevice: function() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    /**
     * Detects if the user is on iOS
     * @return {boolean} True if on iOS device
     */
    isIOS: function() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    },
    
    /**
     * Attempts to download a PDF file with appropriate handling for mobile devices
     * @param {string} url - The URL of the PDF to download
     * @param {string} filename - The desired filename for the downloaded file
     */
    downloadPDF: function(url, filename) {
        // For iOS devices, which have limitations with file downloads
        if (this.isIOS()) {
            // Open in a new tab - iOS will handle PDF viewing/saving
            window.open(url, '_blank');
            return;
        }
        
        // For Android and other devices
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const blobUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                }, 100);
            })
            .catch(error => {
                console.error('Download failed:', error);
                // Fallback method
                window.open(url, '_blank');
            });
    },
    
    /**
     * Creates a download button for a PDF that works on mobile devices
     * @param {string} url - The URL of the PDF to download
     * @param {string} filename - The desired filename for the downloaded file
     * @param {string} elementId - The ID of the element to attach the download functionality to
     */
    setupDownloadButton: function(url, filename, elementId) {
        const button = document.getElementById(elementId);
        if (!button) return;
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading indicator
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Downloading...';
            
            PDFDownloader.downloadPDF(url, filename);
            
            // Reset button text after a delay
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Download Complete!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            }, 2000);
        });
    }
}