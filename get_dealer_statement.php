<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Güvenlik ve Yetki Kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}

$dealer_id = filter_input(INPUT_GET, 'dealer_id', FILTER_VALIDATE_INT);

if (!$dealer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz bayi ID.']);
    exit();
}

try {
    // 1. Bayi bilgilerini çek
    $dealer_stmt = $pdo->prepare("SELECT * FROM dealers WHERE id = ?");
    $dealer_stmt->execute([$dealer_id]);
    $dealer = $dealer_stmt->fetch();

    if (!$dealer) {
        echo json_encode(['status' => 'error', 'message' => 'Bayi bulunamadı.']);
        exit();
    }

    // 2. Bayiye ait tüm işlemleri çek
    $transactions_stmt = $pdo->prepare("SELECT * FROM dealer_transactions WHERE dealer_id = ? ORDER BY created_at ASC");
    $transactions_stmt->execute([$dealer_id]);
    $transactions = $transactions_stmt->fetchAll();

    // 3. WhatsApp mesajını formatla
    $message = "*DOST GSM - Hesap Ekstresi*" . "\n";
    $message .= "-----------------------------------" . "\n";
    $message .= "*Bayi:* " . $dealer['dealer_name'] . "\n";
    $message .= "*Tarih:* " . date('d.m.Y H:i') . "\n\n";
    $message .= "*İŞLEM DETAYLARI:*" . "\n";

    if (count($transactions) > 0) {
        foreach ($transactions as $tx) {
            $date = date('d.m.Y', strtotime($tx['created_at']));
            $amount = number_format($tx['amount'], 2, ',', '.');
            $type = ($tx['transaction_type'] === 'Borc') ? 'BORÇ' : 'ÖDEME';
            $description = !empty($tx['description']) ? ' (' . $tx['description'] . ')' : '';
            
            $message .= "- " . $date . ": *" . $amount . " TL* - " . $type . $description . "\n";
        }
    } else {
        $message .= "Henüz bir işlem hareketi bulunmamaktadır.\n";
    }

    $message .= "-----------------------------------" . "\n";
    $message .= "*GÜNCEL TOPLAM BORÇ: " . number_format($dealer['current_balance'], 2, ',', '.') . " TL*" . "\n\n";
    $message .= "_Bu ekstre Dost GSM tarafından oluşturulmuştur._";

    $phone_clean = preg_replace('/[^0-9]/', '', $dealer['phone_number']);
    $whatsapp_link = "https://wa.me/90" . $phone_clean . "?text=" . urlencode($message);

    echo json_encode([
        'status' => 'success',
        'message_preview' => $message,
        'whatsapp_link' => $whatsapp_link
    ]);

} catch (PDOException $e) {
    error_log("Ekstre oluşturma hatası: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası oluştu.']);
}
?>