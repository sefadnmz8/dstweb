<?php
require_once '../includes/config.php'; 
date_default_timezone_set('Europe/Istanbul'); 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Admin') { header("Location: dashboard.php?status=yetki_yok"); exit(); }
$alarm_stoklar = [];
try { $alarm_stok_stmt = $pdo->query("SELECT i.part_name, i.sku, i.quantity AS mevcut_miktar, i.min_stock_level AS kritik_seviye, s.supplier_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id WHERE i.quantity <= i.min_stock_level ORDER BY i.part_name ASC"); $alarm_stoklar = $alarm_stok_stmt->fetchAll(); } catch(Exception $e){}
$aylik_populerler = [];
try { $aylik_populerler_stmt = $pdo->query("SELECT T1.part_name, T1.sku, T1.unit_cost, SUM(T2.quantity_used) AS toplam_kullanim_miktari FROM inventory AS T1 JOIN usage_logs AS T2 ON T1.id = T2.inventory_id WHERE T2.date_used >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY T1.id ORDER BY toplam_kullanim_miktari DESC"); $aylik_populerler = $aylik_populerler_stmt->fetchAll(); } catch(Exception $e){}
$gunluk_kullanim = [];
try { $gunluk_kullanim_stmt = $pdo->query("SELECT T1.part_name, T1.sku, SUM(T2.quantity_used) AS bugunku_kullanim_miktari FROM inventory AS T1 JOIN usage_logs AS T2 ON T1.id = T2.inventory_id WHERE DATE(T2.date_used) = CURDATE() GROUP BY T1.id ORDER BY T1.part_name ASC"); $gunluk_kullanim = $gunluk_kullanim_stmt->fetchAll(); } catch(Exception $e){}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaylı Raporlar | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'header.php'; ?>
    <h2 class="mb-4 text-primary"><i class="fas fa-chart-line me-2"></i> Detaylı Stok Analizi Raporları</h2>
    <div class="card shadow mb-5 border-danger">
        <div class="card-header bg-danger text-white fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Kritik Stok Alarmı</div>
        <div class="card-body">
            <?php if (count($alarm_stoklar) > 0): ?>
                <div class="table-responsive"><table class="table table-bordered table-striped table-sm"><thead><tr class="table-danger"><th>Parça Adı</th><th>SKU</th><th>Tedarikçi</th><th>Mevcut</th><th>Kritik</th></tr></thead><tbody>
                    <?php foreach ($alarm_stoklar as $stok): ?>
                        <tr><td><?= htmlspecialchars($stok['part_name']) ?></td><td><?= htmlspecialchars($stok['sku']) ?></td><td><?= htmlspecialchars($stok['supplier_name'] ?? 'Yok') ?></td><td class="fw-bold"><?= $stok['mevcut_miktar'] ?></td><td><?= $stok['kritik_seviye'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?>
                <p class="text-success fw-bold">Tebrikler! Kritik seviyede parça bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="card shadow mb-5">
        <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-fire me-2"></i> Talep Analizi (Son 30 Gün)</div>
        <div class="card-body">
            <?php if (count($aylik_populerler) > 0): ?>
                <div class="table-responsive"><table class="table table-bordered table-striped table-sm"><thead><tr class="table-info"><th>#</th><th>Parça Adı</th><th>SKU</th><th>Toplam Kullanım</th><th>Birim Maliyet</th></tr></thead><tbody>
                    <?php $sira = 1; foreach ($aylik_populerler as $populer): ?>
                        <tr><td class="fw-bold"><?= $sira++ ?></td><td><?= htmlspecialchars($populer['part_name']) ?></td><td><?= htmlspecialchars($populer['sku']) ?></td><td class="fw-bold text-primary"><?= $populer['toplam_kullanim_miktari'] ?> Adet</td><td><?= number_format($populer['unit_cost'] ?? 0, 2) ?> TL</td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?>
                <p class="text-muted">Son 30 güne ait kullanım verisi bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="card shadow mb-5">
        <div class="card-header bg-success text-white fw-bold"><i class="fas fa-calendar-day me-2"></i> Gün Sonu Envanter Kontrolü</div>
        <div class="card-body">
            <?php if (count($gunluk_kullanim) > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($gunluk_kullanim as $kullanim): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center"><?= htmlspecialchars($kullanim['part_name']) ?> (SKU: <?= htmlspecialchars($kullanim['sku']) ?>)<span class="badge bg-secondary p-2"><?= $kullanim['bugunku_kullanim_miktari'] ?> Adet</span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">Bugün henüz bir parça kullanımı kaydedilmemiştir.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>
</body>
</html>