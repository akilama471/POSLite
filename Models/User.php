<?php

class User extends Model
{
    protected $table = "users";

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
