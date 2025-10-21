<?php
require_once '../includes/config.php'; 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Admin') {
    header("Location: dealers.php?status=error&message=Yetkisiz_Erisim");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $redirect_url = 'dealers.php';

    if ($action === 'add_dealer') {
        $dealer_name = mb_strtoupper(filter_input(INPUT_POST, 'dealer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
        $contact_person = mb_strtoupper(filter_input(INPUT_POST, 'contact_person', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
        $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($dealer_name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO dealers (dealer_name, contact_person, phone_number) VALUES (?, ?, ?)");
                $stmt->execute([$dealer_name, $contact_person, $phone_number]);
                header("Location: " . $redirect_url . "?status=dealer_added"); exit();
            } catch (PDOException $e) { header("Location: " . $redirect_url . "?status=error&message=Add_Failed"); exit(); }
        }
    }
    elseif ($action === 'edit_dealer') {
        $dealer_id = filter_input(INPUT_POST, 'dealer_id', FILTER_VALIDATE_INT);
        $dealer_name = mb_strtoupper(filter_input(INPUT_POST, 'dealer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
        $contact_person = mb_strtoupper(filter_input(INPUT_POST, 'contact_person', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'UTF-8');
        $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($dealer_id && $dealer_name) {
            try {
                $stmt = $pdo->prepare("UPDATE dealers SET dealer_name = ?, contact_person = ?, phone_number = ? WHERE id = ?");
                $stmt->execute([$dealer_name, $contact_person, $phone_number, $dealer_id]);
                header("Location: " . $redirect_url . "?status=dealer_updated"); exit();
            } catch (PDOException $e) { header("Location: " . $redirect_url . "?status=error&message=Update_Failed"); exit(); }
        }
    }
    elseif ($action === 'add_transaction') {
        $dealer_id = filter_input(INPUT_POST, 'dealer_id', FILTER_VALIDATE_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $transaction_type = filter_input(INPUT_POST, 'transaction_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $description_select = filter_input(INPUT_POST, 'description_select', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description_manual = filter_input(INPUT_POST, 'description_manual', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $final_description = ($description_select === 'Diğer') ? $description_manual : $description_select;
        $final_description = mb_strtoupper($final_description, 'UTF-8');

        if ($dealer_id && $amount > 0 && in_array($transaction_type, ['Borc', 'Odeme'])) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO dealer_transactions (dealer_id, transaction_type, amount, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$dealer_id, $transaction_type, $amount, $final_description]);
                $update_amount = ($transaction_type === 'Borc') ? $amount : -$amount;
                $stmt_update = $pdo->prepare("UPDATE dealers SET current_balance = current_balance + ?, last_transaction_date = NOW() WHERE id = ?");
                $stmt_update->execute([$update_amount, $dealer_id]);
                $pdo->commit(); header("Location: " . $redirect_url . "?status=transaction_added");
            } catch (Exception $e) { $pdo->rollBack(); header("Location: " . $redirect_url . "?status=error&message=Islem_Kaydi_Basarisiz"); }
            exit();
        }
    }
    elseif ($action === 'clear_history') {
        $dealer_id = filter_input(INPUT_POST, 'dealer_id', FILTER_VALIDATE_INT);
        if ($dealer_id) {
            try {
                $pdo->beginTransaction();
                $stmt_delete = $pdo->prepare("DELETE FROM dealer_transactions WHERE dealer_id = ?"); $stmt_delete->execute([$dealer_id]);
                $stmt_update = $pdo->prepare("UPDATE dealers SET current_balance = 0, last_transaction_date = NOW() WHERE id = ?"); $stmt_update->execute([$dealer_id]);
                $pdo->commit(); header("Location: " . $redirect_url . "?status=history_cleared"); exit();
            } catch (Exception $e) { $pdo->rollBack(); header("Location: " . $redirect_url . "?status=error&message=Clear_Failed"); exit(); }
        }
    }
}
header("Location: dealers.php");
exit();
?>