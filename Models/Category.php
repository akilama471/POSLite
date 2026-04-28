<?php

class Category extends Model
{
    protected $table = "categories";

    public function products()
    {
        return $this->hasMany("products", "category_id");
    }
}
