<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek metodu.']);
    exit();
}

// Formdan gelen verileri al ve temizle
$tracking_id = filter_input(INPUT_POST, 'tracking_id', FILTER_SANITIZE_NUMBER_INT);
// Telefondaki olası boşluk, tire, parantez gibi tüm yabancı karakterleri temizle
$phone_number_from_form = preg_replace('/[^0-9]/', '', $_POST['phone_number'] ?? '');


if (empty($tracking_id) || empty($phone_number_from_form)) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen tüm zorunlu alanları doldurun.']);
    exit();
}

try {
    // Sadece ID'ye göre kaydı getir
    $stmt = $pdo->prepare(
        "SELECT customer_name, phone_number, device_model, status, updated_at 
         FROM service_records 
         WHERE id = :tracking_id"
    );
    $stmt->execute([':tracking_id' => $tracking_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        // Kayıt bulundu, şimdi telefon numarasını doğrula
        // Veritabanındaki telefon numarasını da temizleyerek karşılaştırma yap
        // Bu, veritabanına '(507) 598 53 68' gibi kaydedilmiş olsa bile eşleşme sağlar.
        $phone_number_from_db = preg_replace('/[^0-9]/', '', $record['phone_number'] ?? '');

        if ($phone_number_from_db === $phone_number_from_form) {
            // Telefon numaraları eşleşti, bilgiyi gönder
            // Sadece müşteriye gösterilecek verileri seçerek geri döndür
            $data_to_send = [
                'customer_name' => $record['customer_name'],
                'device_model' => $record['device_model'],
                'status' => $record['status'],
                'updated_at' => date('d.m.Y H:i', strtotime($record['updated_at']))
            ];
            echo json_encode(['status' => 'success', 'data' => $data_to_send]);
        } else {
            // Kayıt var ama telefon numarası yanlış
            echo json_encode(['status' => 'error', 'message' => 'Girilen bilgilere uygun bir servis kaydı bulunamadı. Lütfen bilgilerinizi kontrol edip tekrar deneyin.']);
        }
    } else {
        // Servis ID'si hiç bulunamadı
        echo json_encode(['status' => 'error', 'message' => 'Girilen bilgilere uygun bir servis kaydı bulunamadı. Lütfen bilgilerinizi kontrol edip tekrar deneyin.']);
    }

} catch (PDOException $e) {
    error_log("Servis Sorgulama Hatası: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Sistemsel bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
}
?>