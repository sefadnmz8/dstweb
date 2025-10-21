<?php
// DOST GSM: Hizmetlerimiz ve Portföy Gösterim Sayfası
require_once 'includes/config.php'; 

// Yönetim panelinden erişim kontrolü (Yönetici Girişi yapıldı mı?)
$is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

$message = ''; 
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'portfolio_added') {
        $message = '<div class="alert alert-success">Yeni tamir örneği portföye başarıyla eklendi!</div>';
    } elseif ($_GET['status'] == 'portfolio_deleted') {
        $message = '<div class="alert alert-success">Tamir örneği başarıyla portföyden kaldırıldı!</div>';
    } elseif (isset($_GET['message'])) {
        $message = '<div class="alert alert-danger">Hata: ' . htmlspecialchars($_GET['message']) . '</div>';
    }
}


// Genel Hizmet Listesi
$services = [
    ['title' => 'Ekran Değişimi ve Onarımı', 'icon' => 'fa-display', 'details' => 'Tüm marka ve modeller için garantili ve hızlı ekran çözümleri.'],
    ['title' => 'Anakart ve Mikro Lehimleme', 'icon' => 'fa-microchip', 'details' => '10 yıllık tecrübemizle en karmaşık anakart arızalarının çözümü.'],
    ['title' => 'Batarya ve Pil Sağlığı', 'icon' => 'fa-battery-full', 'details' => 'Yüksek kaliteli pillerle değişim ve pil sağlığı optimizasyonu.'],
    ['title' => 'Kasa, Soket ve Kamera Onarımı', 'icon' => 'fa-camera', 'details' => 'Kozmetik ve şarj soketi arızalarında orijinal parça onarımı.'],
];

// Portföy Verisini Çekme
$portfolio_stmt = $pdo->query("SELECT * FROM portfolio ORDER BY created_at DESC");
$portfolio_items = $portfolio_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hizmetlerimiz | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-header { background-color: #004d99; color: white; padding: 50px 0; margin-bottom: 30px; }
        .service-card { transition: transform 0.2s; min-height: 250px; }
        .portfolio-item-img { object-fit: cover; height: 200px; width: 100%; }
        .card-portfolio { border-top: 4px solid #ff9900; }
        .card-portfolio:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
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
            <<ul class="navbar-nav ms-auto">
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
        <h1 class="display-5"><i class="fas fa-tools me-2"></i> Kapsamlı Tamir Hizmetlerimiz</h1>
        <p class="lead">10 yıllık uzmanlığımızla çözdüğümüz temel sorunlar ve onarım kategorileri.</p>
    </div>
</div>

<div class="container py-5">
    
    <?= $message ?> <h3 class="mb-4 text-primary" id="genel-hizmetler">Uzmanlık Alanlarımız</h3>
    <div class="row mb-5">
        <?php foreach ($services as $service): ?>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card service-card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas <?= $service['icon'] ?> fa-3x text-primary mb-3"></i>
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($service['title']) ?></h5>
                    <p class="card-text small text-muted">
                        <?= htmlspecialchars($service['details']) ?>
                    </p>
                    <a href="contact.php" class="btn btn-sm btn-outline-warning mt-2">Teklif Al</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <hr class="my-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary" id="portfoy">Başarıyla Tamamlanan İşlerimiz</h3>
        
        <?php if ($is_admin): ?>
        <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
            <i class="fas fa-plus-circle me-1"></i> Yeni Tamir Örneği Ekle
        </button>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <?php if (count($portfolio_items) > 0): ?>
            <?php foreach ($portfolio_items as $item): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-portfolio shadow h-100">
                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/default_repair.jpg') ?>" class="card-img-top portfolio-item-img" alt="<?= htmlspecialchars($item['device_model']) ?>">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($item['service_title']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($item['device_model']) ?></h6>
                        <p class="card-text small"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                        
                        <?php if ($is_admin): ?>
                        <form method="POST" action="admin/portfolio_handler.php" class="d-inline" onsubmit="return confirm('Bu portföy kaydını silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="action" value="delete_portfolio">
                            <input type="hidden" name="portfolio_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger mt-2" title="Sil">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">Henüz sergilenecek tamamlanmış iş örneği bulunmamaktadır. Yönetici Girişi yapıp yeni örnekler ekleyebilirsiniz.</div>
        <?php endif; ?>
    </div>

</div>


<?php if ($is_admin): ?>
<div class="modal fade" id="addPortfolioModal" tabindex="-1" aria-labelledby="addPortfolioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="addPortfolioModalLabel">Yeni Tamir Örneği Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="admin/portfolio_handler.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_portfolio">
                    
                    <div class="mb-3">
                        <label for="service_title" class="form-label">Hizmet Başlığı *</label>
                        <input type="text" class="form-control" id="service_title" name="service_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="device_model" class="form-label">Cihaz Modeli *</label>
                        <input type="text" class="form-control" id="device_model" name="device_model" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Yapılan İşlem Açıklaması</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="repair_image" class="form-label">Tamir Öncesi/Sonrası Görseli</label>
                        <input type="file" class="form-control" id="repair_image" name="repair_image" accept="image/jpeg, image/png" required>
                        <small class="text-muted">Görsel, images/portfolio/ klasörüne yüklenecektir. (Maks 5MB)</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold mt-2">Portföye Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2018 - <?= date('Y') ?> Dost GSM Yönetim Sistemi. Profesyonel Tamir Çözümleri.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>