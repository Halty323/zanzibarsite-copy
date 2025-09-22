<?php
declare(strict_types=1);

// Configure email settings for shared hosting
ini_set('SMTP', 'mail.hosting.reg.ru');
ini_set('smtp_port', 465);
ini_set('sendmail_from', 'applications@zanzibaronline.online');

// For SMTP authentication, we need to use a different approach
// Option 1: Use server.php with authentication (recommended)
$useSmtpAuth = true; // Set to false if your hosting doesn't require auth

if ($useSmtpAuth) {
    // These credentials match your Node.js server
    $smtpUser = 'applications@zanzibaronline.online';
    $smtpPass = 'kV1nF0bZ1etP2vW3';
}

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, indexing');
header('Content-Type: application/json');

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Authentication middleware
function authenticate(): bool {
    $headers = getallheaders();
    return isset($headers['indexing']) && $headers['indexing'] === 'authorizationEnabled';
}

// Send JSON response
function sendResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Send plain text response
function sendTextResponse(string $text, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain');
    echo $text;
    exit();
}

// Build file tree recursively
function buildFileTree(string $dirPath, string $prefix = ''): string {
    $result = '';
    if (!is_dir($dirPath)) {
        return $result;
    }

    $files = scandir($dirPath);
    $dirs = array_filter($files, function($file) use ($dirPath) {
        return $file !== '.' && $file !== '..' && is_dir($dirPath . '/' . $file);
    });

    $dirs = array_values($dirs); // Reset array keys

    foreach ($dirs as $index => $dir) {
        $isLast = $index === count($dirs) - 1;
        $connector = $isLast ? '└── ' : '├── ';
        $nextPrefix = $isLast ? '    ' : '│   ';

        $result .= $prefix . $connector . $dir . "\n";
        $result .= buildFileTree($dirPath . '/' . $dir, $prefix . $nextPrefix);
    }

    return $result;
}

// Route handling - handle different server environments
$method = 'GET';
$path = '/';

// Try different methods to get request info
if (isset($_SERVER['REQUEST_METHOD'])) {
    $method = $_SERVER['REQUEST_METHOD'];
} elseif (isset($_ENV['REQUEST_METHOD'])) {
    $method = $_ENV['REQUEST_METHOD'];
} elseif (isset($HTTP_SERVER_VARS['REQUEST_METHOD'])) {
    $method = $HTTP_SERVER_VARS['REQUEST_METHOD'];
}

// Get path from various sources
if (isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
} elseif (isset($_ENV['REQUEST_URI'])) {
    $path = parse_url($_ENV['REQUEST_URI'], PHP_URL_PATH) ?: '/';
} elseif (isset($HTTP_SERVER_VARS['REQUEST_URI'])) {
    $path = parse_url($HTTP_SERVER_VARS['REQUEST_URI'], PHP_URL_PATH) ?: '/';
} elseif (isset($_SERVER['SCRIPT_NAME'])) {
    $path = $_SERVER['SCRIPT_NAME'];
} else {
    $path = '/';
}

// Ensure path starts with /
if (empty($path) || $path[0] !== '/') {
    $path = '/' . ltrim($path, '/');
}

// Remove trailing slash except for root
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Handle direct script access
if ($path === '/server.php') {
    $path = '/';
}

switch ($path) {
    case '/send-email':
        if ($method !== 'POST') {
            sendResponse(['error' => 'Method not allowed'], 405);
        }

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendResponse(['error' => 'Invalid JSON'], 400);
        }

        $firstName = $input['first-name'] ?? '';
        $lastName = $input['last-name'] ?? '';
        $email = $input['email'] ?? '';
        $telephone = $input['telephone'] ?? '';
        $subject = $input['subject'] ?? '';
        $message = $input['comment'] ?? '';

        // Basic validation
        if (empty($email) || empty($subject) || empty($message)) {
            sendResponse(['error' => 'Missing required fields'], 400);
        }

        // Email configuration using PHP built-in mail function
        $to = 'applications@zanzibaronline.online';
        $subjectLine = 'Contact Form Submission: ' . $subject;
        $messageBody = "Name: $firstName $lastName\nEmail: $email\nTelephone: $telephone\nMessage: $message";
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send email
        if (mail($to, $subjectLine, $messageBody, $headers)) {
            sendResponse(['message' => 'Email sent successfully']);
        } else {
            // If mail fails, provide helpful error message
            $errorMsg = 'Failed to send email. ';

            // Check if SMTP auth is needed
            if ($useSmtpAuth) {
                $errorMsg .= 'Your hosting may require SMTP authentication. ';
                $errorMsg .= 'Try setting $useSmtpAuth = false in server.php or contact your hosting provider.';
            } else {
                $errorMsg .= 'Check your hosting email configuration.';
            }

            error_log('Email sending failed: ' . $errorMsg);
            sendResponse(['error' => $errorMsg], 500);
        }
        break;

    case '/upload':
        if ($method !== 'POST') {
            sendResponse(['error' => 'Method not allowed'], 405);
        }

        if (!authenticate()) {
            sendResponse(['error' => 'Unauthorized'], 401);
        }

        if (!isset($_FILES['file'])) {
            sendResponse(['error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];
        $uploadPath = __DIR__ . '/../' . basename($file['name']);

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            sendResponse(['message' => 'File uploaded successfully']);
        } else {
            sendResponse(['error' => 'Failed to upload file'], 500);
        }
        break;

    case (preg_match('/^\/delete\/(.+)$/', $path, $matches) ? $path : ''):
        if ($method !== 'DELETE') {
            sendResponse(['error' => 'Method not allowed'], 405);
        }

        if (!authenticate()) {
            sendResponse(['error' => 'Unauthorized'], 401);
        }

        $filename = $matches[1] ?? '';
        $filePath = __DIR__ . '/../' . $filename;

        if (!file_exists($filePath)) {
            sendResponse(['error' => 'File not found'], 404);
        }

        if (unlink($filePath)) {
            sendResponse(['message' => 'File deleted successfully']);
        } else {
            sendResponse(['error' => 'Error deleting file'], 500);
        }
        break;

    case (preg_match('/^\/delete-folder\/(.+)$/', $path, $matches) ? $path : ''):
        if ($method !== 'DELETE') {
            sendResponse(['error' => 'Method not allowed'], 405);
        }

        if (!authenticate()) {
            sendResponse(['error' => 'Unauthorized'], 401);
        }

        $foldername = $matches[1] ?? '';
        $folderPath = __DIR__ . '/../' . $foldername;

        if (!is_dir($folderPath)) {
            sendResponse(['error' => 'Folder not found'], 404);
        }

        // Remove directory recursively
        $files = array_diff(scandir($folderPath), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $folderPath . '/' . $file;
            if (is_dir($filePath)) {
                rmdir_recursive($filePath);
            } else {
                unlink($filePath);
            }
        }

        if (rmdir($folderPath)) {
            sendResponse(['message' => 'Folder deleted successfully']);
        } else {
            sendResponse(['error' => 'Error deleting folder'], 500);
        }
        break;

    case '/files':
        if ($method !== 'GET') {
            sendResponse(['error' => 'Method not allowed'], 405);
        }

        if (!authenticate()) {
            sendResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $rootPath = __DIR__ . '/../';
            $fileTree = buildFileTree($rootPath);
            sendTextResponse($fileTree);
        } catch (Exception $e) {
            sendResponse(['error' => 'Error reading file tree'], 500);
        }
        break;

    case '/health':
        sendResponse([
            'status' => 'ok',
            'message' => 'PHP Server is running',
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? ($_ENV['SERVER_SOFTWARE'] ?? 'Unknown'),
            'request_method' => $method,
            'request_path' => $path,
            'server_vars' => [
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'not set',
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
                'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
                'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'not set'
            ]
        ]);
        break;

    case '/debug':
        sendResponse([
            'debug' => 'Server debugging information',
            'all_server_vars' => $_SERVER,
            'all_env_vars' => $_ENV,
            'method' => $method,
            'path' => $path,
            'current_file' => __FILE__,
            'current_dir' => __DIR__
        ]);
        break;

    default:
        sendResponse(['error' => 'Not found'], 404);
        break;
}

// Helper function for recursive directory removal
function rmdir_recursive(string $dir): bool {
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            rmdir_recursive($filePath);
        } else {
            unlink($filePath);
        }
    }
    return rmdir($dir);
}