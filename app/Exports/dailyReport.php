<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class dailyReport implements FromCollection, WithMapping, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(int $spvId)
    {
        $this->spvId = $spvId;
    }

    public function collection()
    {
        $date_now = date('Y-m-d');
        $idSpv = $this->spvId;
        $user = \App\Models\User::leftJoin('orders', function ($q) use($date_now) {
                                    $q->on('users.id', '=', 'orders.user_id')
                                    ->where(function($qr) use ($date_now) {
                                        $qr->whereNotNull('customer_id')
                                        ->whereDate('orders.created_at',$date_now);
                                    });
                                })
                    ->whereHas('sls_exists',function ($query) use ($idSpv){
                        $query->where('spv_id',$idSpv);
                    })
                    ->get(['orders.customer_id AS customerId',
                            'orders.id AS orderId',
                            'orders.created_at AS orderCreate',
                            'users.name AS userName', 
                            'orders.status AS status']);
        
        return collect($user);
    }

    public function map($user) : array {
        
        $orders = $this->getOrder($user->orderId);
        
        //orders check
        if($orders){
            $storeCode = $orders->customers->store_code;
            $storeName = $orders->customers->store_name;
            $totalQuantity = $orders->totalQuantity;
        }else{
            $storeCode = '';
            $storeName = '';
            $totalQuantity = '';
        }

        return[
            $user->userName,
            $user->orderCreate,
            $storeCode,
            $storeName,
            $totalQuantity,
            $user->status
        ];
        
        
    }

    public function headings() : array {
        return [
            'Sales',
            'Date',
            'Cust-Code',
            'Customer',
            'Order Qty (Dus)',
            'Status',
        ] ;
    }

    function getOrder($id){
        $order = \App\Models\Order::with('products')->with('customers')
								->where('id',$id)->first();;
        
        return $order;
    }
}
