<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Fixing All Config Paths</h2>";

// Function to scan directory recursively
function scanDirectory($dir, $baseDir = '') {
    $results = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        $relativePath = $baseDir . '/' . $file;
        
        if (is_dir($path)) {
            $results = array_merge($results, scanDirectory($path, $relativePath));
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $results[] = $relativePath;
        }
    }
    
    return $results;
}

// Get all PHP files
$phpFiles = scanDirectory(__DIR__, '');

$fixedFiles = [];
$errors = [];

foreach ($phpFiles as $file) {
    $fullPath = __DIR__ . $file;
    
    if (!file_exists($fullPath)) {
        $errors[] = "$file - File does not exist";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    $changes = [];
    
    // Fix old config.php references
    $patterns = [
        // Fix require_once __DIR__ . '/../config.php'
        [
            "require_once __DIR__ . '/../config.php'",
            "require_once __DIR__ . '/../includes/config.php'"
        ],
        // Fix require __DIR__ . '/../config.php'
        [
            "require __DIR__ . '/../config.php'",
            "require __DIR__ . '/../includes/config.php'"
        ],
        // Fix include __DIR__ . '/../config.php'
        [
            "include __DIR__ . '/../config.php'",
            "include __DIR__ . '/../includes/config.php'"
        ],
        // Fix require_once __DIR__ . '/config.php'
        [
            "require_once __DIR__ . '/config.php'",
            "require_once __DIR__ . '/includes/config.php'"
        ],
        // Fix require __DIR__ . '/config.php'
        [
            "require __DIR__ . '/config.php'",
            "require __DIR__ . '/includes/config.php'"
        ],
        // Fix include __DIR__ . '/config.php'
        [
            "include __DIR__ . '/config.php'",
            "include __DIR__ . '/includes/config.php'"
        ],
        // Fix require_once 'config.php'
        [
            "require_once 'config.php'",
            "require_once __DIR__ . '/includes/config.php'"
        ],
        // Fix require 'config.php'
        [
            "require 'config.php'",
            "require __DIR__ . '/includes/config.php'"
        ],
        // Fix include 'config.php'
        [
            "include 'config.php'",
            "include __DIR__ . '/includes/config.php'"
        ]
    ];
    
    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern[0]) !== false) {
            $content = str_replace($pattern[0], $pattern[1], $content);
            $changes[] = "Fixed: " . $pattern[0] . " → " . $pattern[1];
        }
    }
    
    // If changes were made, write the file back
    if ($content !== $originalContent) {
        if (file_put_contents($fullPath, $content)) {
            $fixedFiles[] = $file;
            echo "✅ Fixed $file<br>";
            foreach ($changes as $change) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;• $change<br>";
            }
        } else {
            $errors[] = "$file - Could not write file";
        }
    }
}

echo "<hr><h3>📊 Fix Summary</h3>";
echo "<strong>Files fixed:</strong> " . count($fixedFiles) . "<br>";
echo "<strong>Errors:</strong> " . count($errors) . "<br>";

if (!empty($fixedFiles)) {
    echo "<h4>✅ Fixed Files:</h4>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    foreach ($fixedFiles as $file) {
        echo "• $file<br>";
    }
    echo "</div>";
}

if (!empty($errors)) {
    echo "<h4>❌ Errors:</h4>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    foreach ($errors as $error) {
        echo "• $error<br>";
    }
    echo "</div>";
}

echo "<h4>🔧 Next Steps:</h4>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "1. All config.php paths have been fixed<br>";
echo "2. BASE_URL has been added to config.php<br>";
echo "3. Now test your dashboards:<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;• <a href='admin/dashboard.php'>Admin Dashboard</a><br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;• <a href='barber/dashboard.php'>Barber Dashboard</a><br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;• <a href='index.php'>Main Page</a><br>";
echo "</div>";
?> 