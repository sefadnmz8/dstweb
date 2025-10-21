<?php
require_once '../includes/config.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_role'] !== 'Admin') {
    die("Yetkisiz erişim.");
}

$dealer_id = filter_input(INPUT_GET, 'dealer_id', FILTER_VALIDATE_INT);
if (!$dealer_id) {
    die("Geçersiz bayi ID.");
}

try {
    // Bayi bilgilerini çek
    $dealer_stmt = $pdo->prepare("SELECT dealer_name, current_balance FROM dealers WHERE id = ?");
    $dealer_stmt->execute([$dealer_id]);
    $dealer = $dealer_stmt->fetch();

    if (!$dealer) {
        die("Bayi bulunamadı.");
    }

    // İşlem geçmişini çek
    $transactions_stmt = $pdo->prepare("SELECT * FROM dealer_transactions WHERE dealer_id = ? ORDER BY created_at ASC");
    $transactions_stmt->execute([$dealer_id]);
    $transactions = $transactions_stmt->fetchAll();

    // Dinamik dosya adı oluştur
    $filename = "hesap_ekstresi_" . str_replace(' ', '_', $dealer['dealer_name']) . "_" . date('Y-m-d') . ".csv";

    // Tarayıcıya dosyanın bir CSV dosyası olduğunu ve indirilmesi gerektiğini söyleyen başlıklar
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // PHP'nin çıktı akışını aç
    $output = fopen('php://output', 'w');

    // Excel'in Türkçe karakterleri doğru açması için BOM (Byte Order Mark) ekle
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Başlık Satırını Yaz
    fputcsv($output, ['Bayi Adi', $dealer['dealer_name']]);
    fputcsv($output, ['Guncel Bakiye', number_format($dealer['current_balance'], 2, ',', '.') . ' TL']);
    fputcsv($output, []); // Boş satır
    fputcsv($output, ['Tarih', 'Aciklama', 'Islem Turu', 'Tutar (TL)']);

    // Veri Satırlarını Yaz
    foreach ($transactions as $tx) {
        $row = [
            date('d.m.Y H:i', strtotime($tx['created_at'])),
            $tx['description'],
            ($tx['transaction_type'] === 'Borc') ? 'Borc' : 'Odeme',
            number_format($tx['amount'], 2, ',', '.')
        ];
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();

} catch (PDOException $e) {
    error_log("CSV Export Hatası: " . $e->getMessage());
    die("Rapor oluşturulurken bir veritabanı hatası oluştu.");
}
?>