// PWA Installation and Service Worker Registration

// Initialize deferredPrompt for use later to show browser install prompt
let deferredPrompt;

// Register service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            showUpdateNotification();
                        }
                    });
                });
            })
            .catch(error => {
                console.error('ServiceWorker registration failed: ', error);
            });
        
        // Handle service worker updates
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });
    });
}

// Listen for the beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    // Show the install button
    showInstallButton();
});

// Function to show the install button
function showInstallButton() {
    const installButton = document.getElementById('installButton');
    if (installButton) {
        installButton.classList.remove('hidden');
        
        installButton.addEventListener('click', () => {
            // Hide the button
            installButton.classList.add('hidden');
            // Show the prompt
            deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                    showInstallationSuccessMessage();
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        });
    }
}

// Show notification for available update
function showUpdateNotification() {
    const updateToast = document.createElement('div');
    updateToast.id = 'update-toast';
    updateToast.className = 'fixed bottom-20 left-0 right-0 mx-auto w-11/12 max-w-md bg-red-900 text-white p-4 rounded-lg shadow-lg flex justify-between items-center';
    updateToast.innerHTML = `
        <div>
            <p class="font-bold">Update Available</p>
            <p class="text-sm">A new version is available</p>
        </div>
        <button id="update-button" class="bg-white text-red-900 py-1 px-4 rounded">
            Update
        </button>
    `;
    
    document.body.appendChild(updateToast);
    
    document.getElementById('update-button').addEventListener('click', () => {
        navigator.serviceWorker.ready.then(registration => {
            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            updateToast.remove();
        });
    });
}

// Show success message after installation
function showInstallationSuccessMessage() {
    const successToast = document.createElement('div');
    successToast.className = 'fixed bottom-20 left-0 right-0 mx-auto w-11/12 max-w-md bg-green-600 text-white p-4 rounded-lg shadow-lg';
    successToast.innerHTML = `
        <p class="font-bold">Installation Successful!</p>
        <p class="text-sm">Billgix has been installed on your device</p>
    `;
    
    document.body.appendChild(successToast);
    
    setTimeout(() => {
        successToast.remove();
    }, 3000);
}

// Check if the app is being used in standalone mode (installed)
window.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        // The app is installed and running standalone
        document.body.classList.add('app-installed');
    }
});