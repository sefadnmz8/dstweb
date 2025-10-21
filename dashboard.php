<?php
require_once '../includes/config.php'; 
date_default_timezone_set('Europe/Istanbul'); 

// === GÜVENLİK KONTROLLERİ ===
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { 
    header("Location: ../index.php"); 
    exit(); 
}

$current_user_role = $_SESSION['user_role'] ?? 'Misafir';
$isAdmin = ($current_user_role === 'Admin');
$isStok = in_array($current_user_role, ['Admin', 'Stok']);
$isServis = in_array($current_user_role, ['Admin', 'Servis']);

// === GERÇEK ZAMANLI VERİLER ===
try {
    // SERVİS İSTATİSTİKLERİ
    $servis_istatistikleri = [
        'toplam' => $pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn(),
        'beklemede' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Beklemede'")->fetchColumn(),
        'tamirde' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Tamirde'")->fetchColumn(),
        'parca_bekleyen' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Parça Bekleniyor'")->fetchColumn(),
        'hazir' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Hazır'")->fetchColumn(),
        'bugun_kayit' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'bu_ay_ciro' => $pdo->query("SELECT COALESCE(SUM(final_price), 0) FROM service_records WHERE status = 'Teslim Edildi' AND MONTH(delivery_date) = MONTH(NOW())")->fetchColumn()
    ];

    // STOK İSTATİSTİKLERİ
    $stok_istatistikleri = [
        'toplam_urun' => $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn(),
        'kritik_stok' => $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock_level")->fetchColumn(),
        'stokta_yok' => $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity = 0")->fetchColumn(),
        'toplam_deger' => $pdo->query("SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM inventory")->fetchColumn()
    ];

    // BAYİ İSTATİSTİKLERİ (Sadece Admin)
    $bayi_istatistikleri = [
        'toplam_bayi' => $isAdmin ? $pdo->query("SELECT COUNT(*) FROM dealers")->fetchColumn() : 0,
        'borclu_bayi' => $isAdmin ? $pdo->query("SELECT COUNT(*) FROM dealers WHERE current_balance > 0")->fetchColumn() : 0,
        'toplam_borc' => $isAdmin ? $pdo->query("SELECT COALESCE(SUM(current_balance), 0) FROM dealers WHERE current_balance > 0")->fetchColumn() : 0,
        'aylik_borc' => $isAdmin ? $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM dealer_transactions WHERE transaction_type = 'Borc' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn() : 0
    ];

    // KRİTİK STOK LİSTESİ
    $kritik_stoklar = $pdo->query("
        SELECT part_name, sku, quantity, min_stock_level 
        FROM inventory 
        WHERE quantity <= min_stock_level 
        ORDER BY quantity ASC 
        LIMIT 5
    ")->fetchAll();

    // SON SERVİS KAYITLARI
    $son_servisler = $pdo->query("
        SELECT id, customer_name, device_model, status, created_at 
        FROM service_records 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();

    // SON STOK HAREKETLERİ
    $son_stok_hareketleri = $pdo->query("
        SELECT ul.date_used, i.part_name, ul.quantity_used, sr.customer_name
        FROM usage_logs ul
        LEFT JOIN inventory i ON ul.inventory_id = i.id
        LEFT JOIN service_records sr ON ul.service_record_id = sr.id
        ORDER BY ul.date_used DESC 
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard veri yükleme hatası: " . $e->getMessage());
    // Hata durumunda boş veriler
    $servis_istatistikleri = ['toplam' => 0, 'beklemede' => 0, 'tamirde' => 0, 'parca_bekleyen' => 0, 'hazir' => 0, 'bugun_kayit' => 0, 'bu_ay_ciro' => 0];
    $stok_istatistikleri = ['toplam_urun' => 0, 'kritik_stok' => 0, 'stokta_yok' => 0, 'toplam_deger' => 0];
    $bayi_istatistikleri = ['toplam_bayi' => 0, 'borclu_bayi' => 0, 'toplam_borc' => 0, 'aylik_borc' => 0];
    $kritik_stoklar = [];
    $son_servisler = [];
    $son_stok_hareketleri = [];
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Paneli | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
        }

        /* === MODERN TASARIM === */
        .modern-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        /* === STATS KARTLARI === */
        .stats-card { border-left-color: var(--primary-color); }
        .stats-card-servis { border-left-color: var(--info-color); }
        .stats-card-stok { border-left-color: var(--warning-color); }
        .stats-card-bayi { border-left-color: var(--success-color); }
        .stats-card-danger { border-left-color: var(--danger-color); }

        .stats-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* === QUICK ACTIONS === */
        .quick-action-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 15px;
            color: white;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
            text-decoration: none;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
            color: white;
        }

        .quick-action-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        /* === ALERT CARDS === */
        .alert-card {
            border-left: 4px solid var(--danger-color);
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
        }

        /* === ACTIVITY STREAM === */
        .activity-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
        }

        /* === DARK MODE === */
        [data-bs-theme="dark"] .modern-card {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: #e2e8f0;
        }

        [data-bs-theme="dark"] .stats-number {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* === MOBİL UYUMLULUK === */
        @media (max-width: 768px) {
            .stats-number { font-size: 1.5rem; }
            .modern-card { margin-bottom: 1rem; }
            .quick-action-btn { margin-bottom: 1rem; }
        }

        /* === ANIMASYONLAR === */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* === BADGE STYLING === */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body data-bs-theme="light">
<?php require_once 'sidebar.php'; ?>

<main class="main-content">
    <?php require_once 'header.php'; ?>
    
    <div class="container-fluid py-4">
        <!-- === HEADER & QUICK ACTIONS === -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-rocket fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h1 class="h3 mb-1 fw-bold text-gradient">Kontrol Paneli</h1>
                        <p class="text-muted mb-0">Hoş geldiniz, <?= $_SESSION['user_email'] ?? 'Kullanıcı' ?>! • <?= date('d F Y, l') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-primary" onclick="toggleTheme()">
                        <i class="fas fa-moon"></i> Tema
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickActionsModal">
                        <i class="fas fa-bolt"></i> Hızlı İşlemler
                    </button>
                </div>
            </div>
        </div>

        <!-- === SERVİS İSTATİSTİKLERİ === -->
        <div class="row mb-4 fade-in">
            <div class="col-12">
                <h4 class="fw-bold mb-3"><i class="fas fa-tools me-2"></i> Servis Durumu</h4>
            </div>
            
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">TOPLAM KAYIT</div>
                            <div class="stats-number"><?= $servis_istatistikleri['toplam'] ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card-servis h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">BEKLEMEDE</div>
                            <div class="stats-number"><?= $servis_istatistikleri['beklemede'] ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card-servis h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">TAMİRDE</div>
                            <div class="stats-number"><?= $servis_istatistikleri['tamirde'] ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-wrench fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card-danger h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">PARÇA BEKLİYOR</div>
                            <div class="stats-number"><?= $servis_istatistikleri['parca_bekleyen'] ?></div>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-box-open fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card-bayi h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">TESLİME HAZIR</div>
                            <div class="stats-number"><?= $servis_istatistikleri['hazir'] ?></div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="modern-card stats-card-stok h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold">BU AY CİRO</div>
                            <div class="stats-number"><?= number_format($servis_istatistikleri['bu_ay_ciro'], 0) ?> TL</div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-lira-sign fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- === HIZLI İŞLEMLER & KRİTİK UYARILAR === -->
        <div class="row mb-4 fade-in">
            <!-- HIZLI İŞLEMLER -->
            <div class="col-lg-8 mb-4">
                <div class="modern-card h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Hızlı İşlemler</h5>
                    <div class="row g-3">
                        <?php if ($isServis): ?>
                        <div class="col-md-3 col-6">
                            <a href="service_records.php" class="quick-action-btn">
                                <i class="fas fa-tools"></i>
                                <div class="fw-bold">Servis Kaydı</div>
                                <small>Yeni cihaz</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="service_records.php?status=Beklemede" class="quick-action-btn" style="background: linear-gradient(135deg, #4cc9f0, #4895ef);">
                                <i class="fas fa-clock"></i>
                                <div class="fw-bold">Bekleyenler</div>
                                <small><?= $servis_istatistikleri['beklemede'] ?> kayıt</small>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($isStok): ?>
                        <div class="col-md-3 col-6">
                            <a href="inventory.php" class="quick-action-btn" style="background: linear-gradient(135deg, #f8961e, #f3722c);">
                                <i class="fas fa-boxes"></i>
                                <div class="fw-bold">Stok Ekle</div>
                                <small>Yeni ürün</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="inventory.php" class="quick-action-btn" style="background: linear-gradient(135deg, #f72585, #b5179e);">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="fw-bold">Kritik Stok</div>
                                <small><?= $stok_istatistikleri['kritik_stok'] ?> ürün</small>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                        <div class="col-md-3 col-6">
                            <a href="dealers.php" class="quick-action-btn" style="background: linear-gradient(135deg, #43aa8b, #4d908e);">
                                <i class="fas fa-handshake"></i>
                                <div class="fw-bold">Bayi İşlemleri</div>
                                <small>Veresiye takip</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="reports.php" class="quick-action-btn" style="background: linear-gradient(135deg, #577590, #4a4e69);">
                                <i class="fas fa-chart-bar"></i>
                                <div class="fw-bold">Raporlar</div>
                                <small>Detaylı analiz</small>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KRİTİK UYARILAR -->
            <div class="col-lg-4 mb-4">
                <div class="modern-card alert-card h-100 p-4">
                    <h5 class="fw-bold mb-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Kritik Uyarılar</h5>
                    
                    <?php if ($stok_istatistikleri['kritik_stok'] > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Kritik Stok</span>
                            <span class="badge bg-danger"><?= $stok_istatistikleri['kritik_stok'] ?> ürün</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($kritik_stoklar as $stok): ?>
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small"><?= htmlspecialchars($stok['part_name']) ?></span>
                                    <span class="badge bg-danger"><?= $stok['quantity'] ?> adet</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="inventory.php" class="btn btn-sm btn-outline-danger w-100 mt-2">Stokları Görüntüle</a>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-success mb-0">Kritik seviyede stok bulunmuyor</p>
                    </div>
                    <?php endif; ?>

                    <?php if ($isAdmin && $bayi_istatistikleri['toplam_borc'] > 0): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Açık Veresiye</span>
                            <span class="badge bg-warning text-dark"><?= number_format($bayi_istatistikleri['toplam_borc'], 0) ?> TL</span>
                        </div>
                        <a href="dealers.php" class="btn btn-sm btn-outline-warning w-100">Bayileri Görüntüle</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- === SON AKTİVİTELER & STOK DURUMU === -->
        <div class="row fade-in">
            <!-- SON SERVİS KAYITLARI -->
            <div class="col-lg-6 mb-4">
                <div class="modern-card h-100">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Son Servis Kayıtları</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($son_servisler) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($son_servisler as $kayit): ?>
                            <a href="service_records.php" class="list-group-item list-group-item-action border-0 px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($kayit['customer_name']) ?></h6>
                                        <p class="mb-1 text-muted small"><?= htmlspecialchars($kayit['device_model']) ?></p>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($kayit['created_at'])) ?></small>
                                    </div>
                                    <span class="status-badge 
                                        <?= $kayit['status'] == 'Beklemede' ? 'bg-warning text-dark' : '' ?>
                                        <?= $kayit['status'] == 'Tamirde' ? 'bg-info' : '' ?>
                                        <?= $kayit['status'] == 'Parça Bekleniyor' ? 'bg-danger' : '' ?>
                                        <?= $kayit['status'] == 'Hazır' ? 'bg-success' : '' ?>
                                    "><?= $kayit['status'] ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="service_records.php" class="btn btn-outline-primary btn-sm">Tümünü Görüntüle</a>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Henüz servis kaydı bulunmuyor</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STOK DURUMU -->
            <div class="col-lg-6 mb-4">
                <div class="modern-card h-100">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="fw-bold mb-0"><i class="fas fa-box me-2"></i>Stok Durumu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="stats-number"><?= $stok_istatistikleri['toplam_urun'] ?></div>
                                    <small class="text-muted">Toplam Ürün</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="stats-number text-danger"><?= $stok_istatistikleri['kritik_stok'] ?></div>
                                    <small class="text-muted">Kritik Stok</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div>
                                    <div class="stats-number text-warning"><?= number_format($stok_istatistikleri['toplam_deger'], 0) ?> TL</div>
                                    <small class="text-muted">Toplam Değer</small>
                                </div>
                            </div>
                        </div>

                        <?php if (count($son_stok_hareketleri) > 0): ?>
                        <h6 class="fw-bold mb-3">Son Stok Hareketleri</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ($son_stok_hareketleri as $hareket): ?>
                            <div class="list-group-item border-0 px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars($hareket['part_name']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($hareket['customer_name'] ?? 'Stok çıkışı') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary"><?= $hareket['quantity_used'] ?> adet</span>
                                        <small class="text-muted d-block"><?= date('d.m.Y', strtotime($hareket['date_used'])) ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="inventory.php" class="btn btn-outline-primary btn-sm">Stokları Yönet</a>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Henüz stok hareketi bulunmuyor</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Hızlı İşlemler Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-bolt me-2"></i>Hızlı İşlemler</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <?php if ($isServis): ?>
                    <div class="col-md-4">
                        <a href="service_records.php" class="btn btn-outline-primary w-100 h-100 p-3 text-start">
                            <i class="fas fa-tools fa-2x mb-2"></i>
                            <h6>Yeni Servis</h6>
                            <small class="text-muted">Cihaz kabulü</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="service_records.php?status=Beklemede" class="btn btn-outline-warning w-100 h-100 p-3 text-start">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <h6>Bekleyenler</h6>
                            <small class="text-muted">Onay bekleyen</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="service_records.php?status=Hazır" class="btn btn-outline-success w-100 h-100 p-3 text-start">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h6>Teslime Hazır</h6>
                            <small class="text-muted">Tamamlanan</small>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($isStok): ?>
                    <div class="col-md-4">
                        <a href="inventory.php" class="btn btn-outline-info w-100 h-100 p-3 text-start">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <h6>Stok Ekle</h6>
                            <small class="text-muted">Yeni ürün</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="inventory.php" class="btn btn-outline-danger w-100 h-100 p-3 text-start">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h6>Kritik Stok</h6>
                            <small class="text-muted">Uyarı verenler</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="reports.php" class="btn btn-outline-secondary w-100 h-100 p-3 text-start">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <h6>Stok Raporu</h6>
                            <small class="text-muted">Detaylı analiz</small>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                    <div class="col-md-4">
                        <a href="dealers.php" class="btn btn-outline-success w-100 h-100 p-3 text-start">
                            <i class="fas fa-handshake fa-2x mb-2"></i>
                            <h6>Bayi İşlemleri</h6>
                            <small class="text-muted">Veresiye takip</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="user_management.php" class="btn btn-outline-dark w-100 h-100 p-3 text-start">
                            <i class="fas fa-users-cog fa-2x mb-2"></i>
                            <h6>Kullanıcılar</h6>
                            <small class="text-muted">Yetki yönetimi</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="reports.php" class="btn btn-outline-warning w-100 h-100 p-3 text-start">
                            <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                            <h6>Finansal Rapor</h6>
                            <small class="text-muted">Gelir-gider</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>

<script>
// Tema değiştirme fonksiyonu
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Buton ikonunu güncelle
    const themeButton = document.querySelector('[onclick="toggleTheme()"]');
    const icon = themeButton.querySelector('i');
    icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Sayfa yüklendiğinde tema ayarı
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    
    // Buton ikonunu ayarla
    const themeButton = document.querySelector('[onclick="toggleTheme()"]');
    if (themeButton) {
        const icon = themeButton.querySelector('i');
        icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
});

// Sayfayı her 30 saniyede bir yenile (gerçek zamanlı veri)
setTimeout(() => {
    window.location.reload();
}, 30000);
</script>
</body>
</html>