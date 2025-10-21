<?php
require_once '../includes/config.php'; 
require_once '../includes/barcode_generator.php'; 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../index.php"); exit(); }
$current_user_role = $_SESSION['user_role'] ?? 'Misafir'; 
$isAdmin = ($current_user_role === 'Admin');
if ($current_user_role !== 'Admin' && $current_user_role !== 'Stok') { header("Location: dashboard.php?status=yetki_yok"); exit(); }
$message = ''; 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add_part' || $action === 'edit_part') {
            // VERİLERİ BÜYÜK HARFE ÇEVİREREK ALMA
            $part_name = mb_strtoupper(filter_input(INPUT_POST, 'part_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
            $sku = mb_strtoupper(filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
            $location = mb_strtoupper(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
            
            // Diğer veriler
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            $min_stock_level = filter_input(INPUT_POST, 'min_stock_level', FILTER_VALIDATE_INT);
            
            if ($action === 'add_part') {
                $stmt = $pdo->prepare("INSERT INTO inventory (part_name, sku, quantity, min_stock_level, location) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$part_name, $sku, $quantity, $min_stock_level, $location]);
                $message = '<div class="alert alert-success">Yeni parça eklendi!</div>';
            } else {
                $part_id = filter_input(INPUT_POST, 'part_id', FILTER_VALIDATE_INT);
                if ($part_id) {
                    $stmt = $pdo->prepare("UPDATE inventory SET part_name = ?, sku = ?, quantity = ?, min_stock_level = ?, location = ? WHERE id = ?");
                    $stmt->execute([$part_name, $sku, $quantity, $min_stock_level, $location, $part_id]);
                    $message = '<div class="alert alert-success">Parça güncellendi!</div>';
                }
            }
        } elseif ($action === 'delete_part' && $isAdmin) {
             $part_id = filter_input(INPUT_POST, 'part_id', FILTER_VALIDATE_INT);
             if($part_id){ $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?"); $stmt->execute([$part_id]); $message = '<div class="alert alert-success">Parça silindi.</div>'; }
        }
    } catch (PDOException $e) { $message = '<div class="alert alert-danger">Veritabanı hatası.</div>'; }
}
$records_per_page = 20;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($current_page < 1) $current_page = 1;
$search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$base_sql = "FROM inventory i";
$where_sql = '';
$params = [];
if (!empty($search_term)) { $where_sql = " WHERE (i.part_name LIKE ? OR i.sku LIKE ? OR i.location LIKE ?)"; $wildcard_search = "%" . $search_term . "%"; $params = [$wildcard_search, $wildcard_search, $wildcard_search]; }
$total_records_sql = "SELECT COUNT(i.id) " . $base_sql . $where_sql;
$total_records_stmt = $pdo->prepare($total_records_sql);
$total_records_stmt->execute($params);
$total_records = $total_records_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
$offset = ($current_page - 1) * $records_per_page;
$records_sql = "SELECT i.* " . $base_sql . $where_sql . " ORDER BY i.part_name ASC LIMIT ? OFFSET ?";
$stok_listesi_stmt = $pdo->prepare($records_sql);
$param_index = 1;
foreach ($params as $param) { $stok_listesi_stmt->bindValue($param_index++, $param); }
$stok_listesi_stmt->bindValue($param_index++, (int) $records_per_page, PDO::PARAM_INT);
$stok_listesi_stmt->bindValue($param_index, (int) $offset, PDO::PARAM_INT);
$stok_listesi_stmt->execute();
$stok_listesi = $stok_listesi_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Yönetimi | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>.kritik-stok { background-color: #fcebeb; color: #c0392b; font-weight: bold; }</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'header.php'; ?>
    <h2 class="mb-4 text-primary"><i class="fas fa-boxes me-2"></i> Stok Yönetimi</h2>
    <?= $message ?> 
    <button class="btn btn-warning mb-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#addPartModal"><i class="fas fa-plus-circle me-2"></i> Yeni Parça Ekle</button>
    <div class="card shadow mb-4"><div class="card-body"><form action="inventory.php" method="GET" class="row g-3 align-items-center"><div class="col"><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="text" class="form-control" name="search" placeholder="Parça Adı, SKU, Konum..." value="<?= htmlspecialchars($search_term) ?>"></div></div><div class="col-auto"><button type="submit" class="btn btn-primary">Ara</button></div></form></div></div>
    <div class="card shadow">
        <div class="card-header fw-bold">Stok Listesi (Toplam: <?= $total_records ?> Kayıt)</div>
        <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover align-middle">
            <thead><tr class="table-secondary"><th>ID</th><th>Parça Adı</th><th>SKU</th><th>Mevcut</th><th>Konum</th><th>Durum</th><th>İşlemler</th></tr></thead>
            <tbody>
                 <?php if (count($stok_listesi) > 0): ?>
                    <?php foreach ($stok_listesi as $stok): ?>
                    <tr class="<?= $stok['quantity'] <= $stok['min_stock_level'] ? 'kritik-stok' : '' ?>">
                        <td><?= $stok['id'] ?></td><td><?= htmlspecialchars($stok['part_name']) ?></td><td><?= htmlspecialchars($stok['sku']) ?></td><td><?= $stok['quantity'] ?></td><td><b><?= htmlspecialchars($stok['location'] ?? '-') ?></b></td>
                        <td>
                            <?php if ($stok['quantity'] == 0): ?><span class="badge bg-dark">Bitti</span>
                            <?php elseif ($stok['quantity'] <= $stok['min_stock_level']): ?><span class="badge bg-danger">KRİTİK!</span>
                            <?php else: ?><span class="badge bg-success">Yeterli</span><?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" title="Düzenle" data-bs-toggle="modal" data-bs-target="#editPartModal<?= $stok['id'] ?>"><i class="fas fa-edit"></i></button>
                            <?php if ($isAdmin): ?><form method="POST" action="inventory.php" class="d-inline" onsubmit="return confirm('Bu parçayı silmek istediğinizden emin misiniz?');"><input type="hidden" name="action" value="delete_part"><input type="hidden" name="part_id" value="<?= $stok['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" title="Sil"><i class="fas fa-trash"></i></button></form><?php endif; ?>
                        </td>
                    </tr>
                    <div class="modal fade" id="editPartModal<?= $stok['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Parçayı Düzenle: <?= htmlspecialchars($stok['part_name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="inventory.php"><input type="hidden" name="action" value="edit_part"><input type="hidden" name="part_id" value="<?= $stok['id'] ?>"><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Parça Adı *</label><input type="text" class="form-control" name="part_name" value="<?= htmlspecialchars($stok['part_name']) ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">SKU</label><input type="text" class="form-control" name="sku" value="<?= htmlspecialchars($stok['sku'] ?? '') ?>"></div></div><div class="row"><div class="col-md-4 mb-3"><label class="form-label">Mevcut Adet *</label><input type="number" class="form-control" name="quantity" value="<?= $stok['quantity'] ?>" required></div><div class="col-md-4 mb-3"><label class="form-label">Kritik Stok Seviyesi *</label><input type="number" class="form-control" name="min_stock_level" value="<?= $stok['min_stock_level'] ?>" required></div><div class="col-md-4 mb-3"><label class="form-label">Konum</label><input type="text" class="form-control" name="location" value="<?= htmlspecialchars($stok['location'] ?? '') ?>"></div></div><button type="submit" class="btn btn-primary w-100 fw-bold">Değişiklikleri Kaydet</button></form></div></div></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Aradığınız kriterlere uygun parça bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4"><ul class="pagination justify-content-center">
                <?php $query_params = $_GET; unset($query_params['page']); $base_query_string = http_build_query($query_params); ?>
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $base_query_string ?>&page=<?= $current_page - 1 ?>">Önceki</a></li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?<?= $base_query_string ?>&page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?>
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $base_query_string ?>&page=<?= $current_page + 1 ?>">Sonraki</a></li>
        </ul></nav>
        <?php endif; ?>
        </div>
    </div>
</main>
<div class="modal fade" id="addPartModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Yeni Stok Parçası Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="inventory.php"><input type="hidden" name="action" value="add_part"><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Parça Adı *</label><input type="text" class="form-control" name="part_name" required></div><div class="col-md-6 mb-3"><label class="form-label">SKU</label><input type="text" class="form-control" name="sku"></div></div><div class="row"><div class="col-md-4 mb-3"><label class="form-label">Giriş Miktarı *</label><input type="number" class="form-control" name="quantity" required></div><div class="col-md-4 mb-3"><label class="form-label">Kritik Stok Seviyesi *</label><input type="number" class="form-control" name="min_stock_level" required></div><div class="col-md-4 mb-3"><label class="form-label">Konum</label><input type="text" class="form-control" name="location" placeholder="Örn: Kutu 5, Raf B"></div></div><button type="submit" class="btn btn-success w-100 fw-bold">Yeni Parçayı Kaydet</button></form></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>
</body>
</html>