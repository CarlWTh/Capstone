<<<<<<< HEAD
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bottlesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Bottles Collected',
                data: [250, 320, 280, 400],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderWidth: 3,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');

        const icon = sidebarToggle.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bx-menu');
            icon.classList.add('bx-menu-alt-right');
        } else {
            icon.classList.remove('bx-menu-alt-right');
            icon.classList.add('bx-menu');
        }
        
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);

        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('bx-menu');
            icon.classList.add('bx-menu-alt-right');
        }
    }

    const navLinks = document.querySelectorAll('.sidebar nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            this.parentElement.classList.add('active');
        });
    });

    const transactionFilter = document.querySelector('.transaction-logs select');
    if (transactionFilter) {
        transactionFilter.addEventListener('change', function() {
            console.log('Selected filter:', this.value);
        });
    }

    const exportBtn = document.querySelector('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            alert('Exported transactions to CSV');
        });
    }
=======
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bottlesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Bottles Collected',
                data: [250, 320, 280, 400],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderWidth: 3,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');

        const icon = sidebarToggle.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bx-menu');
            icon.classList.add('bx-menu-alt-right');
        } else {
            icon.classList.remove('bx-menu-alt-right');
            icon.classList.add('bx-menu');
        }
        
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);

        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('bx-menu');
            icon.classList.add('bx-menu-alt-right');
        }
    }

    const navLinks = document.querySelectorAll('.sidebar nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            this.parentElement.classList.add('active');
        });
    });

    const transactionFilter = document.querySelector('.transaction-logs select');
    if (transactionFilter) {
        transactionFilter.addEventListener('change', function() {
            console.log('Selected filter:', this.value);
        });
    }

    const exportBtn = document.querySelector('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            alert('Exported transactions to CSV');
        });
    }
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
});