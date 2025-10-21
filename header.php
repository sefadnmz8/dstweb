<div class="modern-header sticky-top">
    <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <!-- Mobile Menu Button -->
            <button class="btn btn-primary mobile-menu-btn" type="button" onclick="toggleMobileSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Brand -->
            <div class="navbar-brand d-none d-md-block">
                <span class="fw-bold text-primary">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Dost GSM Yönetim Paneli
                </span>
            </div>
            
            <!-- Page Title -->
            <div class="page-title mx-auto">
                <h1 class="h4 mb-0 fw-bold text-dark">
                    <?php
                    $pageTitles = [
                        'dashboard.php' => 'Kontrol Paneli',
                        'service_records.php' => 'Servis Takip',
                        'inventory.php' => 'Stok Yönetimi',
                        'dealers.php' => 'Bayi Hesapları',
                        'reports.php' => 'Raporlar',
                        'user_management.php' => 'Kullanıcı Yönetimi'
                    ];
                    $currentPage = basename($_SERVER['PHP_SELF']);
                    echo $pageTitles[$currentPage] ?? 'Dost GSM Panel';
                    ?>
                </h1>
            </div>

            <!-- User Menu -->
            <div class="user-menu">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle user-dropdown" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <span class="d-none d-md-inline"><?= $_SESSION['user_email'] ?? 'Kullanıcı' ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted">Giriş yapan:</small><br>
                                <strong><?= $_SESSION['user_email'] ?? 'Kullanıcı' ?></strong>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="toggleTheme()">
                                <i class="fas fa-palette me-2"></i>Tema Değiştir
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</div>

<style>
.modern-header {
    z-index: 999;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
}

.mobile-menu-btn {
    border: none;
    border-radius: 10px;
    padding: 8px 12px;
}

.page-title {
    text-align: center;
}

.user-dropdown {
    border-radius: 10px;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
}

.user-dropdown:hover {
    background: #4361ee;
    color: white;
}

.dropdown-menu {
    border: none;
    border-radius: 12px;
    min-width: 200px;
}

@media (max-width: 768px) {
    .page-title h1 {
        font-size: 1.1rem;
    }
    
    .navbar-brand {
        display: none !important;
    }
}
</style>