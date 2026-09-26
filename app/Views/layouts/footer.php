<?php $footerText = 'Qarshi Stock Aging Dashboard Powered By Team (Symbol-Alpha)'; ?>
<?php if (auth_user()): ?>
        <footer class="app-footer"><?= e($footerText) ?></footer>
    </main>
</div>
<?php else: ?>
    <div class="guest-footer"><?= e($footerText) ?></div>
</div>
<?php endif; ?>
</body>
</html>
