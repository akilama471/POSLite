<?php

declare(strict_types=1);

/**
 * PrintModel — secure, prepared-statement data fetchers for all print documents.
 */
class PrintModel extends Model
{
    // ── POS Invoice ─────────────────────────────────────────────────

    public function getInvoiceData(string $billNumber): array
    {
        $stmtBill = $this->db->prepare(
            "SELECT b.*, c.cus_name, c.cus_mobile, c.cus_addr,
                    u.ankaya AS cashier_name,
                    s.shop_info_name, s.shop_info_addr, s.shop_tel_1, s.shop_email
             FROM shop_pos_billdetails b
             LEFT JOIN shop_customer c ON b.customer_id = c.recordid
             LEFT JOIN sys_user u ON b.operator = u.myid
             LEFT JOIN sys_shop s ON b.shop_id = s.shopid
             WHERE b.billnumber = :bill LIMIT 1"
        );
        $stmtBill->execute(['bill' => $billNumber]);
        $header = $stmtBill->fetch();

        $stmtItems = $this->db->prepare(
            "SELECT item_name, imei_part_no, qty, sale_price, discount, sub_total, waranty, type
             FROM shop_pos_mainsale WHERE billnumber = :bill"
        );
        $stmtItems->execute(['bill' => $billNumber]);
        $items = $stmtItems->fetchAll();

        return ['header' => $header, 'items' => $items];
    }

    public function getBillBarcodeItems(string $billNumber): array
    {
        $stmtItems = $this->db->prepare(
            "SELECT m.item_name, m.imei_part_no, m.qty, m.waranty, m.type,
                    i.supplier_id
             FROM shop_pos_mainsale m
             LEFT JOIN shop_stock_item i ON m.imei_part_no = i.gen_refno AND m.type = 1
             WHERE m.billnumber = :bill"
        );
        $stmtItems->execute(['bill' => $billNumber]);
        return $stmtItems->fetchAll();
    }

    // ── Customer Payment Receipt ────────────────────────────────────

    public function getCustomerPayReceipt(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cus_name, cus_addr, cus_mobile, accbalance
             FROM shop_customer WHERE recordid = :id LIMIT 1"
        );
        $stmt->execute(['id' => $customerId]);
        return $stmt->fetch() ?: [];
    }

    // ── Supplier Payment Receipt ────────────────────────────────────

    public function getSupplierPayReceipt(int $supplierId): array
    {
        $stmt = $this->db->prepare(
            "SELECT suppler_name, suppler_mobile, accbalance
             FROM supplier_detail WHERE supplerid = :id LIMIT 1"
        );
        $stmt->execute(['id' => $supplierId]);
        return $stmt->fetch() ?: [];
    }

    // ── Repair Job Receipt ──────────────────────────────────────────

    public function getRepairJobReceipt(string $jobNumber): array
    {
        $stmt = $this->db->prepare(
            "SELECT j.*, u.ankaya AS cashier_name,
                    s.shop_info_name, s.shop_info_addr, s.shop_tel_1
             FROM repair_job_list j
             LEFT JOIN sys_user u ON j.job_operator = u.myid
             LEFT JOIN sys_shop s ON j.job_inshop = s.shopid
             WHERE j.job_number = :job LIMIT 1"
        );
        $stmt->execute(['job' => $jobNumber]);
        return $stmt->fetch() ?: [];
    }

    // ── GRN Label Items ─────────────────────────────────────────────

    public function getGrnLabelItems(string $grnRefNo): array
    {
        $stmt = $this->db->prepare(
            "SELECT gi.item_name, gi.imei_no, gi.item_qty, gi.item_costpri, gi.item_sellpri,
                    g.grn_refno, g.suppler_name, g.operation_time
             FROM grn_temp_item gi
             LEFT JOIN grn_main g ON gi.grn_refno = g.grn_refno
             WHERE gi.grn_refno = :ref"
        );
        $stmt->execute(['ref' => $grnRefNo]);
        return $stmt->fetchAll();
    }

    // ── Transfer Note ───────────────────────────────────────────────

    public function getTransferNote(string $transId): array
    {
        $stmtMain = $this->db->prepare(
            "SELECT t.*, sf.shop_info_name AS from_shop, u.visibledata AS operator_name
             FROM stock_transmain t
             LEFT JOIN sys_shop sf ON t.trans_fromshop = sf.shopid
             LEFT JOIN sys_user u ON t.processed_operator = u.myid
             WHERE t.trans_id = :id LIMIT 1"
        );
        $stmtMain->execute(['id' => $transId]);
        $header = $stmtMain->fetch();

        $stmtItems = $this->db->prepare(
            "SELECT l.Item_name, l.code, l.stock_count, l.part_cost, l.transfer_value,
                    st.shop_info_name AS to_shop
             FROM stock_translog l
             LEFT JOIN sys_shop st ON l.to_shop = st.shopid
             WHERE l.trans_id = :id ORDER BY l.recorded_time ASC"
        );
        $stmtItems->execute(['id' => $transId]);
        $items = $stmtItems->fetchAll();

        return ['header' => $header, 'items' => $items];
    }

    // ── Shop info helper ────────────────────────────────────────────

    public function getShopSession(): array
    {
        return [
            'bill_printer'  => $_SESSION['PRNTER_NME_1'] ?? '',
            'label_printer' => $_SESSION['PRNTER_NME_2'] ?? '',
            'shop_address'  => $_SESSION['my_shop_address'] ?? '',
            'shop_tel_1'    => $_SESSION['my_shop_tel_1'] ?? '',
            'shop_name'     => $_SESSION['my_shop_name'] ?? '',
        ];
    }
}
