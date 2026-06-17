<?php

declare(strict_types=1);

// ── Core ───────────────────────────────────────────────────────────────────────

$router->get("/",          "HomeController@index");
$router->get("/login",     "AuthController@showLoginForm", ["guest"]);
$router->post("/login",    "AuthController@login",         ["guest"]);
$router->post("/logout",   "AuthController@logout",        ["auth"]);
$router->get("/dashboard", "DashboardController@index",    ["auth"]);

// ── Print & Document Output ────────────────────────────────────────────────────

$router->get("/print/invoice",          "PrintController@invoice",         ["auth"]);
$router->get("/print/barcode",          "PrintController@barcode",         ["auth"]);
$router->get("/print/customer-payment", "PrintController@customerPayment", ["auth"]);
$router->get("/print/supplier-payment", "PrintController@supplierPayment", ["auth"]);
$router->get("/print/repair-bill",      "PrintController@repairBill",      ["auth"]);
$router->get("/print/grn-label",        "PrintController@grnLabel",        ["auth"]);
$router->get("/print/transfer-note",    "PrintController@transferNote",     ["auth"]);

// ── Cashier ────────────────────────────────────────────────────────────────────

$router->get("/cashier",         "CashierController@index", ["auth"]);
$router->post("/cashier/start",  "CashierController@start", ["auth"]);
$router->post("/cashier/close",  "CashierController@close", ["auth"]);

// Expenses
$router->get("/cashier/expenses",                          "ExpenseController@index",         ["auth", "permission:p_57"]);
$router->post("/cashier/expenses",                         "ExpenseController@store",         ["auth", "permission:p_57", "cashier"]);
$router->post("/cashier/expenses/accounts",                "ExpenseController@storeAccount",  ["auth", "permission:p_60"]);
$router->post("/cashier/expenses/accounts/{id}/update",    "ExpenseController@updateAccount", ["auth", "permission:p_60"]);

// Cash In
$router->get("/cashier/cash-in",                         "CashInController@index",         ["auth", "permission:p_57"]);
$router->post("/cashier/cash-in",                        "CashInController@store",         ["auth", "permission:p_57", "cashier"]);
$router->post("/cashier/cash-in/accounts",               "CashInController@storeAccount",  ["auth", "permission:p_61"]);
$router->post("/cashier/cash-in/accounts/{id}/update",   "CashInController@updateAccount", ["auth", "permission:p_61"]);

// ── Reports ────────────────────────────────────────────────────────────────────

$router->get("/reports",                          "ReportController@index",               ["auth", "permission:p_62"]);
$router->get("/reports/sales/shop",               "ReportController@shopSale",            ["auth", "permission:p_21"]);
$router->get("/reports/sales/category",           "ReportController@categorySale",        ["auth", "permission:p_24"]);
$router->get("/reports/sales/bestsale",           "ReportController@salesBestSale",       ["auth", "permission:p_24"]);
$router->get("/reports/sales/itemwise",           "ReportController@salesItemWise",       ["auth", "permission:p_24"]);
$router->get("/reports/sales/itemcatwise",        "ReportController@salesItemCatWise",    ["auth", "permission:p_24"]);
$router->get("/reports/sales/overcost",           "ReportController@salesOverCost",       ["auth", "permission:p_24"]);
$router->get("/reports/sales/undercost",          "ReportController@salesUnderCost",      ["auth", "permission:p_24"]);
$router->get("/reports/sales/phonesale",          "ReportController@salesPhoneSale",      ["auth", "permission:p_24"]);

$router->get("/reports/cashier/transactions",     "ReportController@cashierTransactions",      ["auth", "permission:p_34"]);
$router->get("/reports/cashier/openclose",        "ReportController@cashierOpenClose",         ["auth", "permission:p_35"]);
$router->get("/reports/cashier/expenses",         "ReportController@cashierExpenses",          ["auth", "permission:p_36"]);
$router->get("/reports/cashier/profit",           "ReportController@cashierProfit",            ["auth", "permission:p_38"]);
$router->get("/reports/cashier/cashin",           "ReportController@cashierCashIn",            ["auth", "permission:p_38"]);
$router->get("/reports/cashier/accwise_expenses", "ReportController@cashierAccWiseExpenses",   ["auth", "permission:p_38"]);
$router->get("/reports/cashier/operation",        "ReportController@cashierOperation",         ["auth", "permission:p_38"]);

$router->get("/reports/product/categories",       "ReportController@productCategories",   ["auth", "permission:p_6"]);
$router->get("/reports/product/items",            "ReportController@productList",         ["auth", "permission:p_7"]);
$router->get("/reports/product/stock",            "ReportController@productStock",        ["auth", "permission:p_8"]);

$router->get("/reports/supplier/master",          "ReportController@supplierMaster",      ["auth", "permission:p_40"]);
$router->get("/reports/supplier/ledger",          "ReportController@supplierLedger",      ["auth", "permission:p_41"]);

$router->get("/reports/customer/master",          "ReportController@customerMaster",      ["auth", "permission:p_43"]);
$router->get("/reports/customer/ledger",          "ReportController@customerLedger",      ["auth", "permission:p_44"]);

$router->get("/reports/user/master",              "ReportController@userMaster",          ["auth", "permission:p_50"]);
$router->get("/reports/user/sales",               "ReportController@userSales",           ["auth", "permission:p_51"]);
$router->get("/reports/user/security",            "ReportController@userSecurity",        ["auth", "permission:p_52"]);

$router->get("/reports/grn/list",                 "ReportController@grnList",             ["auth", "permission:p_60"]);
$router->get("/reports/grn/detail",               "ReportController@grnDetail",           ["auth", "permission:p_60"]);
$router->get("/reports/grn/reorder",              "ReportController@grnReorder",          ["auth", "permission:p_60"]);
$router->get("/reports/grn/returns",              "ReportController@grnReturns",          ["auth", "permission:p_60"]);
$router->get("/reports/grn/return_detail",        "ReportController@grnReturnDetail",     ["auth", "permission:p_60"]);
$router->get("/reports/grn/discard",              "ReportController@grnDiscard",          ["auth", "permission:p_60"]);
$router->get("/reports/grn/transfer_bin",         "ReportController@grnTransferBin",      ["auth", "permission:p_60"]);
$router->get("/reports/grn/sales_return_bin",     "ReportController@grnSalesReturnBin",   ["auth", "permission:p_60"]);
$router->get("/reports/grn/supplier_wise",        "ReportController@grnSupplierWise",     ["auth", "permission:p_60"]);

$router->get("/reports/logs/price-edit",          "ReportController@logsPriceEdit",       ["auth", "permission:p_61"]);
$router->get("/reports/logs/stock-edit",          "ReportController@logsStockEdit",       ["auth", "permission:p_61"]);
$router->get("/reports/logs/stock-delete",        "ReportController@logsStockDelete",     ["auth", "permission:p_61"]);

$router->get("/reports/repair/jobs",              "ReportController@repairJobList",       ["auth", "permission:p_30"]);
$router->get("/reports/repair/detail",            "ReportController@repairJobDetail",     ["auth", "permission:p_30"]);

$router->get("/reports/transfer/list",            "ReportController@transferList",        ["auth", "permission:p_63"]);
$router->get("/reports/transfer/detail",          "ReportController@transferDetail",      ["auth", "permission:p_63"]);
$router->get("/reports/transfer/logcheck",        "ReportController@transferLogCheck",    ["auth", "permission:p_63"]);

$router->post("/reports/api/fetch",               "ReportController@fetch",               ["auth", "permission:p_62"]);

$router->get("/reports/supplier-payments",        "FinanceReportController@supplierPayments",    ["auth", "permission:r_15"]);
$router->get("/reports/grns",                     "FinanceReportController@grnList",             ["auth", "permission:r_30"]);
$router->get("/reports/grns/summary",             "FinanceReportController@grnSupplierSummary",  ["auth", "permission:r_29"]);
$router->get("/reports/grns/{id}",                "FinanceReportController@grnDetail",           ["auth", "permission:r_31"]);
$router->get("/reports/customer-payments",        "FinanceReportController@customerPayments",    ["auth", "permission:r_19"]);

// ── Repair Center ──────────────────────────────────────────────────────────────

$router->get("/repair/jobs/new",            "RepairJobController@create",              ["auth", "permission:p_3", "cashier"]);
$router->post("/repair/jobs",               "RepairJobController@store",               ["auth", "permission:p_3", "cashier"]);
$router->get("/repair/process",             "RepairProcessController@index",           ["auth", "permission:p_4"]);
$router->post("/repair/process/load",       "RepairProcessController@loadJob",         ["auth", "permission:p_4"]);
$router->post("/repair/process/add-part",   "RepairProcessController@addPart",         ["auth", "permission:p_4"]);
$router->post("/repair/process/finish",     "RepairProcessController@finishTechnical", ["auth", "permission:p_4"]);
$router->get("/repair/release",             "RepairReleaseController@index",           ["auth", "permission:p_5"]);
$router->post("/repair/release/load",       "RepairReleaseController@loadJobData",     ["auth", "permission:p_5"]);
$router->post("/repair/release",            "RepairReleaseController@store",           ["auth", "permission:p_5"]);
$router->get("/repair/handover",            "RepairHandoverController@index",          ["auth", "permission:p_6"]);
$router->post("/repair/handover/load",      "RepairHandoverController@loadJobData",    ["auth", "permission:p_6"]);
$router->post("/repair/handover",           "RepairHandoverController@store",          ["auth", "permission:p_6", "cashier"]);

// Repair Admin
$router->get("/repair/admin/faults",                    "RepairAdminController@faultsIndex",  ["auth"]);
$router->post("/repair/admin/faults",                   "RepairAdminController@faultsStore",  ["auth"]);
$router->post("/repair/admin/faults/{id}/update",       "RepairAdminController@faultsUpdate", ["auth"]);
$router->post("/repair/admin/faults/{id}/delete",       "RepairAdminController@faultsDelete", ["auth"]);
$router->get("/repair/admin/belongs",                   "RepairAdminController@belongsIndex",  ["auth"]);
$router->post("/repair/admin/belongs",                  "RepairAdminController@belongsStore",  ["auth"]);
$router->post("/repair/admin/belongs/{id}/update",      "RepairAdminController@belongsUpdate", ["auth"]);
$router->post("/repair/admin/belongs/{id}/delete",      "RepairAdminController@belongsDelete", ["auth"]);

// ── Point of Sale ──────────────────────────────────────────────────────────────

$router->get("/pos",                                    "PosController@index",              ["auth", "permission:p_2", "cashier"]);
$router->get("/pos/slots/{slot}",                       "PosController@switchSlot",         ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/slots/{slot}/clear",                "PosController@clearSlot",          ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/lookup/name",                       "PosController@lookupByName",       ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/lookup/code",                       "PosController@lookupByCode",       ["auth", "permission:p_2", "cashier"]);
$router->get("/api/pos/salespeople/{id}",               "PosController@salesPersonLookup",  ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/customer",                          "PosController@selectCustomer",     ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/seller",                            "PosController@selectSalesPerson",  ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/items",                             "PosController@addItem",            ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/items/imei-bulk",                   "PosController@addBulkImeiItems",   ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/items/{id}",                        "PosController@updateLine",         ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/items/{id}/delete",                 "PosController@removeLine",         ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/reset",                             "PosController@resetCart",          ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/payment",                           "PosController@updatePayment",      ["auth", "permission:p_2", "cashier"]);
$router->post("/pos/checkout",                          "PosController@checkout",           ["auth", "permission:p_2", "cashier"]);
$router->get("/pos/bills/today",                        "PosController@dailyBills",         ["auth", "permission:p_31"]);
$router->post("/pos/bills/{billnumber}/cancel",         "PosController@cancelBill",         ["auth", "permission:p_31"]);
$router->get("/pos/bills/search",                       "PosController@searchBills",        ["auth", "permission:p_32"]);
$router->get("/pos/bills/{billnumber}/returns/create",  "BillReturnController@create",      ["auth", "permission:p_33"]);
$router->post("/pos/bills/{billnumber}/returns",        "BillReturnController@store",       ["auth", "permission:p_33"]);
$router->get("/pos/bills/{billnumber}/returns",         "BillReturnController@history",     ["auth", "permission:p_33"]);
$router->get("/pos/returns/pending",                    "BillReturnController@pending",     ["auth", "permission:p_34"]);
$router->get("/pos/returns/pending/{billnumber}/{altertime}", "BillReturnController@activity", ["auth", "permission:p_34"]);
$router->post("/pos/returns/items/{id}/settle",         "BillReturnController@settle",      ["auth", "permission:p_34"]);
$router->post("/pos/returns/items/{id}/credit",         "BillReturnController@credit",      ["auth", "permission:p_34"]);
$router->get("/pos/receipts/{billnumber}",              "PosController@receipt",            ["auth", "permission:p_2"]);
$router->get("/pos/receipts/{billnumber}/print",        "PosController@printReceipt",       ["auth", "permission:p_2"]);
$router->get("/pos/receipts/{billnumber}/barcodes",     "PosController@barcodeLabels",      ["auth", "permission:p_2"]);
$router->post("/pos/receipts/{billnumber}/barcodes/print", "PosController@printBarcodeLabels", ["auth", "permission:p_2"]);

// ── POS API helpers ────────────────────────────────────────────────────────────

$router->post("/api/pos/customers",        "PosHelperController@customerLookup",    ["auth"]);
$router->post("/api/pos/items/by-name",    "PosHelperController@itemLookupByName",  ["auth"]);
$router->post("/api/pos/items/by-code",    "PosHelperController@itemLookupByCode",  ["auth"]);
$router->get("/api/items/by-category",     "StockSearchController@itemsByCategory", ["auth", "permission:p_17"]);

// ── Settings ───────────────────────────────────────────────────────────────────

$router->get("/settings",                           "SettingsController@index",              ["auth", "permission:p_63"]);
$router->get("/settings/users",                     "UserManagementController@index",        ["auth", "permission:p_65"]);
$router->get("/settings/users/create",              "UserManagementController@create",       ["auth", "permission:p_64"]);
$router->post("/settings/users",                    "UserManagementController@store",        ["auth", "permission:p_64"]);
$router->post("/settings/users/{id}/status",        "UserManagementController@updateStatus", ["auth", "permission:p_65"]);
$router->get("/settings/shops",                     "ShopController@index",                  ["auth", "permission:p_71"]);
$router->get("/settings/shops/create",              "ShopController@create",                 ["auth", "permission:p_70"]);
$router->post("/settings/shops",                    "ShopController@store",                  ["auth", "permission:p_70"]);
$router->get("/settings/shops/{id}/edit",           "ShopController@edit",                   ["auth", "permission:p_71"]);
$router->post("/settings/shops/{id}",               "ShopController@update",                 ["auth", "permission:p_71"]);
$router->get("/settings/profile",                   "UserProfileController@edit",            ["auth"]);
$router->post("/settings/profile",                  "UserProfileController@updateDetails",   ["auth"]);
$router->post("/settings/profile/password",         "UserProfileController@updatePassword",  ["auth"]);
$router->get("/settings/privileges",                "PrivilegeController@index",             ["auth", "permission:p_67"]);
$router->post("/settings/privileges",               "PrivilegeController@store",             ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}",          "PrivilegeController@update",            ["auth", "permission:p_67"]);
$router->get("/settings/privileges/{id}/functions", "PrivilegeController@editFunctions",    ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}/functions","PrivilegeController@updateFunctions",  ["auth", "permission:p_67"]);
$router->get("/settings/privileges/{id}/reports",   "PrivilegeController@editReports",      ["auth", "permission:p_67"]);
$router->post("/settings/privileges/{id}/reports",  "PrivilegeController@updateReports",    ["auth", "permission:p_67"]);
$router->get("/settings/user-privileges",           "UserPrivilegeController@index",        ["auth", "permission:p_68"]);
$router->post("/settings/user-privileges/{id}",     "UserPrivilegeController@update",       ["auth", "permission:p_68"]);

// ── Catalogue ──────────────────────────────────────────────────────────────────

$router->get("/categories",              "ProductCategoryController@index",   ["auth", "permission:p_18"]);
$router->post("/categories",             "ProductCategoryController@store",   ["auth", "permission:p_18"]);
$router->post("/categories/{id}",        "ProductCategoryController@update",  ["auth", "permission:p_18"]);
$router->post("/categories/{id}/delete", "ProductCategoryController@destroy", ["auth", "permission:p_18"]);

$router->get("/item-colors",              "ItemColorController@index",   ["auth", "permission:p_19"]);
$router->post("/item-colors",             "ItemColorController@store",   ["auth", "permission:p_19"]);
$router->post("/item-colors/{id}",        "ItemColorController@update",  ["auth", "permission:p_19"]);
$router->post("/item-colors/{id}/delete", "ItemColorController@destroy", ["auth", "permission:p_19"]);

$router->get("/operators",         "RechargeOperatorController@index",  ["auth", "permission:p_22"]);
$router->post("/operators",        "RechargeOperatorController@store",  ["auth", "permission:p_22"]);
$router->post("/operators/{id}",   "RechargeOperatorController@update", ["auth", "permission:p_22"]);

// ── Items ──────────────────────────────────────────────────────────────────────

$router->get("/items",            "ItemController@index",  ["auth", "permission:p_16"]);
$router->get("/items/create",     "ItemController@create", ["auth", "permission:p_15"]);
$router->post("/items",           "ItemController@store",  ["auth", "permission:p_15"]);
$router->get("/items/{id}/edit",  "ItemController@edit",   ["auth", "permission:p_16"]);
$router->post("/items/{id}",      "ItemController@update", ["auth", "permission:p_16"]);
$router->get("/items/search",     "StockSearchController@index", ["auth", "permission:p_17"]);

$router->get("/item-alerts",              "StockAlertController@index",   ["auth", "permission:p_52"]);
$router->post("/item-alerts",             "StockAlertController@store",   ["auth", "permission:p_52"]);
$router->post("/item-alerts/{id}",        "StockAlertController@update",  ["auth", "permission:p_52"]);
$router->post("/item-alerts/{id}/delete", "StockAlertController@destroy", ["auth", "permission:p_52"]);

// ── Suppliers ──────────────────────────────────────────────────────────────────

$router->get("/suppliers",              "SupplierController@index",  ["auth", "permission:p_26"]);
$router->get("/suppliers/create",       "SupplierController@create", ["auth", "permission:p_25"]);
$router->post("/suppliers",             "SupplierController@store",  ["auth", "permission:p_25"]);
$router->get("/suppliers/{id}/edit",    "SupplierController@edit",   ["auth", "permission:p_26"]);
$router->post("/suppliers/{id}",        "SupplierController@update", ["auth", "permission:p_26"]);

$router->get("/supplier-accounts",                      "AccountBalanceController@suppliers",                 ["auth", "permission:p_27"]);
$router->get("/supplier-credit-balances",               "AccountBalanceController@supplierCashCredits",       ["auth", "permission:p_28"]);
$router->post("/supplier-credit-balances/refresh",      "AccountBalanceController@refreshSupplierCashCredits",["auth", "permission:p_28"]);
$router->get("/supplier-payments",                      "PaymentController@supplierForm",                     ["auth", "permission:p_29", "cashier"]);
$router->get("/api/supplier-payments/details",          "PaymentController@supplierDetails",                  ["auth", "permission:p_29", "cashier"]);
$router->post("/supplier-payments",                     "PaymentController@storeSupplierPayment",             ["auth", "permission:p_29", "cashier"]);

// ── GRN / Purchases ────────────────────────────────────────────────────────────

$router->get("/grns/create",                    "GrnController@create",       ["auth", "permission:p_43", "cashier"]);
$router->post("/grns/draft/header",             "GrnController@updateHeader", ["auth", "permission:p_43", "cashier"]);
$router->post("/grns/draft/lines",              "GrnController@addLine",      ["auth", "permission:p_43", "cashier"]);
$router->post("/grns/draft/lines/{index}/delete","GrnController@removeLine",  ["auth", "permission:p_43", "cashier"]);
$router->post("/grns/draft/clear",              "GrnController@clear",        ["auth", "permission:p_43", "cashier"]);
$router->post("/grns/submit",                   "GrnController@submit",       ["auth", "permission:p_43", "cashier"]);
$router->get("/grns",                           "GrnController@index",        ["auth", "permission:p_45"]);
$router->get("/api/grns/items/details",         "GrnController@itemDetails",  ["auth", "permission:p_43", "cashier"]);

$router->get("/grn-payments",              "GrnPaymentController@index",    ["auth", "permission:p_29", "cashier"]);
$router->get("/grn-payments/{id}",         "GrnPaymentController@show",     ["auth", "permission:p_29", "cashier"]);
$router->post("/grn-payments/{id}/cash",   "GrnPaymentController@payCash",  ["auth", "permission:p_29", "cashier"]);
$router->post("/grn-payments/{id}/cheque", "GrnPaymentController@payCheque",["auth", "permission:p_29", "cashier"]);
$router->post("/grn-payments/{id}/credit", "GrnPaymentController@payCredit",["auth", "permission:p_29", "cashier"]);

// ── Customers ──────────────────────────────────────────────────────────────────

$router->get("/customers",              "CustomerController@index",        ["auth", "permission:p_37"]);
$router->get("/customers/create",       "CustomerController@create",       ["auth", "permission:p_36"]);
$router->post("/customers",             "CustomerController@store",        ["auth", "permission:p_36"]);
$router->get("/customers/{id}/edit",    "CustomerController@edit",         ["auth", "permission:p_37"]);
$router->post("/customers/{id}",        "CustomerController@update",       ["auth", "permission:p_37"]);
$router->post("/customers/{id}/status", "CustomerController@updateStatus", ["auth", "permission:p_37"]);

$router->get("/customer-accounts",                     "AccountBalanceController@customers",                  ["auth", "permission:p_39"]);
$router->get("/customer-credit-balances",              "AccountBalanceController@customerCashCredits",         ["auth", "permission:p_40"]);
$router->post("/customer-credit-balances/refresh",     "AccountBalanceController@refreshCustomerCashCredits",  ["auth", "permission:p_40"]);
$router->get("/customer-payments",                     "PaymentController@customerForm",                       ["auth", "permission:p_41", "cashier"]);
$router->get("/api/customer-payments/details",         "PaymentController@customerDetails",                    ["auth", "permission:p_41", "cashier"]);
$router->post("/customer-payments",                    "PaymentController@storeCustomerPayment",               ["auth", "permission:p_41", "cashier"]);

// ── Stock ──────────────────────────────────────────────────────────────────────

// Stock Transfers
$router->get("/stock/transfers/create",                   "StockTransferController@create",          ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/target",           "StockTransferController@updateTarget",    ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/search",           "StockTransferController@search",          ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/lines",            "StockTransferController@addLine",         ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/lines/{id}",       "StockTransferController@updateLine",      ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/lines/{id}/delete","StockTransferController@removeLine",      ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/clear",            "StockTransferController@clearDraft",      ["auth", "permission:p_48"]);
$router->post("/stock/transfers/create/submit",           "StockTransferController@submitDraft",     ["auth", "permission:p_48"]);
$router->get("/stock/transfers",                          "StockTransferController@outgoing",        ["auth", "permission:p_49"]);
$router->post("/stock/transfers/{id}/dispatch",           "StockTransferController@markInTransit",   ["auth", "permission:p_49"]);
$router->get("/stock/transfers/received",                 "StockTransferController@incoming",        ["auth", "permission:p_50"]);
$router->post("/stock/transfers/received/{id}/accept",    "StockTransferController@accept",          ["auth", "permission:p_50"]);
$router->post("/stock/transfers/received/{id}/complain",  "StockTransferController@complain",        ["auth", "permission:p_50"]);
$router->get("/stock/transfers/complaints",               "StockTransferController@complaints",      ["auth"]);
$router->post("/stock/transfers/complaints/{id}",         "StockTransferController@resolveComplaint",["auth"]);
$router->get("/stock/transfers/{id}/print",               "StockTransferController@printNote",       ["auth", "permission:p_49"]);

// Stock Adjust
$router->get("/stock/adjust",          "StockAdjustController@index",  ["auth", "permission:p_54"]);
$router->post("/stock/adjust/search",  "StockAdjustController@search", ["auth", "permission:p_54"]);
$router->post("/stock/adjust/submit",  "StockAdjustController@submit", ["auth", "permission:p_54"]);

// Stock Remove
$router->get("/stock/remove",          "StockRemoveController@index",  ["auth", "permission:p_53"]);
$router->post("/stock/remove/search",  "StockRemoveController@search", ["auth", "permission:p_53"]);
$router->post("/stock/remove/submit",  "StockRemoveController@submit", ["auth", "permission:p_53"]);

// Stock Return
$router->get("/stock/returns/create",   "StockReturnController@create", ["auth", "permission:p_51"]);
$router->post("/stock/returns/search",  "StockReturnController@search", ["auth", "permission:p_51"]);
$router->post("/stock/returns/submit",  "StockReturnController@submit", ["auth", "permission:p_51"]);
