# Old-Code to New-Code Migration Roadmap

## Goal
Convert `old-code` from procedural PHP pages into the `new-code` MVC application in controlled steps, preserving business behavior while replacing direct page scripts with controllers, models, views, and service-style backend logic.

## Current Status
- Completed:
  - MVC bootstrap cleanup
  - Routing and middleware wiring
  - Login migration
  - Auth/session migration
  - Dashboard migration
- Source already migrated:
  - `old-code/login/index.php`
  - `old-code/app/home.php`
  - `old-code/app/dashboard.php`

## Migration Rules
1. Do not copy legacy pages directly into `new-code`.
2. Extract each legacy workflow into:
   - controller
   - model
   - view
   - helper/service logic where needed
3. Replace raw SQL interpolation with prepared statements.
4. Add CSRF protection to all POST actions.
5. Keep legacy table contracts first; optimize schema usage after parity is reached.
6. Migrate business domains, not isolated files, because most flows depend on multiple `c_*.php` endpoints.

## Target Structure
- `new-code/controller`
  - page controllers
  - action controllers
- `new-code/Models`
  - table access
  - query methods
- `new-code/views`
  - route views
  - partials
- `new-code/middleware`
  - auth
  - permission gates
- `new-code/docs`
  - migration notes
  - domain maps

## Step 1: Auth and Dashboard
- Status: complete
- Legacy sources:
  - `login/index.php`
  - `app/home.php`
  - `app/dashboard.php`
  - `app/inc/top_func_banner.php`
  - `app/inc/side_func_menu.php`
- New MVC targets:
  - `AuthController`
  - `DashboardController`
  - `User`, `Permission`, `Shop`, `Dashboard` models
  - auth login/dashboard views

## Step 2: Settings and Permission System
- Status: complete
- Why next:
  - permission checks drive almost every other module
  - we need centralized authorization before exposing more routes
- Legacy sources:
  - `app/sys_settings.php`
  - `app/settings/sys_manageuser.php`
  - `app/settings/sys_addsystemuser.php`
  - `app/settings/sys_settings_changemydetails.php`
  - `app/settings/c_funcmapping.php`
  - `app/settings/c_mngpermission.php`
  - `app/settings/c_rpt_mngpermission.php`
  - `app/settings/c_manageuser_operation.php`
- New MVC targets:
  - `SettingsController`
  - `UserManagementController`
  - `PermissionController`
  - `UserProfileController`
  - `User`, `PermissionMap`, `Privilege`, `Shop` models
  - settings views
- Deliverables:
  - user list
  - create user
  - enable/disable/reset user actions
  - assign privilege profile
  - manage function/report permissions

## Step 3: Master Data
- Status: complete
- Legacy sources:
  - `manage_category.php`
  - `manage_item_a.php`
  - `manage_item_e.php`
  - `manage_item_color.php`
  - `manage_operator.php`
  - `add_new_shop.php`
  - `shop_list.php`
  - `shop_list_edit.php`
  - `add_supplier.php`
  - `supplier_list.php`
  - `add_new_customer.php`
  - `manage_customer.php`
  - `edit_customer.php`
  - supporting `c_man_*`, `c_mancus_*`, `c_cust_upd_details.php`, `c_supp_*`
- New MVC targets:
  - `CatalogController`
  - `CategoryController`
  - `CustomerController`
  - `SupplierController`
  - `ShopController`
  - `OperatorController`
- Deliverables:
  - CRUD screens
  - search/filter
  - safe write actions

## Step 4: Cashier and Opening/Closing
- Status: complete
- Legacy sources:
  - `cashier_onoff.php`
  - `shop_openbalance.php`
  - `shop_close.php`
  - `add_expense.php`
  - `cashin_account.php`
  - `expence_account.php`
  - `c_cashier.php`
  - `c_expence_operation.php`
  - `c_income_operation.php`
- New MVC targets:
  - `CashierController`
  - `ExpenseController`
  - `CashInController`
  - `Cashbook` and account models
- Deliverables:
  - cashier duty on/off
  - open balance
  - expense/cash-in logs
  - cashier slot checks

## Step 5: POS Core
- Status: complete
- Why isolated:
  - highest risk domain
  - many temp tables and side effects
- Legacy sources:
  - `pointofsale.php`
  - `pointofsale_new.php`
  - `c_pos_*`
  - `c_bilcancel.php`
  - `dailybills.php`
  - `search_bill.php`
  - `reprintbill.php`
  - `printmybill.php`
  - `dt_print_invoice.php`
- New MVC targets:
  - `PosController`
  - `BillingController`
  - `ReceiptController`
  - `TempPos`, `Bill`, `Sale`, `CashBook`, `CustomerLedger` models
- Deliverables:
  - bill slot lifecycle
  - item scan/add/update/delete
  - customer attach
  - checkout transaction
  - bill search/reprint/cancel

## Step 6: Customer Finance
- Status: complete
- Legacy sources:
  - `customer_accounts.php`
  - `customer_chashcredit_list.php`
  - `customer_payment.php`
  - `customer_bill_payment.php`
  - `c_customer_bill_payment.php`
  - `c_customer_cashcredit_*`
  - `c_cus_accounts.php`
  - `c_upd_cus_accounts.php`
- New MVC targets:
  - `CustomerAccountController`
  - `CustomerPaymentController`
  - ledger models
- Deliverables:
  - balances
  - payments
  - due settlement
  - ledger history

## Step 7: Supplier Finance and GRN
- Status: complete
- Legacy sources:
  - `grn_new.php`
  - `grn_add.php`
  - `grn_add_new.php`
  - `findgrn.php`
  - `supplier_accounts.php`
  - `supplier_chashcredit_list.php`
  - `supplier_payment.php`
  - `supplier_grn_payment.php`
  - all `c_grn_*`
  - `c_supplier_grn_payment.php`
  - `c_upd_supp_*`
- New MVC targets:
  - `GrnController`
  - `SupplierAccountController`
  - `SupplierPaymentController`
  - temp GRN and supplier models
- Deliverables:
  - supplier attach
  - GRN temp flow
  - GRN finalize
  - supplier balances/payments

## Step 8: Stock Operations
- Status: complete
- Legacy sources:
  - `transfer_product.php`
  - `transfer_product_new.php`
  - `transfer_note.php`
  - `transfer_received.php`
  - `transfer_error_correct.php`
  - `return_stock.php`
  - `stock_adjust.php`
  - `stock_remove.php`
  - `item_alert_config.php`
  - all `c_trans_*`, `c_stock_*`, `c_itm_alert*`, `c_valied_check_imei.php`
- New MVC targets:
  - `StockTransferController`
  - `StockAdjustmentController`
  - `StockRemovalController`
  - `StockAlertController`
- Deliverables:
  - transfer out/in
  - complaints/error correction
  - stock adjust/remove
  - item alerts

## Step 9: Repair Center
- Status: complete
- Legacy sources:
  - `rep_new_job.php`
  - `rep_job_process.php`
  - `rep_job_release.php`
  - `rep_job_handover.php`
  - `rep_job_log.php`
  - `rep_job_reprint.php`
  - `rep_job_billreprint.php`
  - `find_rep_job.php`
  - `manage_customer_belongs.php`
  - `manage_common_fault.php`
  - all `c_rep_*`, `c_sch_rep_*`
- New MVC targets:
  - `RepairJobController`
  - `RepairProcessController`
  - `RepairReleaseController`
  - `RepairLookupController`
- Deliverables:
  - create job
  - process workflow
  - billing and handover
  - receipt reprint
  - customer belong/fault management

## Step 10: Reporting
- Status: **COMPLETE** ✔ (all 6 phases: Sales, Cashier, Products, Entity Ledgers, GRN & Logs, Repair & Transfer)
- Legacy sources:
  - `sys_reports.php`
  - all `reports/rpt_*.php`
  - all `reports/c_*.php`
- New MVC targets:
  - `ReportController`
  - report-specific query classes/models
- Deliverables:
  - filter screens
  - server-side report queries
  - export-ready pages

## Step 11: Notifications, Tasks, SMS
- Status: pending
- Legacy sources:
  - `sys_notification.php`
  - `sms_broadcast.php`
  - `c_sms_broadcast.php`
  - `c_ngin_run_notifi_1.php`
  - `c_task_mng.php`
  - `c_path/*`
  - `task/task_notification.php`
- New MVC targets:
  - `NotificationController`
  - `SmsController`
  - `TaskController`
- Deliverables:
  - inbox
  - unread count
  - message compose
  - background task endpoints

## Step 12: Printing and Document Output
- Status: complete
- Legacy sources:
  - `print*.php`
  - `barcodeprint*.php`
  - `repair_job_lbl.php`
  - `grncodeprint.php`
  - `DownloadTransferNote.php`
  - JSPrintManager integration files
- New MVC targets:
  - `PrintController`
  - print view templates
  - printer service wrapper
- Deliverables:
  - invoice print
  - payment print
  - barcode print
  - transfer note print

## Step 13: New Module Cleanup
- Status: pending
- Legacy sources:
  - `app/module/index.php`
  - `app/module/stock_manage.php`
  - `app/module/new_item_manager.php`
  - `app/module/ajax_*`
  - `app/module/api/product_catalog/*`
- New MVC targets:
  - fold into the same controllers/models as Steps 3, 8, and 10
- Deliverables:
  - remove duplicate architecture
  - delete dangerous unauthenticated AJAX patterns

## Conversion Sequence
1. Settings and permission system
2. Master data
3. Cashier
4. POS core
5. Customer finance
6. Supplier finance and GRN
7. Stock operations
8. Repair center
9. Reporting
10. Notifications and SMS
11. Printing
12. Module cleanup

## Route and Controller Naming Convention
- Page list: `index`
- View form/page: `show`
- Store action: `store`
- Update action: `update`
- Delete action: `destroy`
- Search/filter action: `search`
- Print action: `print`
- Export action: `export`

## Full Conversion Execution Style
For each step:
1. Identify legacy entry pages and AJAX/action pages.
2. Build models for the involved tables.
3. Create controller endpoints and migrate backend logic first.
4. Create views that match legacy behavior closely enough for parity.
5. Add middleware/permission checks.
6. Replace insecure raw SQL with prepared statements.
7. Run syntax checks.
8. Move to next domain only after the previous slice is stable.

## Immediate Next Task
Start Step 11 (Notifications, Tasks, SMS):
- Create `NotificationController`, `SmsController`, `TaskController`
- Build `Notification`, `Task` models
- Implement inbox, unread count, compose, and background task endpoints

See `docs/migration-audit.md` for the full validated status of all 13 steps.
