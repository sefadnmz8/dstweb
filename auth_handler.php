<?php
// DOST GSM: Auth Handler - E-POSTA GÖNDERİMİ AKTİF

// Hata ayıklamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PHPMailer kütüphanelerini dahil edelim
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

// Config dosyasını yükle
$config_path = __DIR__ . '/includes/config.php';
if (!file_exists($config_path)) {
    die(json_encode(['status' => 'error', 'message' => 'Sistem yapılandırma dosyası bulunamadı.']));
}
require_once $config_path;

// JSON başlığı gönder
header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(['status' => 'error', 'message' => 'Geçersiz istek türü.']));
}

// 'action' parametresi var mı kontrol et
if (!isset($_POST['action'])) {
    die(json_encode(['status' => 'error', 'message' => 'İşlem parametresi eksik.']));
}

try {
    $action = $_POST['action'];
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // E-postaya göre kullanıcıyı bulan fonksiyon
    function findUserByEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // KOD GÖNDERME İŞLEMİ
    if ($action === 'send_code') {
        if (!$email) {
            throw new Exception('Lütfen geçerli bir e-posta adresi girin.');
        }

        $user = findUserByEmail($pdo, $email);
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Bu e-posta adresi sistemde kayıtlı değil.']);
            exit();
        }
        
        // 6 haneli benzersiz kod üret
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $code_hash = hash('sha256', $code); 

        // Kullanıcının eski token'larını temizle
        $pdo->prepare("DELETE FROM login_tokens WHERE user_id = ?")->execute([$user['id']]);
        
        // Yeni token'ı veritabanına kaydet (3 dakika geçerli)
        $expiry = time() + TOKEN_EXPIRY;
        $stmt = $pdo->prepare("INSERT INTO login_tokens (user_id, token_hash, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
        $stmt->execute([$user['id'], $code_hash, $expiry]);
        
        // E-POSTA GÖNDERME BÖLÜMÜ
        $mail = new PHPMailer(true);
        try {
            // Sunucu Ayarları
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';
            if (defined('MAIL_DEBUG') && MAIL_DEBUG > 0) {
                $mail->SMTPDebug = MAIL_DEBUG;
            }

            // Gönderici ve Alıcı Bilgileri
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($email);

            // E-posta İçeriği
            $mail->isHTML(true);
            $mail->Subject = 'Dost GSM Yönetim Paneli Giriş Kodu';
            $mail->Body    = "Merhaba,<br><br>Yönetim paneline giriş yapmak için doğrulama kodunuz: <b>$code</b><br><br>Bu kod 3 dakika boyunca geçerlidir.<br><br>Saygılarımızla,<br>Dost GSM Destek Ekibi";
            $mail->AltBody = "Yönetim paneline giriş yapmak için doğrulama kodunuz: $code";

            $mail->send();
            
            echo json_encode([
                'status' => 'success_send', 
                'message' => 'Doğrulama kodu e-posta adresinize gönderildi.'
            ]);

        } catch (Exception $e) {
            error_log("PHPMailer Hatası: {$mail->ErrorInfo}");
            throw new Exception("Kod gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.");
        }

    // KOD DOĞRULAMA İŞLEMİ
    } elseif ($action === 'verify_code') {
        $code = $_POST['code'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
            throw new Exception('Lütfen 6 haneli geçerli bir kod girin.');
        }

        if (empty($email)) {
            throw new Exception('E-posta adresi oturumda bulunamadı.');
        }
        
        $code_hash = hash('sha256', $code);

        // Kodu ve süresini doğrula
        $stmt = $pdo->prepare("
            SELECT t.user_id, t.expires_at, u.role 
            FROM login_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE u.email = ? AND t.token_hash = ?
        ");
        $stmt->execute([$email, $code_hash]);
        $data = $stmt->fetch();

        if (!$data) {
            throw new Exception('Girdiğiniz kod geçersiz veya hatalı.');
        }
        
        if (time() > strtotime($data['expires_at'])) {
            $pdo->prepare("DELETE FROM login_tokens WHERE user_id = ?")->execute([$data['user_id']]);
            throw new Exception('Kodun süresi dolmuş. Lütfen yeni bir kod isteyin.');
        }
        
        // GİRİŞ BAŞARILI
        $pdo->prepare("DELETE FROM login_tokens WHERE user_id = ?")->execute([$data['user_id']]);

        // Oturum (Session) bilgilerini ayarla
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user_role'] = $data['role'];
        $_SESSION['user_email'] = $email;

        // "Beni Hatırla" seçeneği işaretliyse çerez oluştur
        if ($remember_me) {
            refreshRememberMeToken($pdo, $data['user_id']); // Bu fonksiyon config.php içinde mevcut
        }
        
        echo json_encode([
            'status' => 'success_login', 
            'redirect' => 'admin/dashboard.php',
            'message' => 'Giriş başarılı! Yönetim paneline yönlendiriliyorsunuz...'
        ]);

    } else {
        throw new Exception('Geçersiz işlem türü.');
    }

} catch (Exception $e) {
    // Hataları logla ve kullanıcıya genel bir mesaj göster
    error_log("Auth Handler Hatası: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

exit();
?>