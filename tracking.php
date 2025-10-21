<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Durumu Sorgulama | Dost GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-header { background-color: #004d99; color: white; padding: 50px 0; margin-bottom: 30px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="images/logo.png" alt="Dost GSM Logo" style="height: 40px; width: auto;"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Anasayfa</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Hizmetlerimiz</a></li>
                <li class="nav-item"><a class="nav-link active" href="tracking.php">Servis Takip</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">Hakkımızda</a></li>
                <li class="nav-item"><a class="nav-link btn btn-sm btn-warning ms-lg-3" href="contact.php">Teklif Al</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1 class="display-5"><i class="fas fa-search-location me-2"></i> Servis Durumu Sorgulama</h1>
        <p class="lead">Cihazınızın güncel tamir durumunu öğrenmek için aşağıdaki bilgileri girin.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm p-4">
                <form id="statusCheckForm" novalidate>
                    <div class="mb-3">
                        <label for="tracking_id" class="form-label fw-bold">Servis Takip Numaranız *</label>
                        <input type="text" class="form-control form-control-lg" id="tracking_id" name="tracking_id" required placeholder="Size verilen servis numarası">
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label fw-bold">Telefon Numaranız *</label>
                        <input type="text" class="form-control form-control-lg" id="phone_number" name="phone_number" required placeholder="Sisteme kayıtlı numaranız">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                        <i class="fas fa-search"></i> Durumu Sorgula
                    </button>
                </form>
            </div>

            <div id="resultContainer" class="mt-4">
                </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> Dost GSM. Profesyonel Tamir Çözümləri.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('statusCheckForm');
    const resultContainer = document.getElementById('resultContainer');
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const originalButtonHtml = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sorgulanıyor...`;

        const formData = new FormData(form);

        fetch('status_checker.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let resultHtml = '';
            if (data.status === 'success') {
                const record = data.data;
                
                let statusClass = 'bg-secondary';
                if (record.status === 'Tamirde') statusClass = 'bg-info';
                if (record.status === 'Parça Bekleniyor') statusClass = 'bg-danger';
                if (record.status === 'Hazır') statusClass = 'bg-success';

                resultHtml = `
                <div class="card shadow-lg">
                    <div class="card-header ${statusClass} text-white">
                        <h5 class="mb-0">Cihaz Durum Bilgisi</h5>
                    </div>
                    <div class="card-body p-4">
                        <p><strong>Müşteri:</strong> ${record.customer_name}</p>
                        <p><strong>Cihaz:</strong> ${record.device_model}</p>
                        <hr>
                        <p class="mb-1"><strong>Güncel Durum:</strong></p>
                        <h3 class="text-primary fw-bold">${record.status}</h3>
                        <small class="text-muted">Son Güncelleme: ${record.updated_at}</small>
                    </div>
                </div>`;
            } else {
                resultHtml = `<div class="alert alert-danger">${data.message}</div>`;
            }
            resultContainer.innerHTML = resultHtml;
        })
        .catch(error => {
            resultContainer.innerHTML = '<div class="alert alert-danger">Beklenmedik bir hata oluştu. Lütfen daha sonra tekrar deneyin.</div>';
            console.error('Error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonHtml;
        });
    });
});
</script>
</body>
</html>