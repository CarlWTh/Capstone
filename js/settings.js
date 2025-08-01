        document.addEventListener('DOMContentLoaded', function() {
        
            const newPassword = document.getElementById('new-password');
            const strengthSegments = document.querySelectorAll('.strength-segment');
            const strengthText = document.querySelector('.password-strength strong');
            
            newPassword.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                strengthSegments.forEach(segment => {
                    segment.style.backgroundColor = '#ddd';
                });
                
                for (let i = 0; i < strength; i++) {
                    if (strengthSegments[i]) {
                        const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71'];
                        strengthSegments[i].style.backgroundColor = colors[i];
                    }
                }
                
                const strengthLabels = ['Weak', 'Medium', 'Strong', 'Very Strong'];
                strengthText.textContent = strength > 0 ? strengthLabels[strength-1] : 'None';
            });
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