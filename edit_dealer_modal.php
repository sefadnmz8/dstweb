<?php
// Bu dosya dealers.php içindeki bir döngüde çağrılır, $dealer değişkeni oradan gelir.
if (!isset($dealer)) return; 
?>
<div class="modal fade" id="editDealerModal<?= $dealer['id'] ?>" tabindex="-1" aria-labelledby="editDealerModalLabel<?= $dealer['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editDealerModalLabel<?= $dealer['id'] ?>">Bayi Bilgilerini Düzenle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="dealers_handler.php">
                    <input type="hidden" name="action" value="edit_dealer">
                    <input type="hidden" name="dealer_id" value="<?= $dealer['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="dealer_name_<?= $dealer['id'] ?>" class="form-label">Bayi/Firma Adı *</label>
                        <input type="text" class="form-control" id="dealer_name_<?= $dealer['id'] ?>" name="dealer_name" value="<?= htmlspecialchars($dealer['dealer_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact_person_<?= $dealer['id'] ?>" class="form-label">İlgili Kişi</label>
                        <input type="text" class="form-control" id="contact_person_<?= $dealer['id'] ?>" name="contact_person" value="<?= htmlspecialchars($dealer['contact_person'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone_number_<?= $dealer['id'] ?>" class="form-label">Telefon No</label>
                        <input type="text" class="form-control" id="phone_number_<?= $dealer['id'] ?>" name="phone_number" value="<?= htmlspecialchars($dealer['phone_number'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-info w-100 fw-bold text-white mt-2">Değişiklikleri Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>