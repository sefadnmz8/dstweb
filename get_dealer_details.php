<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}

$dealer_id = filter_input(INPUT_GET, 'dealer_id', FILTER_VALIDATE_INT);
if (!$dealer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz bayi ID.']);
    exit();
}

try {
    // Bayi ana bilgilerini çek
    $dealer_stmt = $pdo->prepare("SELECT * FROM dealers WHERE id = ?");
    $dealer_stmt->execute([$dealer_id]);
    $dealer = $dealer_stmt->fetch();

    if (!$dealer) {
        echo json_encode(['status' => 'error', 'message' => 'Bayi bulunamadı.']);
        exit();
    }

    // Bayi işlem geçmişini çek
    $transactions_stmt = $pdo->prepare("SELECT * FROM dealer_transactions WHERE dealer_id = ? ORDER BY created_at DESC");
    $transactions_stmt->execute([$dealer_id]);
    $transactions = $transactions_stmt->fetchAll();

    // İşlem geçmişini HTML tablosu olarak hazırla
    $transactions_html = '';
    if (count($transactions) > 0) {
        foreach ($transactions as $tx) {
            $date = date('d.m.Y H:i', strtotime($tx['created_at']));
            $amount = number_format($tx['amount'], 2, ',', '.');
            if ($tx['transaction_type'] === 'Borc') {
                $transactions_html .= "<tr><td>{$date}</td><td>{$tx['description']}</td><td class='text-danger fw-bold'>+ {$amount} TL</td></tr>";
            } else {
                $transactions_html .= "<tr><td>{$date}</td><td>{$tx['description']}</td><td class='text-success fw-bold'>- {$amount} TL</td></tr>";
            }
        }
    } else {
        $transactions_html = "<tr><td colspan='3' class='text-center text-muted'>Bu bayi için henüz işlem yapılmamış.</td></tr>";
    }
    
    // Tüm verileri JSON olarak geri döndür
    echo json_encode([
        'status' => 'success',
        'data' => [
            'dealer_name' => $dealer['dealer_name'],
            'contact_person' => $dealer['contact_person'],
            'phone_number' => $dealer['phone_number'],
            'current_balance_formatted' => number_format($dealer['current_balance'], 2, ',', '.') . ' TL',
            'transactions_html' => $transactions_html
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası.']);
}
?>