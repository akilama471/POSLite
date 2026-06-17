<?php

class Model
{
    protected $table;
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // Get all records
    public function all()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Find by ID
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ======================
           RELATIONSHIPS
        ======================= */

    // hasMany
    protected function hasMany(
        $relatedModel,
        $foreignKey,
        $localKey = "id",
        $value = null,
    ) {
        $value = $value ?? $this->{$localKey};

        $stmt = $this->db->prepare(
            "SELECT * FROM {$relatedModel} WHERE {$foreignKey} = ?",
        );

        $stmt->execute([$value]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // belongsTo
    protected function belongsTo($relatedModel, $foreignKey, $ownerKey = "id")
    {
        $foreignValue = $this->{$foreignKey} ?? null;

        $stmt = $this->db->prepare(
            "SELECT * FROM {$relatedModel} WHERE {$ownerKey} = ?",
        );

        $stmt->execute([$foreignValue]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ======================
       QUERY BUILDER
    ====================== */

    protected $query = "";
    protected $bindings = [];

    public function select($columns = "*")
    {
        $this->query = "SELECT $columns FROM {$this->table}";
        return $this;
    }

    public function where($column, $operator, $value)
    {
        $this->query .= " WHERE $column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function andWhere($column, $operator, $value)
    {
        $this->query .= " AND $column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function get()
    {
        $stmt = $this->db->prepare($this->query);
        $stmt->execute($this->bindings);

        // reset
        $this->query = "";
        $this->bindings = [];

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
