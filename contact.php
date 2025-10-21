<?php
// DOST GSM: TEKLİF ALMA FORMU İŞLEYİCİ
require_once 'includes/config.php'; 
require_once 'libs/PHPMailer/src/PHPMailer.php';
require_once 'libs/PHPMailer/src/SMTP.php';
require_once 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = ''; // Kullanıcıya gösterilecek başarı/hata mesajı

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['send_quote'])) {
    
    // Form verilerini temizle
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $device = filter_input(INPUT_POST, 'device', FILTER_SANITIZE_STRING);
    $fault = filter_input(INPUT_POST, 'fault', FILTER_SANITIZE_STRING);

    if ($name && $email && $phone && $device && $fault) {
        $mail = new PHPMailer(true);

        try {
            // SMTP Ayarları (config.php'den çekilir)
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';
            
            // Mailin Alıcıları ve İçeriği
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            // Kendi mail adresinize gönderir
            $mail->addAddress(MAIL_USER); 
            // Müşteriye cevap verebilmek için Reply-To olarak ekle
            $mail->addReplyTo($email, $name); 

            $mail->isHTML(true);
            $mail->Subject = "YENİ TEKLİF TALEBİ: {$device} - {$name}";
            $mail->Body    = "<h2>Dost GSM Yeni Teklif Talebi</h2>" .
                             "<hr style='border: 1px solid #ff9900;'>" .
                             "<p><b>Müşteri Adı:</b> " . htmlspecialchars($name) . "</p>" .
                             "<p><b>Telefon:</b> " . htmlspecialchars($phone) . "</p>" .
                             "<p><b>E-posta:</b> " . htmlspecialchars($email) . "</p>" .
                             "<p><b>Cihaz Modeli:</b> " . htmlspecialchars($device) . "</p>" .
                             "<p><b>Arıza/Talep:</b> " . nl2br(htmlspecialchars($fault)) . "</p>" .
                             "<hr>" .
                             "<p style='font-style: italic;'>Lütfen bu talebe hızla geri dönüş yapın.</p>";
            
            $mail->send();
            $message = '<div class="alert alert-success">Teklif talebiniz başarıyla bize ulaştı. En kısa sürede size dönüş yapacağız!</div>';
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Mail gönderilirken bir sorun oluştu. Lütfen direkt telefonla ulaşmayı deneyin.</div>';
            error_log("Teklif Formu Hatası: " . $e->getMessage());
        }
    } else {
        $message = '<div class="alert alert-warning">Lütfen tüm zorunlu alanları doldurun.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif Al | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-header { background-color: #004d99; color: white; padding: 50px 0; margin-bottom: 30px; }
        .page-header h1 { margin-bottom: 0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="images/logo.png" alt="Dost GSM Logo" style="height: 40px; width: auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
    <li class="nav-item"><a class="nav-link" href="index.php">Anasayfa</a></li>
    <li class="nav-item"><a class="nav-link" href="services.php">Hizmetlerimiz</a></li>
    <li class="nav-item"><a class="nav-link" href="tracking.php">Servis Takip</a></li>
    <li class="nav-item"><a class="nav-link" href="about.php">Hakkımızda</a></li>
    <li class="nav-item"><a class="nav-link btn btn-sm btn-warning ms-lg-3" href="contact.php">Teklif Al</a></li>
        </ul>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1 class="display-5"><i class="fas fa-calculator me-2"></i> Hızlı Teklif Alın</h1>
        <p class="lead">Arızanızı bize bildirin, uzman ekibimiz en kısa sürede size fiyat bilgisiyle dönüş yapsın.</p>
    </div>
</div>

<div class="container py-5">
    
    <?= $message ?> <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm p-4">
                <h4 class="mb-4 text-primary">Teklif Talep Formu</h4>
                <form method="POST" action="contact.php">
                    <input type="hidden" name="send_quote" value="1">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Adınız Soyadınız *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-posta Adresiniz *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefon Numaranız *</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="device" class="form-label">Cihaz Marka ve Modeli *</label>
                        <input type="text" class="form-control" id="device" name="device" placeholder="Örn: iPhone 11 Pro Max" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fault" class="form-label">Arıza Tanımı/Talep *</label>
                        <textarea class="form-control" id="fault" name="fault" rows="4" required placeholder="Yaşadığınız sorunu detaylıca açıklayın (Ekran kırık, şarj olmuyor, vb.)"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">Teklif Gönder</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm p-4 h-100 bg-light">
                <h4 class="mb-4 text-primary">Bize Ulaşın</h4>
                <p><i class="fas fa-map-marker-alt me-2"></i> Kale Mah. Matbaa Sk. No:7 Dönmez Kundura Üstü  Niğde/Merkez</p>
                <p><i class="fas fa-phone me-2"></i> +90 (507) 598 53 68</p>
                <p><i class="fas fa-envelope me-2"></i> sdonmez@dostgsm.com</p>
                
                <h5 class="mt-4 text-primary">Çalışma Saatleri</h5>
                <ul class="list-unstyled">
                    <li>Pazartesi - Cuma: 09:00 - 19:30</li>
                    <li>Cumartesi: 09:00 - 19:30</li>
                    <li>Pazar: Kapalı</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2018 Dost GSM Yönetim Sistemi. Profesyonel Tamir Çözümleri.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>