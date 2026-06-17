# Migration Audit Report
**Date:** 2026-05-03 | **Scope:** All steps in `migration-roadmap.md`

---

## ✅ COMPLETE Steps (Fully Migrated)

### Step 1 — Auth & Dashboard
| Evidence | |
|---|---|
| Controllers | `AuthController.php`, `DashboardController.php` |
| Models | `User.php`, `Permission.php`, `Shop.php`, `Dashboard.php` |
| Views | `views/auth/`, `views/dashboard/` |
| **Verdict** | ✅ Complete |

### Step 2 — Settings & Permissions
| Evidence | |
|---|---|
| Controllers | `SettingsController.php`, `UserManagementController.php`, `UserPrivilegeController.php`, `PrivilegeController.php`, `UserProfileController.php` |
| Models | `Permission.php`, `PermissionCatalog.php`, `Privilege.php` |
| Views | `views/settings/` |
| **Verdict** | ✅ Complete |

### Step 3 — Master Data
| Evidence | |
|---|---|
| Controllers | `CustomerController.php`, `SupplierController.php`, `ShopController.php`, `ItemController.php`, `ProductCategoryController.php`, `ItemColorController.php`, `RechargeOperatorController.php` |
| Models | `Customer.php`, `Supplier.php`, `Shop.php`, `Item.php`, `ProductCategory.php`, `ItemColor.php`, `RechargeOperator.php` |
| Views | `views/catalog/` |
| **Verdict** | ✅ Complete |

### Step 4 — Cashier & Opening/Closing
| Sub-feature | Controller | View | Route | Status |
|---|---|---|---|---|
| Cashier duty on/off | `CashierController@index/start/close` | `views/cashier/index.php` | `GET /cashier`, `POST /cashier/start`, `POST /cashier/close` | ✅ |
| Expense log | `ExpenseController@index/store` | `views/cashier/expenses/index.php` | `GET/POST /cashier/expenses` | ✅ |
| Expense account manage | `ExpenseController@storeAccount/updateAccount` | Embedded in expenses view | `POST /cashier/expenses/accounts` | ✅ |
| Cash-in log | `CashInController@index/store` | `views/cashier/cashin/index.php` | `GET/POST /cashier/cash-in` | ✅ |
| Cash-in account manage | `CashInController@storeAccount/updateAccount` | Embedded in cashin view | `POST /cashier/cash-in/accounts` | ✅ |
| Session balance summary | `Cashier::getSessionSummary()` | Embedded in `cashier/index.php` | — | ✅ (was missing — now added) |
| Cashier transfer on close | `Cashier::closeDutyWithTransfer()` | Transfer dropdown in `cashier/index.php` | `POST /cashier/close` + `close_type=2` | ✅ (was missing — now added) |
| **Verdict** | | | | ✅ **Fully Complete** — all 7 sub-features confirmed |

> Legacy sources confirmed migrated: `cashier_onoff.php`, `shop_openbalance.php`, `shop_close.php`, `add_expense.php`, `expence_account.php`, `cashin_account.php`, `c_cashier.php`, `c_expence_operation.php`, `c_income_operation.php`
>
> **Gaps found and fixed during audit:**
> - `shop_close.php` balance summary screen (open bal / income / expenses / sys-close totals + transaction table) — added to `Cashier::getSessionSummary()` and `cashier/index.php`
> - `c_cashier.php` OP_TYPE=1 transfer mode — added to `Cashier::closeDutyWithTransfer()` and the close form


### Step 5 — POS Core
| Evidence | |
|---|---|
| Controllers | `PosController.php` (33KB), `BillReturnController.php`, `PaymentController.php`, `PosHelperController.php` |
| Models | `PosSale.php` (35KB), `BillReturn.php` (39KB) |
| Views | `views/pos/` |
| **Verdict** | ✅ Complete |

### Step 6 — Customer Finance
| Evidence | |
|---|---|
| Controllers | `PaymentController.php`, `AccountBalanceController.php`, `FinanceReportController.php` |
| Models | `Finance.php` (30KB), `Customer.php` |
| Views | `views/finance/` |
| **Verdict** | ✅ Complete |

### Step 7 — Supplier Finance & GRN
| Evidence | |
|---|---|
| Controllers | `GrnController.php`, `GrnPaymentController.php` |
| Models | `Grn.php` (39KB), `Supplier.php` |
| Views | `views/grns/` |
| **Verdict** | ✅ Complete |

### Step 8 — Stock Operations
| Evidence | |
|---|---|
| Controllers | `StockTransferController.php`, `StockAdjustController.php`, `StockRemoveController.php`, `StockReturnController.php`, `StockAlertController.php`, `StockSearchController.php` |
| Models | `StockTransfer.php` (41KB), `StockAdjust.php`, `StockRemove.php`, `StockReturn.php`, `StockAlert.php` |
| Views | `views/stock/` |
| **Verdict** | ✅ Complete |

### Step 9 — Repair Center
| Evidence | |
|---|---|
| Controllers | `RepairJobController.php`, `RepairProcessController.php`, `RepairReleaseController.php`, `RepairHandoverController.php`, `RepairAdminController.php` |
| Models | `RepairJob.php`, `RepairLog.php`, `RepairBelong.php`, `RepairFault.php` |
| Views | `views/repair/` |
| **Verdict** | ✅ Complete |

### Step 10 — Reporting (Core)
| Evidence | |
|---|---|
| Controller | `ReportController.php` |
| Model | `ReportEngine.php` (28KB — all 6 phases implemented) |
| Views | `views/reports/` (all subfolders: cashier/, sale/, product/, supplier/, customer/, user/, grn/, logs/, repair/, transfer/) |
| **Verdict** | ✅ Complete — all `c_*.php` report handlers migrated |

---

## ⚠️ PARTIAL — Step 10 Extended Report Variants

These legacy front-end router pages exist in `old-code/app/reports/` but have **no dedicated view** in new-code. The data queries for the core equivalents are handled by `ReportEngine.php`, but these specific filtered/variant views were not individually created:

| Legacy File | Type | New-Code View | Status |
|---|---|---|---|
| `rpt_cashier_cashin.php` | Cashier | `views/reports/cashier/cashin.php` | ✅ Migrated |
| `rpt_cashier_accwiseexpences.php` | Cashier | `views/reports/cashier/accwiseexpenses.php` | ✅ Migrated |
| `rpt_cashier_operation.php` | Cashier | `views/reports/cashier/operation.php` | ✅ Migrated |
| `rpt_sale_bestsale.php` | Sales | `views/reports/sales/bestsale.php` | ✅ Migrated |
| `rpt_sale_itemwisesale.php` | Sales | `views/reports/sales/itemwisesale.php` | ✅ Migrated |
| `rpt_sale_itemcatwisesale.php` | Sales | `views/reports/sales/itemcatwisesale.php` | ✅ Migrated |
| `rpt_sale_overcost.php` | Sales | `views/reports/sales/overcost.php` | ✅ Migrated |
| `rpt_sale_undercost.php` | Sales | `views/reports/sales/undercost.php` | ✅ Migrated |
| `rpt_sale_phonesale.php` | Sales | `views/reports/sales/phonesale.php` | ✅ Migrated |
| `rpt_grn_returnlist.php` | GRN | `views/reports/grn/returnlist.php` | ✅ Migrated |
| `rpt_grn_returndata.php` | GRN | `views/reports/grn/returndata.php` | ✅ Migrated |
| `rpt_grn_returndiscard.php` | GRN | `views/reports/grn/returndiscard.php` | ✅ Migrated |
| `rpt_grn_notreturnwhouse.php` | GRN | `views/reports/grn/notreturnwhouse.php` | ✅ Migrated |
| `rpt_grn_returnagainstock.php` | GRN | `views/reports/grn/returnagainstock.php` | ✅ Migrated |
| `rpt_grn_supwisegrn.php` | GRN | `views/reports/grn/supwisegrn.php` | ✅ Migrated |

**Count: 15 sub-reports completed.**

---

## ❌ PENDING Steps (Not Yet Migrated)

### Step 11 — Notifications, Tasks, SMS
| Legacy File | New-code Target | Status |
|---|---|---|
| `sys_notification.php` | `NotificationController` | ❌ Not created |
| `sms_broadcast.php` + `c_sms_broadcast.php` | `SmsController` | ❌ Not created |
| `c_task_mng.php` | `TaskController` | ❌ Not created |
| `c_ngin_run_notifi_1.php` | Background service | ❌ Not created |
| `task/task_notification.php` | Merged into above | ❌ Not created |

Missing models: No `Notification`, `Sms`, or `Task` model in `Models/`.

### Step 12 — Printing & Document Output
| Legacy File | New-code Target | Status |
|---|---|---|
| `printmybill.php` | `PrintController@invoice` | ✅ Migrated |
| `dt_print_invoice.php` | `PrintController@invoice` | ✅ Migrated |
| `barcodeprint.php` / `barcodeprint_back.php` | `PrintController@barcode` | ✅ Migrated |
| `print_customer_paybill.php` | `PrintController@customerPayment` | ✅ Migrated |
| `print_supplier_paybill.php` | `PrintController@supplierPayment` | ✅ Migrated |
| `printrepairbill.php` / `printrepirjob.php` | `PrintController@repairBill` | ✅ Migrated |
| `repair_job_lbl.php` / `rep_job_lbl_reprint.php` | `PrintController@repairBill` (Labels context) | ✅ Migrated |
| `grncodeprint.php` | `PrintController@grnLabel` | ✅ Migrated |
| `DownloadTransferNote.php` | `PrintController@transferNote` | ✅ Migrated |
| JSPrintManager integration | `views/print/_jspm.php` | ✅ Migrated |

### Step 13 — Module Cleanup
| Legacy Path | Action Needed | Status |
|---|---|---|
| `app/module/index.php` | Fold into Steps 3/8 | ❌ Not done |
| `app/module/stock_manage.php` | Fold into `StockAdjustController` | ❌ Not done |
| `app/module/new_item_manager.php` | Fold into `ItemController` | ❌ Not done |
| `app/module/ajax_*.php` (5 files) | Remove unauthenticated AJAX endpoints | ❌ Not done |
| `app/module/api/product_catalog/*` | Merge into catalog controllers | ❌ Not done |

---

## Summary Table

| Step | Name | Status | Notes |
|---|---|---|---|
| 1 | Auth & Dashboard | ✅ Complete | |
| 2 | Settings & Permissions | ✅ Complete | |
| 3 | Master Data | ✅ Complete | |
| 4 | Cashier & Open/Close | ✅ Complete | Verified: all routes, controllers, views present |
| 5 | POS Core | ✅ Complete | |
| 6 | Customer Finance | ✅ Complete | |
| 7 | Supplier Finance & GRN | ✅ Complete | |
| 8 | Stock Operations | ✅ Complete | |
| 9 | Repair Center | ✅ Complete | |
| 10 | Reporting (core) | ✅ Complete | All `c_*.php` handlers migrated |
| 10+ | Reporting (extended variants) | ✅ Complete | 15 sub-report views migrated |
| 11 | Notifications, Tasks, SMS | ❌ Not Started | |
| 12 | Printing & Document Output | ✅ Complete | Migrated to PrintController & JSPrintManager |
| 13 | Module Cleanup | ❌ Not Started | Security risk from unauthenticated AJAX |

**Overall: 12/13 steps complete, 2 steps not started.**

---

## Recommended Priority Order

1. ~~Step 10 Extended Reports~~ — ✅ **Confirmed Complete**
2. ~~Step 12 Printing~~ — ✅ **Confirmed Complete**
3. **Step 11 Notifications & SMS** — Inbox, unread count, task management
4. **Step 13 Module Cleanup** — Remove unauthenticated `ajax_*.php` endpoints (security risk)
