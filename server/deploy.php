<?php
/**
 * Hidden Garden Zanzibar - Deployment Script
 * Run this script to verify your server setup and get deployment instructions
 */

// Check PHP version
$phpVersion = phpversion();
$requiredVersion = '8.2.28';

echo "=== Hidden Garden Zanzibar - Deployment Check ===\n\n";

// PHP Version Check
echo "1. PHP Version Check:\n";
echo "   Current PHP Version: " . $phpVersion . "\n";
if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo "   ✅ PHP version is compatible\n";
} else {
    echo "   ❌ PHP version is too old. Required: $requiredVersion\n";
}
echo "\n";

// Check required extensions
$requiredExtensions = ['json', 'fileinfo'];
$missingExtensions = [];

echo "2. Required PHP Extensions:\n";
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext extension loaded\n";
    } else {
        echo "   ❌ $ext extension missing\n";
        $missingExtensions[] = $ext;
    }
}
echo "\n";

// Check file permissions
echo "3. File Permissions Check:\n";
$filesToCheck = [
    'server.php' => 'Server file',
    '.htaccess' => 'Apache configuration'
];

foreach ($filesToCheck as $file => $description) {
    if (file_exists($file)) {
        $permissions = substr(sprintf('%o', fileperms($file)), -4);
        echo "   ✅ $description exists (permissions: $permissions)\n";
    } else {
        echo "   ❌ $description missing\n";
    }
}
echo "\n";

// Server configuration
echo "4. Server Configuration:\n";
echo "   Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "   Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "   Script Path: " . __FILE__ . "\n";
echo "\n";

// Email configuration check
echo "5. Email Configuration:\n";
$mailConfig = ini_get('SMTP');
if ($mailConfig) {
    echo "   ✅ SMTP configured: $mailConfig\n";
} else {
    echo "   ⚠️  SMTP not configured in php.ini (will use server default)\n";
}

$sendmailFrom = ini_get('sendmail_from');
if ($sendmailFrom) {
    echo "   ✅ sendmail_from configured: $sendmailFrom\n";
} else {
    echo "   ⚠️  sendmail_from not configured (will be set by server.php)\n";
}

// Check if server.php has email configuration
$serverPhpContent = file_get_contents('server.php');
if (strpos($serverPhpContent, "ini_set('sendmail_from'") !== false) {
    echo "   ✅ server.php has email configuration\n";
} else {
    echo "   ❌ server.php missing email configuration\n";
}
echo "\n";

// Deployment instructions
echo "=== Deployment Instructions ===\n\n";

echo "For Shared Hosting Deployment:\n";
echo "1. Upload all files from 'server/' directory to your hosting\n";
echo "2. Access your server at: https://yourdomain.com/server/server.php\n";
echo "3. Test with: https://yourdomain.com/server/server.php/health\n";
echo "\n";

echo "For Local Development:\n";
echo "1. Navigate to server directory: cd server\n";
echo "2. Start server: php -S localhost:3000 server.php\n";
echo "3. Test with: http://localhost:3000/health\n";
echo "\n";

echo "API Endpoints:\n";
echo "- Health Check: /health\n";
echo "- Debug Info: /debug (shows server variables)\n";
echo "- Send Email: /send-email (POST)\n";
echo "- Upload File: /upload (POST, requires auth)\n";
echo "- Delete File: /delete/{filename} (DELETE, requires auth)\n";
echo "- Delete Folder: /delete-folder/{foldername} (DELETE, requires auth)\n";
echo "- List Files: /files (GET, requires auth)\n";
echo "\n";

echo "Troubleshooting:\n";
echo "If you get 'Not found' errors:\n";
echo "1. Check /health endpoint first\n";
echo "2. Check /debug for server configuration\n";
echo "3. Ensure file permissions are set correctly\n";
echo "4. Verify the script path is accessible\n";
echo "\n";

echo "Authentication:\n";
echo "Include header: indexing: authorizationEnabled\n";
echo "\n";

echo "Email Configuration for Different Hosts:\n";
echo "If emails don't work, update server.php with:\n";
echo "\n";
echo "Hostinger:\n";
echo "ini_set('SMTP', 'smtp.hostinger.com');\n";
echo "ini_set('smtp_port', 587);\n";
echo "\n";
echo "GoDaddy:\n";
echo "ini_set('SMTP', 'relay-hosting.secureserver.net');\n";
echo "ini_set('smtp_port', 25);\n";
echo "\n";
echo "SiteGround:\n";
echo "ini_set('SMTP', 'mail.yourdomain.com');\n";
echo "ini_set('smtp_port', 587);\n";
echo "\n";
echo "SMTP Authentication:\n";
echo "Your Node.js server uses: applications@zanzibaronline.online / kV1nF0bZ1etP2vW3\n";
echo "For PHP, try setting \$useSmtpAuth = false in server.php first\n";
echo "If that doesn't work, contact your hosting provider for SMTP settings\n";
echo "\n";

// Show current configuration
echo "=== Current Configuration ===\n";
echo "Email recipient: applications@zanzibaronline.online\n";
echo "Upload directory: " . realpath(__DIR__ . '/../') . "\n";
echo "Server file location: " . __FILE__ . "\n";
echo "\n";

echo "=== Deployment Complete ===\n";
echo "Your PHP server is ready for deployment!\n";