<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$currentUserRole = $_SESSION['user_role'] ?? 'Misafir';
$current_page = basename($_SERVER['PHP_SELF']);

// AKILLI MENÜ SİSTEMİ
$menu_gruplari = [
    'servis' => [
        'baslik' => 'Servis Yönetimi',
        'icon' => 'fas fa-tools',
        'roller' => ['Admin', 'Servis'],
        'items' => [
            ['title' => 'Servis Takip', 'url' => 'service_records.php', 'icon' => 'fas fa-list'],
            ['title' => 'Hızlı Kayıt', 'url' => 'service_records.php?quick=true', 'icon' => 'fas fa-plus-circle'],
            ['title' => 'Tamirde Olanlar', 'url' => 'service_records.php?status=Tamirde', 'icon' => 'fas fa-wrench'],
            ['title' => 'Teslime Hazır', 'url' => 'service_records.php?status=Hazır', 'icon' => 'fas fa-check-circle'],
        ]
    ],
    'stok' => [
        'baslik' => 'Stok Yönetimi',
        'icon' => 'fas fa-boxes',
        'roller' => ['Admin', 'Stok'],
        'items' => [
            ['title' => 'Stok Listesi', 'url' => 'inventory.php', 'icon' => 'fas fa-box'],
            ['title' => 'Stok Ekle', 'url' => 'inventory.php?action=add', 'icon' => 'fas fa-plus'],
            ['title' => 'Kritik Stok', 'url' => 'inventory.php?critical=true', 'icon' => 'fas fa-exclamation-triangle'],
            ['title' => 'Stok Hareketleri', 'url' => 'reports.php?type=stock', 'icon' => 'fas fa-exchange-alt'],
        ]
    ],
    'bayi' => [
        'baslik' => 'Bayi & Finans',
        'icon' => 'fas fa-handshake',
        'roller' => ['Admin'],
        'items' => [
            ['title' => 'Bayi Hesapları', 'url' => 'dealers.php', 'icon' => 'fas fa-users'],
            ['title' => 'Veresiye Takip', 'url' => 'dealers.php?filter=debt', 'icon' => 'fas fa-file-invoice-dollar'],
            ['title' => 'Yeni Bayi Ekle', 'url' => 'dealers.php?action=add', 'icon' => 'fas fa-user-plus'],
        ]
    ],
    'rapor' => [
        'baslik' => 'Rapor & Analiz',
        'icon' => 'fas fa-chart-line',
        'roller' => ['Admin', 'Stok'],
        'items' => [
            ['title' => 'Genel Raporlar', 'url' => 'reports.php', 'icon' => 'fas fa-chart-bar'],
            ['title' => 'Servis Raporları', 'url' => 'reports.php?type=service', 'icon' => 'fas fa-tools'],
            ['title' => 'Stok Raporları', 'url' => 'reports.php?type=stock', 'icon' => 'fas fa-boxes'],
            ['title' => 'Finansal Rapor', 'url' => 'reports.php?type=finance', 'icon' => 'fas fa-money-bill-wave'],
        ]
    ],
    'sistem' => [
        'baslik' => 'Sistem Yönetimi',
        'icon' => 'fas fa-cogs',
        'roller' => ['Admin'],
        'items' => [
            ['title' => 'Kullanıcı Yönetimi', 'url' => 'user_management.php', 'icon' => 'fas fa-users-cog'],
            ['title' => 'Sistem Ayarları', 'url' => 'settings.php', 'icon' => 'fas fa-sliders-h'],
            ['title' => 'Yedekleme', 'url' => 'backup.php', 'icon' => 'fas fa-database'],
        ]
    ]
];

// Kullanıcının rolüne uygun menüyü oluştur
$allowed_menu_gruplari = [];
foreach ($menu_gruplari as $key => $grup) {
    if (in_array($currentUserRole, $grup['roller'])) {
        $allowed_menu_gruplari[$key] = $grup;
    }
}
?>

<div class="sidebar modern-sidebar d-flex flex-column">
    <div class="sidebar-header text-center py-4">
        <div class="brand-logo mb-3">
            <i class="fas fa-mobile-alt fa-2x text-warning"></i>
        </div>
        <h4 class="brand-text fw-bold text-white mb-1">DOST GSM</h4>
        <small class="text-white-50">Profesyonel Yönetim</small>
        
        <div class="user-info mt-3 p-3 rounded">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-3">
                    <i class="fas fa-user-circle fa-2x text-light"></i>
                </div>
                <div class="user-details flex-grow-1">
                    <div class="user-name text-white fw-bold small"><?= $_SESSION['user_email'] ?? 'Kullanıcı' ?></div>
                    <div class="user-role">
                        <span class="badge 
                            <?= $currentUserRole === 'Admin' ? 'bg-danger' : '' ?>
                            <?= $currentUserRole === 'Stok' ? 'bg-warning text-dark' : '' ?>
                            <?= $currentUserRole === 'Servis' ? 'bg-info' : '' ?>
                            badge-sm">
                            <?= $currentUserRole ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-menu flex-grow-1">
        <ul class="nav flex-column mb-4">
            <li class="nav-item dashboard-item">
                <a class="nav-link text-white <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                    <div class="nav-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <span class="nav-text">Kontrol Paneli</span>
                    <span class="nav-badge"></span>
                </a>
            </li>

            <?php foreach ($allowed_menu_gruplari as $grup_key => $grup): ?>
            <li class="nav-group active">
                <div class="nav-group-header">
                    <div class="nav-group-icon">
                        <i class="<?= $grup['icon'] ?>"></i>
                    </div>
                    <span class="nav-group-title"><?= $grup['baslik'] ?></span>
                    <i class="nav-group-arrow fas fa-chevron-down"></i>
                </div>
                <ul class="nav-group-items">
                    <?php foreach ($grup['items'] as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white <?= ($current_page == $item['url'] || strpos($item['url'], $current_page) !== false) ? 'active' : '' ?>" 
                           href="<?= htmlspecialchars($item['url']) ?>">
                            <div class="nav-icon">
                                <i class="<?= $item['icon'] ?>"></i>
                            </div>
                            <span class="nav-text"><?= htmlspecialchars($item['title']) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="sidebar-footer mt-auto">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white theme-toggle" href="#" onclick="toggleSidebarTheme()">
                    <div class="nav-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <span class="nav-text">Tema Değiştir</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white" href="system_status.php">
                    <div class="nav-icon">
                        <i class="fas fa-heart-pulse"></i>
                    </div>
                    <span class="nav-text">Sistem Durumu</span>
                    <span class="nav-badge bg-success"></span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-danger logout-btn" href="logout.php">
                    <div class="nav-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <span class="nav-text">Güvenli Çıkış</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-copyright text-center py-3">
            <small class="text-white-50">
                <i class="fas fa-copyright me-1"></i>
                <?= date('Y') ?> Dost GSM<br>
                <small>v2.0</small>
            </small>
        </div>
    </div>
</div>

<div class="sidebar-overlay"></div>

<style>
/* === MODERN SIDEBAR STYLES === */
.modern-sidebar {
    background: linear-gradient(135deg, #2b2d42 0%, #4a4e69 100%);
    box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    min-height: 100vh;
    position: fixed;
    width: 280px;
    z-index: 1000;
    transition: all 0.3s ease;
}

/* Sidebar Header */
.sidebar-header {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
}

.brand-logo {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.user-info {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

/* DÜZELTME: Ana Menü Alanı */
.sidebar-menu {
    flex-grow: 1;
    overflow-y: auto; /* Dikey kaydırma çubuğunu otomatik olarak gösterir */
    overflow-x: hidden; /* Yatay kaydırmayı engeller */
}

/* Navigation Items */
.sidebar-menu .nav-item {
    margin: 2px 0;
}

.sidebar-menu .nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 2px 15px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.sidebar-menu .nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.sidebar-menu .nav-link:hover {
    background: rgba(255,255,255,0.1);
    transform: translateX(5px);
}

.sidebar-menu .nav-link:hover::before {
    transform: scaleY(1);
}

.sidebar-menu .nav-link.active {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
}

.sidebar-menu .nav-link.active::before {
    transform: scaleY(1);
}

/* Nav Icons */
.nav-icon {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 1.1rem;
}

.nav-text {
    flex-grow: 1;
    font-weight: 500;
}

.nav-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Menu Groups */
.nav-group {
    margin: 10px 0;
}

.nav-group-header {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 2px 15px;
    border-radius: 8px;
}

.nav-group-header:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.nav-group-icon {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 1rem;
}

.nav-group-title {
    flex-grow: 1;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.nav-group-arrow {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.nav-group.active .nav-group-arrow {
    transform: rotate(180deg);
}

.nav-group-items {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.nav-group.active .nav-group-items {
    max-height: 500px; /* Yeterince büyük bir değer */
}

.nav-group-items .nav-link {
    padding-left: 52px;
    font-size: 0.9rem;
    margin: 1px 15px;
}

/* Dashboard Item */
.dashboard-item .nav-link {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    margin: 10px 15px;
}

.dashboard-item .nav-link .nav-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Sidebar Footer */
.sidebar-footer {
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.2);
}

.logout-btn:hover {
    background: rgba(220, 53, 69, 0.2) !important;
}

/* Mobil Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-sidebar {
        transform: translateX(-100%);
        width: 260px;
    }
    
    .modern-sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

/* Dark Theme Variant */
[data-bs-theme="dark"] .modern-sidebar {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

/* Scrollbar Styling */
.sidebar-menu::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.2);
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.5);
}
</style>

<script>
// Menu grup toggle
document.addEventListener('DOMContentLoaded', function() {
    const navGroups = document.querySelectorAll('.nav-group-header');
    
    navGroups.forEach(header => {
        header.addEventListener('click', function() {
            const parent = this.parentElement;
            parent.classList.toggle('active');
        });
    });
});

// Tema değiştirme
function toggleSidebarTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Buton ikonunu güncelle
    const themeIcon = document.querySelector('.theme-toggle .nav-icon i');
    themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Mobile menu toggle
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.modern-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('sidebar-overlay')) {
        toggleMobileSidebar();
    }
});

// Initialize theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    
    // Update theme button icon
    const themeIcon = document.querySelector('.theme-toggle .nav-icon i');
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
});
</script>