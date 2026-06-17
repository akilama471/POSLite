<?php

declare(strict_types=1);

class PrintController
{
    private function shopInfo(): array
    {
        $m = new PrintModel();
        return $m->getShopSession();
    }

    // ── POS Invoice Print ────────────────────────────────────────────

    public function invoice(Request $request): void
    {
        $billNumber = trim((string) $request->input('docid', ''));
        $slotId     = trim((string) $request->input('slotid', ''));

        if (!$billNumber) {
            http_response_code(400);
            echo 'Missing bill number.';
            return;
        }

        $model = new PrintModel();
        $data  = $model->getInvoiceData($billNumber);

        if (!$data['header']) {
            http_response_code(404);
            echo 'Bill not found.';
            return;
        }

        View::make('print/invoice', [
            'title'      => 'Print Invoice',
            'bill'       => $data['header'],
            'items'      => $data['items'],
            'billNumber' => $billNumber,
            'slotId'     => $slotId,
            'shop'       => $this->shopInfo(),
        ]);
    }

    // ── Barcode / Warranty Label Print ───────────────────────────────

    public function barcode(Request $request): void
    {
        $billNumber = trim((string) $request->input('docid', ''));

        if (!$billNumber) {
            http_response_code(400);
            echo 'Missing bill number.';
            return;
        }

        $model = new PrintModel();
        $items = $model->getBillBarcodeItems($billNumber);

        View::make('print/barcode', [
            'title'      => 'Barcode / Warranty Labels',
            'items'      => $items,
            'billNumber' => $billNumber,
            'shop'       => $this->shopInfo(),
        ]);
    }

    // ── Customer Payment Receipt ─────────────────────────────────────

    public function customerPayment(Request $request): void
    {
        $customerId  = (int) $request->input('cusid', 0);
        $payType     = (int) $request->input('paytype', 1);
        $amount      = (float) $request->input('amtpay', 0);
        $chequeNo    = trim((string) $request->input('chqunmber', ''));

        $model    = new PrintModel();
        $customer = $model->getCustomerPayReceipt($customerId);

        View::make('print/customer_payment', [
            'title'    => 'Customer Payment Receipt',
            'customer' => $customer,
            'payType'  => $payType,
            'amount'   => $amount,
            'chequeNo' => $chequeNo,
            'shop'     => $this->shopInfo(),
        ]);
    }

    // ── Supplier Payment Receipt ─────────────────────────────────────

    public function supplierPayment(Request $request): void
    {
        $supplierId = (int) $request->input('supid', 0);
        $payType    = (int) $request->input('paytype', 1);
        $amount     = (float) $request->input('amtpay', 0);
        $chequeNo   = trim((string) $request->input('chqunmber', ''));

        $model    = new PrintModel();
        $supplier = $model->getSupplierPayReceipt($supplierId);

        View::make('print/supplier_payment', [
            'title'    => 'Supplier Payment Receipt',
            'supplier' => $supplier,
            'payType'  => $payType,
            'amount'   => $amount,
            'chequeNo' => $chequeNo,
            'shop'     => $this->shopInfo(),
        ]);
    }

    // ── Repair Job Bill ──────────────────────────────────────────────

    public function repairBill(Request $request): void
    {
        $jobNumber = trim((string) $request->input('docid', ''));
        $returnTo  = trim((string) $request->input('opr', ''));

        if (!$jobNumber) {
            http_response_code(400);
            echo 'Missing job number.';
            return;
        }

        $model = new PrintModel();
        $job   = $model->getRepairJobReceipt($jobNumber);

        View::make('print/repair_bill', [
            'title'     => 'Repair Bill',
            'job'       => $job,
            'jobNumber' => $jobNumber,
            'returnTo'  => $returnTo,
            'shop'      => $this->shopInfo(),
        ]);
    }

    // ── GRN Label Print ──────────────────────────────────────────────

    public function grnLabel(Request $request): void
    {
        $grnRefNo = trim((string) $request->input('docid', ''));

        if (!$grnRefNo) {
            http_response_code(400);
            echo 'Missing GRN reference.';
            return;
        }

        $model = new PrintModel();
        $items = $model->getGrnLabelItems($grnRefNo);

        View::make('print/grn_label', [
            'title'    => 'GRN Barcode Labels',
            'items'    => $items,
            'grnRefNo' => $grnRefNo,
            'shop'     => $this->shopInfo(),
        ]);
    }

    // ── Transfer Note Print ───────────────────────────────────────────

    public function transferNote(Request $request): void
    {
        $transId = trim((string) $request->input('docid', ''));

        if (!$transId) {
            http_response_code(400);
            echo 'Missing transfer ID.';
            return;
        }

        $model = new PrintModel();
        $data  = $model->getTransferNote($transId);

        View::make('print/transfer_note', [
            'title'   => 'Transfer Note',
            'header'  => $data['header'],
            'items'   => $data['items'],
            'transId' => $transId,
            'shop'    => $this->shopInfo(),
        ]);
    }
}
