# Progress Log - 2026-04-28

## Project State
Migration work was performed inside `new-code` as an MVC rewrite target for `old-code`.

There is no Git repository initialized at `M:\Project\Nextgen Easy POS`, so progress was saved by updating project files and writing this checkpoint log.

## Completed So Far

### 1. MVC Foundation
- Fixed bootstrap and route wiring
- Added request, middleware, and view loading cleanup
- Added CSRF helpers and auth helpers
- Added permission middleware support

Key files:
- `new-code/bootstrap/app.php`
- `new-code/core/Router.php`
- `new-code/core/View.php`
- `new-code/core/Request.php`
- `new-code/public/index.php`

### 2. Legacy Login and Dashboard Migration
Migrated from:
- `old-code/login/index.php`
- `old-code/app/home.php`
- `old-code/app/dashboard.php`

Implemented in:
- `new-code/controller/AuthController.php`
- `new-code/controller/DashboardController.php`
- `new-code/Models/User.php`
- `new-code/Models/Permission.php`
- `new-code/Models/Shop.php`
- `new-code/Models/Dashboard.php`
- `new-code/views/auth/login.php`
- `new-code/views/dashboard/index.php`

### 3. Step 2 Settings and Permission System
Migrated from legacy settings pages and endpoints:
- `sys_settings.php`
- `sys_manageuser.php`
- `sys_addsystemuser.php`
- `sys_settings_changemydetails.php`
- `c_manageuser_operation.php`
- `c_funcmapping.php`
- `c_mngpermission.php`
- `c_rpt_mngpermission.php`

Implemented in:
- `new-code/controller/SettingsController.php`
- `new-code/controller/UserManagementController.php`
- `new-code/controller/UserProfileController.php`
- `new-code/controller/PrivilegeController.php`
- `new-code/controller/UserPrivilegeController.php`
- `new-code/middleware/PermissionMiddleware.php`
- `new-code/Models/Privilege.php`
- `new-code/Models/PermissionCatalog.php`
- `new-code/views/settings/*`

Step 2 status:
- complete for current migration scope

### 4. Step 3 Master Data Started

#### Shops
Migrated from:
- `old-code/app/add_new_shop.php`
- `old-code/app/shop_list.php`
- `old-code/app/shop_list_edit.php`

Implemented in:
- `new-code/controller/ShopController.php`
- `new-code/views/settings/shops/create.php`
- `new-code/views/settings/shops/index.php`
- `new-code/views/settings/shops/edit.php`
- `new-code/Models/Shop.php`

#### Categories
Migrated from:
- `old-code/app/manage_category.php`
- `old-code/app/c_man_cat_show.php`
- `old-code/app/c_man_cat_updt.php`
- `old-code/app/c_man_cat_delc.php`

Implemented in:
- `new-code/controller/ProductCategoryController.php`
- `new-code/Models/ProductCategory.php`
- `new-code/views/catalog/categories/index.php`

#### Suppliers
Migrated from:
- `old-code/app/add_supplier.php`
- `old-code/app/supplier_list.php`
- `old-code/app/c_supp_shw.php`
- `old-code/app/c_upd_supp_this.php`

Implemented in:
- `new-code/controller/SupplierController.php`
- `new-code/Models/Supplier.php`
- `new-code/views/catalog/suppliers/create.php`
- `new-code/views/catalog/suppliers/index.php`
- `new-code/views/catalog/suppliers/edit.php`

#### Customers
Migrated from:
- `old-code/app/add_new_customer.php`
- `old-code/app/manage_customer.php`
- `old-code/app/edit_customer.php`
- `old-code/app/c_mancus_delete.php`
- `old-code/app/c_mancus_recover.php`

Implemented in:
- `new-code/controller/CustomerController.php`
- `new-code/Models/Customer.php`
- `new-code/views/catalog/customers/create.php`
- `new-code/views/catalog/customers/index.php`
- `new-code/views/catalog/customers/edit.php`
- `new-code/views/catalog/_nav.php`

#### Operators
Migrated from:
- `old-code/app/manage_operator.php`
- `old-code/app/c_man_rcv_ope_show.php`
- `old-code/app/c_man_rcv_ope_updt.php`

Implemented in:
- `new-code/controller/RechargeOperatorController.php`
- `new-code/Models/RechargeOperator.php`
- `new-code/views/catalog/operators/index.php`

#### Items
Migrated from:
- `old-code/app/manage_item_a.php`
- `old-code/app/manage_item_e.php`
- `old-code/app/c_manitem_e_getdata.php`

Implemented in:
- `new-code/controller/ItemController.php`
- `new-code/Models/Item.php`
- `new-code/Models/RechargeCard.php`
- `new-code/views/catalog/items/create.php`
- `new-code/views/catalog/items/index.php`
- `new-code/views/catalog/items/edit.php`

#### Account Balance Views
Migrated from:
- `old-code/app/supplier_accounts.php`
- `old-code/app/customer_accounts.php`
- `old-code/app/c_supp_accounts.php`
- `old-code/app/c_cus_accounts.php`

Implemented in:
- `new-code/controller/AccountBalanceController.php`
- `new-code/views/catalog/accounts/suppliers.php`
- `new-code/views/catalog/accounts/customers.php`
- `new-code/Models/Supplier.php`
- `new-code/Models/Customer.php`

#### POS Helper Replacements
Migrated from:
- `old-code/app/c_pos_chk_customer.php`
- `old-code/app/search_item.php`
- `old-code/app/c_itm_serch_func.php`
- `old-code/app/c_pos_chg_item.php`
- `old-code/app/c_pos_chg_itemcode.php`

Implemented in:
- `new-code/controller/PosHelperController.php`
- `new-code/controller/StockSearchController.php`
- `new-code/views/catalog/items/search.php`
- `new-code/Models/Customer.php`
- `new-code/Models/Item.php`
- `new-code/Models/ProductCategory.php`

#### Payment Entry
Migrated from:
- `old-code/app/supplier_payment.php`
- `old-code/app/customer_payment.php`
- `old-code/app/c_supp_upd_details.php`
- `old-code/app/c_cust_upd_details.php`

Implemented in:
- `new-code/controller/PaymentController.php`
- `new-code/Models/Finance.php`
- `new-code/views/finance/suppliers/form.php`
- `new-code/views/finance/customers/form.php`

#### Cash Credit Balance Pages
Migrated from:
- `old-code/app/supplier_chashcredit_list.php`
- `old-code/app/customer_chashcredit_list.php`
- `old-code/app/c_supp_cashcredit_list.php`
- `old-code/app/c_customer_cashcredit_list.php`
- `old-code/app/c_supp_cashcredit_upd.php`
- `old-code/app/c_customer_cashcredit_upd.php`

Implemented in:
- `new-code/controller/AccountBalanceController.php`
- `new-code/Models/Finance.php`
- `new-code/Models/Supplier.php`
- `new-code/Models/Customer.php`
- `new-code/views/finance/credits/suppliers.php`
- `new-code/views/finance/credits/customers.php`

#### Finance Payment Reports
Migrated from:
- `old-code/app/reports/rpt_supply_payment.php`
- `old-code/app/reports/rpt_customer_payment.php`
- `old-code/app/reports/c_supply.php`
- `old-code/app/reports/c_customer.php`

Implemented in:
- `new-code/controller/FinanceReportController.php`
- `new-code/Models/Finance.php`
- `new-code/Models/Company.php`
- `new-code/views/reports/finance/supplier_payments.php`
- `new-code/views/reports/finance/customer_payments.php`

#### Stock Configuration
Migrated from:
- `old-code/app/manage_item_color.php`
- `old-code/app/c_man_col_show.php`
- `old-code/app/item_alert_config.php`
- `old-code/app/c_itm_alert.php`
- `old-code/app/c_itm_alert_view.php`

Implemented in:
- `new-code/controller/ItemColorController.php`
- `new-code/controller/StockAlertController.php`
- `new-code/Models/ItemColor.php`
- `new-code/Models/StockAlert.php`
- `new-code/views/catalog/colors/index.php`
- `new-code/views/catalog/alerts/index.php`

#### Cashier Duty
Migrated from:
- `old-code/app/cashier_onoff.php`
- `old-code/app/c_cashier.php`

Implemented in:
- `new-code/controller/CashierController.php`
- `new-code/Models/Cashier.php`
- `new-code/middleware/CashierActiveMiddleware.php`
- `new-code/views/cashier/index.php`

Integrated with:
- payment entry routes now require active cashier duty
- dashboard cashier tile
- catalog navigation cashier link

#### POS Workspace
Migrated from:
- `old-code/app/pointofsale.php`
- `old-code/app/pointofsale_new.php`

Implemented in:
- `new-code/controller/PosController.php`
- `new-code/views/pos/index.php`

Current scope:
- customer lookup and selection
- item lookup by name/code
- cart staging in session
- line update/remove
- payment draft totals
- transaction-safe checkout
- stock mutation
- bill header + line writes
- customer ledger writes
- cash-book writes
- receipt confirmation page
- printable receipt page
- barcode label selection page
- printable barcode label page

Deferred intentionally:
- direct JSPrintManager / named-printer automation
- temp slot compatibility features

## Current Route Coverage

### Auth / Dashboard
- `/`
- `/login`
- `/logout`
- `/dashboard`

### Settings
- `/settings`
- `/settings/users`
- `/settings/users/create`
- `/settings/profile`
- `/settings/privileges`
- `/settings/privileges/{id}/functions`
- `/settings/privileges/{id}/reports`
- `/settings/user-privileges`
- `/settings/shops`
- `/settings/shops/create`
- `/settings/shops/{id}/edit`

### Catalog
- `/operators`
- `/api/pos/customers`
- `/api/pos/items/by-name`
- `/api/pos/items/by-code`
- `/api/items/by-category`
- `/items`
- `/items/create`
- `/item-colors`
- `/item-alerts`
- `/items/{id}/edit`
- `/items/search`
- `/categories`
- `/suppliers`
- `/suppliers/create`
- `/suppliers/{id}/edit`
- `/supplier-accounts`
- `/supplier-credit-balances`
- `/supplier-payments`
- `/reports/supplier-payments`
- `/api/supplier-payments/details`
- `/customers`
- `/customers/create`
- `/customers/{id}/edit`
- `/customer-accounts`
- `/customer-credit-balances`
- `/customer-payments`
- `/reports/customer-payments`
- `/api/customer-payments/details`

## Validation Completed
- Ran `php -l` over all PHP files in `new-code`
- Result: no syntax errors detected at end of session

## Next Recommended Task
Continue Step 3 with remaining master data domains in this order:
1. POS printing and legacy slot compatibility
2. Broader reports module conversion
3. GRN/purchase flow migration
4. cashier transfer / advanced drawer flows

Recommended legacy files to inspect next:
- `old-code/app/pointofsale_new.php`
- `old-code/app/printmybill.php`
- `old-code/app/barcodeprint.php`
- `old-code/app/c_pos_temp_*.php`

## 2026-04-29 POS Print Slice
Migrated from:
- `old-code/app/printmybill.php`
- `old-code/app/barcodeprint.php`
- `old-code/app/barcode_warrenty_list.php`

Implemented in:
- `new-code/controller/PosController.php`
- `new-code/Models/PosSale.php`
- `new-code/public/barcode.php`
- `new-code/views/layout/print.php`
- `new-code/views/pos/receipt.php`
- `new-code/views/pos/receipt_print.php`
- `new-code/views/pos/barcodes.php`
- `new-code/views/pos/barcodes_print.php`

Route additions:
- `/pos/receipts/{billnumber}`
- `/pos/receipts/{billnumber}/print`
- `/pos/receipts/{billnumber}/barcodes`
- `/pos/receipts/{billnumber}/barcodes/print`

Scope completed:
- receipt reprint page from committed sale data
- browser print handoff for receipts
- barcode label quantity selection per sold line
- print-friendly barcode/warranty label output using a migrated barcode image endpoint
- receipt and barcode reprint routes no longer depend on active cashier duty, but remain permission-protected and shop-scoped

## 2026-04-29 POS Slot Compatibility Slice
Migrated from:
- `old-code/app/pointofsale_new.php`
- `old-code/app/c_pos_newbill.php`
- `old-code/app/c_pos_removebill.php`
- `old-code/app/c_pos_btn_infoview.php`
- `old-code/app/c_pos_temp_customerupd.php`
- `old-code/app/c_pos_temp_itemtbshow.php`
- `old-code/app/c_pos_temp_itemtbdelete.php`
- `old-code/app/c_pos_temp_editbeforeinfo.php`
- `old-code/app/c_pos_temp_editposupdatedb.php`

Implemented in:
- `new-code/controller/PosController.php`
- `new-code/views/pos/index.php`
- `new-code/public/index.php`

Scope completed:
- three POS bill slots in session (`1`, `2`, `3`)
- active slot switching without temp DB tables
- per-slot customer, staged lines, and payment draft state
- per-slot item count and total indicators in the sidebar
- clear and close operations for extra slots
- completed extra-slot checkout auto-closes the slot and returns the operator to the default slot

Intentional design difference from legacy:
- stock is not reserved or reverted during staging, edit, delete, or slot clear operations
- stock changes remain deferred until transaction-safe checkout only

## 2026-04-29 POS Deep Parity Slice
Migrated from:
- `old-code/app/c_small_function.php`
- seller-selection and keyboard workflow parts of `old-code/app/pointofsale_new.php`

Implemented in:
- `new-code/controller/PosController.php`
- `new-code/Models/User.php`
- `new-code/Models/PosSale.php`
- `new-code/views/pos/index.php`
- `new-code/public/index.php`

Scope completed:
- sale person lookup by legacy numeric user ID
- per-slot sale person staging in POS
- checkout now writes staged `seller_id` and `seller_name` instead of forcing cashier identity
- keyboard focus shortcuts for category (`F4`), code lookup (`F8`), and cash amount (`F2`)
- cashier-entered cash field can fast-submit checkout for registered-customer bills

## 2026-04-29 POS Modal Edit Slice
Migrated from:
- line-edit interaction parts of `old-code/app/pointofsale_new.php`
- legacy `c_pos_temp_editbeforeinfo.php` and `c_pos_temp_editposupdatedb.php` user flow

Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- replaced inline line-item editing inputs with a cashier-friendly in-page edit modal
- edit modal preloads current line values and respects IMEI single-quantity behavior
- click-to-edit and `Esc`/backdrop close behavior now match the old popup-style operator flow more closely
- delete remains immediate from the bill grid while edits happen in a focused dialog

## 2026-04-29 POS Bulk IMEI Slice
Migrated from:
- bulk IMEI entry parts of `old-code/app/pointofsale_new.php`
- `old-code/app/c_pos_blk_chkitem.php`
- `old-code/app/c_pos_temp_itemtblupdblk.php`
- `old-code/app/c_pos_tempbulkimei_delete.php`

Implemented in:
- `new-code/controller/PosController.php`
- `new-code/Models/Item.php`
- `new-code/views/pos/index.php`
- `new-code/public/index.php`

Scope completed:
- session-safe bulk IMEI add for IMEI-controlled items
- bulk modal accepts one-per-line IMEIs or a continuous 15-digit stream
- validates item/shop stock before staging
- prevents duplicate IMEIs across the current user's open POS slots
- adds matched IMEIs directly into the active bill without temp bulk tables

## 2026-04-29 POS Fast Entry Slice
Migrated from:
- item-entry speed and validation parts of `old-code/app/pointofsale_new.php`

Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- code lookup `Enter` now triggers direct lookup
- item-name `Enter` and change now trigger direct lookup
- successful lookups shift focus forward through the cashier flow
- add-to-bill now validates selected item, quantity, stock limit, sale price, and discount before submit
- IMEI items with quantity greater than `1` branch directly into bulk IMEI flow instead of failing late
- under-cost selling now prompts the operator before staging the line

## 2026-04-29 POS Inline Status Slice
Migrated from:
- cashier workflow polish expectations from `old-code/app/pointofsale_new.php`

Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- replaced the main client-side interruptive `alert()` path with inline POS status feedback
- item lookup, seller lookup, customer search, bulk IMEI open, and fast cash checkout now report status in-page
- customer search fields now submit on `Enter`
- payment method focus shortcut `F9` is now restored in the MVC POS screen
- under-cost warning and draft validation now stay inside the POS workspace instead of bouncing the cashier through modal browser prompts

## 2026-04-30 POS Shortcut Cleanup Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- seller ID lookup now submits on `Enter`
- card-number field can fast-stage payment details on `Enter`
- `F5` now points the cashier back toward current-bill reset instead of doing nothing

## 2026-04-30 POS Sidebar Cleanup Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- cleaned the bill-slot summary separator rendering in the POS sidebar
- normalized the slot summary display to stable ASCII separators
- removed the visible encoding artifact from the slot summary text

## 2026-04-30 POS Payment Flow Parity Slice
Implemented in:
- `new-code/controller/PosController.php`
- `new-code/views/pos/index.php`

Scope completed:
- checkout now consumes the current live payment draft instead of relying only on a previously staged payment snapshot
- restored live cashier-facing payment math for total, paid amount, and change or balance display
- added exact-cash fast fill for the current bill total
- added client-side finish-bill validation for cash, card, split, and cash-customer full-payment rules before checkout submit
- cash-amount Enter fast path now uses the live payment draft and can finish the bill directly when validation passes

## 2026-04-30 POS Customer Shortcut Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- restored direct POS-side customer quick actions for cash-customer reset and new-customer launch
- added permission-aware `Add New Customer` and `Manage Customers` links from the POS workspace
- added `F6` customer-search focus so the cashier can jump back into customer selection faster

## 2026-04-30 POS Customer Result Speed Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- customer search results now expose numbered fast-select labels for the first matches
- number keys `1-9` can now pick customer matches directly from the keyboard
- the first matched customer now receives focus automatically after search for quicker Enter-based selection
- customer-result handling now uses a shared POS-side selector path instead of duplicating submit logic

## 2026-04-30 POS Seller Shortcut Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- added `F7` seller focus shortcut in the POS workspace
- seller lookup now treats blank or `0` as a direct reset back to the current cashier
- seller preview now reflects current-cashier reset immediately before the slot update posts
- seller section now documents the fast reset path for cashier use

## 2026-05-03 POS Shortcut Scope Cleanup Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- restricted numeric customer fast-select keys so they only fire while the cashier is actively in customer-selection context
- removed the risk of customer shortcut digits hijacking quantity, price, or payment entry elsewhere in the POS screen

## 2026-05-03 POS Item Entry Focus Slice
Implemented in:
- `new-code/views/pos/index.php`

Scope completed:
- category change now pushes focus into item-name selection when category items are loaded
- successful item-name and code lookups now select the next cashier input field for faster overwrite and confirmation
- discount field `Enter` now follows the same fast add-to-bill path as sale price
- main-screen `Escape` now clears the current lookup draft and returns focus to code lookup without disturbing bill lines

## 2026-05-03 POS Bill History Slice
Implemented in:
- `new-code/Models/PosSale.php`
- `new-code/controller/PosController.php`
- `new-code/public/index.php`
- `new-code/views/pos/daily_bills.php`
- `new-code/views/pos/search_bills.php`
- `new-code/views/pos/index.php`
- `new-code/views/pos/receipt.php`
- `new-code/views/pos/barcodes.php`

Scope completed:
- migrated daily-bill history into `GET /pos/bills/today` using committed MVC bill data
- migrated bill search into `GET /pos/bills/search` with bill-data and item-data filters
- wired receipt, bill reprint, and barcode reprint links from the new history/search screens
- added POS navigation links for daily bills and find bill when the user has `p_31` or `p_32`

Deferred in this slice:
- legacy bill-cancel flow from `c_bilcancel.php`
- legacy bill-return activity links from `search_bill.php` and `dailybills.php`

## 2026-05-03 POS Bill Cancel Slice
Implemented in:
- `new-code/Models/PosSale.php`
- `new-code/controller/PosController.php`
- `new-code/public/index.php`
- `new-code/views/pos/daily_bills.php`

Scope completed:
- added transactional bill cancel handling at `POST /pos/bills/{billnumber}/cancel`
- scoped cancel actions to the current cashier's own daily bills, matching the legacy daily-bill removal boundary
- bill cancel now archives the bill into legacy cancel tables, restores stock, removes customer bill-payment ledger links, records reversing cash-book entries, and deletes the live bill rows
- daily bills now include a required cancel-reason form with confirmation before running the rollback

Runtime assumptions:
- legacy tables `cancel_bill_billdetails` and `cancel_bill_mainsale` exist with the expected column layout
- stock restoration still depends on the same legacy stock-row lookup patterns used by the old codebase

## 2026-05-03 POS Return Visibility Slice
Implemented in:
- `new-code/Models/BillReturn.php`
- `new-code/controller/BillReturnController.php`
- `new-code/public/index.php`
- `new-code/views/pos/return_history.php`
- `new-code/views/pos/return_pending.php`
- `new-code/views/pos/daily_bills.php`
- `new-code/views/pos/search_bills.php`
- `new-code/views/pos/receipt.php`

Scope completed:
- added bill return history view at `GET /pos/bills/{billnumber}/returns`
- added pending return-activity queue view at `GET /pos/returns/pending`
- surfaced legacy `alter_bill_*` and `alter_bill_information` records from MVC bill pages
- linked return history from daily bills, bill search, and receipt pages when the user has `p_33`
- linked pending return activity queue when the user has `p_34`

Deferred in this slice:
- write-side bill return creation from `bill_return.php`
- write-side return-activity processing from `return_cus_activity.php` and `c_bilretactivity_*`

## 2026-05-03 POS Return Request Slice
Migrated from:
- `old-code/app/bill_return.php`

Implemented in:
- `new-code/Models/BillReturn.php`
- `new-code/controller/BillReturnController.php`
- `new-code/public/index.php`
- `new-code/views/pos/return_create.php`
- `new-code/views/pos/daily_bills.php`
- `new-code/views/pos/search_bills.php`
- `new-code/views/pos/receipt.php`

Scope completed:
- added return-request entry form at `GET /pos/bills/{billnumber}/returns/create`
- added return-request submit flow at `POST /pos/bills/{billnumber}/returns`
- validates selected bill lines, return quantities, and IMEI single-quantity constraints before writing
- blocks duplicate pending return queues for the same bill
- writes staged return rows into legacy `alter_bill_mainsale`
- writes return event headers into legacy `alter_bill_billdata`
- updates the live bill `alter_bill` flag on successful request creation
- restores stock immediately for re-sell return lines using the same legacy stock-table families
- writes `stock_return_log` rows for both re-sell and discard return paths
- linked `Create Return` entry points from daily bills, bill search, and receipt pages when the user has `p_33`

Deferred in this slice:
- follow-up processing from `return_cus_activity.php`
- `c_bilretactivity_*` operational decisions after the return request is queued

## 2026-05-03 POS Return Activity Slice
Migrated from:
- `old-code/app/return_cus_activity.php`
- `old-code/app/c_bilretactivity_get_details.php`
- `old-code/app/c_bilretactivity_updateprocess.php`
- `old-code/app/c_bilretactivity_loadretitems.php`

Implemented in:
- `new-code/Models/BillReturn.php`
- `new-code/controller/BillReturnController.php`
- `new-code/public/index.php`
- `new-code/views/pos/return_activity.php`
- `new-code/views/pos/return_pending.php`
- `new-code/views/pos/return_history.php`

Scope completed:
- added pending return-activity processor page at `GET /pos/returns/pending/{billnumber}/{altertime}`
- added replacement/cash settlement action at `POST /pos/returns/items/{id}/settle`
- added customer-credit settlement action at `POST /pos/returns/items/{id}/credit`
- pending-return queue now opens directly into activity processing instead of only history
- replacement settlement supports code or item-name lookup against migrated MVC POS helper endpoints
- replacement settlements now decrement current shop stock, write `alter_bill_information`, update `alter_bill_mainsale`, and write `cash_book` movements for money returned or collected
- customer-credit settlements now create `account_cashcredit_customer` rows for registered customers and mark the return item accordingly
- pending return headers now auto-close by setting `activity_update = 1` once all pending items for that alter batch are processed
- corrected return-history status display so `activity = 2` is treated as customer credit rather than cancellation

Deferred in this slice:
- deeper legacy barcode/warranty print coupling from the old return activity page
- broader damage-return downstream processing tied to later `stock_return_log` activity stages (`3+`)

## 2026-05-03 GRN Read Side Slice
Migrated from:
- `old-code/app/findgrn.php`

Implemented in:
- `new-code/Models/Grn.php`
- `new-code/controller/GrnController.php`
- `new-code/public/index.php`
- `new-code/views/grns/index.php`
- `new-code/controller/DashboardController.php`

Scope completed:
- added GRN search page at `GET /grns`
- migrated legacy GRN lookup filters for GRN ID, supplier name, item name, IMEI, shop, and date range
- default no-filter behavior now loads today's GRNs, matching the old page intent
- each GRN result now shows header details plus the full `shop_grnitem` item breakdown
- dashboard Purchases tile now resolves to the migrated GRN search page for users with `p_45`
- replaced raw legacy lookup SQL with prepared statements and grouped MVC model logic

Deferred in this slice:
- GRN temp entry flow from `grn_new.php`, `grn_add.php`, and `c_grn_temp_*`
- GRN payment write flow from `supplier_grn_payment.php` and `c_supplier_grn_payment.php`

## 2026-05-03 GRN Draft And Finalize Slice
Migrated from:
- `old-code/app/grn_new.php`
- `old-code/app/grn_add.php`
- `old-code/app/c_grn_temp_add.php`
- `old-code/app/c_grn_temp_delete.php`
- `old-code/app/c_grn_temp_load.php`
- `old-code/app/c_grn_temp_ghange.php`

Implemented in:
- `new-code/Models/Grn.php`
- `new-code/controller/GrnController.php`
- `new-code/public/index.php`
- `new-code/views/grns/create.php`
- `new-code/views/grns/index.php`
- `new-code/controller/DashboardController.php`

Scope completed:
- added GRN create workspace at `GET /grns/create`
- added session-backed GRN draft header update at `POST /grns/draft/header`
- added draft line staging, remove, and clear actions under `/grns/draft/*`
- added item-detail helper at `GET /api/grns/items/details` for used type and latest GRN pricing preload
- added transaction-backed GRN submit flow at `POST /grns/submit`
- GRN finalization now writes `shop_grnmain`, `shop_grnitem`, supplier ledger rows, GRN payment rows, and stock mutations together
- barcode, IMEI, and recharge-card stock now route into `shop_stock_item`, `shop_stock_imei`, and `shop_rcv_stock` from the new MVC flow
- dashboard Purchases tile now prefers the GRN create page for users with `p_43`
- removed direct `$_POST` and `$_SESSION` coupling from GRN payment persistence so the model uses explicit method inputs

Deferred in this slice:
- draft line edit/update UI parity from the old temp-grid flow
- bulk IMEI staging during GRN entry
- follow-up GRN balance payment flow from `supplier_grn_payment.php` and `c_supplier_grn_payment.php`

## 2026-05-03 GRN Payment Slice
Migrated from:
- `old-code/app/supplier_grn_payment.php`
- `old-code/app/c_supplier_grn_payment.php`

Implemented in:
- `new-code/Models/Finance.php`
- `new-code/controller/GrnPaymentController.php`
- `new-code/public/index.php`
- `new-code/views/finance/grns/index.php`
- `new-code/views/finance/grns/show.php`
- `new-code/views/catalog/_nav.php`
- `new-code/views/grns/index.php`
- `new-code/views/grns/create.php`

Scope completed:
- added GRN payment list/search page at `GET /grn-payments`
- added GRN payment detail and history page at `GET /grn-payments/{id}`
- added due settlement actions for cash, cheque, and supplier cash-credit
- GRN due settlement now updates `shop_grn_pay`, writes `shop_grn_pay_log`, writes supplier ledger rows, and writes `cash_book` / `account_cheque` rows where applicable
- supplier cash-credit settlement now consumes selected `account_cashcredit` rows and refreshes supplier credit balances
- linked the new GRN payment page from the GRN workspace and the main catalog navigation

Deferred in this slice:
- legacy print-bill handoff after GRN payment actions
- the broader GRN payment reporting slice from `reports/c_grn.php`

## 2026-05-03 GRN Reporting Slice
Migrated from:
- `old-code/app/reports/c_grn.php`

Implemented in:
- `new-code/Models/Grn.php`
- `new-code/controller/FinanceReportController.php`
- `new-code/public/index.php`
- `new-code/views/reports/grns/list.php`
- `new-code/views/reports/grns/detail.php`
- `new-code/views/reports/grns/summary.php`
- `new-code/views/catalog/_nav.php`

Scope completed:
- added GRN list report at `GET /reports/grns`
- added single GRN detail report at `GET /reports/grns/{id}`
- added supplier-wise GRN payment summary report at `GET /reports/grns/summary`
- report pages now use the migrated GRN and GRN-payment tables directly through MVC model methods
- linked GRN report routes into the report navigation using `r_29`, `r_30`, and `r_31`

Deferred in this slice:
- the remaining lower-priority report modes still bundled into legacy `c_grn.php`
- GRN/barcode print-specific report outputs from later printing slices

## 2026-05-03 Stock Transfer Read And Receive Slice
Migrated from:
- `old-code/app/transfer_note.php`
- `old-code/app/transfer_received.php`
- `old-code/app/c_trans_received_accept.php`

Implemented in:
- `new-code/Models/StockTransfer.php`
- `new-code/controller/StockTransferController.php`
- `new-code/public/index.php`
- `new-code/views/stock/transfers/outgoing.php`
- `new-code/views/stock/transfers/incoming.php`
- `new-code/views/catalog/_nav.php`
- `new-code/controller/DashboardController.php`

Scope completed:
- added transfer-note history page at `GET /stock/transfers`
- added incoming transfer acceptance page at `GET /stock/transfers/received`
- added transfer dispatch action at `POST /stock/transfers/{id}/dispatch`
- added receive acceptance action at `POST /stock/transfers/received/{id}/accept`
- outgoing transfer notes now show grouped item logs, status labels, source shop, and operator
- incoming transfer notes now show pending received stock and activate transferred barcode/IMEI stock on acceptance
- dashboard Stocks tile now points to the migrated transfer pages when the user has `p_49` or `p_50`

Deferred in this slice:
- transfer creation/staging UI from `transfer_product_new.php` and `c_trans_stock.php`
- complaint / audit handling from `c_trans_received_complain.php`
- stock-return, stock-adjust, and stock-remove workflows

## 2026-05-03 Stock Transfer Draft And Submit Slice
Migrated from:
- `old-code/app/transfer_product_new.php`
- `old-code/app/c_trans_stock.php`

Implemented in:
- `new-code/Models/StockTransfer.php`
- `new-code/controller/StockTransferController.php`
- `new-code/public/index.php`
- `new-code/views/stock/transfers/create.php`
- `new-code/views/stock/transfers/outgoing.php`
- `new-code/views/catalog/_nav.php`
- `new-code/controller/DashboardController.php`

Scope completed:
- added stock transfer workspace at `GET /stock/transfers/create`
- added session-backed target-shop and transfer-line draft flow instead of reviving legacy `temp_stock_trans`
- added stock search by barcode/IMEI and item name from the current shop
- added draft line add, update, remove, clear, and final submit actions
- transfer submit now writes `stock_transmain`, `stock_translog`, moves barcode/IMEI stock into transfer state, and moves recharge stock to the target shop in one transaction
- dashboard Stocks tile now prefers the transfer-create page for users with `p_48`

Deferred in this slice:
- legacy complaint / audit raise flow from `c_trans_received_complain.php`
- print/download transfer note document output
- stock-return, stock-adjust, and stock-remove workflows

## 2026-05-03 Stock Transfer Complaint Slice
Migrated from:
- `old-code/app/c_trans_received_complain.php`
- `old-code/app/transfer_error_correct.php`
- `old-code/app/c_trans_errcorrect.php`

Implemented in:
- `new-code/Models/StockTransfer.php`
- `new-code/controller/StockTransferController.php`
- `new-code/public/index.php`
- `new-code/views/stock/transfers/incoming.php`
- `new-code/views/stock/transfers/complaints.php`
- `new-code/views/catalog/_nav.php`

Scope completed:
- added complaint raise action on the received-transfer page
- complaint raise now writes `shop_transerror` and moves the transfer main record into complaint state
- added admin-only stock error handling queue at `GET /stock/transfers/complaints`
- added complaint resolution action with release or discard handling
- release resolution now re-activates transferred barcode/IMEI stock, and discard resolution now marks transferred barcode/IMEI stock as discarded
- added admin-only navigation link for stock error handling

Deferred in this slice:
- the legacy half-finished per-line “release with corrected amount” editor

## 2026-05-03 Deferred Stock Management Slice
Migrated from:
- `old-code/app/DownloadTransferNote.php`
- `old-code/app/stock_adjust.php` & `c_stock_adjust.php`
- `old-code/app/stock_remove.php` & `c_stock_remove.php`
- `old-code/app/return_stock.php` & `c_stock_return_sch.php`

Implemented in:
- `new-code/Models/StockAdjust.php`
- `new-code/Models/StockRemove.php`
- `new-code/Models/StockReturn.php`
- `new-code/controller/StockAdjustController.php`
- `new-code/controller/StockRemoveController.php`
- `new-code/controller/StockReturnController.php`
- `new-code/views/stock/transfers/print.php`
- `new-code/views/stock/adjust/index.php`
- `new-code/views/stock/remove/index.php`
- `new-code/views/stock/returns/create.php`

Scope completed:
- added transfer note print view and integrated print button in outgoing/incoming transfers
- added Stock Adjustment workspace allowing search by name/code and manual quantity modification (logging to `stock_edit_log`)
- added Stock Removal workspace allowing permanent removal of stock with reasons (logging to `stock_delete_log`)
- added Stock Return to Supplier workspace allowing direct quantity return and cost updates (logging to `stock_return_log`)

Deferred in this slice:
- the legacy half-finished per-line “release with corrected amount” editor (from transfer complaints)

## Notes For Tomorrow
- Reuse the current MVC pattern already established:
  - controller
  - model
  - view
  - route + middleware permission
- Keep using legacy DB tables first
- Replace request-driven SQL with prepared statements
- Do not reintroduce the old iframe/AJAX-inline-edit pattern unless strictly necessary
