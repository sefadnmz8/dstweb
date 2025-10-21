<?php
// DOST GSM: Otomatik Tamamlama (Autocomplete) Arka Plan İşleyici
require_once '../includes/config.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$query = $_GET['query'] ?? '';
$results = [];

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $wildcard_query = '%' . mb_strtoupper($query, 'UTF-8') . '%';

    if ($type === 'customer_name') {
        // Müşteri adına göre arama yaparken, telefon numarasını da çekiyoruz.
        // Aynı isimli müşterilerin telefonları farklı olabilir, bu yüzden gruplama yapıyoruz.
        $stmt = $pdo->prepare(
            "SELECT DISTINCT customer_name, phone_number 
             FROM service_records 
             WHERE customer_name LIKE ? 
             ORDER BY customer_name ASC LIMIT 10"
        );
        $stmt->execute([$wildcard_query]);
        $data = $stmt->fetchAll();

        foreach ($data as $row) {
            $results[] = [
                'label' => $row['customer_name'] . ' (' . $row['phone_number'] . ')', // Ekranda görünecek etiket (İsim + Tel No)
                'value' => $row['customer_name'], // Input'a yazılacak değer (Sadece İsim)
                'phone' => $row['phone_number']   // Otomatik doldurmak için telefon verisi
            ];
        }

    } elseif ($type === 'phone_number') {
        $stmt = $pdo->prepare("SELECT DISTINCT phone_number FROM service_records WHERE phone_number LIKE ? LIMIT 10");
        $stmt->execute(['%' . $query . '%']);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($data as $value) {
            $results[] = ['label' => $value, 'value' => $value];
        }
        
    } elseif ($type === 'device_model') {
        $stmt = $pdo->prepare("SELECT DISTINCT device_model FROM service_records WHERE device_model LIKE ? ORDER BY device_model ASC LIMIT 10");
        $stmt->execute([$wildcard_query]);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($data as $value) {
            $results[] = ['label' => $value, 'value' => $value];
        }
    }

} catch (PDOException $e) {
    // Hata durumunda boş sonuç döndür
    error_log('Autocomplete Hatası: ' . $e->getMessage());
}

echo json_encode($results);
?>