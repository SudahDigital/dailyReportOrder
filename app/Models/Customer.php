<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = "customers";

    public function users(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function orders(){
        return $this->hasMany(Order::class,'customer_id');
    }

    public function spv_sales(){
        return $this->belongsTo(Spv_sales::class,'user_id','sls_id');
    }

    public function store_targets(){
        return $this->hasMany(Store_Targets::class,'customer_id');
    }

}
