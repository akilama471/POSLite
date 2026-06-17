<?php

declare(strict_types=1);

class Company extends Model
{
    protected $table = "sys_company";

    private function mapLegacyFields(?array $company): ?array
    {
        if ($company === null) {
            return null;
        }

        if (isset($company['id']) && !isset($company['companyid'])) {
            $company['companyid'] = $company['id'];
        }

        return $company;
    }

    public function primary(): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_company WHERE id = 1 LIMIT 1",
        );
        $stmt->execute();

        $company = $stmt->fetch();
        return $this->mapLegacyFields($company ?: null);
    }
}
