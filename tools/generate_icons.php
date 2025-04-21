<?php
/**
 * Icon Generator Script for PWA
 * 
 * This script generates various icon sizes required for PWA from a source image.
 * It requires the GD library to be installed in PHP.
 * 
 * Usage: php generate_icons.php [source_image.png]
 */

// Check if GD is installed
if (!extension_loaded('gd')) {
    die("Error: GD library is required but not installed on this server.\n");
}

// Set appropriate error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$basePath = dirname(__DIR__) . '/';
$sourcePath = isset($argv[1]) ? $argv[1] : $basePath . 'source_logo.png';
$iconsDir = $basePath . 'assets/icons/';

// Check if source image exists
if (!file_exists($sourcePath)) {
    die("Error: Source image not found at: $sourcePath\n");
}

// Create icons directory if it doesn't exist
if (!is_dir($iconsDir)) {
    if (!mkdir($iconsDir, 0755, true)) {
        die("Error: Failed to create icons directory. Please check permissions.\n");
    }
    echo "Created icons directory: $iconsDir\n";
}

// Icon sizes for PWA
$sizes = [
    72, 96, 128, 144, 152, 192, 384, 512
];

// Splash screen sizes for iOS
$splashSizes = [
    '640x1136', '750x1334', '1242x2208', '1125x2436', 
    '1536x2048', '1668x2224', '2048x2732'
];

// Load the source image
$sourceImage = imagecreatefrompng($sourcePath);
if (!$sourceImage) {
    die("Error: Failed to load source image.\n");
}

// Preserve transparency
imagealphablending($sourceImage, false);
imagesavealpha($sourceImage, true);

// Generate icons
foreach ($sizes as $size) {
    $iconPath = $iconsDir . "icon-{$size}x{$size}.png";
    echo "Generating $iconPath...\n";
    
    // Create a new image with the desired size
    $icon = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    
    // Fill with transparent background
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefill($icon, 0, 0, $transparent);
    
    // Copy and resize the source image
    imagecopyresampled(
        $icon, $sourceImage,
        0, 0, 0, 0,
        $size, $size, imagesx($sourceImage), imagesy($sourceImage)
    );
    
    // Save the icon
    imagepng($icon, $iconPath);
    imagedestroy($icon);
}

// Generate favicon.ico (16x16)
$iconPath = $iconsDir . "favicon.ico";
echo "Generating $iconPath...\n";
$icon = imagecreatetruecolor(16, 16);
imagealphablending($icon, false);
imagesavealpha($icon, true);
$transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
imagefill($icon, 0, 0, $transparent);
imagecopyresampled($icon, $sourceImage, 0, 0, 0, 0, 16, 16, imagesx($sourceImage), imagesy($sourceImage));
imagepng($icon, $iconPath);
imagedestroy($icon);

// Generate splash screens
echo "Generating splash screens...\n";
foreach ($splashSizes as $splashSize) {
    list($width, $height) = explode('x', $splashSize);
    $splashPath = $iconsDir . "splash-$splashSize.png";
    
    // Create splash screen canvas
    $splash = imagecreatetruecolor((int)$width, (int)$height);
    
    // Create background color (you can change this)
    $bgColor = imagecolorallocate($splash, 59, 130, 246); // #3b82f6 - red-900
    imagefill($splash, 0, 0, $bgColor);
    
    // Calculate icon size (40% of the smallest dimension)
    $iconSize = round(min((int)$width, (int)$height) * 0.4);
    
    // Calculate position (center)
    $x = round(((int)$width - $iconSize) / 2);
    $y = round(((int)$height - $iconSize) / 2);
    
    // Copy and resize the source image
    imagecopyresampled(
        $splash, $sourceImage,
        $x, $y, 0, 0,
        $iconSize, $iconSize, imagesx($sourceImage), imagesy($sourceImage)
    );
    
    // Save the splash screen
    imagepng($splash, $splashPath);
    imagedestroy($splash);
    
    echo "Generated $splashPath\n";
}

// Cleanup
imagedestroy($sourceImage);

echo "\nAll icons and splash screens have been generated successfully!\n";
echo "Place a source logo named 'source_logo.png' in the root directory or specify a path when running this script.\n";
echo "Example: php generate_icons.php /path/to/your/logo.png\n";