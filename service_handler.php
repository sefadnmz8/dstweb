<?php
// DOST GSM: Servis Kaydı İşleyici (Gelişmiş Versiyon)
require_once '../includes/config.php'; 
date_default_timezone_set('Europe/Istanbul'); 

// **GÜVENLİK KONTROLÜ**
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Türkçe karakter destekli büyük harfe çevirme
function toUpperSafe($input) {
    $cleaned_string = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
    return mb_strtoupper($cleaned_string, 'UTF-8');
}

// Temizleme fonksiyonu
function sanitizeString($input) {
    return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $record_id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT); 
    $current_user_role = $_SESSION['user_role'] ?? 'Misafir';

    // YETKİ KONTROLÜ
    if (in_array($action, ['complete_service', 'deliver_service', 'update_status']) && !in_array($current_user_role, ['Admin', 'Stok', 'Servis'])) {
        header("Location: service_records.php?status=error&message=Yetki_Yok");
        exit();
    }

    // --------------------------------------------------------
    // A. YENİ KAYIT EKLEME İŞLEMİ
    // --------------------------------------------------------
    if ($action === 'add_service') {
        
        // BÜYÜK HARFE ÇEVRİLEN ALANLAR
        $customer_name = toUpperSafe($_POST['customer_name'] ?? ''); 
        $device_model = toUpperSafe($_POST['device_model'] ?? ''); 
        $color = toUpperSafe($_POST['color'] ?? ''); 
        
        // SADECE TEMİZLENEN ALANLAR
        $phone_number = sanitizeString($_POST['phone_number'] ?? '');
        $fault_description = sanitizeString($_POST['fault_description'] ?? '');
        $imei = sanitizeString($_POST['imei'] ?? '');
        $housing_status = sanitizeString($_POST['housing_status'] ?? '');
        $extra_notes = sanitizeString($_POST['extra_notes'] ?? '');
        
        $status = 'Beklemede'; 
        
        if ($customer_name && $phone_number && $device_model && $fault_description) {
            try {
                $stmt = $pdo->prepare("INSERT INTO service_records 
                    (customer_name, phone_number, device_model, fault_description, status, imei, color, housing_status, extra_notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $customer_name, $phone_number, $device_model, $fault_description, $status, 
                    $imei, $color, $housing_status, $extra_notes
                ]);
                
                header("Location: service_records.php?status=new_record");
                exit();
            } catch (PDOException $e) {
                error_log("Yeni Kayıt Hatası: " . $e->getMessage());
                header("Location: service_records.php?status=error&message=Kayit_Olusturma_Basarisiz");
                exit();
            }
        } else {
             header("Location: service_records.php?status=error&message=Zorunlu_Alanlar_Eksik");
             exit();
        }
    }

    // record_id kontrolü
    if (!$record_id && $action !== 'add_service') {
        header("Location: service_records.php?status=error&message=Gecersiz_Kayit_ID");
        exit();
    }

    // --------------------------------------------------------
    // B. SERVİS TAMAMLAMA VE STOK DÜŞME İŞLEMİ (YENİ ÖZELLİK)
    // --------------------------------------------------------
    if ($action === 'complete_service') {
        
        $parca_idler = $_POST['parca_id'] ?? [];
        $tutar = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);
        $isci_ucreti = filter_input(INPUT_POST, 'isci_ucreti', FILTER_VALIDATE_FLOAT) ?: 0;

        try {
            $pdo->beginTransaction();

            // 1. Kullanılan Parçaları Stoktan Düşme
            $toplam_malzeme_maliyeti = 0;
            if (!empty($parca_idler)) {
                $stmt_parca = $pdo->prepare("SELECT id, part_name, unit_cost FROM inventory WHERE id = ?");
                $stmt_stok_dus = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE id = ? AND quantity >= 1");
                
                foreach ($parca_idler as $inventory_id) {
                    $inventory_id = (int)$inventory_id;
                    
                    // Parça bilgilerini al
                    $stmt_parca->execute([$inventory_id]);
                    $parca = $stmt_parca->fetch();
                    
                    if ($parca) {
                        $toplam_malzeme_maliyeti += $parca['unit_cost'];
                        
                        // Stok düş
                        $stmt_stok_dus->execute([$inventory_id]);
                        
                        if ($stmt_stok_dus->rowCount() > 0) {
                            // Kullanım logu kaydet
                            $stmt_log = $pdo->prepare("INSERT INTO usage_logs (inventory_id, quantity_used, service_record_id, date_used) VALUES (?, 1, ?, NOW())");
                            $stmt_log->execute([$inventory_id, $record_id]);
                        } else {
                            throw new Exception("Stok yetersiz: " . $parca['part_name']); 
                        }
                    }
                }
            }

            // 2. Servis Kaydını Güncelle
            $toplam_tutar = $tutar + $isci_ucreti;
            $stmt = $pdo->prepare("UPDATE service_records SET status = 'Hazır', final_price = ? WHERE id = ?");
            $stmt->execute([$toplam_tutar, $record_id]);
            
            // 3. Servis Logu Kaydet
            $log_detay = "Servis tamamlandı. ";
            if (!empty($parca_idler)) {
                $log_detay .= "Malzeme maliyeti: " . number_format($toplam_malzeme_maliyeti, 2) . " TL. ";
            }
            if ($isci_ucreti > 0) {
                $log_detay .= "İşçilik: " . number_format($isci_ucreti, 2) . " TL. ";
            }
            $log_detay .= "Toplam: " . number_format($toplam_tutar, 2) . " TL.";
            
            $stmt_log = $pdo->prepare("INSERT INTO service_logs (record_id, log_type, log_details, log_user_email) VALUES (?, 'Tamamlandı', ?, ?)");
            $stmt_log->execute([$record_id, $log_detay, $_SESSION['user_email'] ?? 'Sistem']);

            $pdo->commit();

            header("Location: service_records.php?status=completed");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Servis Tamamlama Hatası: " . $e->getMessage());
            header("Location: service_records.php?status=error&message=" . urlencode($e->getMessage()));
            exit();
        }
    }
    
    // --------------------------------------------------------
    // C. DURUM GÜNCELLEME İŞLEMİ
    // --------------------------------------------------------
    elseif ($action === 'update_status') {
        $new_status = sanitizeString($_POST['new_status'] ?? '');
        $notes = sanitizeString($_POST['notes'] ?? '');
        
        if ($new_status) {
             try {
                // Mevcut durumu al
                $old_status = $pdo->query("SELECT status FROM service_records WHERE id = {$record_id}")->fetchColumn(); 
                
                // Durumu güncelle
                $stmt = $pdo->prepare("UPDATE service_records SET status = ?, internal_notes = ? WHERE id = ?");
                $stmt->execute([$new_status, $notes, $record_id]);
                
                // Log kaydı
                $log_detay = "Durum değişikliği: {$old_status} → {$new_status}";
                if (!empty($notes)) {
                    $log_detay .= ". Not: {$notes}";
                }
                
                $stmt_log = $pdo->prepare("INSERT INTO service_logs (record_id, log_type, log_details, log_user_email) VALUES (?, 'Durum Değişikliği', ?, ?)");
                $stmt_log->execute([$record_id, $log_detay, $_SESSION['user_email'] ?? 'Sistem']);

                header("Location: service_records.php?status=updated");
                exit();
            } catch (PDOException $e) {
                header("Location: service_records.php?status=error&message=Durum_Guncelleme_Basarisiz");
                exit();
            }
        }
    }

    // --------------------------------------------------------
    // D. SERVİSİ TESLİM ETME İŞLEMİ
    // --------------------------------------------------------
    elseif ($action === 'deliver_service') {
        try {
            $pdo->beginTransaction();
            
            // Teslimat tarihini güncelle
            $stmt = $pdo->prepare("UPDATE service_records SET status = 'Teslim Edildi', delivery_date = NOW() WHERE id = ?");
            $stmt->execute([$record_id]);
            
            // Log kaydı
            $final_price = $pdo->query("SELECT final_price FROM service_records WHERE id = {$record_id}")->fetchColumn();
            $log_detay = "Cihaz teslim edildi. Tahsilat: " . ($final_price > 0 ? number_format($final_price, 2) . " TL" : "Ücretsiz");
            
            $stmt_log = $pdo->prepare("INSERT INTO service_logs (record_id, log_type, log_details, log_user_email) VALUES (?, 'Teslim Edildi', ?, ?)");
            $stmt_log->execute([$record_id, $log_detay, $_SESSION['user_email'] ?? 'Sistem']);
            
            $pdo->commit();
            
            header("Location: service_records.php?status=delivered");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: service_records.php?status=error&message=Teslimat_Basarisiz");
            exit();
        }
    }
}

header("Location: service_records.php");
exit();
?>