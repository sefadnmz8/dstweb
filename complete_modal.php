<?php 
// modals/complete_modal.php (service_records.php içinde kullanılır)
if (!isset($kayit) || $kayit['status'] == 'Hazır' || $kayit['status'] == 'Teslim Edildi') return;
?>
<div class="modal fade" id="completeServiceModal<?= $kayit['id'] ?>" tabindex="-1" aria-labelledby="completeServiceModalLabel<?= $kayit['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="completeServiceModalLabel<?= $kayit['id'] ?>">Servisi Tamamla ve Stok Düş</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Cihaz ID: #<?= $kayit['id'] ?> - **<?= htmlspecialchars($kayit['device_model']) ?>** (<?= htmlspecialchars($kayit['customer_name']) ?>)</p>
                <form method="POST" action="service_handler.php">
                    <input type="hidden" name="action" value="complete_service">
                    <input type="hidden" name="record_id" value="<?= $kayit['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kullanılan Yedek Parçalar (Stoktan Düşülecektir)</label>
                        <select class="form-control" name="parca_id[]" multiple required size="5">
                            <option value="">-- Parça Seçin (Çoklu Seçim) --</option>
                            <?php foreach ($stok_listesi as $stok): ?>
                                <?php if ($stok['quantity'] > 0): ?>
                                    <option value="<?= $stok['id'] ?>">
                                        <?= htmlspecialchars($stok['part_name']) ?> (Mevcut: <?= $stok['quantity'] ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Ctrl (Cmd) tuşu ile birden fazla parça seçebilirsiniz.</small>
                    </div>

                    <div class="mb-3">
                        <label for="total_price<?= $kayit['id'] ?>" class="form-label fw-bold">Toplam Servis Ücreti (TL)</label>
                        <input type="number" class="form-control" id="total_price<?= $kayit['id'] ?>" name="total_price" step="0.01" required min="0" value="<?= number_format($kayit['final_price'] ?? 0, 2, '.', '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold mt-3">Tamamla, Stok Düş ve Hazır Durumuna Getir</button>
                </form>
            </div>
        </div>
    </div>
</div>