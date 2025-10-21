<div class="modal fade" id="dealerDetailModal" tabindex="-1" aria-labelledby="dealerDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="dealerDetailModalLabel">Bayi Detayları Yükleniyor...</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="modalLoader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
                
                <div id="modalContent" class="d-none">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h4 class="card-title text-primary" id="modalDealerName"></h4>
                            <p class="card-text mb-1"><strong>Yetkili:</strong> <span id="modalContactPerson"></span></p>
                            <p class="card-text"><strong>Telefon:</strong> <span id="modalPhoneNumber"></span></p>
                            <hr>
                            <p class="card-text fs-5"><strong>Güncel Bakiye:</strong> <span class="fw-bold text-danger" id="modalCurrentBalance"></span></p>
                        </div>
                    </div>

                    <h5 class="mt-4">İşlem Geçmişi</h5>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-striped table-sm">
                            <thead><tr><th>Tarih</th><th>Açıklama</th><th>Tutar</th></tr></thead>
                            <tbody id="modalTransactionsTableBody">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-start" id="modalFooterActions">
                </div>
        </div>
    </div>
</div>