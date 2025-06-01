<?php if (isLoggedIn()): ?>
            <footer class="bg-white p-4 border-t">
                <div class="text-center text-sm text-gray-500">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
            </footer>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
    <script>
        // Toggle user menu
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', () => {
                userMenu.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (event) => {
                if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }
        
        // Mobile sidebar toggle
        const toggleSidebarButton = document.getElementById('toggle-sidebar');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        
        if (toggleSidebarButton && closeSidebarButton && mobileSidebar) {
            toggleSidebarButton.addEventListener('click', () => {
                mobileSidebar.classList.remove('hidden');
            });
            
            closeSidebarButton.addEventListener('click', () => {
                mobileSidebar.classList.add('hidden');
            });
        }
    </script>
</body>
</html>