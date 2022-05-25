<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTarget extends Model
{
    use HasFactory;

    public function store_targets()
    {
    	return $this->belongsTo(Store_Targets::class,'storeTargetId');
    }

    public function products(){
        return $this->belongsTo(product::class,'productId');
    }
    
}
