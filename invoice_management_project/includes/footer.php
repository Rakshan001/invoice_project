            </div> <!-- End of main-content -->
        </div> <!-- End of row -->
    </div> <!-- End of container-fluid -->

    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-copyright">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> InvoicePro. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="contact.php">Contact Us</a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- ApexCharts for Dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }
            
            if (sidebarClose && sidebar) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || menuToggle.contains(event.target);
                
                if (!isClickInside && window.innerWidth < 992 && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        });
    </script>
    
    <style>
        /* Footer Styles */
        .app-footer {
            background-color: #fff;
            box-shadow: 0 -1px 10px rgba(34, 41, 47, 0.1);
            padding: 1rem 1.5rem;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed);
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-copyright {
            font-size: 0.9rem;
            color: #6E6B7B;
        }
        
        .footer-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-links a {
            color: #6E6B7B;
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 991.98px) {
            .app-footer {
                margin-left: 0;
            }
        }
        
        @media (max-width: 767.98px) {
            .footer-content {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .footer-links {
                gap: 1rem;
            }
        }
    </style>
</body>
</html> 