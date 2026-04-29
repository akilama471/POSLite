<?php

declare(strict_types=1);

class RechargeCard extends Model
{
    protected $table = "shop_rcv_cards";

    public function findByProductId(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT card.*, operator.operator_name
             FROM shop_rcv_cards AS card
             LEFT JOIN shop_rcv_operator AS operator
               ON operator.recordid = card.operator
             WHERE card.prod_id = :prod_id
             LIMIT 1",
        );
        $stmt->execute(["prod_id" => $productId]);

        $card = $stmt->fetch();
        return $card ?: null;
    }
}
