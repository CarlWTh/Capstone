// Fixed Sidebar Controller JavaScript
class SidebarController {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.sidebarToggle = document.querySelector('.sidebar-toggle');
        this.mobileSidebarToggle = document.querySelector('.mobile-sidebar-toggle');
        this.overlay = null;

        this.init();
    }
    
    init() {
        // Create mobile overlay FIRST
        this.createMobileOverlay();
        
        // Event listeners
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Mobile toggle button listener
        if (this.mobileSidebarToggle) {
            this.mobileSidebarToggle.addEventListener('click', () => this.toggleMobileSidebar());
        }
        
        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Handle mobile overlay clicks
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeSidebar());
        }
        
        // Initialize responsive behavior
        this.handleResize();
        
        // Handle escape key for mobile
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobile() && this.sidebar.classList.contains('show')) {
                this.closeSidebar();
            }
        });

        // Prevent sidebar from disappearing on orientation change
        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.handleResize(), 100);
        });
    }
    
    createMobileOverlay() {
        // Remove existing overlay if it exists
        const existingOverlay = document.querySelector('.sidebar-overlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }
        
        this.overlay = document.createElement('div');
        this.overlay.className = 'sidebar-overlay';
        
        // Insert overlay at the beginning of body, before sidebar
        document.body.insertBefore(this.overlay, document.body.firstChild);
    }
    
    toggleSidebar() {
        console.log('Toggle sidebar - isMobile:', this.isMobile()); // Debug log
        
        if (this.isMobile()) {
            this.toggleMobileSidebar();
        } else {
            this.toggleDesktopSidebar();
        }
    }
    
    toggleDesktopSidebar() {
        this.sidebar.classList.toggle('collapsed');
        if (this.mainContent) {
            this.mainContent.classList.toggle('sidebar-collapsed');
        }
        
        // Save state to localStorage - but check if localStorage is available
        try {
            const isCollapsed = this.sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed.toString());
        } catch (e) {
            console.warn('localStorage not available');
        }
        
        // Trigger custom event for other components
        window.dispatchEvent(new CustomEvent('sidebarToggle', { 
            detail: { collapsed: this.sidebar.classList.contains('collapsed') }
        }));
    }
    
    toggleMobileSidebar() {
        const isShown = this.sidebar.classList.contains('show');
        
        console.log('Mobile sidebar toggle - currently shown:', isShown); // Debug log
        
        if (isShown) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
    
    openSidebar() {
        console.log('Opening mobile sidebar'); // Debug log
        
        // Ensure sidebar is visible
        this.sidebar.style.display = 'block';
        this.sidebar.classList.add('show');
        
        if (this.overlay) {
            this.overlay.classList.add('show');
        }
        
        document.body.style.overflow = 'hidden'; // Prevent body scroll
        
        // Force a repaint
        this.sidebar.offsetHeight;
    }
    
    closeSidebar() {
        console.log('Closing mobile sidebar'); // Debug log
        
        this.sidebar.classList.remove('show');
        
        if (this.overlay) {
            this.overlay.classList.remove('show');
        }
        
        document.body.style.overflow = ''; // Restore body scroll
    }
    
    isMobile() {
        return window.innerWidth <= 768;
    }
    
    handleResize() {
        console.log('Handle resize - isMobile:', this.isMobile()); // Debug log
        
        if (this.isMobile()) {
            // Mobile: remove desktop collapse states
            this.sidebar.classList.remove('collapsed');
            if (this.mainContent) {
                this.mainContent.classList.remove('sidebar-collapsed');
            }
            
            // Ensure sidebar is properly positioned for mobile
            this.sidebar.style.display = 'block';
            
        } else {
            // Desktop: remove mobile show states and restore saved state
            this.sidebar.classList.remove('show');
            if (this.overlay) {
                this.overlay.classList.remove('show');
            }
            document.body.style.overflow = '';
            
            // Ensure sidebar is visible on desktop
            this.sidebar.style.display = 'block';
            
            // Restore saved collapse state
            try {
                const savedState = localStorage.getItem('sidebarCollapsed');
                if (savedState === 'true') {
                    this.sidebar.classList.add('collapsed');
                    if (this.mainContent) {
                        this.mainContent.classList.add('sidebar-collapsed');
                    }
                }
            } catch (e) {
                console.warn('localStorage not available');
            }
        }
    }
    
    // Public methods for external control
    collapse() {
        if (!this.isMobile()) {
            this.sidebar.classList.add('collapsed');
            if (this.mainContent) {
                this.mainContent.classList.add('sidebar-collapsed');
            }
            try {
                localStorage.setItem('sidebarCollapsed', 'true');
            } catch (e) {
                console.warn('localStorage not available');
            }
        }
    }
    
    expand() {
        if (!this.isMobile()) {
            this.sidebar.classList.remove('collapsed');
            if (this.mainContent) {
                this.mainContent.classList.remove('sidebar-collapsed');
            }
            try {
                localStorage.setItem('sidebarCollapsed', 'false');
            } catch (e) {
                console.warn('localStorage not available');
            }
        }
    }
    
    // Force sidebar to be visible (debug method)
    forceVisible() {
        this.sidebar.style.display = 'block !important';
        this.sidebar.style.visibility = 'visible !important';
        this.sidebar.style.opacity = '1 !important';
        console.log('Forced sidebar visible');
    }
    
    // Get current sidebar state
    getState() {
        return {
            isMobile: this.isMobile(),
            isCollapsed: this.sidebar.classList.contains('collapsed'),
            isShown: this.sidebar.classList.contains('show'),
            display: window.getComputedStyle(this.sidebar).display,
            transform: window.getComputedStyle(this.sidebar).transform,
            visibility: window.getComputedStyle(this.sidebar).visibility
        };
    }
    
    // Set active navigation item
    setActiveNav(selector) {
        // Remove all active states
        document.querySelectorAll('.sidebar nav li').forEach(li => {
            li.classList.remove('active');
        });
        
        // Add active state to selected item
        const activeItem = document.querySelector(selector);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }
    
    // Add navigation item dynamically
    addNavItem(config) {
        const nav = this.sidebar.querySelector('nav ul');
        if (!nav) return;
        
        const li = document.createElement('li');
        const a = document.createElement('a');
        
        a.href = config.href || '#';
        a.innerHTML = `
            <i class="${config.icon || 'fas fa-circle'}"></i>
            <span>${config.text}</span>
        `;
        
        if (config.onClick) {
            a.addEventListener('click', config.onClick);
        }
        
        li.appendChild(a);
        nav.appendChild(li);
        
        return li;
    }
    
    // Remove navigation item
    removeNavItem(selector) {
        const item = document.querySelector(selector);
        if (item && item.parentNode) {
            item.parentNode.removeChild(item);
        }
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sidebarController = new SidebarController();
    
    // Debug: Log sidebar state every 2 seconds
    setInterval(() => {
        if (window.sidebarController && window.innerWidth <= 768) {
            console.log('Sidebar state:', window.sidebarController.getState());
        }
    }, 2000);
});

// Alternative initialization for manual control
function initSidebar() {
    return new SidebarController();
}

// Enhanced utility functions for easy integration
const SidebarUtils = {
    // Toggle sidebar programmatically
    toggle() {
        if (window.sidebarController) {
            window.sidebarController.toggleSidebar();
        }
    },
    
    // Set active navigation
    setActive(selector) {
        if (window.sidebarController) {
            window.sidebarController.setActiveNav(selector);
        }
    },
    
    // Check if sidebar is collapsed (desktop only)
    isCollapsed() {
        const sidebar = document.querySelector('.sidebar');
        return sidebar ? sidebar.classList.contains('collapsed') : false;
    },
    
    // Check if sidebar is shown (mobile only)
    isShown() {
        const sidebar = document.querySelector('.sidebar');
        return sidebar ? sidebar.classList.contains('show') : false;
    },
    
    // Force collapse/expand (desktop only)
    collapse() {
        if (window.sidebarController) {
            window.sidebarController.collapse();
        }
    },
    
    expand() {
        if (window.sidebarController) {
            window.sidebarController.expand();
        }
    },
    
    // Debug methods
    forceVisible() {
        if (window.sidebarController) {
            window.sidebarController.forceVisible();
        }
    },
    
    getState() {
        if (window.sidebarController) {
            return window.sidebarController.getState();
        }
        return null;
    }
};