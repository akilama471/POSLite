<?php declare(strict_types=1); ?>

<div style="margin-bottom: 20px; text-align: right;">
    <button class="btn btn-info" onclick="window.print();" style="margin-top: 10px; margin-right: 5%;">
        Print Document
    </button>
</div>

<div id="print-area" style="min-width: 21cm; min-height: 29.7cm; position: relative; margin-top: 5vh; background-color: #fff; padding: 20px;">
    <div id="headding" style="width: 80%; margin-left: 10%; height: 10vh;">
        <div id="div_company_name" style="font-size: 25px; text-align: center; font-weight: bold; margin-top: 3vh;">
            <?= htmlspecialchars($company["companyname"] ?? "") ?>
        </div>
        <div id="div_company_addr" style="font-size: 18px; text-align: center;">
            <?= htmlspecialchars($company["companyaddress"] ?? "") ?>
        </div>
        <div id="div_company_tpno" style="font-size: 18px; text-align: center;">
            <?php if (($company["company_tel_1"] ?? "") !== ""): ?>
                TP: <?= htmlspecialchars($company["company_tel_1"]) ?>
            <?php endif; ?>
            <?php if (($company["company_fax"] ?? "") !== ""): ?>
                Mobile: <?= htmlspecialchars($company["company_fax"]) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <hr style="border-top: 2px solid; margin-top: 20px; margin-bottom: 20px;">
    
    <div id="job-info" style="width: 90%; margin-left: 5%; height: 25vh;">
        <div id="div_company_name" style="font-size: 20px; text-align: center; margin-bottom: 1vh;">
            <label><u>Transfer Stock Note</u></label>
        </div>
        <div style="font-size: 12px; width: 25%; float: left; text-align: right;">
            <label style="font-weight: bold;">Transfer Job ID: </label><br>
            <label style="font-weight: bold;">Job Created User:</label><br>
            <label style="font-weight: bold;">Job Created Time:</label><br>
            <label style="font-weight: bold;">Transfering Shop:</label><br>
            <label style="font-weight: bold;">Transfer Handle By:</label><br>
            <label style="font-weight: bold;">Transfer Start Time:</label><br>
        </div>
        <div style="font-size: 12px; width: 73%; float: right; text-align: left; padding-left: 10px;">
            <label><?= htmlspecialchars($transfer["trans_id"] ?? "") ?></label><br>
            <label><?= htmlspecialchars($transfer["processed_operator_name"] ?? "User Not Found") ?></label><br>
            <label><?= htmlspecialchars($transfer["record_time"] ?? "") ?></label><br>
            <label><?= htmlspecialchars($transfer["from_shop_name"] ?? "Shop Not Found") ?></label><br>
            <label><?= htmlspecialchars($handlerName ?? "User Not Found") ?></label><br>
            <label><?= htmlspecialchars($transfer["sending_time"] ?? "") ?></label><br>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div id="infomation" style="width: 90%; margin-left: 5%; min-height: 25vh; font-size: 12px;">
        <?php if (!empty($transfer["logs"])): ?>
            <table style="width: 100%; border-collapse: collapse; font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif; font-size: 13px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Item Name</th>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Item Code</th>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Transfer From</th>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Transfer To</th>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Qty</th>
                        <th style="border: 1px solid #ddd; padding: 5px; text-align: center; background-color: #878A86; color: white;">Billed Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transfer["logs"] as $log): ?>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 30%; text-align: center;"><?= htmlspecialchars($log["Item_name"] ?? "") ?></td>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 15%; text-align: center;"><?= htmlspecialchars($log["code"] ?? "") ?></td>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 20%; text-align: center;"><?= htmlspecialchars($log["from_shop_name"] ?? "Shop Not Found") ?></td>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 15%; text-align: center;"><?= htmlspecialchars($log["to_shop_name"] ?? "Shop Not Found") ?></td>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 10%; text-align: center;"><?= htmlspecialchars($log["stock_count"] ?? "") ?></td>
                            <td style="border: 1px solid #ddd; padding: 3px; width: 10%; text-align: center;"><?= htmlspecialchars($log["transfer_value"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <label>Sorry. No related data found with this Transfer ID.</label>
        <?php endif; ?>
    </div>
    
    <div id="signing" style="width: 90%; margin-left: 5%; font-size: 12px; text-align: center; margin-top: 30px;">
        <div style="text-align: left;">
            <label>Transfer Shop:</label><br>
            <label>Person dispatched the Items:.....................................................</label>
            <label style="float: right;">ID No:......................................</label>
            <br><br>
            <label>Sign of dispatched Person  :.....................................................</label>
            <label style="float: right;">Date:......................................</label>
        </div>
        <div style="text-align: left; margin-top: 20px;">
            <label>Transfer Person:</label><br>
            <label>Person transfering the Items:.....................................................</label>
            <label style="float: right;">ID No:......................................</label>
            <br><br>
            <label>Sign of transfering Person  :.....................................................</label>
            <label style="float: right;">Date:......................................</label>
        </div>
        <div style="text-align: left; margin-top: 20px;">
            <label>Received Shop:</label><br>
            <label>Person received the Items:.....................................................</label>
            <label style="float: right;">ID No:......................................</label>
            <br><br>
            <label>Sign of received Person  :.....................................................</label>
            <label style="float: right;">Date:......................................</label>
        </div>
        <br><br>
        <label>After cheking documents please approve the receied stocks from system.</label><br>
        <label>*****This is sample format of transfer note.*****</label>
    </div>
</div>

<style>
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    .btn {
        display: none !important;
    }
    #print-area {
        margin-top: 0 !important;
        padding: 0 !important;
    }
}
</style>
