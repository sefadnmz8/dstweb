<?php 
// modals/log_modal.php (service_records.php içinde kullanılır)
if (!isset($kayit)) return; 

// Servis Kaydına ait tüm logları çek
$log_stmt = $pdo->prepare("SELECT * FROM service_logs WHERE record_id = ? ORDER BY created_at DESC");
$log_stmt->execute([$kayit['id']]);
$logs = $log_stmt->fetchAll();

// Servis kaydının detaylarını çek (IMEI, Renk, Kasa Durumu vb.)
$detail_stmt = $pdo->prepare("SELECT imei, color, housing_status, fault_description, extra_notes FROM service_records WHERE id = ?");
$detail_stmt->execute([$kayit['id']]);
$detay = $detail_stmt->fetch();
?>
<div class="modal fade" id="logModal<?= $kayit['id'] ?>" tabindex="-1" aria-labelledby="logModalLabel<?= $kayit['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="logModalLabel<?= $kayit['id'] ?>">#<?= $kayit['id'] ?> Servis İşlem Geçmişi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <h6 class="text-primary border-bottom pb-2">Cihaz Ekspertiz Detayları</h6>
                <div class="row small mb-4">
                    <div class="col-md-4"><b>IMEI:</b> <?= htmlspecialchars($detay['imei'] ?? 'Yok') ?></div>
                    <div class="col-md-4"><b>Renk:</b> <?= htmlspecialchars($detay['color'] ?? 'Yok') ?></div>
                    <div class="col-md-4"><b>Kasa Durumu:</b> <?= htmlspecialchars($detay['housing_status'] ?? 'Yok') ?></div>
                    <div class="col-12 mt-2"><b>Arıza Tanımı:</b> <?= nl2br(htmlspecialchars($detay['fault_description'] ?? 'Yok')) ?></div>
                    <div class="col-12 mt-2"><b>Ek Notlar:</b> <?= nl2br(htmlspecialchars($detay['extra_notes'] ?? 'Yok')) ?></div>
                </div>

                <h6 class="text-primary border-bottom pb-2 mt-4">İşlem ve Durum Logları</h6>
                <div class="list-group">
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                                <div class="me-auto">
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($log['log_type']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($log['log_details']) ?></small>
                                </div>
                                <small class="text-nowrap text-end">
                                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?><br>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($log['log_user_email']) ?></span>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">Bu kayıt için henüz bir işlem geçmişi bulunmamaktadır.</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>