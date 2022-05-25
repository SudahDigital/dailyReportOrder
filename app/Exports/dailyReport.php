<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DatePeriod;
use DateInterval;
use DateTime;

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
        if($today == '0'){
            $user = \App\Models\User::whereHas('sls_exists',function ($query) use ($idSpv){
                            $query->where('spv_id',$idSpv);
                        })
                        ->get();
        }
        else{
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
        }
        return collect($user);
    }

    public function map($user) : array {
        $rows = [];
        $date_now = date('Y-m-d');
        $today = date('w', strtotime($date_now));
        if($today == '0'){
            //$rows = [];
            $begin = date('Y-m-d', strtotime('-6 day', strtotime($date_now)));
            $end = $date_now;
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod(new DateTime($begin), $interval, new DateTime($end));
            foreach ($period as $dt) {
                $orders = $this->getResumeOrder($user->id,$dt->format('Y-m-d'));
                //orders check
                if($orders){
                    $custTarget = $this->getResumeTargetItem($orders->customer_id,$dt->format('Y-m-d'));
                    $storeCode = $orders->customers->store_code;
                    $storeName = $orders->customers->store_name;
                    $totalQuantity = $orders->totalQuantity;
                    $dateOrder = date('F j, Y', strtotime($orders->created_at));
                    $tiemOrder = date('H:i', strtotime($orders->created_at));
                    $notes = '';
                    $status = $orders->status;
                    $userLoc = $orders->user_loc;
                }else{
                    $storeCode = '';
                    $storeName = '';
                    $totalQuantity = '';
                    $dateOrder = '';
                    $tiemOrder = '';
                    $custTarget = '';
                    $notes = 'Doesn\'t have order at '.$dt->format('Y-m-d');
                    $status = '';
                    $userLoc = '';
                }

                array_push($rows,[
                    $user->name,
                    $dateOrder,
                    $tiemOrder,
                    $userLoc,
                    $storeCode,
                    $storeName,
                    $totalQuantity,
                    $custTarget,
                    $status,
                    $notes
                ]);
            }
        }else{
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

            array_push($rows,[
                $user->userName,
                $dateOrder,
                $tiemOrder,
                $user->UserLoc,
                $storeCode,
                $storeName,
                $totalQuantity,
                $custTarget,
                $user->status
            ]);
        }
        return $rows;
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
            'Notes'
        ] ;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
   
                $event->sheet->getDelegate()->getStyle('A1:J1')
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
        $period_par = \App\Models\Store_Targets::where('customer_id',$customerId)
                    ->where('period','<=',$date_now)
                    ->max('period');
        if($period_par){
            $targetStore = \App\Models\Store_Targets::where('customer_id',$customerId)
                            ->where('period',$period_par)
                            ->first();
            if($targetStore){
                $totalQtyTarget = $targetStore->TotalQty;
            }else{
                $totalQtyTarget = ' ';
            }
        }else{
            $totalQtyTarget = '';
        }

        return $totalQtyTarget;
    }

    function getResumeOrder($userId,$date){
        $order = \App\Models\Order::with('products')->with('customers')
								->where('user_id',$userId)
                                ->whereNotNull('customer_id')
                                ->whereDate('orders.created_at',$date)
                                ->first();
        
        return $order;
    }

    function getResumeTargetItem($customerId,$date){
        //$client = \App\Models\Customer::findOrfail($customerId);
        $period_par = \App\Models\Store_Targets::where('customer_id',$customerId)
                    ->whereDate('period','<=',$date)
                    ->max('period');
        if($period_par){
            $targetStore = \App\Models\Store_Targets::where('customer_id',$customerId)
                            ->where('period',$period_par)
                            ->first();
            if($targetStore){
                $totalQtyTarget = $targetStore->TotalQty;
            }else{
                $totalQtyTarget = '';
            }
        }else{
            $totalQtyTarget = '';
        }

        return $totalQtyTarget;
    }
}
