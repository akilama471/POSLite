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

    public function listAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM sys_company ORDER BY id ASC");
        $companies = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $companies);
    }

    public function createCompany(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_company (company_name, company_address, company_phone, company_email)
             VALUES (:company_name, :company_address, :company_phone, :company_email)"
        );

        return $stmt->execute([
            "company_name" => $data["company_name"],
            "company_address" => $data["company_address"] ?? null,
            "company_phone" => $data["company_phone"] ?? null,
            "company_email" => $data["company_email"] ?? null,
        ]);
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_company WHERE company_name = :name"
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
