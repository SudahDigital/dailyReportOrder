<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DatePeriod;
use DateInterval;

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
        $today = date('w', strtotime($date_now));
        if($today == 0){
            //$yesterday   = date('Y-m-d', strtotime("-1 day", $date_now));
            $begin = date('Y-m-d', strtotime("-6 day", $date_now));
            $end = $date_now;
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);
            foreach ($period as $dt) {
                $dateSelect = $dt->format('Y-m-d');
                $user = \App\Models\User::leftJoin('orders', function ($q) use($dateSelect) {
                                $q->on('users.id', '=', 'orders.user_id')
                                        ->where(function($qr) use ($dateSelect) {
                                            $qr->whereNotNull('customer_id')
                                            ->whereDate('orders.created_at',$dateSelect);
                                            //->whereBetween('orders.created_at', [$dateWeekAgo, $yesterday]);
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
            
        }else{
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
                
        
    }

    public function map($user) : array {
        
        $orders = $this->getOrder($user->orderId);
        
        //orders check
        if($orders){
            $custTarget = $this->getTargetItem($user->customerId);
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
            $custTarget = '';
        }

        return[
            $user->userName,
            $dateOrder,
            $tiemOrder,
            $user->UserLoc,
            $storeCode,
            $storeName,
            $totalQuantity,
            $custTarget,
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
            'Total Target (Dus)',
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

    function getTargetItem($customerId){
        $date_now = date('Y-m-d');
        $client = \App\Models\Customer::findOrfail($customerId);
        $period_par = \App\Models\Store_Targets::where('client_id',$client->id)
                    ->where('period','<=',$date_now)
                    ->max('period');
        if($period_par){
            $targetStore = \App\Models\Store_Targets::where('customer_id',$customerId)
                            ->where('period',$period_par)
                            ->first();
            if($targetStore){
                $totalQtyTarget = $targetStore->TotalQty;
            }else{
                $totalQtyTarget = 'Doesn\'t have target';
            }
        }else{
            $totalQtyTarget = 'Doesn\'t have target';
        }

        return $totalQtyTarget;
    }


}
