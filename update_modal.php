<?php 
// modals/update_modal.php (service_records.php içinde kullanılır)
if (!isset($kayit)) return; 
?>
<div class="modal fade" id="updateRecordModal<?= $kayit['id'] ?>" tabindex="-1" aria-labelledby="updateRecordModalLabel<?= $kayit['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="updateRecordModalLabel<?= $kayit['id'] ?>">Servis Durumunu Güncelle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Cihaz ID: #<?= $kayit['id'] ?> - **<?= htmlspecialchars($kayit['device_model']) ?>** (<?= htmlspecialchars($kayit['customer_name']) ?>)</p>
                <form method="POST" action="service_handler.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="record_id" value="<?= $kayit['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="new_status<?= $kayit['id'] ?>" class="form-label fw-bold">Yeni Durum</label>
                        <select class="form-select" id="new_status<?= $kayit['id'] ?>" name="new_status" required>
                            <?php 
                            $durumlar_listesi = ['Beklemede', 'Tamirde', 'Parça Bekleniyor', 'Hazır', 'Teslim Edildi'];
                            foreach ($durumlar_listesi as $durum): ?>
                                <option value="<?= $durum ?>" <?= ($kayit['status'] == $durum) ? 'selected' : '' ?>>
                                    <?= $durum ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes<?= $kayit['id'] ?>" class="form-label">İç Notlar (Yapılan İşlem/Tahmini Süre)</label>
                        <textarea class="form-control" id="notes<?= $kayit['id'] ?>" name="notes" rows="3"><?= htmlspecialchars($kayit['internal_notes'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-info w-100 fw-bold mt-3">Durumu Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>