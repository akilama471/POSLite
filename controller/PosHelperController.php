<?php

declare(strict_types=1);

class PosHelperController
{
    public function customerLookup(Request $request): void
    {
        $customerModel = new Customer();
        $customers = $customerModel->activeLookup(
            trim((string) $request->input("js_inp_cusname", "")),
            trim((string) $request->input("js_inp_cusmobi", "")),
        );

        if ($customers === []) {
            json_response([["0"], ["Cash Customer"], [""]]);
        }

        $ids = [];
        $names = [];
        $mobiles = [];

        foreach ($customers as $customer) {
            $ids[] = (string) $customer["recordid"];
            $names[] = (string) $customer["cus_name"];
            $mobiles[] = (string) ($customer["cus_mobile"] ?? "");
        }

        json_response([$ids, $names, $mobiles]);
    }

    public function itemLookupByName(Request $request): void
    {
        $itemModel = new Item();
        $auth = auth_user() ?? [];

        json_response(
            $itemModel->posLookupByName(
                trim((string) $request->input("js_inp_item", "")),
                (int) ($auth["shop_id"] ?? 0),
            ),
        );
    }

    public function itemLookupByCode(Request $request): void
    {
        $itemModel = new Item();
        $auth = auth_user() ?? [];

        json_response(
            $itemModel->posLookupByCode(
                trim((string) $request->input("js_inp_itemcode", "")),
                (int) ($auth["shop_id"] ?? 0),
            ),
        );
    }
}
