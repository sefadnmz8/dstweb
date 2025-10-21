<?php
// DOST GSM: Hakkımızda Sayfası İçeriği ve PHP Ayarları
require_once 'includes/config.php'; 

// Yönetim panelinde giriş yapılıp yapılmadığını kontrol et
$is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Düzenlenmiş Hikaye Metni
$hikaye = [
    'giris_baslik' => '2014’ten Bugüne Uzanan Bir Tutku Hikayesi',
    'giris_metni' => 'Merhaba, ben Sefa Dönmez. Dost GSM’in temelleri, tahmin edebileceğiniz gibi, Kırıkkale’de üniversite yıllarımda, tamamen kişisel bir meraktan atıldı. 2014-2015 yılları arasında üniversitede okurken bozulan kendi telefonumu yaptırmak için gittiğim telefoncu dükkanını o kadar çok sevdim ki, işi öğrenmek istediğimi söyledim.',
    'ilk_deneyim' => 'Bu serüvene ilk olarak, severek çalıştığım bu dükkanda sadece telefonların **yazılım ve yazılımsal onarım** kısımlarıyla ilgilenerek başladım. Bu süreç, elektroniğe ve cep telefonu teknolojisine olan ilgimi bir kariyere dönüştürme kararımı pekiştirdi.',
    'niğde_donus' => 'Spor Yöneticiliği ve Beden Eğitimi Öğretmenliği bölümünü bitirdikten sonra, 2017 yılında memleketim olan **Niğde’ye temelli dönüş** yaptım. Kısa bir süre farklı bir işletmede tecrübe kazandıktan sonra, Niğde’de **Paşakapı Caddesi’nde pasaj içinde kendi küçük dükkanımı** açtım.',
    'kurulus' => 'Ortalama 2 yıl boyunca burayı işlettikten sonra, sizlere daha iyi hizmet verebilmek için mevcut adresimiz olan **Matbaa Sokak, 2. kattaki modern ve donanımlı dükkanımızı** kurduk. Beş yılı aşkın süredir işlettiğimiz bu noktada, toplamda **10 yıla yakın** süredir bu mesleği büyük bir zevkle yapıyorum.',
    'misyon' => 'Misyonum, sadece bozuk bir telefonu tamir etmek değil, aynı zamanda **elektroniğe olan sevgimi** ve her başarılı onarımdan aldığım **büyük zevki** işime yansıtarak, cihazınızı ilk günkü performansına döndürmektir. Dost GSM olarak tecrübeyi ve dürüstlüğü her zaman ön planda tutuyoruz.'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-header { background-color: #004d99; color: white; padding: 50px 0; margin-bottom: 30px; }
        .hikaye-kart { border-left: 4px solid #ff9900; padding: 20px; background-color: #f9f9f9; }
        .profile-img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 5px solid #ff9900; }
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
        <h1 class="display-5"><i class="fas fa-history me-2"></i> Dost GSM'in Hikayesi</h1>
        <p class="lead">10 Yıllık Tecrübe, Kişisel Tutkudan Profesyonel Hizmete...</p>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4 text-center">
            <img src="images/sefa_donmez_profile.jpg" alt="Sefa Dönmez Profil" class="profile-img shadow-lg mb-3">
            <h4 class="text-primary fw-bold mb-0">Sefa Dönmez</h4>
            <p class="text-muted small">Kurucu ve Baş Teknisyen</p>
            
            <div class="alert alert-info mt-4">
                <h6 class="fw-bold">Vizyonumuz</h6>
                <p class="small mb-0">Tecrübeyi ve teknoloji sevgisini birleştirerek, Niğde'de cep telefonu tamirinde en güvenilir, en şeffaf ve en kaliteli hizmeti sunmak.</p>
            </div>
        </div>

        <div class="col-lg-8">
            <h3 class="mb-3 text-dark"><?= $hikaye['giris_baslik'] ?></h3>

            <div class="hikaye-kart mb-4">
                <p class="lead"><?= $hikaye['giris_metni'] ?></p>
                <p><?= $hikaye['ilk_deneyim'] ?></p>
            </div>

            <div class="hikaye-kart mb-4">
                <h5 class="fw-bold text-warning"><i class="fas fa-map-marker-alt me-2"></i> Niğde'de Kurumsallaşma</h5>
                <p><?= $hikaye['niğde_donus'] ?></p>
            </div>
            
            <div class="hikaye-kart mb-4">
                <h5 class="fw-bold text-warning"><i class="fas fa-medal me-2"></i> 10 Yıllık Tutku</h5>
                <p>Yaklaşık 10 yıldır büyük bir zevkle yaptığım bu meslekteki ana amacım, **elektroniği seviyor olmam** ve her tamir ettiğim telefonda başarılı bir çözüm bulmaktan büyük bir tatmin duymamdır. Bu, işimize duyduğumuz saygının en büyük kanıtıdır.</p>
            </div>
        </div>
    </div>
</div>


<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2018 - <?= date('Y') ?> Dost GSM Yönetim Sistemi. Profesyonel Tamir Çözümleri.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>