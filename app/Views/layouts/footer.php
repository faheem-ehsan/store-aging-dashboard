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
</body>
</html>
