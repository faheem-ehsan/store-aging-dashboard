# Stock Aging Dashboard — PHP MVC + MySQL

A lightweight PHP MVC dispatch-store dashboard for division-based stores. It follows the supplied batch fields and grades **each batch separately** from its Expiry Date, with EX applied on the actual Expiry Date.

## Included

- PHP 8.1+ MVC structure.
- MySQL / PDO with prepared statements.
- Division-based login and data isolation.
- Roles:
  - **Super Admin** — all divisions, stock management, users, divisions.
  - **Division Admin** — add/edit/delete stock batches only inside their assigned division.
  - **User** — read-only dashboard and dispatch-stock report for their assigned division.
- Preloaded divisions: **JSD**, **SNW**, **RTD**.
- Your fourth division was not named, so the Super Admin can add it from **Divisions** without code changes.
- Dispatch stock fields: division, material number, material description, current quantity, stock value, batch number, manufacturing date, expiry date, aging days, remaining days and receiving store.
- CSV import for all active divisions. Super Admin can import multiple divisions in one file; Division Admins can import only their own division.
- FEFO-style alert ordering by earliest expiry.
- Five common grades across all divisions: **A+**, **A**, **B**, **C**, and **Expired**. Manufacture-date thresholds are configurable in days separately for every division by Super Admin.
- CSRF protection, password hashing, session regeneration, output escaping, authorization checks.
- `stock_movements` table included as the audit foundation for receipts/issues/adjustments.

## Default grading

Grades are calculated from the **Expiry Date** using division-specific day thresholds. The default settings are:

| Grade | Color | Default rule |
|---|---|---|
| A+ | Dark Blue | First 30 days from manufacture |
| A | Green | After A+ through day 60 |
| B | Yellow | After A through day 90 |
| C | Red | After B; default C threshold day 120, then remains C until expiry |
| Expired | Black | On the Expiry Date and afterwards |

Super Admin can open **Grading Criteria** and enter the day thresholds independently for every division. The colors stay fixed across all divisions. Threshold values must progress **A+ < A < B < C**. Expired is always determined by the actual Expiry Date, not by a configured day value.

## XAMPP / Windows setup

This version supports running directly from a subfolder such as:

`C:\xampp\htdocs\store-aging-dashboard`

1. Extract/copy the complete project into:

   `C:\xampp\htdocs\store-aging-dashboard`

2. Start **Apache** and **MySQL** in XAMPP.

3. Open phpMyAdmin:

   `http://localhost/phpmyadmin/`

4. Import:

   `database/schema.sql`

   The script creates the `store_aging` database automatically.

5. If your MySQL root user has no password (normal default XAMPP setup), no DB config change is needed. Otherwise edit `config/config.php` or use environment variables.

6. Make sure Apache `mod_rewrite` is enabled and `AllowOverride All` applies to the XAMPP `htdocs` directory. In a normal XAMPP installation, `mod_rewrite` is usually already available.

7. Open:

   `http://localhost/store-aging-dashboard/`

Do **not** browse only the folder listing. The project root now contains an `index.php` and `.htaccess` specifically so XAMPP can run the application directly from the project folder.

### If you still see “Index of /store-aging-dashboard”

Confirm that both of these files exist directly inside `C:\xampp\htdocs\store-aging-dashboard`:

- `index.php`
- `.htaccess`

Windows can hide dot-files, so enable **View > Show > Hidden items** if needed. Restart Apache after changing Apache configuration.

## Production web-server setup

For production, the safer setup is to point the Apache/Nginx document root directly to the **`public/`** directory. Apache rewrite rules are included in `public/.htaccess`.

For PHP's built-in server:

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Then open `http://127.0.0.1:8000`.

## Initial login

- Division: **Super Admin**
- Email: `admin@example.com`
- Password: `Admin@123`

Change the initial credentials for production use.

## Suggested next steps

- Add receipt/issue screens that post to `stock_movements` and calculate `current_qty` transactionally.
- Add pagination/server-side filtering when batch volume becomes large.
- Add database backups and HTTPS before production deployment.

## Project layout

```text
index.php                 # XAMPP/WAMP convenience front controller
.htaccess                 # XAMPP/WAMP root rewrite and source protection
app/
  Controllers/
  Core/
  Models/
  Services/
  Views/
config/
database/schema.sql
public/index.php           # production front controller
public/assets/css/app.css
```

## User profile pictures (existing database)

If you are upgrading an existing installation, import this migration once in phpMyAdmin before uploading user pictures:

```text
database/migrations/001_add_user_avatar.sql
```

New installations that import `database/schema.sql` already include the `avatar_path` column and do not need the migration.

Profile pictures are uploaded to `public/uploads/users/`. Make sure this folder is writable by PHP/Apache. JPG, PNG and WEBP files up to 2 MB are accepted.

## Dispatch stock fields and CSV import

The Add Batch screen now follows the uploaded Stock Aging Report layout:

- Division
- Product Code
- Product Name
- Current Stock
- UoM
- Stock Value
- Batch No
- Manufacturing Date
- Expiry Date
- Aging (Days) — calculated automatically
- Aging Remaining (Days) — calculated automatically
- Stock Location

The **Import CSV** button is available to Super Admin and Division Admin users from the stock screen.

- Super Admin can import rows for any active division using the `Division` column.
- Division Admin can import only their own division.
- Existing rows with the same **Division + Product Code + Batch No** are updated instead of duplicated.
- CSV dates accept `YYYY-MM-DD`, `DD.MM.YYYY`, `DD/MM/YYYY`, and common Excel date serials.
- Aging columns can be present but blank; the system calculates them from the dates.
- The source-system division code `SNMW` is accepted as `SNW` during import.
- A downloadable template is available from the CSV Import screen. A copy is also included at `database/templates/qarshi_dispatch_stock_import_template.csv`.

### Existing database upgrade

If you already installed an earlier version, import this migration **once** in phpMyAdmin:

```text
database/migrations/002_add_dispatch_stock_fields.sql
```

It adds `stock_value` and `receiving_store`, and makes the legacy `last_receipt_date` field optional. New installations using `database/schema.sql` already contain these changes.

## Super Admin System Logs

This version includes an audit log available only to **Super Admin** accounts. The sidebar shows **System Logs** only for Super Admin, and the `/logs` and `/logs/export` routes enforce Super Admin authorization server-side.

For an existing database, import this migration once in phpMyAdmin:

```text
database/migrations/003_add_audit_logs.sql
```

The log records successful and failed logins, logout, manual batch create/update/delete, CSV import summaries, user create/update/status changes, division create/status changes, and Super Admin grading-criteria changes. Entries include date/time, actor, division, action, description, source IP, and structured details. Passwords are never written to the audit log.

## Manufacture-date grading upgrade

If you are upgrading an existing database, import this migration once in phpMyAdmin:

```text
database/migrations/004_add_division_grade_settings.sql
```

It creates the per-division grading settings table and initializes every existing division with **A+ = 1 month**, **A = 2 months**, and **B = 3 months**. After the migration, Super Admin can change each division independently from **Grading Criteria**.


The grade names and colors are fixed for all divisions: **A+ = Dark Blue**, **A = Green**, **B = Yellow**, **C = Red**, and **Expired = Black**. The dashboard grade cards show quantity and stock value for each grade.


### Five-grade upgrade
For an existing database, import `database/migrations/005_add_grade_c_and_b_threshold.sql` once. This adds the B threshold needed for A+, A, B, C, and Expired grading.

## Day-based grading criteria upgrade

For an existing database that already has the earlier grading migrations, import this migration once in phpMyAdmin:

```text
database/migrations/006_change_grading_criteria_to_days.sql
```

It adds **A+ Days, A Days, B Days, and C Days** for each division and converts the existing month settings to initial day values. Super Admin can then edit the exact day thresholds from **Grading Criteria**. Grade colors are unchanged.

## Market Return Store module

A separate Market Return Store module is available at `/market-return`. It does not change the existing Home/Dashboard calculations or Stock Aging data tables.

### Permissions

- **Super Admin:** can view all divisions, add/edit/delete items, and import Market Return files with automatic division assignment.
- **Division Admin:** can view and manage only the division assigned to the login.
- **User:** can view only the assigned division and cannot import or change records.

The module reuses the existing `divisions` and `users` tables, so no separate Market Return users or division masters are required.

### Install on an existing database

Import this file once in phpMyAdmin:

`database/migrations/007_add_market_return_store_module.sql`

Do not re-import the full `database/schema.sql` into an existing database.

### Multi-sheet import

Use **Market Return Store > Import Market Return File**. The importer accepts `.xlsx` and `.csv` files. The supplied XLSX template is the original multi-sheet Market Return workbook and recognizes these sheets:

- Cabinets
- Export Stock
- Promotion Items
- RawalKot stock
- Tablet Strips / loose items
- Fresh Saleable Stock final
- Utilizable Stock
- Expired

Division assignment is automatic but fully independent from the Stock Aging material master. For Super Admin imports, the importer uses a `Division` column when present, then the dedicated **Market Return Division Mapping** rules, then prior Market Return history. Division Admin imports are automatically locked to the division assigned to the login, and exact product mappings are learned for future imports. Rows that cannot be matched to one unique division are never guessed; they are listed as unmatched. If unmatched rows exist, **Replace existing records** is automatically disabled for that import to protect existing data. Existing installations should also import `database/migrations/008_add_market_return_division_mapping.sql`.

The XLSX reader uses PHP `ZipArchive` when available and falls back to `PharData`, so it does not require Composer or PhpSpreadsheet.

## Market Return Store expiry cards

The Market Return Store dashboard groups imported rows by Expiry Date using these dashboard colors:
- A+ (dark blue): 365+ days remaining
- A (green): 181-364 days remaining
- B (yellow): 91-180 days remaining
- C (red): 0-90 days remaining
- Expired (black): expiry date has passed, or expiry is explicitly marked Expired
- No Expiry (gray): blank, N.A, Not Found or otherwise unavailable expiry

The original workbook sheet/category remains available as a separate Source Category filter. No database migration is required for these expiry groups because they are calculated at runtime from the existing expiry fields.


## Market Return expiry grouping
Market Return Store uses only four expiry cards: B (365+ days remaining), C (1-364 days remaining), Expired (on the Expiry Date and after), and Other / No Expiry (blank, N.A, Not Found, or invalid expiry). Source workbook categories remain separate filters.
