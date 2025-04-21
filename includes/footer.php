<?php
// Close the main div
if (!isset($basePath)) {
  $basePath = '';  // Or set a default value based on your project structure
}
echo '</main>';

// Add the footer
echo '<footer class="text-center text-xs text-gray-500 py-4 mt-auto">
        <p>' . APP_NAME . ' v' . APP_VERSION . ' &copy; ' . date('Y') . '</p>
      </footer>';

// Add PWA script
echo '<script src="' . $basePath . 'assets/js/pwa.js"></script>';

// Add app.js script
// echo '<script src="' . $basePath . 'assets/js/app.js"></script>';

// Close HTML tags
echo '</body></html>';

// End output buffering and send the content to the browser
ob_end_flush();
?>