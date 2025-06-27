<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Comprehensive File Scanner</h2>";

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

echo "<h3>📁 Found " . count($phpFiles) . " PHP files</h3>";

$issues = [];
$workingFiles = [];

foreach ($phpFiles as $file) {
    $fullPath = __DIR__ . $file;
    
    echo "<hr><h4>🔍 Scanning: $file</h4>";
    
    // Check if file exists
    if (!file_exists($fullPath)) {
        echo "❌ File does not exist<br>";
        $issues[] = "$file - File does not exist";
        continue;
    }
    
    // Check file permissions
    $perms = fileperms($fullPath);
    $perms_octal = substr(sprintf('%o', $perms), -4);
    echo "📄 Permissions: $perms_octal<br>";
    
    // Check file size
    $size = filesize($fullPath);
    echo "📏 Size: " . number_format($size) . " bytes<br>";
    
    // Check for syntax errors
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "❌ <strong>Syntax Error:</strong><br>";
        foreach ($output as $line) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;$line<br>";
        }
        $issues[] = "$file - Syntax error: " . implode(' ', $output);
    } else {
        echo "✅ Syntax OK<br>";
    }
    
    // Read file content to check for specific issues
    $content = file_get_contents($fullPath);
    
    // Check for problematic patterns
    $problems = [];
    
    // Check for old config.php references
    if (strpos($content, "require_once __DIR__ . '/../includes/config.php'") !== false ||
        strpos($content, "require __DIR__ . '/../includes/config.php'") !== false ||
        strpos($content, "include __DIR__ . '/../includes/config.php'") !== false) {
        $problems[] = "Old config.php path reference";
    }
    
    // Check for missing semicolons at end of PHP tags
    if (preg_match('/\?>\s*$/m', $content) && !preg_match('/\?>\s*$/m', trim($content))) {
        $problems[] = "Missing semicolon after closing PHP tag";
    }
    
    // Check for undefined constants
    if (strpos($content, 'BASE_URL') !== false && strpos($content, "define('BASE_URL'") === false) {
        $problems[] = "BASE_URL constant used but not defined";
    }
    
    // Check for undefined functions
    if (strpos($content, 'password_hash') !== false && !function_exists('password_hash')) {
        $problems[] = "password_hash function not available (PHP < 5.5)";
    }
    
    // Check for database connection issues
    if (strpos($content, 'new mysqli') !== false && strpos($content, 'DB_HOST') !== false) {
        $problems[] = "Direct mysqli usage (should use Database class)";
    }
    
    // Check for session issues
    if (strpos($content, '$_SESSION') !== false && strpos($content, 'session_start()') === false) {
        $problems[] = "Session variables used without session_start()";
    }
    
    // Check for file inclusion issues
    if (preg_match_all('/(require|include)(_once)?\s*\([^)]+\)/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            if (strpos($match, '__DIR__') === false && strpos($match, 'dirname(__FILE__)') === false) {
                $problems[] = "Relative include path: $match";
            }
        }
    }
    
    if (!empty($problems)) {
        echo "⚠️ <strong>Potential Issues:</strong><br>";
        foreach ($problems as $problem) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;• $problem<br>";
        }
        $issues[] = "$file - " . implode(', ', $problems);
    } else {
        echo "✅ No obvious issues found<br>";
        $workingFiles[] = $file;
    }
}

// Summary
echo "<hr><h3>📊 Scan Summary</h3>";
echo "<strong>Total files scanned:</strong> " . count($phpFiles) . "<br>";
echo "<strong>Files with issues:</strong> " . count($issues) . "<br>";
echo "<strong>Files OK:</strong> " . count($workingFiles) . "<br>";

if (!empty($issues)) {
    echo "<h4>🚨 Issues Found:</h4>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    foreach ($issues as $issue) {
        echo "• $issue<br>";
    }
    echo "</div>";
} else {
    echo "<h4>✅ No issues found!</h4>";
}

// Test specific problematic files
echo "<h3>🧪 Testing Specific Files</h3>";

$testFiles = [
    'admin/dashboard.php',
    'barber/dashboard.php',
    'index.php',
    'login.php'
];

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    echo "<h4>Testing: $testFile</h4>";
    
    if (!file_exists($fullPath)) {
        echo "❌ File not found<br>";
        continue;
    }
    
    // Try to include the file in a controlled way
    try {
        // Create a test environment
        $testContent = "<?php\n";
        $testContent .= "error_reporting(E_ALL);\n";
        $testContent .= "ini_set('display_errors', 1);\n";
        $testContent .= "echo 'Testing $testFile...<br>';\n";
        
        // Read the original file
        $originalContent = file_get_contents($fullPath);
        
        // Remove the opening PHP tag and add our test code
        $originalContent = preg_replace('/^<\?php\s*/', '', $originalContent);
        $testContent .= $originalContent;
        
        // Create a temporary test file
        $tempFile = __DIR__ . '/temp_test_' . basename($testFile);
        file_put_contents($tempFile, $testContent);
        
        // Test the file
        $output = [];
        $returnCode = 0;
        exec("php " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "❌ <strong>Error when testing:</strong><br>";
            foreach ($output as $line) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;$line<br>";
            }
        } else {
            echo "✅ File can be executed without errors<br>";
        }
        
        // Clean up
        unlink($tempFile);
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
    }
}

echo "<hr><h3>🔧 Recommendations</h3>";
if (!empty($issues)) {
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Fix these issues first:</strong><br>";
    echo "1. Fix any syntax errors<br>";
    echo "2. Update old config.php references to includes/config.php<br>";
    echo "3. Ensure all required files exist<br>";
    echo "4. Check file permissions<br>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ All files look good! The issue might be:<br>";
    echo "• Server configuration<br>";
    echo "• .htaccess file issues<br>";
    echo "• PHP version compatibility<br>";
    echo "• Missing server modules<br>";
    echo "</div>";
}
?> 