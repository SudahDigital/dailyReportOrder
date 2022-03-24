<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function products(){
        return $this->belongsToMany(product::class)->withPivot('id','quantity','price_item',
                'price_item_promo','vol_disc_price','discount_item','group_id','paket_id','bonus_cat',
                'available','preorder','deliveryQty');
    }

    public function products_nonpaket(){
        return $this->belongsToMany(product::class)
        ->withPivot('id','quantity','price_item','price_item_promo','vol_disc_price',
                    'discount_item','group_id','paket_id','bonus_cat','available','preorder','deliveryQty')
        ->wherePivot('paket_id',null)
        ->wherePivot('group_id',null);
    }

    public function products_pkt(){
        return $this->belongsToMany(product::class)
        ->withPivot('id','quantity','price_item','price_item_promo','vol_disc_price',
        'discount_item','group_id','paket_id','bonus_cat','available','preorder','deliveryQty')
        ->wherePivot('paket_id','!=',null)
        ->wherePivot('group_id','!=',null)
        ->wherePivot('bonus_cat','=',null);
    }

    public function products_bns(){
        return $this->belongsToMany(product::class)
        ->withPivot('id','quantity','price_item','price_item_promo','vol_disc_price',
        'discount_item','group_id','paket_id','bonus_cat','available','preorder','deliveryQty')
        ->wherePivot('group_id','!=',null)
        ->wherePivot('bonus_cat','!=',null);
    }

    public function products_pktbns(){
        return $this->belongsToMany(product::class)
        ->withPivot('id','quantity','price_item','price_item_promo','vol_disc_price',
        'discount_item','group_id','paket_id','bonus_cat','available','preorder','deliveryQty')
        ->wherePivot('paket_id','!=',null)
        ->wherePivot('group_id','!=',null);
    }

    public function customers(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function users(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function canceledBy(){
        return $this->belongsTo(User::class,'canceled_by','id');
    }

    public function spv_sales(){
        return $this->belongsTo(Spv_sales::class,'user_id','sls_id');
    }

    public function getTotalQuantityAttribute(){
        $total_quantity = 0;
        foreach($this->products as $p){
        $total_quantity += $p->pivot->quantity;
        }
        return $total_quantity;
    }

    public function getTotalDeliveryAttribute(){
        $total_delivery = 0;
        foreach($this->products as $p){
            $total_delivery += $p->pivot->deliveryQty;
        }
        return $total_delivery;
    }

    public function getTotalPreorderAttribute(){
        $total_preorder = 0;
        foreach($this->products as $p){
            $total_preorder += $p->pivot->preorder;
        }
        return $total_preorder;
    }

    public function getTotalNominalAttribute(){
        $total_nominal = 0;
        foreach($this->products as $p){
        $total_nominal += $p->pivot->price_item_promo;
        }
        return $total_nominal;
    }

    public function getTotalQtyDiscVolumeAttribute(){
        $total = 0;
        foreach($this->products_nonpaket as $p){
        $total += $p->pivot->quantity;
        }
        return $total;
    }

    public function getTotalQtyPaketAttribute(){
        $total = 0;
        foreach($this->products_pktbns as $p){
        $total += $p->pivot->quantity;
        }
        return $total;
    }
}
