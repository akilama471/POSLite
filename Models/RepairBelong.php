<?php

declare(strict_types=1);

class RepairBelong extends Model
{
    public function allActive(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM repair_belongs WHERE valid = 1 ORDER BY recordid ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM repair_belongs ORDER BY recordid ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $name): void
    {
        $stmt = $this->db->prepare("INSERT INTO repair_belongs (belong_name, valid) VALUES (:name, 1)");
        $stmt->execute(["name" => $name]);
    }

    public function update(int $id, string $name): void
    {
        $stmt = $this->db->prepare("UPDATE repair_belongs SET belong_name = :name WHERE recordid = :id");
        $stmt->execute(["name" => $name, "id" => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE repair_belongs SET valid = 0 WHERE recordid = :id");
        $stmt->execute(["id" => $id]);
    }

    public function getMappedBelongs(string $jobNumber): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.belong_name FROM repair_map_belong m
             JOIN repair_belongs b ON m.belong_id = b.recordid
             WHERE m.job_number = :job AND m.belong_val = 1"
        );
        $stmt->execute(["job" => $jobNumber]);
        return $stmt->fetchAll();
    }

    public function mapBelongs(string $jobNumber, array $belongIds): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO repair_map_belong (job_number, belong_id, belong_val, recorded_time)
             VALUES (:job, :belong_id, 1, :recorded_time)"
        );

        foreach ($belongIds as $id) {
            $stmt->execute([
                "job" => $jobNumber,
                "belong_id" => $id,
                "recorded_time" => $now,
            ]);
        }
    }
}
