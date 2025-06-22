<?php
// Ensure sessions are properly started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize payment_message array if not set
if (!isset($_SESSION['payment_message']) || !is_array($_SESSION['payment_message'])) {
    $_SESSION['payment_message'] = [];
}

// Environment detection
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'localhost') === false);

// Base URL configuration
$protocol = $isProduction ? 'https://' : 'http://';
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/c2c_platform/');
define('UPLOAD_BASE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/c2c_platform/assets/images/products/');

// Database configuration LOCAL
//define('DB_HOST', 'localhost');
//define('DB_USER', 'root');
//define('DB_PASS', '');
//define('DB_NAME', 'platform_db');

// Database configuration ONLINE
define('DB_HOST', 'sql304.infinityfree.com');
define('DB_USER', 'if0_39295150');
define('DB_PASS', 'dIqYHUTIUcJv9I');
define('DB_NAME', 'if0_39295150_platform_db');

/** online hosting details
 * home:            https://onberg.infinityfreeapp.com/c2c_platform/
 * MYSQL username:  if0_39295150
 * pswrd:           dIqYHUTIUcJv9I
 * MYSQL Host name: sql304.infinityfree.com
 * DB name:         if0_39295150_platform_db
 * 
 * Local hosting details:
 * localhost
 * root
 * 
 * platform_db
 */

// Error reporting
ini_set('display_errors', !$isProduction);
error_reporting($isProduction ? E_ALL & ~E_DEPRECATED & ~E_STRICT : E_ALL);

// logging
define('LOG_FILE', __DIR__ . '/upload_errors.log');
ini_set('error_log', LOG_FILE);
ini_set('log_errors', 1);

// Database connection class
class Database
{
    private $conn;

    public function __construct()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

/// /// /// Security functions /// /// ///
function sanitizeInput($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

/// /// /// Helper function to check if user is seller /// /// ///
function isSeller($db)
{
    if (!isLoggedIn())
        return false;

    if (!isset($_SESSION['is_seller'])) {
        $user = new User($db);
        $_SESSION['is_seller'] = $user->isSeller($_SESSION['user_id']);
    }

    return $_SESSION['is_seller'];
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/// /// Messaging system helpers /// ///

// Helper functions - these should be moved to a utilities file eventually
function truncateMessage($text, $length)
{
    if (strlen($text) > $length) {
        return htmlspecialchars(substr($text, 0, $length)) . '...';
    }
    return htmlspecialchars($text);
} // truncateMessage()

function formatMessageTime($timestamp)
{
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->days === 0) {
        return $date->format('H:i');
    } elseif ($diff->days === 1) {
        return 'Yesterday';
    } elseif ($diff->days < 7) {
        return $date->format('D');
    } else {
        return $date->format('M j');
    }
} // formatMessageTime()

// Returns a truncated message cleanly
function displayMessageContent($text, $length = null)
{
    if ($text === null)
        return 'No messages yet';
    $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return $length ? htmlspecialchars_decode(truncateMessage($safeText, $length)) : htmlspecialchars_decode($safeText);
} // displayMessageContent()

/// ///

function getStatusColor($status)
{
    //error_log("Getting color for status: " . $status); // Debug

    $status = strtolower(trim($status ?? ''));
    $colors = [
        'completed' => 'success',
        'processing' => 'primary',
        'shipped' => 'info',
        'cancelled' => 'danger',
        'pending' => 'warning'
    ];

    return $colors[$status] ?? 'secondary';
} // getStatusColor()

/// /// /// Final imports /// /// ///
require_once __DIR__ . '/src/User.php';
?>
