<?php
// === GÜVENLİK KONTROLLERİ ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php'; 
date_default_timezone_set('Europe/Istanbul'); 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { 
    header("Location: ../index.php"); 
    exit(); 
}

$current_user_role = $_SESSION['user_role'] ?? 'Misafir'; 
if ($current_user_role !== 'Admin' && $current_user_role !== 'Servis') { 
    header("Location: dashboard.php?status=yetki_yok"); 
    exit(); 
}

// CSRF Token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === DEĞİŞKEN TANIMLAMALARI ===
$message = '';
$durumlar = ['Tümü', 'Beklemede', 'Tamirde', 'Parça Bekleniyor', 'Hazır', 'Teslim Edildi'];

// === SAYFALAMA AYARLARI ===
$records_per_page = 15;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($current_page < 1) $current_page = 1;

// === FİLTRELEME PARAMETRELERİ ===
$search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// === VERİTABANI SORGULARI ===
try {
    // Filtreleme koşullarını oluştur
    $where_clauses = [];
    $params = [];
    
    // Arama filtresi
    if (!empty($search_term)) { 
        $where_clauses[] = "(customer_name LIKE ? OR phone_number LIKE ? OR device_model LIKE ? OR imei LIKE ?)"; 
        $wildcard_search = "%" . $search_term . "%"; 
        $params[] = $wildcard_search; 
        $params[] = $wildcard_search; 
        $params[] = $wildcard_search;
        $params[] = $wildcard_search;
    }
    
    // Durum filtresi
    if (!empty($filter_status) && $filter_status !== 'Tümü') { 
        $where_clauses[] = "status = ?"; 
        $params[] = $filter_status; 
    }
    
    // Tarih filtresi (Güvenli versiyon)
    if (!empty($start_date) && !empty($end_date)) { 
        $start = DateTime::createFromFormat('Y-m-d', $start_date);
        $end = DateTime::createFromFormat('Y-m-d', $end_date);
        
        if ($start && $end && $start <= $end) { 
            $where_clauses[] = "DATE(created_at) BETWEEN ? AND ?"; 
            $params[] = $start_date; 
            $params[] = $end_date; 
        } else {
            $message = '<div class="alert alert-warning">Geçersiz tarih aralığı!</div>';
        }
    }
    
    // WHERE koşulunu birleştir
    $where_sql = '';
    if (!empty($where_clauses)) { 
        $where_sql = " WHERE " . implode(" AND ", $where_clauses); 
    }
    
    // Toplam kayıt sayısını bul
    $total_records_sql = "SELECT COUNT(*) FROM service_records" . $where_sql;
    $total_records_stmt = $pdo->prepare($total_records_sql);
    $total_records_stmt->execute($params);
    $total_records = $total_records_stmt->fetchColumn();
    
    // Toplam sayfa sayısını hesapla
    $total_pages = ceil($total_records / $records_per_page);
    
    // Sayfalama için offset hesapla
    $offset = ($current_page - 1) * $records_per_page;
    
    // Ana sorguyu çalıştır
    $records_sql = "SELECT * FROM service_records" . $where_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $kayit_listesi_stmt = $pdo->prepare($records_sql);
    
    // Parametreleri bağla (DÜZELTİLDİ)
    $param_index = 1;
    foreach ($params as $param) { 
        $kayit_listesi_stmt->bindValue($param_index++, $param); 
    }
    $kayit_listesi_stmt->bindValue($param_index++, (int) $records_per_page, PDO::PARAM_INT);
    $kayit_listesi_stmt->bindValue($param_index++, (int) $offset, PDO::PARAM_INT); // DÜZELTİLDİ: ++ eklendi
    $kayit_listesi_stmt->execute();
    $kayit_listesi = $kayit_listesi_stmt->fetchAll();
    
    // === SERVİS İSTATİSTİKLERİ ===
    $servis_istatistikleri = [
        'toplam' => $pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn(),
        'beklemede' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Beklemede'")->fetchColumn(),
        'tamirde' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Tamirde'")->fetchColumn(),
        'parca_bekleyen' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Parça Bekleniyor'")->fetchColumn(),
        'hazir' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE status = 'Hazır'")->fetchColumn(),
        'bugun_kayit' => $pdo->query("SELECT COUNT(*) FROM service_records WHERE DATE(created_at) = CURDATE()")->fetchColumn()
    ];
    
    // === STOK LİSTESİ (Tamirde malzeme seçimi için) ===
    $stok_listesi = $pdo->query("SELECT id, part_name, sku, quantity, unit_cost FROM inventory WHERE quantity > 0 ORDER BY part_name ASC")->fetchAll();

} catch (PDOException $e) {
    error_log("Servis kayıtları yüklenirken hata: " . $e->getMessage());
    $message = '<div class="alert alert-danger">Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.</div>';
    $kayit_listesi = [];
    $servis_istatistikleri = ['toplam' => 0, 'beklemede' => 0, 'tamirde' => 0, 'parca_bekleyen' => 0, 'hazir' => 0, 'bugun_kayit' => 0];
    $stok_listesi = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Takibi | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        /* === MOBİL UYUMLULUK === */
        @media (max-width: 768px) {
            .main-content { padding: 0.5rem !important; }
            .servis-kartlari .col-lg-2 { margin-bottom: 1rem; }
            .btn-group-mobile { display: flex; flex-direction: column; gap: 0.5rem; }
            .btn-group-mobile .btn { width: 100%; margin-bottom: 0.5rem; }
            .table-responsive { font-size: 0.875rem; }
        }
        
        /* === SERVİS KARTLARI === */
        .servis-kart { 
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
            height: 100%;
            border-left: 4px solid;
        }
        .servis-kart:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; 
        }
        .kart-beklemede { border-left-color: #f0ad4e !important; }
        .kart-tamirde { border-left-color: #17a2b8 !important; }
        .kart-parca { border-left-color: #dc3545 !important; }
        .kart-hazir { border-left-color: #28a745 !important; }
        .kart-teslim { border-left-color: #6c757d !important; }
        .kart-toplam { border-left-color: #007bff !important; }
        
        /* === DURUM RENKLERİ === */
        .badge-beklemede { background-color: #f0ad4e !important; color: black; }
        .badge-tamirde { background-color: #17a2b8 !important; }
        .badge-parca { background-color: #dc3545 !important; }
        .badge-hazir { background-color: #28a745 !important; }
        .badge-teslim { background-color: #6c757d !important; }
        
        /* === HIZLI İŞLEMLER === */
        .hizli-islemler {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }
        
        /* === FİLTRE GRUBU === */
        .filtre-grubu {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        /* === TABLO STİLLERİ === */
        .servis-satiri:hover { 
            cursor: pointer; 
            background-color: #f8f9fa; 
            transition: background-color 0.2s ease;
        }
        
        .section-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #fff;
        }
        
        .section-title {
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .price-card {
            border-left: 4px solid #007bff;
        }
        
        .info-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'header.php'; ?>
    
    <div class="container-fluid">
        <!-- === HIZLI İŞLEMLER BAŞLIK === -->
        <div class="hizli-islemler">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0 text-white"><i class="fas fa-tools me-2"></i> Servis Takip Sistemi</h2>
                    <p class="mb-0 text-white-50">Tamir süreçleri ve müşteri takibi</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Cihaz Kabul
                    </button>
                    <button class="btn btn-outline-light" data-bs-toggle="collapse" data-bs-target="#filtreSection">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                </div>
            </div>
        </div>

        <?= $message ?>

        <!-- === SERVİS İSTATİSTİK KARTLARI === -->
        <div class="row servis-kartlari mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-toplam shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Toplam Kayıt</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['toplam'] ?></div>
                        <div class="mt-2 text-xs text-muted">Tüm servis kayıtları</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-beklemede shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Beklemede</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['beklemede'] ?></div>
                        <div class="mt-2 text-xs text-muted">Onay bekleyen</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-tamirde shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Tamirde</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['tamirde'] ?></div>
                        <div class="mt-2 text-xs text-muted">Aktif tamir</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-parca shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Parça Bekleyen</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['parca_bekleyen'] ?></div>
                        <div class="mt-2 text-xs text-muted">Malzeme bekliyor</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-hazir shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Teslime Hazır</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['hazir'] ?></div>
                        <div class="mt-2 text-xs text-muted">Müşteri bekliyor</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card servis-kart kart-teslim shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Bugünkü Kayıt</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $servis_istatistikleri['bugun_kayit'] ?></div>
                        <div class="mt-2 text-xs text-muted">Yeni kabul</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- === FİLTRELEME BÖLÜMÜ === -->
        <div class="collapse" id="filtreSection">
            <div class="filtre-grubu">
                <form action="service_records.php" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Arama</label>
                        <input type="text" class="form-control" name="search" placeholder="Müşteri, Telefon, Model, IMEI..." value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Durum</label>
                        <select name="status" class="form-select">
                            <?php foreach ($durumlar as $durum): ?>
                                <option value="<?= $durum ?>" <?= ($filter_status == $durum) ? 'selected' : '' ?>>
                                    <?= ($durum == 'Tümü') ? 'Tüm Durumlar' : $durum ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Başlangıç</label>
                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Bitiş</label>
                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Filtrele
                            </button>
                            <a href="service_records.php" class="btn btn-outline-secondary">Sıfırla</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- === SERVİS LİSTESİ TABLOSU === -->
        <div class="card shadow">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <div>
                    Servis Kayıtları 
                    <small class="text-muted">(Detay için satıra tıklayın)</small>
                </div>
                <div>
                    <span class="badge bg-primary"><?= count($kayit_listesi) ?> kayıt listeleniyor</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="table-secondary">
                                <th width="8%">ID</th>
                                <th width="20%">Müşteri / İletişim</th>
                                <th width="15%">Cihaz Bilgisi</th>
                                <th width="22%">Arıza / Notlar</th>
                                <th width="10%">Durum</th>
                                <th width="15%">Tarih</th>
                                <th width="10%">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kayit_listesi)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">
                                        <?php if (!empty($search_term) || !empty($filter_status) || !empty($start_date)): ?>
                                        Arama kriterlerinize uygun kayıt bulunamadı.
                                        <?php else: ?>
                                        Henüz servis kaydı bulunmuyor.
                                        <?php endif; ?>
                                    </h5>
                                    <?php if (!empty($search_term) || !empty($filter_status) || !empty($start_date)): ?>
                                    <a href="service_records.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-times me-1"></i> Filtreleri Temizle
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                                        <i class="fas fa-plus-circle me-1"></i> İlk Kaydı Oluştur
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($kayit_listesi as $kayit): ?>
                            <tr class="servis-satiri" data-bs-toggle="modal" data-bs-target="#servisDetayModal<?= $kayit['id'] ?>">
                                <td>
                                    <strong>#<?= $kayit['id'] ?></strong>
                                    <?php if (!empty($kayit['imei'])): ?>
                                    <br><small class="text-muted">IMEI: <?= substr($kayit['imei'], 0, 8) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($kayit['customer_name']) ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($kayit['phone_number']) ?>
                                    </small>
                                    <?php if (!empty($kayit['extra_notes'])): ?>
                                    <br><small class="text-warning"><i class="fas fa-sticky-note me-1"></i>Not var</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($kayit['device_model']) ?></div>
                                    <?php if (!empty($kayit['color'])): ?>
                                    <small class="text-muted">Renk: <?= htmlspecialchars($kayit['color']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($kayit['housing_status'])): ?>
                                    <br><small class="text-info">Kasa: <?= htmlspecialchars($kayit['housing_status']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?= htmlspecialchars(substr($kayit['fault_description'], 0, 50)) ?>...</div>
                                    <?php if ($kayit['final_price'] > 0): ?>
                                    <div class="text-success fw-bold mt-1">
                                        <i class="fas fa-lira-sign me-1"></i><?= number_format($kayit['final_price'], 2, ',', '.') ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-secondary';
                                    if ($kayit['status'] == 'Beklemede') $badge_class = 'badge-beklemede';
                                    if ($kayit['status'] == 'Tamirde') $badge_class = 'badge-tamirde';
                                    if ($kayit['status'] == 'Parça Bekleniyor') $badge_class = 'badge-parca';
                                    if ($kayit['status'] == 'Hazır') $badge_class = 'badge-hazir';
                                    if ($kayit['status'] == 'Teslim Edildi') $badge_class = 'badge-teslim';
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($kayit['status']) ?></span>
                                </td>
                                <td>
                                    <div><?= date('d.m.Y', strtotime($kayit['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($kayit['created_at'])) ?></small>
                                    <?php if ($kayit['status'] == 'Hazır' && $kayit['final_price'] > 0): ?>
                                    <div class="text-success small mt-1">
                                        <i class="fas fa-clock me-1"></i>Ödeme bekliyor
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button class="btn btn-info mb-1" title="Detay" data-bs-toggle="modal" data-bs-target="#servisDetayModal<?= $kayit['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($kayit['status'] != 'Teslim Edildi'): ?>
                                        <button class="btn btn-warning mb-1" title="Güncelle" data-bs-toggle="modal" data-bs-target="#updateRecordModal<?= $kayit['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Tamamlama Butonu - Sadece belirli durumlarda göster -->
                                        <?php if (in_array($kayit['status'], ['Beklemede', 'Tamirde', 'Parça Bekleniyor'])): ?>
                                        <button class="btn btn-success mb-1" title="Tamamla" data-bs-toggle="modal" data-bs-target="#tamamlamaModal<?= $kayit['id'] ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php endif; ?>
                                        
                                        <?php if ($kayit['status'] == 'Hazır'): ?>
                                        <form method="POST" action="service_handler.php" class="d-inline">
                                            <input type="hidden" name="action" value="deliver_service">
                                            <input type="hidden" name="record_id" value="<?= $kayit['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="btn btn-dark" title="Teslim Et" 
                                                    onclick="return confirm('Cihazı teslim etmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-handshake"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Servis Tamamlama Modal -->
                            <div class="modal fade" id="tamamlamaModal<?= $kayit['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Servisi Tamamla - #<?= $kayit['id'] ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="service_handler.php" id="tamamlamaForm<?= $kayit['id'] ?>">
                                                <input type="hidden" name="action" value="complete_service">
                                                <input type="hidden" name="record_id" value="<?= $kayit['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                
                                                <!-- Müşteri ve Cihaz Bilgisi -->
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <div class="info-card p-3 rounded bg-light">
                                                            <h6 class="fw-bold text-primary mb-2">
                                                                <i class="fas fa-user me-2"></i>Müşteri Bilgisi
                                                            </h6>
                                                            <p class="mb-1"><strong><?= htmlspecialchars($kayit['customer_name']) ?></strong></p>
                                                            <p class="mb-0 text-muted small">
                                                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($kayit['phone_number']) ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-card p-3 rounded bg-light">
                                                            <h6 class="fw-bold text-primary mb-2">
                                                                <i class="fas fa-mobile-alt me-2"></i>Cihaz Bilgisi
                                                            </h6>
                                                            <p class="mb-1"><strong><?= htmlspecialchars($kayit['device_model']) ?></strong></p>
                                                            <p class="mb-0 text-muted small"><?= htmlspecialchars($kayit['fault_description']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 1. Kullanılan Malzemeler -->
                                                <div class="section-card mb-4">
                                                    <h6 class="section-title">
                                                        <i class="fas fa-boxes me-2"></i>Kullanılan Malzemeler
                                                        <small class="text-muted">(Stoktan otomatik düşülecektir)</small>
                                                    </h6>
                                                    
                                                    <div class="stok-secim-alani">
                                                        <label class="form-label fw-bold">Stoktan Malzeme Seçin:</label>
                                                        
                                                        <?php if (count($stok_listesi) > 0): ?>
                                                        <div class="stok-listesi-container">
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-hover">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th width="5%">Seç</th>
                                                                            <th width="35%">Parça Adı</th>
                                                                            <th width="20%">SKU</th>
                                                                            <th width="15%">Stok</th>
                                                                            <th width="15%">Maliyet</th>
                                                                            <th width="10%">Fiyat</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($stok_listesi as $stok): ?>
                                                                        <tr class="stok-item <?= $stok['quantity'] <= 2 ? 'table-warning' : '' ?>">
                                                                            <td>
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input malzeme-checkbox" 
                                                                                           type="checkbox" 
                                                                                           name="parca_id[]" 
                                                                                           value="<?= $stok['id'] ?>"
                                                                                           id="malzeme_<?= $kayit['id'] ?>_<?= $stok['id'] ?>"
                                                                                           data-maliyet="<?= $stok['unit_cost'] ?>"
                                                                                           data-parca-adi="<?= htmlspecialchars($stok['part_name']) ?>" <!-- DÜZELTİLDİ: parca-adı -> parca-adi -->
                                                                                           <?= $stok['quantity'] == 0 ? 'disabled' : '' ?>>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <label class="form-check-label" for="malzeme_<?= $kayit['id'] ?>_<?= $stok['id'] ?>">
                                                                                    <strong><?= htmlspecialchars($stok['part_name']) ?></strong>
                                                                                    <?php if ($stok['quantity'] <= 2): ?>
                                                                                    <span class="badge bg-warning text-dark ms-1">Az Stok</span>
                                                                                    <?php endif; ?>
                                                                                </label>
                                                                            </td>
                                                                            <td>
                                                                                <small class="text-muted"><?= htmlspecialchars($stok['sku']) ?></small>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge 
                                                                                    <?= $stok['quantity'] == 0 ? 'bg-danger' : '' ?>
                                                                                    <?= $stok['quantity'] > 0 && $stok['quantity'] <= 2 ? 'bg-warning text-dark' : '' ?>
                                                                                    <?= $stok['quantity'] > 2 ? 'bg-success' : '' ?>
                                                                                ">
                                                                                    <?= $stok['quantity'] ?> adet
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <small class="text-muted"><?= number_format($stok['unit_cost'], 2, ',', '.') ?> TL</small>
                                                                            </td>
                                                                            <td>
                                                                                <small class="text-success fw-bold"><?= number_format($stok['unit_cost'] * 1.5, 2, ',', '.') ?> TL</small>
                                                                            </td>
                                                                        </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Stokta kullanılabilecek malzeme bulunmuyor.
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- 2. Fiyatlandırma -->
                                                <div class="section-card mb-4">
                                                    <h6 class="section-title">
                                                        <i class="fas fa-calculator me-2"></i>Fiyatlandırma
                                                    </h6>
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <div class="price-card p-3 rounded bg-light">
                                                                <label class="form-label fw-bold text-primary">Malzeme Toplamı</label>
                                                                <div class="price-display">
                                                                    <span id="malzemeToplami<?= $kayit['id'] ?>" class="h5 text-primary">0.00 TL</span>
                                                                    <small class="text-muted d-block" id="malzemeDetay<?= $kayit['id'] ?>">Seçili malzeme yok</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-4">
                                                            <div class="price-card p-3 rounded bg-light">
                                                                <label for="isci_ucreti<?= $kayit['id'] ?>" class="form-label fw-bold text-success">İşçilik Ücreti</label>
                                                                <div class="input-group">
                                                                    <input type="number" class="form-control" 
                                                                           id="isci_ucreti<?= $kayit['id'] ?>" 
                                                                           name="isci_ucreti" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           value="0"
                                                                           placeholder="0.00">
                                                                    <span class="input-group-text">TL</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-4">
                                                            <div class="price-card p-3 rounded bg-success text-white">
                                                                <label for="total_price<?= $kayit['id'] ?>" class="form-label fw-bold">Toplam Tutar</label>
                                                                <div class="input-group">
                                                                    <input type="number" class="form-control fw-bold" 
                                                                           id="total_price<?= $kayit['id'] ?>" 
                                                                           name="total_price" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           required
                                                                           placeholder="0.00">
                                                                    <span class="input-group-text bg-white text-success fw-bold">TL</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 3. Müşteri İletişim -->
                                                <div class="section-card">
                                                    <h6 class="section-title">
                                                        <i class="fas fa-comment-dots me-2"></i>Müşteri İletişimi
                                                    </h6>
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-md-8">
                                                            <div class="alert alert-info">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-info-circle fa-2x me-3"></i>
                                                                    <div>
                                                                        <strong>Fiyat teklifini müşteriye gönderin</strong><br>
                                                                        <small>Müşteri onayından sonra tamir işlemini başlatabilirsiniz.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="d-grid gap-2">
                                                                <button type="button" class="btn btn-success btn-lg" 
                                                                        onclick="whatsappFiyatGonder(<?= $kayit['id'] ?>)">
                                                                    <i class="fab fa-whatsapp me-2"></i>
                                                                    WhatsApp ile Gönder
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                        onclick="smsFiyatGonder(<?= $kayit['id'] ?>)">
                                                                    <i class="fas fa-sms me-1"></i>
                                                                    SMS Gönder
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Onay Butonu -->
                                                <div class="mt-4 p-3 bg-light rounded">
                                                    <div class="alert alert-warning mb-3">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>Uyarı:</strong> Malzeme seçerseniz stok otomatik düşülecektir. Bu işlem geri alınamaz.
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                                                        <i class="fas fa-check-circle me-2"></i> Servisi Tamamla ve Kaydet
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Servis Detay Modal -->
                            <div class="modal fade" id="servisDetayModal<?= $kayit['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Servis Detayı - #<?= $kayit['id'] ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Müşteri Bilgileri</h6>
                                                    <p><strong>Adı:</strong> <?= htmlspecialchars($kayit['customer_name']) ?></p>
                                                    <p><strong>Telefon:</strong> <?= htmlspecialchars($kayit['phone_number']) ?></p>
                                                    <p><strong>IMEI:</strong> <?= htmlspecialchars($kayit['imei'] ?? 'Belirtilmemiş') ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Cihaz Bilgileri</h6>
                                                    <p><strong>Model:</strong> <?= htmlspecialchars($kayit['device_model']) ?></p>
                                                    <p><strong>Renk:</strong> <?= htmlspecialchars($kayit['color'] ?? 'Belirtilmemiş') ?></p>
                                                    <p><strong>Kasa Durumu:</strong> <?= htmlspecialchars($kayit['housing_status'] ?? 'Belirtilmemiş') ?></p>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Arıza Tanımı</h6>
                                            <p><?= nl2br(htmlspecialchars($kayit['fault_description'])) ?></p>
                                            
                                            <?php if (!empty($kayit['extra_notes'])): ?>
                                            <h6>Ek Notlar</h6>
                                            <p class="text-info"><?= nl2br(htmlspecialchars($kayit['extra_notes'])) ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if ($kayit['final_price'] > 0): ?>
                                            <hr>
                                            <h6>Fiyat Bilgisi</h6>
                                            <p class="h5 text-success">
                                                <strong>Toplam Tutar: <?= number_format($kayit['final_price'], 2, ',', '.') ?> TL</strong>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                            <?php if ($kayit['status'] != 'Teslim Edildi'): ?>
                                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateRecordModal<?= $kayit['id'] ?>">
                                                <i class="fas fa-edit me-1"></i> Güncelle
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Güncelleme Modal -->
                            <div class="modal fade" id="updateRecordModal<?= $kayit['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title">Durum Güncelle - #<?= $kayit['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="service_handler.php">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="record_id" value="<?= $kayit['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Yeni Durum</label>
                                                    <select class="form-select" name="new_status" required>
                                                        <option value="Beklemede" <?= $kayit['status'] == 'Beklemede' ? 'selected' : '' ?>>Beklemede</option>
                                                        <option value="Tamirde" <?= $kayit['status'] == 'Tamirde' ? 'selected' : '' ?>>Tamirde</option>
                                                        <option value="Parça Bekleniyor" <?= $kayit['status'] == 'Parça Bekleniyor' ? 'selected' : '' ?>>Parça Bekleniyor</option>
                                                        <option value="Hazır" <?= $kayit['status'] == 'Hazır' ? 'selected' : '' ?>>Hazır</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">İç Notlar</label>
                                                    <textarea class="form-control" name="notes" rows="3" placeholder="Durum değişikliği hakkında not..."><?= htmlspecialchars($kayit['internal_notes'] ?? '') ?></textarea>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-warning w-100">Durumu Güncelle</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php 
                        $query_params = $_GET; 
                        unset($query_params['page']); 
                        $base_query_string = http_build_query($query_params); 
                        ?>
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $base_query_string ?>&page=<?= $current_page - 1 ?>">
                                <i class="fas fa-chevron-left me-1"></i> Önceki
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= $base_query_string ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $base_query_string ?>&page=<?= $current_page + 1 ?>">
                                Sonraki <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Yeni Kayıt Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i> Yeni Cihaz Kabulü</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="service_handler.php" id="yeniKayitForm">
                    <input type="hidden" name="action" value="add_service">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <h6 class="text-primary border-bottom pb-2">1. Müşteri ve Cihaz Bilgileri</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="customer_name" class="form-label">Müşteri Adı *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone_number" class="form-label">Telefon *</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="imei" class="form-label">IMEI</label>
                            <input type="text" class="form-control" id="imei" name="imei" maxlength="15">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="device_model" class="form-label">Cihaz Modeli *</label>
                            <input type="text" class="form-control" id="device_model" name="device_model" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Cihaz Rengi</label>
                            <input type="text" class="form-control" id="color" name="color">
                        </div>
                    </div>
                    
                    <h6 class="text-primary border-bottom pb-2 mt-4">2. Ekspertiz Detayları</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="housing_status" class="form-label">Kasa Durumu</label>
                            <select class="form-select" id="housing_status" name="housing_status">
                                <option value="Temiz">Temiz</option>
                                <option value="Normal">Normal</option>
                                <option value="Cizik_Hafif">Çizik (Hafif)</option>
                                <option value="Cizik_Cok">Çizik (Çok)</option>
                                <option value="Kirik_Hasarli">Kırık / Hasarlı</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="fault_description" class="form-label">Arıza Tanımı *</label>
                            <textarea class="form-control" id="fault_description" name="fault_description" rows="2" required placeholder="Örn: Ekran kırık, şarj girişi bozuk..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="extra_notes" class="form-label">Ek Notlar</label>
                        <textarea class="form-control" id="extra_notes" name="extra_notes" rows="1" placeholder="Örn: Kılıf/Şarj aleti ile alındı, müşteri acil istiyor..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold mt-3">
                        <i class="fas fa-save me-2"></i> Kaydı Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Dosyaları -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>

<script>
// === SERVİS TAMAMLAMA FONKSİYONLARI ===

// Fiyat hesaplama fonksiyonu
function hesaplaToplamTutar(recordId) {
    let malzemeToplami = 0;
    let seciliMalzemeler = [];
    
    // Seçili malzemeleri bul ve toplamı hesapla
    const checkboxes = document.querySelectorAll('#tamamlamaModal' + recordId + ' .malzeme-checkbox:checked');
    
    checkboxes.forEach(checkbox => { // DÜZELTİLDİ: check -> checkboxes
        const maliyet = parseFloat(checkbox.getAttribute('data-maliyet')) || 0;
        const parcaAdi = checkbox.getAttribute('data-parca-adi');
        const satisFiyati = maliyet * 1.5; // %50 kar
        
        malzemeToplami += satisFiyati;
        seciliMalzemeler.push({
            adi: parcaAdi,
            maliyet: maliyet,
            satis: satisFiyati
        });
    });
    
    // İşçilik ücretini al
    const isciUcreti = parseFloat(document.getElementById('isci_ucreti' + recordId).value) || 0;
    
    // Toplam tutarı hesapla
    const toplamTutar = malzemeToplami + isciUcreti;
    
    // Görüntüleri güncelle
    document.getElementById('malzemeToplami' + recordId).textContent = malzemeToplami.toFixed(2) + ' TL';
    
    // Malzeme detayını güncelle
    const malzemeDetay = seciliMalzemeler.length > 0 
        ? seciliMalzemeler.length + ' malzeme seçildi' 
        : 'Seçili malzeme yok';
    document.getElementById('malzemeDetay' + recordId).textContent = malzemeDetay;
    
    document.getElementById('total_price' + recordId).value = toplamTutar.toFixed(2);
    
    return {
        toplam: toplamTutar,
        malzeme: malzemeToplami,
        iscilik: isciUcreti,
        malzemeler: seciliMalzemeler
    };
}

// Malzeme seçimi değiştiğinde
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('malzeme-checkbox')) {
        const modal = e.target.closest('.modal');
        const recordId = modal.id.replace('tamamlamaModal', '');
        hesaplaToplamTutar(recordId);
    }
});

// İşçilik ücreti değiştiğinde
document.addEventListener('input', function(e) {
    if (e.target.id && e.target.id.startsWith('isci_ucreti')) {
        const recordId = e.target.id.replace('isci_ucreti', '');
        hesaplaToplamTutar(recordId);
    }
});

// WhatsApp fiyat gönderme
function whatsappFiyatGonder(recordId) {
    const hesaplama = hesaplaToplamTutar(recordId);
    
    if (hesaplama.toplam <= 0) {
        alert('Lütfen önce toplam tutarı hesaplayın!');
        return;
    }
    
    const musteriBilgi = document.querySelector('#tamamlamaModal' + recordId + ' .info-card:first-child p strong');
    const cihazBilgi = document.querySelector('#tamamlamaModal' + recordId + ' .info-card:nth-child(2) p strong');
    const telefonElement = document.querySelector('#tamamlamaModal' + recordId + ' .info-card:first-child .text-muted');
    
    const musteriAdi = musteriBilgi ? musteriBilgi.textContent.trim() : 'Müşteri';
    const cihazModel = cihazBilgi ? cihazBilgi.textContent.trim() : 'Cihaz';
    const telefon = telefonElement ? telefonElement.textContent.replace('📱', '').trim() : '';
    
    let mesaj = '*DOST GSM - FİYAT TEKLİFİ* 📱\\n\\n';
    mesaj += 'Sayın *' + musteriAdi + '*,\\n';
    mesaj += '*' + cihazModel + '* cihazınız için fiyat teklifimiz:\\n\\n';
    mesaj += '💰 *TOPLAM TUTAR: ' + hesaplama.toplam.toFixed(2) + ' TL*\\n\\n';
    
    if (hesaplama.malzeme > 0) {
        mesaj += '📦 Malzeme Bedeli: ' + hesaplama.malzeme.toFixed(2) + ' TL\\n';
    }
    
    if (hesaplama.iscilik > 0) {
        mesaj += '🔧 İşçilik Ücreti: ' + hesaplama.iscilik.toFixed(2) + ' TL\\n';
    }
    
    mesaj += '\\n✅ *Onayınız durumunda tamire başlayacağız.*\\n\\n';
    mesaj += 'Teşekkür ederiz! 🙏';
    
    // Telefon numarasını temizle
    const temizTelefon = telefon.replace(/\\D/g, '');
    
    if (temizTelefon.length >= 10) {
        const whatsappLink = 'https://wa.me/90' + temizTelefon + '?text=' + encodeURIComponent(mesaj);
        window.open(whatsappLink, '_blank');
        alert('WhatsApp mesajı gönderiliyor...');
    } else {
        alert('Geçerli bir telefon numarası bulunamadı!');
    }
}

// SMS fiyat gönderme
function smsFiyatGonder(recordId) {
    alert('SMS gönderme özelliği geliştirme aşamasındadır. WhatsApp kullanabilirsiniz.');
}

// Modal açıldığında temizle
document.addEventListener('show.bs.modal', function(e) {
    if (e.target.id && e.target.id.startsWith('tamamlamaModal')) {
        const recordId = e.target.id.replace('tamamlamaModal', '');
        
        // Değerleri sıfırla
        const isciUcreti = document.getElementById('isci_ucreti' + recordId);
        const totalPrice = document.getElementById('total_price' + recordId);
        const malzemeToplami = document.getElementById('malzemeToplami' + recordId);
        const malzemeDetay = document.getElementById('malzemeDetay' + recordId);
        
        if (isciUcreti) isciUcreti.value = 0;
        if (totalPrice) totalPrice.value = 0;
        if (malzemeToplami) malzemeToplami.textContent = '0.00 TL';
        if (malzemeDetay) malzemeDetay.textContent = 'Seçili malzeme yok';
        
        // Checkbox'ları temizle
        const checkboxes = e.target.querySelectorAll('.malzeme-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
});

// Form gönderim kontrolü
document.addEventListener('submit', function(e) {
    if (e.target.id && e.target.id.startsWith('tamamlamaForm')) {
        const totalPrice = e.target.querySelector('[name="total_price"]');
        
        if (!totalPrice || !totalPrice.value || parseFloat(totalPrice.value) <= 0) {
            e.preventDefault();
            alert('Lütfen geçerli bir toplam tutar girin!');
            return false;
        }
        
        const seciliMalzemeler = e.target.querySelectorAll('.malzeme-checkbox:checked');
        if (seciliMalzemeler.length > 0) {
            if (!confirm(seciliMalzemeler.length + ' adet malzeme stoktan düşülecek. Emin misiniz?')) {
                e.preventDefault();
                return false;
            }
        }
        
        alert('Servis kaydı tamamlanıyor...');
        return true;
    }
});

// Otomatik tamamlama fonksiyonları
$(function() {
    // Tarih seçici
    $(".datepicker").datepicker({ 
        dateFormat: "yy-mm-dd", 
        changeMonth: true, 
        changeYear: true,
        dayNamesMin: [ "Pz", "Pt", "Sa", "Ça", "Pe", "Cu", "Ct" ],
        monthNamesShort: [ "Oca", "Şub", "Mar", "Nis", "May", "Haz", "Tem", "Ağu", "Eyl", "Eki", "Kas", "Ara" ],
        firstDay: 1 
    });
    
    // Müşteri adı otomatik tamamlama
    $("#customer_name").autocomplete({ 
        source: function(request, response) { 
            $.ajax({ 
                url: "autocomplete_handler.php", 
                dataType: "json", 
                data: { type: "customer_name", query: request.term }, 
                success: function(data) { response(data); }, 
                error: function() { console.log("Autocomplete hatası!"); } 
            }); 
        }, 
        minLength: 2,
        select: function(event, ui) { 
            if (ui.item && ui.item.phone) { 
                $("#phone_number").val(ui.item.phone); 
            } 
        } 
    });
    
    // Telefon numarası otomatik tamamlama
    $("#phone_number").autocomplete({ 
        source: function(request, response) { 
            $.ajax({ 
                url: "autocomplete_handler.php", 
                dataType: "json", 
                data: { type: "phone_number", query: request.term }, 
                success: function(data) { response(data); } 
            }); 
        }, 
        minLength: 3 
    });
    
    // Cihaz modeli otomatik tamamlama
    $("#device_model").autocomplete({ 
        source: function(request, response) { 
            $.ajax({ 
                url: "autocomplete_handler.php", 
                dataType: "json", 
                data: { type: "device_model", query: request.term }, 
                success: function(data) { response(data); } 
            }); 
        }, 
        minLength: 2 
    });
});

// Form doğrulama
document.getElementById('yeniKayitForm').addEventListener('submit', function(e) {
    const customerName = document.getElementById('customer_name').value.trim();
    const phoneNumber = document.getElementById('phone_number').value.trim();
    const deviceModel = document.getElementById('device_model').value.trim();
    const faultDescription = document.getElementById('fault_description').value.trim();
    
    if (!customerName || !phoneNumber || !deviceModel || !faultDescription) {
        e.preventDefault();
        alert('Lütfen zorunlu alanları doldurun! (* işaretli alanlar)');
        return false;
    }
});

// Mobil cihaz kontrolü
function isMobile() {
    return window.innerWidth <= 768;
}

// Mobil cihazlarda otomatik filtre açma
if (isMobile() && (window.location.search.includes('search=') || window.location.search.includes('status='))) {
    const filtreSection = document.getElementById('filtreSection');
    if (filtreSection) {
        new bootstrap.Collapse(filtreSection, { toggle: true });
    }
}
</script>
</body>
</html>