<div class="modal fade" id="statementModal" tabindex="-1" aria-labelledby="statementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="statementModalLabel"><i class="fab fa-whatsapp me-2"></i> Hesap Ekstresi Gönder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Aşağıdaki mesaj WhatsApp üzerinden gönderilmek üzere hazırlanmıştır.</p>
                <div class="mb-3">
                    <label for="messagePreview" class="form-label fw-bold">Mesaj Önizleme:</label>
                    <textarea class="form-control" id="messagePreview" rows="12" readonly></textarea>
                </div>
                <a href="#" id="sendWhatsAppButton" target="_blank" class="btn btn-success w-100 fw-bold" onclick="document.getElementById('statementModal').querySelector('.btn-close').click();">
                    <i class="fab fa-whatsapp me-2"></i> WhatsApp'ta Gönder
                </a>
            </div>
        </div>
    </div>
</div>