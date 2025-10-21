<?php
// DOST GSM: Tamir Portföyü İşleyici (Yeni Hizmet Ekle/Sil)
require_once '../includes/config.php'; 

// **GÜVENLİK KONTROLÜ**
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Dosya Yükleme Hedef Klasörü (public_html/images/portfolio'yu işaret eder)
$upload_dir = '../images/portfolio/'; 
$redirect_url = '../services.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // --------------------------------------------------------
    // A. YENİ TAMİR ÖRNEĞİ EKLEME İŞLEMİ (GÖRSEL YÜKLEME DAHİL)
    // --------------------------------------------------------
    if ($action === 'add_portfolio') {
        $service_title = filter_input(INPUT_POST, 'service_title', FILTER_SANITIZE_STRING);
        $device_model = filter_input(INPUT_POST, 'device_model', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        
        $image_url_db = NULL; // Veritabanına kaydedilecek görsel yolu
        $error_message = null; // Hata mesajı tutucusu
        
        // --- 1. GÖRSEL YÜKLEME İŞLEMİ ---
        if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] == 0) {
            $file = $_FILES['repair_image'];
            $max_size = 5 * 1024 * 1024; // 5 MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Güvenli Dosya Adı Oluşturma: Benzersiz ID + Uzantı
            $new_file_name = uniqid('repair_', true) . '.' . $file_extension;
            $destination = $upload_dir . $new_file_name;
            
            // Güvenlik Kontrolleri
            if ($file['size'] > $max_size) {
                $error_message = "Dosya boyutu 5MB'ı geçemez.";
            } elseif (!in_array($file['type'], $allowed_types)) {
                $error_message = "Yalnızca JPG, PNG ve GIF dosyalarına izin verilir.";
            } elseif (!move_uploaded_file($file['tmp_name'], $destination)) {
                 $error_message = "Dosya sunucuya taşınamadı. (images/portfolio/ klasörünün yazma iznini kontrol edin - chmod 755 veya 777)";
            } else {
                // Başarılı yükleme: Veritabanına kaydedilecek yolu ayarla
                $image_url_db = 'images/portfolio/' . $new_file_name;
            }

            if ($error_message) {
                // Hata varsa yönlendir ve işlemi durdur
                header("Location: " . $redirect_url . "?status=error&message=" . urlencode($error_message));
                exit();
            }
        }
        // --- GÖRSEL YÜKLEME BİTTİ ---

        if ($service_title && $device_model) {
            try {
                // image_url_db yüklenmediyse NULL kalır
                $stmt = $pdo->prepare("INSERT INTO portfolio (service_title, device_model, description, image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$service_title, $device_model, $description, $image_url_db]);
                header("Location: " . $redirect_url . "?status=portfolio_added");
                exit();
            } catch (PDOException $e) {
                // Hata oluşursa yüklenen dosyayı sil (Temizlik)
                if ($image_url_db && file_exists('../' . $image_url_db)) {
                    unlink('../' . $image_url_db);
                }
                header("Location: " . $redirect_url . "?status=error&message=Kayit_Veritabani_Hatasi");
                exit();
            }
        }
    }
    
    // --------------------------------------------------------
    // B. TAMİR ÖRNEĞİ SİLME İŞLEMİ (Görseli Silme Dahil)
    // --------------------------------------------------------
    elseif ($action === 'delete_portfolio') {
        $id = filter_input(INPUT_POST, 'portfolio_id', FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                // 1. Görsel yolunu çek
                $stmt = $pdo->prepare("SELECT image_url FROM portfolio WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                
                // 2. Kaydı sil
                $stmt_delete = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
                $stmt_delete->execute([$id]);
                
                // 3. Görseli sunucudan sil (varsa)
                if ($item && $item['image_url'] && file_exists('../' . $item['image_url'])) {
                    unlink('../' . $item['image_url']);
                }

                header("Location: " . $redirect_url . "?status=portfolio_deleted");
                exit();
            } catch (PDOException $e) {
                header("Location: " . $redirect_url . "?status=error&message=Silme_Basarisiz");
                exit();
            }
        }
    }
}

header("Location: " . $redirect_url);
exit();
?>