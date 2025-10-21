<?php 
if (!isset($dealer)) return;

// Bu modalın ihtiyacı olan hazır açıklamaları veritabanından çekelim
$descriptions_stmt = $pdo->query("SELECT description_text FROM transaction_descriptions ORDER BY description_text ASC");
$predefined_descriptions = $descriptions_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="modal fade" id="addTransactionModal<?= $dealer['id'] ?>" tabindex="-1" aria-labelledby="addTransactionModalLabel<?= $dealer['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Cari İşlem: <?= htmlspecialchars($dealer['dealer_name']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Mevcut Bakiye: <span class="fw-bold text-danger"><?= number_format($dealer['current_balance'], 2, ',', '.') ?> TL</span></p>
                <form method="POST" action="dealers_handler.php">
                    <input type="hidden" name="action" value="add_transaction">
                    <input type="hidden" name="dealer_id" value="<?= $dealer['id'] ?>">
                    <input type="hidden" name="transaction_type" id="transactionType_<?= $dealer['id'] ?>"> 

                    <div class="mb-3">
                        <label for="amount_<?= $dealer['id'] ?>" class="form-label fw-bold">Miktar (TL) *</label>
                        <input type="number" class="form-control" id="amount_<?= $dealer['id'] ?>" name="amount" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description_select_<?= $dealer['id'] ?>" class="form-label">Açıklama</label>
                        <select class="form-select" name="description_select" id="description_select_<?= $dealer['id'] ?>">
                            <option value="">-- Hızlı Açıklama Seçin --</option>
                            <?php foreach ($predefined_descriptions as $desc): ?>
                                <option value="<?= htmlspecialchars($desc) ?>"><?= htmlspecialchars($desc) ?></option>
                            <?php endforeach; ?>
                            <option value="Diğer">-- Diğer (Manuel Girin) --</option>
                        </select>
                    </div>

                    <div class="mb-3" id="manual_description_div_<?= $dealer['id'] ?>" style="display:none;">
                        <label for="description_manual_<?= $dealer['id'] ?>" class="form-label">Manuel Açıklama</label>
                        <input type="text" class="form-control" id="description_manual_<?= $dealer['id'] ?>" name="description_manual" placeholder="İşlem açıklamasını buraya yazın">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold mt-2">İşlemi Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bu script'in her modal için tekrar etmemesi için, global bir fonksiyona taşımak daha iyidir,
// ama bu yapı için en basit çözüm her modal'a kendi script'ini eklemektir.
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('description_select_<?= $dealer['id'] ?>');
    const manualInputDiv = document.getElementById('manual_description_div_<?= $dealer['id'] ?>');
    if(select) {
        select.addEventListener('change', function() {
            if (this.value === 'Diğer') {
                manualInputDiv.style.display = 'block';
            } else {
                manualInputDiv.style.display = 'none';
            }
        });
    }
});
</script>