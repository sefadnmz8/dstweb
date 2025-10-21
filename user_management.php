<?php
require_once '../includes/config.php'; 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../index.php"); exit(); }
$current_user_role = $_SESSION['user_role'] ?? 'Misafir';
$isAdmin = ($current_user_role === 'Admin');
if (!$isAdmin) { header("Location: dashboard.php?status=yetki_yok"); exit(); }
$message = ''; 
if (isset($_GET['status']) && $_GET['status'] == 'permissions_updated') { $message = '<div class="alert alert-success">Rol yetkileri başarıyla güncellendi!</div>'; }
$roles = ['Admin', 'Stok', 'Servis'];
$all_menu_items = $pdo->query("SELECT id, menu_title, page_url FROM menu_items ORDER BY display_order ASC")->fetchAll();
$permissions_stmt = $pdo->query("SELECT role_name, menu_item_id FROM role_permissions");
$current_permissions = [];
foreach ($permissions_stmt->fetchAll() as $perm) { $current_permissions[$perm['role_name']][$perm['menu_item_id']] = true; }
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    if ($action === 'add_user') {
        if ($email && in_array($role, $roles)) {
            try {
                $isAdminFlag = ($role === 'Admin') ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO users (email, is_admin, role) VALUES (?, ?, ?)");
                $stmt->execute([$email, $isAdminFlag, $role]);
                $message = '<div class="alert alert-success">Kullanıcı başarıyla eklendi!</div>';
            } catch (PDOException $e) { $message = '<div class="alert alert-danger">Hata: Kullanıcı zaten mevcut.</div>'; }
        }
    } elseif ($action === 'update_user') {
        if ($user_id && $user_id == $_SESSION['user_id']) {
             $message = '<div class="alert alert-danger">Kendi yetkinizi değiştiremezsiniz.</div>';
        } elseif ($user_id && $role && in_array($role, $roles)) {
            try {
                $isAdminFlag = ($role === 'Admin') ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE users SET role = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$role, $isAdminFlag, $user_id]);
                $message = '<div class="alert alert-success">Kullanıcı yetkisi güncellendi!</div>';
            } catch (PDOException $e) { $message = '<div class="alert alert-danger">Yetki güncelleme başarısız.</div>'; }
        }
    } elseif ($action === 'delete_user') {
        if ($user_id && $user_id != $_SESSION['user_id']) {
             try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = '<div class="alert alert-success">Kullanıcı başarıyla silindi.</div>';
            } catch (PDOException $e) { $message = '<div class="alert alert-danger">Kullanıcı silinemedi.</div>'; }
        } else { $message = '<div class="alert alert-danger">Kendi hesabınızı silemezsiniz.</div>'; }
    }
}
$users = $pdo->query("SELECT id, email, role FROM users ORDER BY role, email ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'header.php'; ?>
    <h2 class="mb-4 text-primary"><i class="fas fa-users-cog me-2"></i> Kullanıcı ve Yetki Yönetimi</h2>
    <?= $message ?> 
    <button class="btn btn-success mb-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-user-plus me-2"></i> Yeni Çalışan Ekle</button>
    <div class="card shadow">
        <div class="card-header fw-bold">Mevcut Çalışanlar (<?= count($users) ?> Kişi)</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead><tr class="table-secondary"><th># ID</th><th>E-posta</th><th>Yetki Rolü</th><th>İşlemler</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td><td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?php $badge_class = ($user['role'] == 'Admin') ? 'bg-danger' : (($user['role'] == 'Stok') ? 'bg-warning text-dark' : 'bg-primary');?><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($user['role']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info me-1" title="Yetkiyi Düzenle" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['id'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="user_management.php" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= $user['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" title="Sil"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                        <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-header bg-info text-white"><h6 class="modal-title">Yetki Düzenle</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="user_management.php"><input type="hidden" name="action" value="update_user"><input type="hidden" name="user_id" value="<?= $user['id'] ?>"><div class="mb-3"><label class="form-label">Yeni Yetki Rolü</label><select class="form-select" name="role" required><?php foreach ($roles as $role_option): ?><option value="<?= $role_option ?>" <?= ($user['role'] === $role_option) ? 'selected' : '' ?>><?= $role_option ?></option><?php endforeach; ?></select></div><button type="submit" class="btn btn-success w-100">Kaydet</button></form></div></div></div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card shadow mt-5">
        <div class="card-header fw-bold bg-primary text-white"><i class="fas fa-user-shield me-2"></i> Rol Yetkileri ve Menü Erişimi</div>
        <div class="card-body">
            <p class="small text-muted">Hangi rolün hangi menüyü göreceğini buradan ayarlayabilirsiniz.</p>
            <form action="permission_handler.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle">
                        <thead><tr class="table-light"><th class="text-start ps-3">Menü Sayfası</th><?php foreach ($roles as $role): ?><th><h5><?= htmlspecialchars($role) ?></h5></th><?php endforeach; ?></tr></thead>
                        <tbody>
                            <?php foreach ($all_menu_items as $item): ?>
                            <tr>
                                <td class="text-start ps-3 fw-bold"><?= htmlspecialchars($item['menu_title']) ?></td>
                                <?php foreach ($roles as $role): ?>
                                <td><div class="form-check form-switch d-flex justify-content-center fs-4"><input class="form-check-input" type="checkbox" name="permissions[<?= htmlspecialchars($role) ?>][]" value="<?= $item['id'] ?>" <?php if (isset($current_permissions[$role][$item['id']])) echo 'checked'; if ($role === 'Admin' && $item['page_url'] === 'user_management.php') echo ' disabled';?>></div></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary fw-bold float-end mt-3"><i class="fas fa-save me-2"></i> Yetkileri Kaydet</button>
            </form>
        </div>
    </div>
</main>
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-success text-white"><h5 class="modal-title fw-bold">Yeni Çalışan Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="user_management.php"><input type="hidden" name="action" value="add_user"><div class="mb-3"><label class="form-label">E-posta Adresi *</label><input type="email" class="form-control" name="email" required></div><div class="mb-3"><label class="form-label">Yetki Rolü *</label><select class="form-select" name="role" required><option value="">-- Rol Seçin --</option><?php foreach ($roles as $role): ?><option value="<?= $role ?>"><?= $role ?></option><?php endforeach; ?></select></div><button type="submit" class="btn btn-primary w-100 fw-bold">Çalışanı Kaydet</button></form></div></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/js/admin.js"></script>
</body>
</html>