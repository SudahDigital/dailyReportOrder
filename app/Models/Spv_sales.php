<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spv_sales extends Model
{
    use HasFactory;

    protected $table = 'spv_sales';
    protected $fillable = ['spv_id', 
                            'sls_id',
                            'status',
                            'created_at',
                            'updated_at'
                          ];

    public function customer(){
      return $this->belongsTo(Customer::class,'sls_id');
    }

    public function orders(){
      return $this->belongsTo(Order::class,'sls_id');
    }

    public function sales(){
      return $this->belongsTo(User::class,'sls_id');
    }

    public function spvUser(){
      return $this->belongsTo(User::class,'spv_id');
    }
}
