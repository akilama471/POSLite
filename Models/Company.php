<?php

declare(strict_types=1);

class Company extends Model
{
    protected $table = "sys_company";

    public function primary(): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_company WHERE companyid = 1 LIMIT 1",
        );
        $stmt->execute();

        $company = $stmt->fetch();
        return $company ?: null;
    }
}
