<?php
// DOST GSM: Güvenli Çıkış İşlemi (Logout)
require_once '../includes/config.php'; 

// "Beni Hatırla" anahtarını veritabanından temizle
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, remember_token_expiry = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Oturum Değişkenlerini Temizle
$_SESSION = array();

// Oturum Çerezini İptal Et
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// "Beni Hatırla" Çerezini Tarayıcıdan Temizle
if (isset($_COOKIE['remember_me_dostgsm'])) {
    setcookie('remember_me_dostgsm', '', time() - 3600, '/'); 
}

// Oturumu Yok Et
session_destroy();

// Kullanıcıyı Kurumsal Ana Sayfaya Yönlendir
header("Location: ../index.php?status=logout_success");
exit();
?>