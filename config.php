<?php
// ===================================================
// DOST GSM: Genel Konfigürasyon - TAM ÇALIŞAN VERSİYON
// Versiyon: 2.2 | Tarih: 2024
// ===================================================

// ----------------------------------------------------
// 0. SESSION BAŞLATMA - EN KRİTİK BÖLÜM
// ----------------------------------------------------
// Bu kısımda kesinlikle hiçbir çıktı/boşluk olmamalı!
if (session_status() === PHP_SESSION_NONE) {
    // Session config ayarları
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    @session_start();
}

// ----------------------------------------------------
// 1. HATA AYIKLAMA AYARLARI
// ----------------------------------------------------
define('DEBUG_MODE', true); // Canlı sunucuda false yapın

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    if (!defined('LOG_PATH')) {
        define('LOG_PATH', dirname(__DIR__) . '/logs');
    }
    ini_set('error_log', LOG_PATH . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    if (!defined('LOG_PATH')) {
        define('LOG_PATH', dirname(__DIR__) . '/logs');
    }
    ini_set('error_log', LOG_PATH . '/php_errors.log');
}

// Log klasörünü oluştur (yoksa)
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}

// ----------------------------------------------------
// 2. GÜVENLİK AYARLARI
// ----------------------------------------------------
define('ENCRYPTION_KEY', 'dostgsm_secure_key_2024_change_this_immediately');

// CSRF Token otomatik oluşturma
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ----------------------------------------------------
// 3. VERİTABANI (DB) AYARLARI
// ----------------------------------------------------
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'dostgsmcp_Dostgsm'); 
define('DB_USER', 'dostgsmcp_dostgsmadmin'); 
define('DB_PASS', 'Dost0051.');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Veritabanı Bağlantısı (PDO ile güvenli bağlantı)
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
            if (DEBUG_MODE) {
                die(json_encode(['status' => 'error', 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
            } else {
                die(json_encode(['status' => 'error', 'message' => 'Sistem geçici olarak hizmet veremiyor.']));
            }
        }
    }
    
    return $pdo;
}

$pdo = getDBConnection();

// ----------------------------------------------------
// 4. UYGULAMA VE GENEL AYARLAR
// ----------------------------------------------------
define('APP_NAME', 'Dost GSM Yönetim Sistemi');
define('APP_VERSION', '2.2');
define('APP_URL', 'https://dostgsm.com');
define('APP_TIMEZONE', 'Europe/Istanbul');

// Zaman dilimi ayarı
date_default_timezone_set(APP_TIMEZONE);

// Oturum ve Token Ayarları
define('TOKEN_EXPIRY', 180); // Giriş kodu geçerlilik süresi (3 dakika)
define('SESSION_TIMEOUT', 60 * 60); // Oturum zaman aşımı (1 saat)
define('REMEMBER_ME_EXPIRY', 60 * 60 * 24 * 30); // "Beni Hatırla" çerez süresi (30 gün)

// ----------------------------------------------------
// 5. EMAIL AYARLARI
// ----------------------------------------------------
define('MAIL_HOST', 'mail.dostgsm.com'); 
define('MAIL_USER', 'sdonmez@dostgsm.com'); 
define('MAIL_PASS', 'Dost0051.'); 
define('MAIL_PORT', 465); 
define('MAIL_SECURE', 'ssl'); 
define('MAIL_FROM_EMAIL', MAIL_USER);
define('MAIL_FROM_NAME', 'DOST GSM Destek Sistemi');
define('MAIL_DEBUG', 0);

// ----------------------------------------------------
// 6. DOSYA VE YOL AYARLARI
// ----------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
if (!defined('LOG_PATH')) {
    define('LOG_PATH', ROOT_PATH . '/logs');
}

// ----------------------------------------------------
// 7. "BENİ HATIRLA" OTOMATİK GİRİŞ FONKSİYONU
// ----------------------------------------------------
function handleRememberMeLogin($pdo) {
    if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_me_dostgsm'])) {
        
        $cookie_value = $_COOKIE['remember_me_dostgsm'];
        
        // Çerezin format kontrolü
        if (strpos($cookie_value, ':') === false) {
            setcookie('remember_me_dostgsm', '', time() - 3600, '/');
            return false;
        }
        
        list($user_id, $remember_token) = explode(':', $cookie_value, 2);
        
        if (empty($user_id) || empty($remember_token)) {
            setcookie('remember_me_dostgsm', '', time() - 3600, '/');
            return false;
        }
        
        $user_id = (int)$user_id;
        $remember_token_hash = hash('sha256', $remember_token);

        try {
            $stmt = $pdo->prepare("SELECT id, role, remember_token_expiry FROM users WHERE id = ? AND remember_token = ?");
            $stmt->execute([$user_id, $remember_token_hash]);
            $user = $stmt->fetch();

            if ($user && time() < strtotime($user['remember_token_expiry'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['user_role'] = $user['role'];
                
                // Token'ı yenile (güvenlik için)
                refreshRememberMeToken($pdo, $user_id);
                return true;
            } else {
                setcookie('remember_me_dostgsm', '', time() - 3600, '/');
                return false;
            }
        } catch (Exception $e) {
            error_log("Remember me login error: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

// ----------------------------------------------------
// 8. YARDIMCI FONKSİYONLAR
// ----------------------------------------------------
function refreshRememberMeToken($pdo, $user_id) {
    $new_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $new_token);
    $expiry = date('Y-m-d H:i:s', time() + REMEMBER_ME_EXPIRY);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
        $stmt->execute([$token_hash, $expiry, $user_id]);
        
        setcookie('remember_me_dostgsm', $user_id . ':' . $new_token, time() + REMEMBER_ME_EXPIRY, '/', '', true, true);
    } catch (Exception $e) {
        error_log("Token refresh error: " . $e->getMessage());
    }
}

function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    return (strlen($cleaned) >= 10 && strlen($cleaned) <= 11);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Brute Force Koruması için yardımcı fonksiyonlar
function checkBruteForce($identifier, $pdo, $max_attempts = 5, $timeframe = 900) {
    $valid_time = time() - $timeframe;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                              WHERE identifier = ? AND attempt_time > ?");
        $stmt->execute([$identifier, date('Y-m-d H:i:s', $valid_time)]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= $max_attempts;
    } catch (Exception $e) {
        error_log("Brute force check error: " . $e->getMessage());
        return false;
    }
}

function recordLoginAttempt($identifier, $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (identifier, attempt_time, ip_address) VALUES (?, NOW(), ?)");
        $stmt->execute([$identifier, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) {
        error_log("Login attempt record error: " . $e->getMessage());
    }
}

// ----------------------------------------------------
// 9. BRUTE FORCE TABLOSU KONTROLÜ
// ----------------------------------------------------
function createBruteForceTable($pdo) {
    try {
        // Önce tablonun var olup olmadığını kontrol et
        $tableExists = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->fetch();
        
        if (!$tableExists) {
            $pdo->exec("
                CREATE TABLE login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    identifier VARCHAR(255) NOT NULL,
                    attempt_time DATETIME NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    INDEX idx_identifier_time (identifier, attempt_time),
                    INDEX idx_ip_time (ip_address, attempt_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            error_log("Brute force table created successfully");
        }
    } catch (Exception $e) {
        error_log("Brute force table creation error: " . $e->getMessage());
    }
}

// Tabloyu oluştur (eğer yoksa)
createBruteForceTable($pdo);

// ----------------------------------------------------
// 10. OTOMATİK GİRİŞ KONTROLÜNÜ BAŞLAT
// ----------------------------------------------------
handleRememberMeLogin($pdo);

// ----------------------------------------------------
// 11. GÜVENLİK HEADER'LARI
// ----------------------------------------------------
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    // HSTS sadece HTTPS'te çalışsın
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

?>