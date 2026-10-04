<section class="admin-hub">
    <div class="admin-hub-hero">
        <div>
            <span class="admin-hub-kicker">SUPER ADMIN</span>
            <h2>Administration</h2>
            <p>System configuration, access control, audit tools and administrative data controls in one place.</p>
        </div>
        <div class="admin-hub-badge">Admin Control Center</div>
    </div>

    <section class="admin-hub-section">
        <div class="admin-hub-section-head">
            <div>
                <h3>System Configuration</h3>
                <p>Manage divisions, grading rules and Market Return assignment rules.</p>
            </div>
        </div>

        <div class="admin-hub-grid">
            <a class="admin-hub-card" href="<?= e(url('/divisions')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6a2 2 0 0 1 2-2h4v6H4V6Zm10-2h4a2 2 0 0 1 2 2v4h-6V4ZM4 14h6v6H6a2 2 0 0 1-2-2v-4Zm10 0h6v4a2 2 0 0 1-2 2h-4v-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Divisions</strong>
                    <small>Create divisions and control division status.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>

            <a class="admin-hub-card" href="<?= e(url('/grade-criteria')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v6l4 2M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Grading Criteria</strong>
                    <small>Configure shelf-life grading percentages for every division.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>


            <a class="admin-hub-card" href="<?= e(url('/admin/email-notifications')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6Zm0 1 8 6 8-6M17 4v4M15 6h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Grade Email Alerts</strong>
                    <small>SMTP, Division Head recipients and automatic next-grade notifications.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>

            <a class="admin-hub-card" href="<?= e(url('/market-return/mapping')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h9M5 17h6M18 11v8m-4-4h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Market Return Division Mapping</strong>
                    <small>Maintain automatic Market Return product and keyword assignments.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>
        </div>
    </section>

    <section class="admin-hub-section">
        <div class="admin-hub-section-head">
            <div>
                <h3>Access & Monitoring</h3>
                <p>Manage system users and review administrative activity.</p>
            </div>
        </div>

        <div class="admin-hub-grid admin-hub-grid-two">
            <a class="admin-hub-card" href="<?= e(url('/users')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M16 19a4 4 0 0 0-8 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 6a4 4 0 0 0-3-3.87M5 19a4 4 0 0 1 3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>User Management</strong>
                    <small>Create users, assign roles and manage division access.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>

            <a class="admin-hub-card" href="<?= e(url('/logs')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M8 7h8M8 11h8M8 15h5M6 4h12a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Activity Logs</strong>
                    <small>Review system changes, user activity and audit history.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>
        </div>
    </section>

    <section class="admin-hub-section">
        <div class="admin-hub-section-head">
            <div>
                <h3>Administrative Data Controls</h3>
                <p>Permanent bulk-delete tools remain protected by confirmation and Activity Logs.</p>
            </div>
        </div>

        <div class="admin-hub-grid admin-hub-grid-two">
            <a class="admin-hub-card admin-hub-card-danger" href="<?= e(url('/stocks?bulk_delete=1')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Finish Goods Bulk Delete</strong>
                    <small>Delete Finish Goods batch records by selected division.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>

            <a class="admin-hub-card admin-hub-card-danger" href="<?= e(url('/market-return?bulk_delete=1')) ?>">
                <span class="admin-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="admin-hub-card-copy">
                    <strong>Market Return Bulk Delete</strong>
                    <small>Delete Market Return records by selected division.</small>
                </span>
                <span class="admin-hub-arrow">→</span>
            </a>
        </div>
    </section>
</section>

