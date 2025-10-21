<?php
require_once '../includes/config.php';

// Yetki kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Admin') {
    header("Location: dashboard.php?status=yetki_yok");
    exit();
}

$message = '';

// Gelişmiş filtreleme parametreleri
$allowed_sort = ['dealer_name', 'last_transaction_date', 'current_balance', 'created_at'];
$allowed_orders = ['ASC', 'DESC'];
$allowed_balance_filters = ['debt', 'credit', 'zero', 'overdue'];

$sort_by = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'dealer_name';
$sort_order = (isset($_GET['order']) && in_array(strtoupper($_GET['order']), $allowed_orders)) ? strtoupper($_GET['order']) : 'ASC';
$balance_filter = in_array($_GET['balance_filter'] ?? '', $allowed_balance_filters) ? $_GET['balance_filter'] : '';
$search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$search_term = substr($search_term, 0, 100);

try {
    // Ana sorgu
    $sql = "SELECT *, 
            DATEDIFF(NOW(), COALESCE(last_transaction_date, created_at)) as days_since_last_transaction
            FROM dealers";
    $params = [];
    $where_conditions = [];

    // Arama filtresi
    if (!empty($search_term)) {
        $where_conditions[] = "(dealer_name LIKE ? OR contact_person LIKE ? OR phone_number LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }

    // Bakiye filtresi
    if (!empty($balance_filter)) {
        switch ($balance_filter) {
            case 'debt':
                $where_conditions[] = "current_balance > 0";
                break;
            case 'credit':
                $where_conditions[] = "current_balance < 0";
                break;
            case 'zero':
                $where_conditions[] = "current_balance = 0";
                break;
            case 'overdue':
                $where_conditions[] = "current_balance > 0 AND last_transaction_date < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }

    // WHERE koşullarını birleştir
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }

    // Sıralama
    $sql .= " ORDER BY `$sort_by` $sort_order";

    $dealer_listesi_stmt = $pdo->prepare($sql);
    $dealer_listesi_stmt->execute($params);
    $dealer_listesi = $dealer_listesi_stmt->fetchAll();

    // Gelişmiş finansal özet
    $financial_summary_sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN current_balance > 0 THEN current_balance ELSE 0 END), 0) as toplam_borc,
            COALESCE((
                SELECT SUM(amount) FROM dealer_transactions 
                WHERE transaction_type = 'Odeme' AND MONTH(created_at) = MONTH(NOW()) 
                AND YEAR(created_at) = YEAR(NOW())
            ), 0) as aylik_tahsilat,
            COALESCE((
                SELECT SUM(amount) FROM dealer_transactions 
                WHERE transaction_type = 'Borc' AND MONTH(created_at) = MONTH(NOW()) 
                AND YEAR(created_at) = YEAR(NOW())
            ), 0) as aylik_borc,
            COALESCE((
                SELECT SUM(amount) FROM dealer_transactions 
                WHERE transaction_type = 'Odeme' AND DATE(created_at) = CURDATE()
            ), 0) as bugun_tahsilat,
            COUNT(*) as toplam_bayi,
            COUNT(CASE WHEN current_balance > 0 THEN 1 END) as borclu_bayi,
            COUNT(CASE WHEN current_balance > 0 AND last_transaction_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as geciken_bayi
        FROM dealers
    ";
    
    $financial_summary = $pdo->query($financial_summary_sql)->fetch();
    
    // Değişkenlere atama
    $toplam_borc = $financial_summary['toplam_borc'] ?? 0;
    $aylik_tahsilat = $financial_summary['aylik_tahsilat'] ?? 0;
    $aylik_borc = $financial_summary['aylik_borc'] ?? 0;
    $bugun_tahsilat = $financial_summary['bugun_tahsilat'] ?? 0;
    $toplam_bayi = $financial_summary['toplam_bayi'] ?? 0;
    $borclu_bayi = $financial_summary['borclu_bayi'] ?? 0;
    $geciken_bayi = $financial_summary['geciken_bayi'] ?? 0;

} catch (PDOException $e) {
    error_log("Database error in dealers.php: " . $e->getMessage());
    $message = '<div class="alert alert-danger">Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.</div>';
    $dealer_listesi = [];
    // Varsayılan değerler
    $toplam_borc = $aylik_tahsilat = $aylik_borc = $bugun_tahsilat = 0;
    $toplam_bayi = $borclu_bayi = $geciken_bayi = 0;
}

// Sıralama linkleri için yardımcı fonksiyon
function getSortLink($column, $current_sort, $current_order) {
    $order = ($current_sort == $column && $current_order == 'ASC') ? 'DESC' : 'ASC';
    $query_params = $_GET;
    $query_params['sort'] = $column;
    $query_params['order'] = $order;
    unset($query_params['page']);
    return http_build_query($query_params);
}

// Sıralama ikonları için yardımcı fonksiyon
function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort == $column) {
        return $current_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort"></i>';
}

// Bakiye durumu için renk sınıfı
function getBalanceClass($balance, $days_since_transaction = 0) {
    if ($balance > 0) {
        if ($days_since_transaction > 30) {
            return 'balance-overdue';
        } elseif ($balance > 5000) {
            return 'balance-high';
        } else {
            return 'balance-medium';
        }
    } elseif ($balance < 0) {
        return 'balance-credit';
    } else {
        return 'balance-zero';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veresiye Yönetimi | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        /* Mobil Optimizasyon */
        @media (max-width: 768px) {
            .main-content { padding: 0.5rem !important; }
            .card-body { padding: 1rem; }
            .table-responsive { 
                font-size: 0.875rem;
                border: 1px solid #dee2e6;
            }
            .financial-cards .col-lg-4 { 
                margin-bottom: 1rem; 
            }
            .btn-group-mobile { 
                display: flex; 
                flex-direction: column; 
                gap: 0.5rem; 
            }
            .btn-group-mobile .btn { 
                width: 100%; 
                margin-bottom: 0.5rem;
            }
            .dealer-actions { 
                flex-direction: column; 
                gap: 0.5rem;
            }
            .modal-footer .ms-auto { 
                margin-left: 0 !important; 
                margin-top: 1rem; 
            }
            .stats-badge { 
                font-size: 0.7rem; 
                margin-bottom: 0.5rem;
            }
            .quick-actions {
                position: sticky;
                top: 0;
                z-index: 1020;
                background: white;
                padding: 1rem 0;
                border-bottom: 1px solid #dee2e6;
            }
        }

        /* Kart stilleri */
        .financial-card { 
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
            height: 100%;
        }
        .financial-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; 
        }
        
        /* Bakiye renkleri */
        .balance-high { color: #e74a3b; font-weight: bold; }
        .balance-medium { color: #f6c23e; font-weight: bold; }
        .balance-overdue { 
            color: #e74a3b; 
            font-weight: bold;
            background-color: #f8d7da;
            padding: 2px 6px;
            border-radius: 4px;
            animation: pulse 2s infinite;
        }
        .balance-credit { color: #1cc88a; font-weight: bold; }
        .balance-zero { color: #6c757d; }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Tablo stilleri */
        .dealer-row:hover { 
            cursor: pointer; 
            background-color: #f8f9fa; 
            transition: background-color 0.2s ease;
        }
        
        .border-start-danger { border-left: .25rem solid #e74a3b !important; }
        .border-start-success { border-left: .25rem solid #1cc88a !important; }
        .border-start-info { border-left: .25rem solid #36b9cc !important; }
        .border-start-warning { border-left: .25rem solid #f6c23e !important; }
        
        .text-xs { font-size: .7rem; }
        
        .table th a { 
            text-decoration: none; 
            color: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .table th a:hover { color: #0d6efd; }
        .sort-icon { margin-left: 5px; }
        
        /* İstatistik badge */
        .stats-badge { 
            font-size: 0.75rem; 
            position: absolute;
            top: -5px;
            right: -5px;
        }
        
        /* Quick actions */
        .quick-actions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }
        
        /* Filtre grupları */
        .filter-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        /* Yükleme animasyonu */
        .modal-loader { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 200px; 
        }
        
        /* Chip stili */
        .filter-chip {
            display: inline-flex;
            align-items: center;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin: 0.25rem;
        }
    </style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'header.php'; ?>
    
    <div class="container-fluid">
        <!-- Başlık ve Hızlı İşlemler -->
        <div class="quick-actions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0 text-white"><i class="fas fa-handshake me-2"></i> Veresiye Takibi</h2>
                    <p class="mb-0 text-white-50">Bayi hesap yönetimi ve takip sistemi</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addDealerModal">
                        <i class="fas fa-user-plus me-1"></i> Yeni Bayi
                    </button>
                    <button class="btn btn-outline-light" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                </div>
            </div>
        </div>

        <?= $message ?>

        <!-- Filtreleme Bölümü -->
        <div class="collapse" id="filterSection">
            <div class="filter-group">
                <form action="dealers.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Bakiye Durumu</label>
                        <select name="balance_filter" class="form-select">
                            <option value="">Tümü</option>
                            <option value="debt" <?= $balance_filter === 'debt' ? 'selected' : '' ?>>Borçlular</option>
                            <option value="credit" <?= $balance_filter === 'credit' ? 'selected' : '' ?>>Alacaklılar</option>
                            <option value="zero" <?= $balance_filter === 'zero' ? 'selected' : '' ?>>Bakiyesi Sıfır</option>
                            <option value="overdue" <?= $balance_filter === 'overdue' ? 'selected' : '' ?>>Geciken Borçlar</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Bayi Ara</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Bayi adı, yetkili kişi veya telefon..." value="<?= htmlspecialchars($search_term) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Uygula</button>
                            <a href="dealers.php" class="btn btn-outline-secondary">Sıfırla</a>
                        </div>
                    </div>
                </form>
                
                <!-- Aktif Filtreler -->
                <?php if (!empty($search_term) || !empty($balance_filter)): ?>
                <div class="mt-3">
                    <small class="text-muted">Aktif Filtreler:</small>
                    <?php if (!empty($search_term)): ?>
                        <span class="filter-chip">
                            Arama: "<?= htmlspecialchars($search_term) ?>"
                            <a href="?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>" class="ms-2 text-danger"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($balance_filter)): ?>
                        <span class="filter-chip">
                            Durum: <?= $balance_filter === 'debt' ? 'Borçlular' : ($balance_filter === 'credit' ? 'Alacaklılar' : ($balance_filter === 'zero' ? 'Bakiyesi Sıfır' : 'Geciken Borçlar')) ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['balance_filter' => ''])) ?>" class="ms-2 text-danger"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Finansal Özet Kartları -->
        <div class="row financial-cards mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-primary shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Toplam Bayi</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $toplam_bayi ?></div>
                        <div class="mt-2 text-xs text-muted">Aktif bayi sayısı</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-danger shadow h-100">
                    <div class="card-body position-relative">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Toplam Borç</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= number_format($toplam_borc, 2, ',', '.') ?> TL</div>
                        <span class="badge bg-danger stats-badge"><?= $borclu_bayi ?> bayi</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-warning shadow h-100">
                    <div class="card-body position-relative">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Geciken Borç</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $geciken_bayi ?> Bayi</div>
                        <div class="mt-2 text-xs text-muted">30+ gündür</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-success shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Bu Ay Tahsilat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= number_format($aylik_tahsilat, 2, ',', '.') ?> TL</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-info shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Bu Ay Borç</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= number_format($aylik_borc, 2, ',', '.') ?> TL</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card financial-card border-start-secondary shadow h-100">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Bugünkü Tahsilat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= number_format($bugun_tahsilat, 2, ',', '.') ?> TL</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bayi Listesi Tablosu -->
        <div class="card shadow">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <div>
                    Bayi Hesapları 
                    <small class="text-muted">(Detay için satıra tıklayın)</small>
                </div>
                <div>
                    <span class="badge bg-primary"><?= count($dealer_listesi) ?> bayi listeleniyor</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="table-secondary">
                                <th width="25%">
                                    <a href="?<?= getSortLink('dealer_name', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        <span>Bayi Adı</span>
                                        <span class="sort-icon"><?= getSortIcon('dealer_name', $sort_by, $sort_order) ?></span>
                                    </a>
                                </th>
                                <th width="25%">İletişim</th>
                                <th width="20%">
                                    <a href="?<?= getSortLink('last_transaction_date', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        <span>Son İşlem</span>
                                        <span class="sort-icon"><?= getSortIcon('last_transaction_date', $sort_by, $sort_order) ?></span>
                                    </a>
                                </th>
                                <th width="20%">
                                    <a href="?<?= getSortLink('current_balance', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        <span>Bakiye (TL)</span>
                                        <span class="sort-icon"><?= getSortIcon('current_balance', $sort_by, $sort_order) ?></span>
                                    </a>
                                </th>
                                <th width="10%" class="text-center">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dealer_listesi)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">
                                        <?php if (!empty($search_term) || !empty($balance_filter)): ?>
                                        "<?= htmlspecialchars($search_term) ?>" için sonuç bulunamadı.
                                        <?php else: ?>
                                        Henüz bayi kaydı bulunmuyor.
                                        <?php endif; ?>
                                    </h5>
                                    <?php if (!empty($search_term) || !empty($balance_filter)): ?>
                                    <a href="dealers.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-times me-1"></i> Filtreleri Temizle
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#addDealerModal">
                                        <i class="fas fa-user-plus me-1"></i> İlk Bayiyi Ekle
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($dealer_listesi as $dealer): ?>
                            <?php
                                $balance_class = getBalanceClass(
                                    $dealer['current_balance'], 
                                    $dealer['days_since_last_transaction']
                                );
                            ?>
                            <tr class="dealer-row" data-bs-toggle="modal" data-bs-target="#dealerDetailModal" data-dealer-id="<?= $dealer['id'] ?>">
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($dealer['dealer_name']) ?></div>
                                    <small class="text-muted">ID: <?= $dealer['id'] ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($dealer['contact_person'] ?? 'Belirtilmemiş') ?></div>
                                    <?php if (!empty($dealer['phone_number'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($dealer['phone_number']) ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($dealer['last_transaction_date'])): ?>
                                        <div><?= date('d.m.Y', strtotime($dealer['last_transaction_date'])) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($dealer['last_transaction_date'])) ?></small>
                                        <?php if ($dealer['days_since_last_transaction'] > 30): ?>
                                        <div class="text-danger small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?= $dealer['days_since_last_transaction'] ?> gündür
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">İşlem yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?= $balance_class ?>">
                                        <?= number_format($dealer['current_balance'], 2, ',', '.') ?> TL
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($balance_class === 'balance-overdue'): ?>
                                        <span class="badge bg-danger" title="Geciken borç">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                    <?php elseif ($balance_class === 'balance-high'): ?>
                                        <span class="badge bg-warning text-dark" title="Yüksek borç">
                                            <i class="fas fa-arrow-up"></i>
                                        </span>
                                    <?php elseif ($balance_class === 'balance-medium'): ?>
                                        <span class="badge bg-info" title="Orta borç">
                                            <i class="fas fa-minus"></i>
                                        </span>
                                    <?php elseif ($balance_class === 'balance-credit'): ?>
                                        <span class="badge bg-success" title="Alacak">
                                            <i class="fas fa-arrow-down"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" title="Temiz">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Include'ları -->
<?php 
if(file_exists('modals/dealer_detail_modal.php')) include 'modals/dealer_detail_modal.php';
if(file_exists('modals/dealer_statement_modal.php')) include 'modals/dealer_statement_modal.php';
if(file_exists('modals/transaction_modal.php')) include 'modals/transaction_modal.php';
if(file_exists('modals/edit_dealer_modal.php')) include 'modals/edit_dealer_modal.php';
?>

<!-- Yeni Bayi Ekleme Modal -->
<div class="modal fade" id="addDealerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Yeni Bayi Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="dealers_handler.php" id="addDealerForm">
                    <input type="hidden" name="action" value="add_dealer">
                    <div class="mb-3">
                        <label for="add_dealer_name" class="form-label">Bayi/Firma Adı *</label>
                        <input type="text" class="form-control" id="add_dealer_name" name="dealer_name" required maxlength="255" placeholder="Bayi veya firma adını girin">
                    </div>
                    <div class="mb-3">
                        <label for="add_contact_person" class="form-label">İlgili Kişi</label>
                        <input type="text" class="form-control" id="add_contact_person" name="contact_person" maxlength="100" placeholder="Yetkili kişi adı">
                    </div>
                    <div class="mb-3">
                        <label for="add_phone_number" class="form-label">Telefon No</label>
                        <input type="text" class="form-control" id="add_phone_number" name="phone_number" maxlength="20" placeholder="05XX XXX XX XX">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold mt-2">
                        <i class="fas fa-save me-2"></i>Bayi Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>
<script>
// Global fonksiyon - onclick event'leri için gerekli
function setTransactionType(button, type) {
    const targetModalId = button.getAttribute('data-bs-target');
    const modal = document.querySelector(targetModalId);
    if (modal) {
        const hiddenInput = modal.querySelector('input[name="transaction_type"]');
        if (hiddenInput) {
            hiddenInput.value = type;
            // Modal başlığını güncelle
            const modalTitle = modal.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = type === 'Borc' ? 'Borç Ekle' : 'Ödeme Al';
            }
        }
    }
}

// Sayfa tamamen yüklendikten sonra çalışacak olan kodlar
document.addEventListener('DOMContentLoaded', function() {
    // Bayi detay modal'ı
    const detailModal = document.getElementById('dealerDetailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function(event) {
            const row = event.relatedTarget;
            const dealerId = row.getAttribute('data-dealer-id');
            const modalTitle = document.getElementById('dealerDetailModalLabel');
            const modalLoader = document.getElementById('modalLoader');
            const modalContent = document.getElementById('modalContent');
            const modalFooter = document.getElementById('modalFooterActions');
            
            modalTitle.innerText = 'Bayi Detayları Yükleniyor...';
            modalLoader.classList.remove('d-none');
            modalContent.classList.add('d-none');
            modalFooter.innerHTML = '';
            
            fetch(`get_dealer_details.php?dealer_id=${dealerId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    modalLoader.classList.add('d-none');
                    modalContent.classList.remove('d-none');
                    
                    if (data.status === 'success') {
                        const details = data.data;
                        modalTitle.innerText = details.dealer_name + ' - Detay Kartı';
                        document.getElementById('modalDealerName').innerText = details.dealer_name;
                        document.getElementById('modalContactPerson').innerText = details.contact_person || '-';
                        document.getElementById('modalPhoneNumber').innerText = details.phone_number || '-';
                        document.getElementById('modalCurrentBalance').innerText = details.current_balance_formatted;
                        document.getElementById('modalTransactionsTableBody').innerHTML = details.transactions_html;
                        
                        modalFooter.innerHTML = `
                            <div class="btn-group-mobile w-100">
                                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#editDealerModal${dealerId}">
                                    <i class="fas fa-edit me-1"></i> Düzenle
                                </button>
                                <a href="export_dealer_csv.php?dealer_id=${dealerId}" class="btn btn-outline-secondary" download>
                                    <i class="fas fa-file-excel me-1"></i> Excel
                                </a>
                                <form method="POST" action="dealers_handler.php" class="d-inline" onsubmit="return confirm('Bu bayinin tüm işlem geçmişini silmek ve bakiyesini sıfırlamak istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                    <input type="hidden" name="action" value="clear_history">
                                    <input type="hidden" name="dealer_id" value="${dealerId}">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt me-1"></i> Temizle
                                    </button>
                                </form>
                                <div class="dealer-actions d-flex gap-2">
                                   <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addTransactionModal${dealerId}" onclick="setTransactionType(this, 'Borc')">
                                       <i class="fas fa-plus me-1"></i> Borç
                                   </button>
                                   <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal${dealerId}" onclick="setTransactionType(this, 'Odeme')">
                                       <i class="fas fa-minus me-1"></i> Ödeme
                                   </button>
                                   <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#statementModal" data-dealer-id="${dealerId}">
                                       <i class="fab fa-whatsapp me-1"></i> Ekstre
                                   </button>
                                </div>
                            </div>
                        `;
                    } else {
                        modalTitle.innerText = 'Hata';
                        document.getElementById('modalTransactionsTableBody').innerHTML = 
                            `<tr><td colspan="3" class="text-center text-danger">${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalLoader.classList.add('d-none');
                    modalContent.classList.remove('d-none');
                    modalTitle.innerText = 'Ağ Hatası';
                    document.getElementById('modalTransactionsTableBody').innerHTML = 
                        `<tr><td colspan="3" class="text-center text-danger">Sunucuya ulaşılamadı. Lütfen internet bağlantınızı kontrol edin.</td></tr>`;
                });
        });
    }

    // Ekstre modal'ı
    const statementModal = document.getElementById('statementModal');
    if (statementModal) {
        const messagePreviewTextarea = document.getElementById('messagePreview');
        const sendWhatsAppButton = document.getElementById('sendWhatsAppButton');
        
        statementModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const dealerId = button.getAttribute('data-dealer-id');
            
            messagePreviewTextarea.value = 'Hesap ekstresi yükleniyor...';
            sendWhatsAppButton.href = '#';
            sendWhatsAppButton.classList.add('disabled');
            
            fetch(`get_dealer_statement.php?dealer_id=${dealerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        messagePreviewTextarea.value = data.message_preview;
                        sendWhatsAppButton.href = data.whatsapp_link;
                        sendWhatsAppButton.classList.remove('disabled');
                    } else {
                        messagePreviewTextarea.value = 'Hata: ' + data.message;
                    }
                })
                .catch(error => {
                    messagePreviewTextarea.value = 'Sunucuya bağlanırken bir hata oluştu. Lütfen tekrar deneyin.';
                });
        });
    }

    // Form validasyonu
    const addDealerForm = document.getElementById('addDealerForm');
    if (addDealerForm) {
        addDealerForm.addEventListener('submit', function(e) {
            const dealerName = document.getElementById('add_dealer_name').value.trim();
            if (!dealerName) {
                e.preventDefault();
                alert('Bayi adı zorunludur!');
                return false;
            }
        });
    }

    // Mobil cihaz kontrolü
    function isMobile() {
        return window.innerWidth <= 768;
    }

    // Mobil cihazlarda otomatik filtre açma/kapama
    if (isMobile() && (window.location.search.includes('search=') || window.location.search.includes('balance_filter='))) {
        const filterSection = document.getElementById('filterSection');
        if (filterSection) {
            new bootstrap.Collapse(filterSection, { toggle: true });
        }
    }
});
</script>
</body>
</html>