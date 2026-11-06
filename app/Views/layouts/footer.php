<?php $currentYear = date('Y'); ?>
<?php if (auth_user()): ?>
        <footer class="app-footer professional-footer">
            <span>© <?= e($currentYear) ?> Qarshi Stock Aging Dashboard. All Rights Reserved.</span>
            <span class="footer-divider">•</span>
            <span>Powered By Team <strong>(α)</strong></span>
        </footer>
    </main>
</div>
<?php else: ?>
    <div class="guest-footer professional-footer">
        <span>© <?= e($currentYear) ?> Qarshi Stock Aging Dashboard. All Rights Reserved.</span>
        <span class="footer-divider">•</span>
        <span>Powered By Team <strong>(α)</strong></span>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    var root = document.documentElement;
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    function sync() {
        var collapsed = root.classList.contains('sidebar-collapsed');
        var label = collapsed ? 'Expand menu' : 'Collapse menu';
        btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
    }
    btn.addEventListener('click', function () {
        var collapsed = root.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('sad.sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        sync();
    });
    sync();
})();
</script>
</body>
</html>
