# POS System Audit Report

## Critical Issues
- File: `app/pointofsale_new.php` (lines 155-313)
- Issue: The new POS checkout flow inserts `shop_pos_billdetails`, `shop_pos_mainsale`, customer account entries, payment logs, and `cash_book` rows, then marks the bill complete, but it never decrements stock from `shop_stock_item`, `shop_stock_imei`, or `shop_rcv_stock`.
- Risk: High
- Fix recommendation: Block rollout of `pointofsale_new.php` until stock mutation logic is restored and covered by transaction-safe tests against barcode, IMEI, and recharge-card items.

- File: `app/pointofsale_new.php` (lines 137-159), `app/pointofsale.php` (lines 144-163), `app/transfer_product.php` (line 62), `app/transfer_product_new.php` (line 62), `app/rep_new_job.php` (line 70)
- Issue: Sequential IDs are generated with `SELECT MAX(...)` plus application-side incrementing. Under concurrent cashiering or transfers, duplicate bill / transfer / job numbers can be produced.
- Risk: High
- Fix recommendation: Replace `MAX()+1` sequencing with database-enforced unique sequences or locked counters and add unique indexes for business IDs.

- File: `app/pointofsale_new.php` (lines 159-313)
- Issue: Critical billing writes span many tables without `BEGIN/COMMIT/ROLLBACK`. Partial failure can leave completed bills without stock updates, customer ledgers without matching payment rows, or cash-book entries without finalized bills.
- Risk: High
- Fix recommendation: Wrap POS checkout in a single DB transaction, fail fast on any statement error, and reconcile side effects only after commit.

- File: `app/pointofsale_new.php` (lines 218-245)
- Issue: Multiple SQL statements are malformed because commas are missing between `'$SYS_TIME_NOW'` and `'$sys_bill_id'` / `'$CustomerID'`. These inserts can fail silently while the bill still completes.
- Risk: High
- Fix recommendation: Fix the broken SQL, check every `mysqli_query` result, and abort checkout when financial logging fails.

- File: `app/module/ajax_stock_clear.php` (lines 8-12), `app/module/ajax_stock_update.php` (lines 8-20)
- Issue: These endpoints can zero-out or overwrite stock counts directly from POST data, with no login check, no privilege check, no CSRF protection, and no transaction/audit guard.
- Risk: High
- Fix recommendation: Remove public reachability immediately or gate behind authenticated admin-only middleware, CSRF validation, server-side authorization, and immutable audit logging.

## Security Issues
- File: `app/inc/connection.php` (line 4), `login/inc/connection.php` (line 5)
- Issue: Production database credentials are hardcoded in web-served PHP files.
- Risk: High
- Fix recommendation: Move secrets to environment configuration outside the web root, rotate the exposed passwords, and audit DB access.

- File: `login/index.php` (lines 55-60), `app/settings/sys_settings_changemydetails.php` (lines 84-85), `app/settings/sys_addsystemuser.php` (line 61), `app/settings/c_manageuser_operation.php` (line 9)
- Issue: Passwords use unsalted SHA-1 and new / reset users are assigned the fixed default password `pass123`.
- Risk: High
- Fix recommendation: Migrate to `password_hash()` / `password_verify()`, force reset on first login, and eliminate shared default credentials.

- File: `login/index.php` (lines 44-60), `app/module/ajax_item_load.php` (lines 6-14), `app/module/ajax_stock_clear.php` (lines 8-12), `app/module/ajax_stock_update.php` (lines 8-20), `app/module/api/product_catalog/getSalesFromGivenRange.php` (lines 13-18), `app/module/api/product_catalog/getRepairCenterParts.php` (lines 13-25), plus most `app/c_*.php`, `app/reports/*.php`, and settings pages
- Issue: Request data is interpolated directly into SQL across the codebase. This is systemic SQL injection exposure, not an isolated bug.
- Risk: High
- Fix recommendation: Convert all request-driven queries to prepared statements and add input validation per field type.

- File: `login/index.php` (line 115), repository-wide forms (`rg` found 187 `<form` occurrences), repository-wide search for `csrf` returned no matches
- Issue: Forms and mutation endpoints do not implement CSRF tokens.
- Risk: High
- Fix recommendation: Add CSRF protection to all state-changing requests, especially login, user management, stock, cashier, billing, and settings operations.

- File: `app/module/index.php` (lines 1-36), `app/module/stock_manage.php` (lines 1-4), `app/module/api/product_catalog/getAllProduct.php` (lines 1-32), `app/module/api/product_catalog/getProductName.php` (lines 1-32), `app/module/api/product_catalog/getSalesFromGivenRange.php` (lines 1-18), `app/module/api/product_catalog/getRepairCenterParts.php` (lines 1-25)
- Issue: The new module and its APIs start sessions but do not enforce authenticated user presence or privilege checks. Sensitive product and sales data can be requested directly.
- Risk: High
- Fix recommendation: Add centralized auth and authorization checks to every module page and API route before any query runs.

- File: `app/settings/c_manageuser_operation.php` (lines 6-39)
- Issue: Admin user reset / enable / disable actions are executed from raw POST values without verifying the caller is authenticated or privileged.
- Risk: High
- Fix recommendation: Require authenticated admin context, validate target IDs server-side, log the actor, and reject requests lacking CSRF tokens.

- File: `app/module/ajax_file_upload.php` (lines 2-35), `app/module/ajax_file_process.php` (lines 7-8)
- Issue: Uploaded CSV files are stored in a web-accessible directory, retained indefinitely, and then reopened from a user-supplied filename. This enables arbitrary file reads within the folder namespace and leaks imported stock data.
- Risk: High
- Fix recommendation: Store uploads outside the web root, whitelist server-generated IDs instead of filenames, validate MIME/content, and delete processed files.

- File: `app/module/uploaded_doc/*.csv`, `app/error_log`, `app/c_path/error_log`, `app/reports/error_log`, `app/settings/error_log`, `app/logdata.log`
- Issue: Operational logs and uploaded business data are present inside the application tree and may be directly downloadable depending on server config.
- Risk: High
- Fix recommendation: Move logs and uploads outside public directories, deny direct HTTP access, and scrub historical artifacts from deployment packages.

- File: `login/index.php` (lines 10-29), `router/loading.php` (lines 8-19), dozens of app pages beginning with identical `getUserIP()` helpers
- Issue: Audit and security decisions trust `HTTP_X_FORWARDED_FOR` directly. Attackers can spoof the recorded client IP.
- Risk: Medium
- Fix recommendation: Only trust proxy headers from known reverse proxies, otherwise use `REMOTE_ADDR`.

- File: repository-wide session handling (`rg` found 279 `session_start();` calls; no matches for `session_set_cookie_params`, `httponly`, or `SameSite`)
- Issue: Session cookie hardening is absent. Regeneration happens at login, but cookie flags and server-side session controls are not enforced consistently.
- Risk: Medium
- Fix recommendation: Set `HttpOnly`, `Secure`, `SameSite`, strict session mode, and explicit inactivity / rotation policies in one bootstrap file.

## Performance Issues
- File: `router/loading.php` (lines 53-75)
- Issue: Login hydration performs 77 privilege queries plus 48 report privilege queries, one query per permission key.
- Risk: Medium
- Fix recommendation: Load privilege maps in one query and hydrate the session from the result set.

- File: `app/module/api/product_catalog/getThisMonthTopSellingCategory.php` (lines 10-21), `app/module/api/product_catalog/getLastMonthTopSellingCategory.php` (lines 10-20)
- Issue: Each category triggers a full `SELECT *` against `shop_pos_mainsale`, producing an N+1 reporting pattern.
- Risk: Medium
- Fix recommendation: Replace per-category loops with grouped aggregate queries using `COUNT(*)` / `SUM(qty)` and proper date predicates.

- File: `app/module/api/product_catalog/getRepairCenterParts.php` (lines 22-30)
- Issue: Item names are resolved with a query inside the result loop, creating another N+1 pattern.
- Risk: Medium
- Fix recommendation: Join `repair_center_jobs_parts_add` to `prod_items` in SQL.

- File: `app/module/api/product_catalog/getAllProduct.php` (lines 12-32), `app/module/api/product_catalog/getProductName.php` (lines 12-32)
- Issue: APIs labeled for DataTables return whole tables with no pagination, filtering, or LIMIT usage even when `serverSide: true` is enabled on the client.
- Risk: Medium
- Fix recommendation: Implement proper paginated server-side responses and indexes for item search/sort columns.

- File: `app/reports/*.php`, `app/module/api/product_catalog/*.php`
- Issue: Most reporting queries use `SELECT *` and then aggregate in PHP. This inflates memory use and DB/network overhead.
- Risk: Medium
- Fix recommendation: Push aggregation, grouping, and filtering into SQL and only fetch required columns.

- File: asset trees under `app/js`, `app/plugins`, `app/module/plugins`, `app/module/dist`, `login/js`
- Issue: jQuery, Bootstrap, and AdminLTE are duplicated in multiple folders, increasing page weight and maintenance drift.
- Risk: Medium
- Fix recommendation: Consolidate shared frontend assets during migration and remove dead / duplicate vendor trees.

- File: `router/error_log`, `app/c_path/error_log`
- Issue: Historical `Too many connections` warnings show the current architecture already hits MySQL connection pressure.
- Risk: Medium
- Fix recommendation: Centralize bootstrap/connection lifecycle, reduce page-chaining, and review connection pooling / persistent usage strategy.

## Code Duplication
- File: repository-wide PHP pages
- Issue: Authentication guards, `getUserIP()` helpers, timezone setup, and monthly log-table selection are copied into a very large number of files.
- Risk: Medium
- Fix recommendation: Extract a shared bootstrap for auth, session, timezone, logging, and DB access.

- File: `app/pointofsale.php` vs `app/pointofsale_new.php`, `app/transfer_product.php` vs `app/transfer_product_new.php`, `app/old/*` vs current `app/components/*` / `app/scripts/*`
- Issue: Legacy and replacement flows coexist with divergent behavior, including critical business logic drift.
- Risk: High
- Fix recommendation: Define one authoritative implementation per workflow and remove stale forks after parity testing.

- File: `app/module/ajax_item_load.php` (lines 10-14 repeated at 13-14), many report/controller files
- Issue: Query strings and result-mapping logic are manually repeated instead of reused.
- Risk: Medium
- Fix recommendation: Introduce reusable query/service layers during backend refactoring.

- File: UI asset trees
- Issue: The same libraries exist under `app/js`, `app/plugins`, `app/module/plugins`, and `login/js`.
- Risk: Low
- Fix recommendation: Standardize asset loading and remove redundant copies during the Tailwind/Vue migration.

## SEO / AEO Issues
- File: `login/index.php` (lines 95-115), `app/module/index.php` (lines 4-15), `app/module/stock_manage.php` (lines 8-21)
- Issue: Pages generally have titles and viewport tags, but no meta descriptions, no structured data, minimal semantic landmarks, and weak page-specific metadata.
- Risk: Low
- Fix recommendation: If any of these pages are public-facing, add metadata, semantic headings, and machine-readable descriptions during the frontend rewrite.

- File: `app/module/index.php` (lines 45-50), multiple module/report pages with placeholder headings and labels
- Issue: Placeholder text such as `Blank Page`, empty breadcrumbs, and generic labels reduce semantic clarity for search and AI indexing.
- Risk: Low
- Fix recommendation: Replace placeholders with domain-specific headings, labels, and descriptions.

- File: repository-wide
- Issue: No JSON-LD / schema.org usage was found for business, product, invoice, or breadcrumb semantics.
- Risk: Low
- Fix recommendation: Add structured data only to pages that are intentionally public-facing; internal POS screens do not need SEO work.

- File: repository-wide internal POS/reporting pages
- Issue: Most of the system is an authenticated back-office application, so SEO/AEO value is limited and should not be prioritized over security and correctness.
- Risk: Low
- Fix recommendation: Treat SEO/AEO as optional for login/help/public pages only; do not spend migration effort here before fixing core risks.

## Maintainability Issues
- File: repository-wide
- Issue: The codebase is page-oriented PHP with mixed HTML, SQL, business logic, and JavaScript in single files. There is no MVC or equivalent boundary.
- Risk: High
- Fix recommendation: Define service boundaries first during refactor: auth/session, POS checkout, stock, billing, reporting, customer ledger, supplier ledger.

- File: `login/index.php`, `router/loading.php`, `app/home.php`, most `app/*.php`
- Issue: Session bootstrapping, permission loading, page rendering, and side effects are tightly coupled to direct includes and redirects.
- Risk: High
- Fix recommendation: Introduce a shared application bootstrap and explicit controllers/endpoints before layering Vue on top.

- File: `app/module/index.php` and `app/module/*`
- Issue: The "new module" is partially modernized visually but still bypasses centralized auth, routing, and backend conventions, creating a second architecture inside the same repo.
- Risk: High
- Fix recommendation: Decide whether `app/module` becomes the migration seed or is folded back into one backend architecture.

- File: `app/module/api/product_catalog/getProductName.php` (lines 12-24)
- Issue: The query only selects `item_name`, but the code reads `item_id`, `item_cat`, `used_type`, `eff_date`, and `is_active`. This will emit undefined index warnings and unstable JSON.
- Risk: Medium
- Fix recommendation: Align selected columns with response mapping and add error reporting in non-production environments.

- File: `app/module/new_item_manager.php` (lines 98-107)
- Issue: The DataTables AJAX URL points to `api/product/getAllProduct.php`, but the actual file lives under `api/product_catalog/getAllProduct.php`.
- Risk: Medium
- Fix recommendation: Correct the endpoint path and add integration tests for every module page route.

- File: `app/module/api/product_catalog/getThisMonthTopSellingCategory.php` (lines 32-34), `app/module/api/product_catalog/getLastMonthTopSellingCategory.php` (lines 30-31)
- Issue: Both endpoints assume at least 10 categories exist after sorting and will hit undefined offsets on smaller datasets.
- Risk: Medium
- Fix recommendation: Clamp iteration to `min(10, count($catalogArrya))`.

- File: `app/module/ajax_item_load.php` (lines 10-14)
- Issue: `queryF1` and `queryF2` duplicate `queryM1` and `queryM2` but are unused, indicating dead code and unclear intent.
- Risk: Low
- Fix recommendation: Remove dead branches as part of backend cleanup after behavior is preserved elsewhere.

## Migration Risks (Vue + Tailwind)
- File: `app/pointofsale.php`, `app/pointofsale_new.php`, many `app/c_pos_*.php`
- Issue: POS behavior is split across server-rendered pages, temp tables, popup windows, printer state in session, and many auxiliary PHP endpoints. The UI is tightly coupled to current DOM and page flow.
- Risk: High
- Fix recommendation: Migrate POS only after extracting a stable backend checkout API with transaction-safe stock, billing, payment, and cashier-slot operations.

- File: `app/home.php` (iframe-based shell), `app/inc/side_func_menu.php`, many standalone pages
- Issue: Navigation relies on direct file links, iframes, popup windows, and session variables rather than route/state abstractions.
- Risk: High
- Fix recommendation: Inventory screens by business domain and define target Vue routes/components before touching styling.

- File: `router/loading.php` (lines 45-103)
- Issue: Login bootstraps the entire runtime by mutating dozens of session keys that pages later read implicitly.
- Risk: High
- Fix recommendation: Replace implicit session contracts with typed backend responses and explicit permission models.

- File: `app/module/*` and legacy `app/*`
- Issue: There are already two UI stacks with different assumptions, folder structures, and asset pipelines. Migrating incrementally without a compatibility layer will compound drift.
- Risk: High
- Fix recommendation: Choose a single migration spine, likely API-first PHP backend plus Vue frontend, and freeze new feature work in duplicated screens.

- File: printing and hardware integration surfaces (`app/pointofsale*.php`, `app/print*.php`, `app/JSPrintManager.js`, `app/src/Mike42/Escpos/*`)
- Issue: Printer and receipt flows are deeply coupled to session state and page-side behavior. This is likely the hardest area to port cleanly to Vue.
- Risk: High
- Fix recommendation: Isolate printer orchestration behind a backend/domain service contract before any frontend rewrite.

- File: `app/module/ajax_stock_clear.php`, `app/module/ajax_stock_update.php`
- Issue: The newer module already exposes dangerous direct-mutation endpoints. Reusing these endpoints from Vue would carry forward insecure architecture.
- Risk: High
- Fix recommendation: Do not build Vue screens on top of these endpoints; replace them with authenticated, validated service APIs first.

## Summary Score
Security: 2/10  
Performance: 4/10  
Maintainability: 2/10  
Scalability: 3/10  

