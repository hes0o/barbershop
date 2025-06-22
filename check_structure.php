<?php
echo "<h2>📁 Directory Structure Check</h2>";

echo "<h3>Current Location</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h3>Parent Directory Contents</h3>";
$parent_dir = dirname(__DIR__);
if (is_dir($parent_dir)) {
    $contents = scandir($parent_dir);
    echo "Parent directory ($parent_dir) contains:<br>";
    foreach ($contents as $item) {
        if ($item != '.' && $item != '..') {
            $full_path = $parent_dir . '/' . $item;
            if (is_dir($full_path)) {
                echo "📁 $item/ (directory)<br>";
            } else {
                echo "📄 $item (file)<br>";
            }
        }
    }
} else {
    echo "Cannot access parent directory<br>";
}

echo "<h3>Current Directory Contents</h3>";
$current_contents = scandir(__DIR__);
echo "Current directory contains:<br>";
foreach ($current_contents as $item) {
    if ($item != '.' && $item != '..') {
        $full_path = __DIR__ . '/' . $item;
        if (is_dir($full_path)) {
            echo "📁 $item/ (directory)<br>";
        } else {
            echo "📄 $item (file)<br>";
        }
    }
}

echo "<h3>URL Information</h3>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Yes' : 'No') . "<br>";
echo "Full URL: " . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "<br>";
?> 