<?php
/**
 * Fix Config Paths Script
 * 
 * This script fixes all the require_once paths for config.php in the barber directory
 * to point to the correct location in the includes directory.
 */

$barberDir = __DIR__ . '/barber/';
$files = glob($barberDir . '*.php');

echo "<h2>Fixing Config Paths in Barber Directory</h2>";

$fixed = 0;
$total = count($files);

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    // Check if file contains the old config path
    if (strpos($content, "require_once __DIR__ . '/../config.php'") !== false) {
        // Replace the old path with the new one
        $newContent = str_replace(
            "require_once __DIR__ . '/../config.php'",
            "require_once __DIR__ . '/../includes/config.php'",
            $content
        );
        
        // Also fix other variations
        $newContent = str_replace(
            "require_once '../config.php'",
            "require_once '../includes/config.php'",
            $newContent
        );
        
        // Write the updated content back to the file
        if (file_put_contents($file, $newContent)) {
            echo "<p style='color: green;'>✓ Fixed: $filename</p>";
            $fixed++;
        } else {
            echo "<p style='color: red;'>✗ Failed to fix: $filename</p>";
        }
    } else {
        echo "<p style='color: blue;'>- No changes needed: $filename</p>";
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>Total files checked: $total</p>";
echo "<p>Files fixed: $fixed</p>";

if ($fixed > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4>✅ Fix Complete!</h4>";
    echo "<p>All config.php paths have been updated. Your barber dashboard should now work correctly.</p>";
    echo "<p><a href='barber/dashboard.php' style='color: #155724; font-weight: bold;'>→ Test Barber Dashboard</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4>ℹ️ No Changes Needed</h4>";
    echo "<p>All files already have the correct config.php paths.</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #555; margin-top: 20px; }
hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
</style> 