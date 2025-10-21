<?php
require_once '../includes/config.php';

// Güvenlik ve Yetki Kontrolü: Sadece Admin bu işlemi yapabilir
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Admin') {
    header("Location: dashboard.php?status=yetki_yok");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Formdan gelen izinleri al, eğer hiç bir rol için izin gelmediyse boş bir array ata
    $permissions = $_POST['permissions'] ?? [];
    
    try {
        $pdo->beginTransaction();

        // 1. Adım: Mevcut tüm izinleri 'role_permissions' tablosundan sil
        $pdo->exec("TRUNCATE TABLE role_permissions");

        // 2. Adım: Formdan gelen yeni izinleri veritabanına ekle
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_name, menu_item_id) VALUES (?, ?)");

        // Gelen izinler üzerinde döngü kur (örn: $role = "Stok", $menu_ids = [2, 3])
        foreach ($permissions as $role => $menu_ids) {
            // Her bir menü ID'si için kayıt ekle
            foreach ($menu_ids as $menu_id) {
                if (!empty($menu_id)) { // Değerin boş olmadığından emin ol
                    $stmt->execute([$role, (int)$menu_id]);
                }
            }
        }
        
        // 3. Adım (Güvenlik): Admin kullanıcısının "Kullanıcı Yönetimi" sayfasına erişimini her zaman garanti altına al.
        $user_management_id_stmt = $pdo->query("SELECT id FROM menu_items WHERE page_url = 'user_management.php'");
        $user_management_id = $user_management_id_stmt->fetchColumn();
        
        if ($user_management_id) {
            // Bu iznin zaten eklenip eklenmediğini kontrol etmek yerine, benzersiz bir anahtar olmadığı için doğrudan ekleyebiliriz.
            // Daha güvenli olmak için, eklemeden önce Admin rolü için bu iznin var olup olmadığını kontrol edebilirsiniz, ancak bu yapı çalışacaktır.
            $stmt->execute(['Admin', $user_management_id]);
        }
        
        $pdo->commit(); // Tüm işlemler başarılıysa değişiklikleri onayla
        
        header("Location: user_management.php?status=permissions_updated");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack(); // Hata oluşursa tüm değişiklikleri geri al
        error_log("Yetki Güncelleme Hatası: ". $e->getMessage());
        header("Location: user_management.php?status=error&message=Veritabani_Hatasi");
        exit();
    }
}

// POST isteği değilse, ana sayfaya yönlendir
header("Location: user_management.php");
exit();
?>