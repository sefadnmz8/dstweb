<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$message = '';
if (isset($_GET['status'])) {
    $status_messages = [
        'success_mail' => '<div class="alert alert-info text-center">Giriş kodu e-posta adresinize gönderilmiştir. Lütfen kontrol edin.</div>',
        'user_not_found' => '<div class="alert alert-warning text-center">Girilen e-posta adresi yetkili kullanıcılar listesinde bulunmamaktadır.</div>',
        'error_mail' => '<div class="alert alert-danger text-center">Mail gönderilirken bir sorun oluştu. Lütfen yöneticinizle iletişime geçin.</div>',
    ];
    $message = $status_messages[$_GET['status']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dost GSM | Niğde Telefon ve Tablet Tamiri</title>
    <meta name="description" content="Dost GSM - 10 yıllık tecrübe ile Niğde'de garantili telefon ve tablet tamiri. Orijinal parça, şeffaf tamir süreci ve uzman anakart onarımı.">
    <meta name="keywords" content="telefon tamiri, tablet tamiri, Niğde telefon tamir, ekran değişimi, anakart tamiri, iphone tamiri, samsung tamiri">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="/favicon.png"> 

    <style>
        :root {
            --primary-color: #004d99; /* Derin Mavi */
            --secondary-color: #ff9900; /* Turuncu (Vurgu) */
        }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .navbar { background-color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .hero-section {
            background: linear-gradient(135deg, #004d99 0%, #0066cc 100%);
            color: white; padding: 80px 0; text-align: center;
        }
        .feature-box {
            padding: 25px; border-left: 4px solid var(--secondary-color);
            margin-bottom: 30px; transition: all 0.3s ease;
            background: white; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        .feature-box:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .admin-login {
            position: fixed; bottom: 25px; right: 25px; z-index: 1000;
            background: var(--secondary-color); border: none;
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px rgba(255, 153, 0, 0.4); transition: transform 0.3s ease;
        }
        .admin-login:hover { transform: scale(1.1); }
        .footer { background-color: #f8f9fa; color: #6c757d; } /* Footer rengi düzeltildi */
        .footer a { color: #004d99; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Dost GSM Logo" style="max-height: 40px; width: auto;">
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
                    <li class="nav-item"><a class="nav-link btn btn-sm btn-warning ms-lg-3 text-dark" href="contact.php">Teklif Al</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <?= $message ?>
    
    <header class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">2018'den Beri, <span class="text-warning">Tecrübe ve Güvenle</span>.</h1>
            <p class="lead mb-4 fs-5">DOST GSM ile cihazınız emin ellerde. Orijinal parça, garantili işçilik ve şeffaf tamir süreci.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                <a href="https://wa.me/905075985368?text=Merhaba,%20cihazım%20için%20fiyat%20almak%20istiyorum." target="_blank" class="btn btn-success btn-lg fw-bold shadow px-4 py-3"><i class="fab fa-whatsapp me-2"></i> WhatsApp'tan Sor</a>
                <a href="tel:+905075985368" class="btn btn-warning btn-lg fw-bold shadow px-4 py-3 text-dark"><i class="fas fa-phone me-2"></i> Hemen Ara</a>
                <a href="tracking.php" class="btn btn-outline-light btn-lg fw-bold px-4 py-3"><i class="fas fa-search me-2"></i> Servis Sorgula</a>
            </div>
        </div>
    </header>

    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold text-primary">Neden DOST GSM?</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4"><div class="feature-box h-100"><i class="fas fa-mobile-alt fa-2x text-warning mb-3"></i><h4 class="text-primary fw-bold mb-3">Tüm Markalara Hizmet</h4><p class="text-muted">Tüm Android ve iOS cihazlara, marka ayrımı yapmaksızın uzmanlıkla hizmet vermekteyiz.</p></div></div>
                <div class="col-lg-3 col-md-6 mb-4"><div class="feature-box h-100"><i class="fas fa-microchip fa-2x text-warning mb-3"></i><h4 class="text-primary fw-bold mb-3">Anakart Onarımı</h4><p class="text-muted">Mikro lehimleme uzmanlığımız ile "tamir edilemez" denilen cihazlara hayat veriyoruz.</p></div></div>
                <div class="col-lg-3 col-md-6 mb-4"><div class="feature-box h-100"><i class="fas fa-gem fa-2x text-warning mb-3"></i><h4 class="text-primary fw-bold mb-3">Kaliteli Parçalar</h4><p class="text-muted">Sadece envanterimizdeki en yüksek kalite yedek parçaları kullanarak uzun ömürlü çözümler sunarız.</p></div></div>
                <div class="col-lg-3 col-md-6 mb-4"><div class="feature-box h-100"><i class="fas fa-tachometer-alt fa-2x text-warning mb-3"></i><h4 class="text-primary fw-bold mb-3">Hızlı Teslimat & Garanti</h4><p class="text-muted">Onarımlarınızı hızla tamamlar ve yapılan işçilik için size kapsamlı garanti sunarız.</p></div></div>
            </div>
        </div>
    </section>

    <footer class="footer py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3 text-primary">DOST GSM</h5>
                    <p>10 yıllık tecrübemizle telefon ve tablet tamirinde güvenilir çözümler sunuyoruz.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3 text-primary">İletişim</h5>
                    <p><i class="fas fa-phone me-2"></i> <a href="tel:+905075985368">+90 507 598 5368</a></p>
                    <p><i class="fas fa-envelope me-2"></i> <a href="mailto:info@dostgsm.com">info@dostgsm.com</a></p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3 text-primary">Hızlı Bağlantılar</h5>
                    <ul class="list-unstyled">
                        <li><a href="services.php">Hizmetlerimiz</a></li>
                        <li><a href="tracking.php">Servis Takip</a></li>
                        <li><a href="contact.php">İletişim</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center pt-3 border-top">
                <p class="mb-0">&copy; 2018 - <?= date('Y') ?> Dost GSM. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <a href="#" class="btn admin-login rounded-circle shadow-lg" title="Yönetici Girişi" data-bs-toggle="modal" data-bs-target="#adminLoginModal">
        <i class="fas fa-cogs fa-lg text-white"></i>
    </a>
    
    <div class="modal fade" id="adminLoginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Yönetici Girişi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="emailForm">
                        <input type="hidden" name="action" value="send_code">
                        <div class="mb-3"><label for="inputEmail" class="form-label">E-posta Adresiniz</label><input type="email" class="form-control" id="inputEmail" name="email" required></div>
                        <div class="mb-3 form-check"><input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me"><label class="form-check-label" for="rememberMe">Beni hatırla</label></div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Giriş Kodu Gönder</button>
                    </form>
                    <form id="codeForm" style="display:none;">
                        <input type="hidden" name="action" value="verify_code">
                        <input type="hidden" id="verifiedEmail" name="email">
                        <div class="mb-3"><label for="inputCode" class="form-label">6 Haneli Kod</label><input type="text" class="form-control text-center" id="inputCode" name="code" maxlength="6" required></div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">Giriş Yap</button>
                    </form>
                    <div id="statusMessage" class="mt-3 text-center"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailForm = document.getElementById('emailForm');
        const codeForm = document.getElementById('codeForm');
        const statusMessage = document.getElementById('statusMessage');

        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            statusMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
            const formData = new FormData(emailForm);
            
            fetch('auth_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success_send') {
                    document.getElementById('verifiedEmail').value = document.getElementById('inputEmail').value;
                    emailForm.style.display = 'none';
                    codeForm.style.display = 'block';
                    statusMessage.innerHTML = `<div class="alert alert-info">${data.message}</div>`;
                } else {
                    statusMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            }).catch(() => statusMessage.innerHTML = '<div class="alert alert-danger">Bir hata oluştu.</div>');
        });

        codeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            statusMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Doğrulanıyor...';
            const formData = new FormData(codeForm);
            
            // "Beni hatırla" seçeneğini codeForm'a ekle
            if (document.getElementById('rememberMe').checked) {
                formData.append('remember_me', 'on');
            }

            fetch('auth_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success_login') {
                    window.location.href = data.redirect;
                } else {
                    statusMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            }).catch(() => statusMessage.innerHTML = '<div class="alert alert-danger">Bir hata oluştu.</div>');
        });
    });
    </script>
</body>
</html>