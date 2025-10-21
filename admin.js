document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar');
    var sidebarToggleButton = document.getElementById('sidebarToggleBtn');

    if (sidebarToggleButton && sidebar) {
        sidebarToggleButton.addEventListener('click', function(event) {
            event.stopPropagation();
            sidebar.classList.toggle('show');
        });
    }
    
    var mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function() {
            if (sidebar && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
});