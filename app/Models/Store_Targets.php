<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store_Targets extends Model
{
    use HasFactory;

    protected $table = "store_target";
    
    protected $fillable = [
        'client_id',
        'customer_id', 
        'target_values', 
        'target_achievement',
        'period',
        'created_by',
        'updated_by',
        'version_pareto',
        'target_type',
        'target_quantity'
    ];

    public function customers(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function product_target()
    {
    	return $this->hasMany(ProductTarget::class,'storeTargetId');
    }

    public function getTotalNominalAttribute(){
        $total_nominal = 0;
        foreach($this->product_target as $n){
            $total_nominal += $n->nominalValues;
        }
        return $total_nominal;
    }

    public function getTotalQtyAttribute(){
        $totalQty = 0;
        foreach($this->product_target as $q){
            $totalQty += $q->quantityValues;
        }
        return $totalQty;
    }
}
