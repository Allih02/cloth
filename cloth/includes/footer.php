</div> <!-- End page-content -->
        </div> <!-- End page-wrapper -->
    </main> <!-- End main-content -->

    <!-- Global Modal Overlay for confirmations, alerts, etc. -->
    <div class="modal-overlay" id="globalModal">
        <div class="modal-content" id="modalContent">
            <!-- Modal content will be dynamically inserted here -->
        </div>
    </div>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-left">
                <p>&copy; <?php echo date('Y'); ?> Super Sub Jersey Store. All rights reserved.</p>
                <p class="footer-version">Version 1.0.0 | Built with ❤️ for jersey enthusiasts</p>
            </div>
            <div class="footer-right">
                <div class="footer-links">
                    <a href="#" onclick="showAbout()">About</a>
                    <a href="#" onclick="showSupport()">Support</a>
                    <a href="#" onclick="showPrivacy()">Privacy</a>
                </div>
                <div class="footer-status">
                    <span class="status-indicator online" title="System Online"></span>
                    <span class="status-text">System Online</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Additional Footer Styles -->
    <style>
        .app-footer {
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
            margin-top: auto;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-normal);
            font-size: var(--font-size-sm);
        }

        .sidebar.collapsed + .main-content ~ .app-footer {
            margin-left: var(--sidebar-collapsed-width);
        }

        .app-footer {
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
            margin-top: auto;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-normal);
            font-size: var(--font-size-sm);
        }

        .sidebar.collapsed + .main-content ~ .app-footer {
            margin-left: var(--sidebar-collapsed-width);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-left p {
            margin: 0;
            color: var(--text-secondary);
        }

        .footer-version {
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .footer-links {
            display: flex;
            gap: 1rem;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--transition-fast);
            font-weight: 500;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .footer-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success-color);
            animation: pulse 2s infinite;
        }

        .status-indicator.offline {
            background: var(--danger-color);
        }

        .status-text {
            color: var(--text-muted);
            font-size: var(--font-size-xs);
        }

        /* Mobile Footer */
        @media (max-width: 768px) {
            .app-footer {
                margin-left: 0;
                padding: 1rem;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .footer-right {
                flex-direction: column;
                gap: 0.75rem;
            }

            .footer-links {
                gap: 1.5rem;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Notification toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-width: 350px;
        }

        .toast {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--shadow-lg);
            transform: translateX(100%);
            transition: transform var(--transition-normal);
            position: relative;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid var(--success-color);
        }

        .toast.error {
            border-left: 4px solid var(--danger-color);
        }

        .toast.warning {
            border-left: 4px solid var(--warning-color);
        }

        .toast.info {
            border-left: 4px solid var(--info-color);
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .toast-title {
            font-weight: 600;
            font-size: var(--font-size-sm);
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-body {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }

        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            z-index: 1000;
        }

        .scroll-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .scroll-to-top.visible {
            display: flex;
        }

        /* Print styles */
        @media print {
            .app-footer,
            .sidebar,
            .mobile-menu-toggle,
            .scroll-to-top,
            .toast-container,
            .btn,
            .alert {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            body {
                background: white !important;
                color: black !important;
                font-size: 12pt !important;
            }

            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" title="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Global JavaScript -->
    <script>
        // Global utility functions and event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initializeGlobalFeatures();
        });

        function initializeGlobalFeatures() {
            // Initialize scroll to top button
            initScrollToTop();
            
            // Initialize global keyboard shortcuts
            initKeyboardShortcuts();
            
            // Initialize system status check
            initSystemStatus();
            
            // Initialize auto-save functionality
            initAutoSave();
            
            // Initialize responsive table handlers
            initResponsiveTables();
            
            // Initialize form enhancements
            initFormEnhancements();
        }

        // Scroll to top functionality
        function initScrollToTop() {
            const scrollBtn = document.getElementById('scrollToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('visible');
                } else {
                    scrollBtn.classList.remove('visible');
                }
            });
            
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Global keyboard shortcuts
        function initKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + / to show help
                if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                    e.preventDefault();
                    showKeyboardHelp();
                }
                
                // Escape to close modals
                if (e.key === 'Escape') {
                    closeAllModals();
                }
                
                // Ctrl/Cmd + S to save forms
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    const activeForm = document.querySelector('form:focus-within');
                    if (activeForm) {
                        e.preventDefault();
                        const submitBtn = activeForm.querySelector('button[type="submit"], input[type="submit"]');
                        if (submitBtn && !submitBtn.disabled) {
                            submitBtn.click();
                        }
                    }
                }
            });
        }

        // System status monitoring
        function initSystemStatus() {
            // Check system status every 30 seconds
            setInterval(checkSystemStatus, 30000);
        }

        function checkSystemStatus() {
            // In a real implementation, this would ping the server
            const statusIndicator = document.querySelector('.status-indicator');
            const statusText = document.querySelector('.status-text');
            
            // Simulate status check
            fetch('includes/status_check.php', { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        statusIndicator.className = 'status-indicator online';
                        statusText.textContent = 'System Online';
                    } else {
                        throw new Error('Server error');
                    }
                })
                .catch(() => {
                    statusIndicator.className = 'status-indicator offline';
                    statusText.textContent = 'System Offline';
                });
        }

        // Auto-save functionality for forms
        function initAutoSave() {
            const forms = document.querySelectorAll('form[data-autosave]');
            
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, textarea, select');
                const formId = form.dataset.autosave;
                
                // Load saved data
                loadFormData(form, formId);
                
                // Save on input
                inputs.forEach(input => {
                    input.addEventListener('input', debounce(() => {
                        saveFormData(form, formId);
                    }, 1000));
                });
            });
        }

        function saveFormData(form, formId) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
        }

        function loadFormData(form, formId) {
            const savedData = localStorage.getItem(`autosave_${formId}`);
            if (savedData) {
                const data = JSON.parse(savedData);
                Object.entries(data).forEach(([name, value]) => {
                    const input = form.querySelector(`[name="${name}"]`);
                    if (input && input.type !== 'password') {
                        input.value = value;
                    }
                });
            }
        }

        // Responsive table handling
        function initResponsiveTables() {
            const tables = document.querySelectorAll('.table');
            
            tables.forEach(table => {
                // Add mobile-friendly features
                makeTableResponsive(table);
            });
        }

        function makeTableResponsive(table) {
            const wrapper = table.closest('.table-responsive');
            if (!wrapper) return;
            
            // Add scroll indicators
            const leftIndicator = document.createElement('div');
            const rightIndicator = document.createElement('div');
            
            leftIndicator.className = 'scroll-indicator left';
            rightIndicator.className = 'scroll-indicator right';
            
            wrapper.appendChild(leftIndicator);
            wrapper.appendChild(rightIndicator);
            
            wrapper.addEventListener('scroll', function() {
                leftIndicator.style.opacity = this.scrollLeft > 0 ? '1' : '0';
                rightIndicator.style.opacity = 
                    this.scrollLeft < (this.scrollWidth - this.clientWidth) ? '1' : '0';
            });
        }

        // Form enhancements
        function initFormEnhancements() {
            // Add loading states to submit buttons
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                    }
                });
            });
        }

        // Utility functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            toast.innerHTML = `
                <div class="toast-header">
                    <span class="toast-title">${getToastTitle(type)}</span>
                    <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        function getToastTitle(type) {
            const titles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Information'
            };
            return titles[type] || 'Notification';
        }

        // Modal system
        function showModal(title, content, actions = []) {
            const modal = document.getElementById('globalModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    ${actions.map(action => `
                        <button class="btn ${action.class || 'btn-secondary'}" 
                                onclick="${action.onclick || 'closeModal()'}">
                            ${action.text}
                        </button>
                    `).join('')}
                </div>
            `;
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('globalModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function closeAllModals() {
            closeModal();
            // Close any other modals
        }

        // Confirmation dialog
        function confirmAction(message, callback) {
            showModal('Confirm Action', message, [
                {
                    text: 'Cancel',
                    class: 'btn-secondary',
                    onclick: 'closeModal()'
                },
                {
                    text: 'Confirm',
                    class: 'btn-danger',
                    onclick: `${callback.name}(); closeModal();`
                }
            ]);
        }

        // Loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Footer modal functions
        function showAbout() {
            showModal('About Super Sub Jersey Store', `
                <div class="text-center">
                    <i class="fas fa-tshirt fa-3x text-primary mb-3"></i>
                    <h4>Super Sub Jersey Store Management System</h4>
                    <p class="text-muted">Version 1.0.0</p>
                    <p>A comprehensive solution for managing jersey inventory, sales, and customer relationships.</p>
                    <hr>
                    <p><strong>Features:</strong></p>
                    <ul class="text-left">
                        <li>Inventory Management</li>
                        <li>Sales Processing</li>
                        <li>Stock Monitoring</li>
                        <li>Reporting & Analytics</li>
                        <li>User Management</li>
                    </ul>
                </div>
            `, [
                { text: 'Close', onclick: 'closeModal()' }
            ]);
        }

        function showSupport() {
            showModal('Support & Help', `
                <div>
                    <h5>Need Help?</h5>
                    <p>We're here to assist you with any questions or issues.</p>
                    
                    <h6>Contact Information:</h6>
                    <ul>
                        <li><strong>Email:</strong> support@supersub.com</li>
                        <li><strong>Phone:</strong> +1 (555) 123-4567</li>
                        <li><strong>Hours:</strong> Monday - Friday, 9AM - 5PM</li>
                    </ul>
                    
                    <h6>Quick Help:</h6>
                    <ul>
                        <li>Press <kbd>Ctrl</kbd> + <kbd>/</kbd> for keyboard shortcuts</li>
                        <li>Use the search function to find products quickly</li>
                        <li>Check the dashboard for important alerts</li>
                    </ul>
                </div>
            `, [
                { text: 'Close', onclick: 'closeModal()' }
            ]);
        }

        function showPrivacy() {
            showModal('Privacy Policy', `
                <div>
                    <h5>Privacy Policy</h5>
                    <p><small>Last updated: ${new Date().toLocaleDateString()}</small></p>
                    
                    <h6>Data Collection</h6>
                    <p>We collect only the necessary information to operate the jersey store management system effectively.</p>
                    
                    <h6>Data Usage</h6>
                    <ul>
                        <li>Inventory tracking and management</li>
                        <li>Sales processing and reporting</li>
                        <li>User authentication and authorization</li>
                        <li>System performance monitoring</li>
                    </ul>
                    
                    <h6>Data Security</h6>
                    <p>All data is encrypted and stored securely. Access is restricted to authorized personnel only.</p>
                    
                    <h6>Contact</h6>
                    <p>For privacy concerns, contact us at privacy@supersub.com</p>
                </div>
            `, [
                { text: 'Close', onclick: 'closeModal()' }
            ]);
        }

        function showKeyboardHelp() {
            showModal('Keyboard Shortcuts', `
                <div>
                    <h5>Available Shortcuts</h5>
                    
                    <h6>Global Shortcuts:</h6>
                    <ul>
                        <li><kbd>Ctrl</kbd> + <kbd>/</kbd> - Show this help</li>
                        <li><kbd>Ctrl</kbd> + <kbd>S</kbd> - Save current form</li>
                        <li><kbd>Esc</kbd> - Close modals</li>
                    </ul>
                    
                    <h6>Navigation Shortcuts:</h6>
                    <ul>
                        <li><kbd>Alt</kbd> + <kbd>S</kbd> - Make Sale</li>
                        <li><kbd>Alt</kbd> + <kbd>P</kbd> - View Products</li>
                        <li><kbd>Alt</kbd> + <kbd>I</kbd> - Stock Management</li>
                        <li><kbd>Alt</kbd> + <kbd>R</kbd> - Reports</li>
                    </ul>
                    
                    <h6>Form Shortcuts:</h6>
                    <ul>
                        <li><kbd>Tab</kbd> - Move to next field</li>
                        <li><kbd>Shift</kbd> + <kbd>Tab</kbd> - Move to previous field</li>
                        <li><kbd>Enter</kbd> - Submit form (when focused on submit button)</li>
                    </ul>
                </div>
            `, [
                { text: 'Close', onclick: 'closeModal()' }
            ]);
        }

        // Service Worker registration for PWA (optional)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('ServiceWorker registration failed: ', registrationError);
                    });
            });
        }

        // Handle online/offline status
        window.addEventListener('online', function() {
            showToast('Connection restored', 'success');
            checkSystemStatus();
        });

        window.addEventListener('offline', function() {
            showToast('Connection lost - working offline', 'warning');
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            showToast('An unexpected error occurred. Please refresh the page if issues persist.', 'error');
        });

        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            showToast('A network error occurred. Please try again.', 'error');
        });
    </script>

    <!-- Page-specific scripts from individual pages -->
    <?php if (isset($page_scripts)): ?>
        <script><?php echo $page_scripts; ?></script>
    <?php endif; ?>

</body>
</html>