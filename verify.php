<?php
// DOST GSM: Token Doğrulama ve Oturum Açma İşlemi
require_once 'includes/config.php'; 

// **TÜRKİYE SAAT DİLİMİ AYARI**
date_default_timezone_set('Europe/Istanbul'); 

// Güvenlik için token ve e-posta kontrolü
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    header("Location: index.php");
    exit();
}

$token = $_GET['token'];
$email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
$token_hash = hash('sha256', $token);

// 1. Kullanıcı ve Token Kontrolü
$stmt = $pdo->prepare("
    SELECT 
        t.user_id, t.expires_at, u.role
    FROM 
        login_tokens t
    JOIN 
        users u ON t.user_id = u.id
    WHERE 
        t.token_hash = ? AND u.email = ?
");
$stmt->execute([$token_hash, $email]);
$data = $stmt->fetch();

if (!$data) {
    // Token bulunamadı veya e-posta eşleşmedi
    die("Hata: Geçersiz giriş linki veya kullanıcı bulunamadı.");
}

$user_id = $data['user_id'];
$user_role = $data['role']; // Kullanıcının rolünü çektik!
$expiry_timestamp = strtotime($data['expires_at']);

// 2. Süre Kontrolü
if (time() > $expiry_timestamp) {
    // Token süresi dolmuşsa
    $pdo->prepare("DELETE FROM login_tokens WHERE user_id = ?")->execute([$user_id]);
    die("Hata: Giriş kodunun süresi dolmuştur. Lütfen yeni bir kod talep edin.");
}

// 3. BAŞARILI GİRİŞ İŞLEMLERİ

// Token'ı hemen sil (Tek kullanımlık olması için)
$pdo->prepare("DELETE FROM login_tokens WHERE user_id = ?")->execute([$user_id]);

// Oturumu Başlatma ve Rolü Kaydetme (KRİTİK)
$_SESSION['user_id'] = $user_id;
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['user_role'] = $user_role; // Rolü oturuma kaydettik!

// "Beni Hatırla" (Tarayıcıya Güven) Çerezi
$remember_token = bin2hex(random_bytes(64));
$cookie_expiry = time() + REMEMBER_ME_EXPIRY;

// Güvenli Çerez Ayarı
setcookie(
    'remember_me_dostgsm', 
    $remember_token, 
    [
        'expires' => $cookie_expiry, 
        'path' => '/', 
        'secure' => true, // Sadece HTTPS üzerinde çalışır
        'httponly' => true // JavaScript erişimini engeller
    ]
); 

// 4. BAŞARILI YÖNLENDİRME (Yeni sekme sorununu çözen JavaScript ile)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Giriş Başarılı | Dost GSM</title>
</head>
<body>
<p>Yönetim paneline yönlendiriliyorsunuz... Lütfen bekleyin.</p>
<script>
    const targetURL = "admin/dashboard.php";
    
    // Yönlendirme mantığı (Yeni sekme sorununu çözer)
    if (window.opener && !window.opener.closed) {
        window.opener.location.href = targetURL;
        window.close(); // Bu sekmenin kapanmasını dener
    } else {
        window.location.href = targetURL;
    }
</script>
</body>
</html>
<?php
exit();