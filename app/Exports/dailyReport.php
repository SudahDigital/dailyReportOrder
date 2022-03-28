<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class dailyReport implements FromCollection, WithMapping, WithHeadings, WithEvents
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
                            'orders.user_loc AS UserLoc',
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
            $dateOrder = date('F j, Y', strtotime($user->orderCreate));
            $tiemOrder = date('H:i', strtotime($user->orderCreate));
        }else{
            $storeCode = '';
            $storeName = '';
            $totalQuantity = '';
            $dateOrder = '';
            $tiemOrder = '';
        }

        return[
            $user->userName,
            $dateOrder,
            $tiemOrder,
            $user->UserLoc,
            $storeCode,
            $storeName,
            $totalQuantity,
            $user->status
        ];
        
        
    }

    public function headings() : array {
        return [
            'Sales',
            'Order Date',
            'Order Time', 
            'ON/OFF Loc.',
            'Cust-Code',
            'Customer',
            'Order Qty (Dus)',
            'Status',
        ] ;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
   
                $event->sheet->getDelegate()->getStyle('A1:H1')
                                ->getFont()
                                ->setBold(true);
                $event->sheet->getDelegate()
                                ->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
   
            },
        ];
    }

    function getOrder($id){
        $order = \App\Models\Order::with('products')->with('customers')
								->where('id',$id)->first();;
        
        return $order;
    }
}
