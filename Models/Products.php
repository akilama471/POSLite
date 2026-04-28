<?php

class Product extends Model
{
    protected $table = "products";

    public function category()
    {
        return $this->belongsTo("categories", "category_id");
    }
}
