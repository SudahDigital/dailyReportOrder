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
                        ->where('users.status','ACTIVE')
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
                    ->where('users.status','ACTIVE')
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
        //dd($user);
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
                if($orders){
                    foreach($orders as $odr){
                            //$lastOrder = 
                            $storeCode = $odr->customers->store_code;
                            $storeName = $odr->customers->store_name;
                            $dateOrder = date('F j, Y', strtotime($odr->created_at));
                            $timeOrder = date('H:i', strtotime($odr->created_at));
                            $status = $odr->status;
                            $userLoc = $odr->user_loc;
                            if($odr->status == 'NO-ORDER'){
                                $notes = $odr->notes_no_order;
                            }else if($odr->status == 'CANCEL'){
                                $notes = $odr->notes_cancel;
                            }else{
                                $notes = $odr->notes;
                            }

                        foreach($odr->products as $op){
                            [$custTarget,$targetItem]= $this->getResumeTargetItem($odr->customer_id,$op->pivot->product_id,$dt->format('Y-m-d'));
                            
                            $qty = $op->pivot->quantity;
                            
                            if($odr->status == 'NO-ORDER'){
                                $product_name = '';
                            }else{
                                $product_name = $op->Product_name;
                            }
                            array_push($rows,[
                                $user->name,
                                $dateOrder,
                                $timeOrder,
                                $userLoc,
                                $storeCode,
                                $storeName,
                                $product_name,
                                $qty,
                                $op->pivot->paket_id,
                                $op->pivot->group_id,
                                $op->pivot->bonus_cat,
                                $targetItem,
                                $custTarget,
                                $status,
                                $notes
                            ]);
                        }
                    }
                }else{
                        $storeCode = '';
                        $storeName = '';
                        //$totalQuantity = '';
                        $product_name = '';
                        $qty = '';
                        $paket_id = '';
                        $bonus_id = '';
                        $bonus_item = '';
                        $dateOrder = '';
                        $timeOrder = '';
                        $custTarget = '';
                        $targetItem = '';
                        $notes = 'Doesn\'t have record at '.$dt->format('Y-m-d');
                        $status = '';
                        $userLoc = '';

                        
                        array_push($rows,[
                            $user->name,
                            $dateOrder,
                            $timeOrder,
                            $userLoc,
                            $storeCode,
                            $storeName,
                            $product_name,
                            $qty,
                            $paket_id,
                            $bonus_id,
                            $bonus_item,
                            $targetItem,
                            $custTarget,
                            $status,
                            $notes
                        ]);
                    }
                }
            
        }else{
            $orders = $this->getOrder($user->orderId);
        
            //orders check
            if($orders){
                
                $storeCode = $orders->customers->store_code;
                $storeName = $orders->customers->store_name;
                //$totalQuantity = $orders->totalQuantity;
                $dateOrder = date('F j, Y', strtotime($user->orderCreate));
                $timeOrder = date('H:i', strtotime($user->orderCreate));
                if($user->status == 'NO-ORDER'){
                    $notes = $orders->notes_no_order;
                }else if($user->status == 'CANCEL'){
                    $notes = $orders->notes_cancel;
                }else{
                    $notes = $orders->notes;
                }
                foreach($orders->products as $op){
                    [$custTarget,$targetItem] = $this->getTargetItem($user->customerId, $op->pivot->product_id);
                    $qty = $op->pivot->quantity;
                    if($user->status == 'NO-ORDER'){
                        $product_name = '';
                    }else{
                        $product_name = $op->Product_name;
                    }

                    array_push($rows,[
                        $user->userName,
                        $dateOrder,
                        $timeOrder,
                        $user->UserLoc,
                        $storeCode,
                        $storeName,
                        $product_name,
                        $qty,
                        $op->pivot->paket_id,
                        $op->pivot->group_id,
                        $op->pivot->bonus_cat,
                        $targetItem,
                        $custTarget,
                        $user->status,
                        $notes
                    ]);
                }
                
            }else{
                $storeCode = '';
                $storeName = '';
                //$totalQuantity = '';
                $dateOrder = '';
                $timeOrder = '';
                $custTarget = '';

                $storeCode = '';
                $storeName = '';
                //$totalQuantity = '';
                $product_name = '';
                $qty = '';
                $paket_id = '';
                $bonus_id = '';
                $bonus_item = '';
                $dateOrder = '';
                $timeOrder = '';
                $custTarget = '';
                $targetItem = '';
                $notes = 'Doesn\'t have record';
                $status = '';
                $userLoc = '';

                array_push($rows,[
                    $user->userName,
                    $dateOrder,
                    $timeOrder,
                    $user->UserLoc,
                    $storeCode,
                    $storeName,
                    $product_name,
                    $qty,
                    $paket_id,
                    $bonus_id,
                    $bonus_item,
                    $targetItem,
                    $custTarget,
                    $status,
                    $notes
                ]);
            }
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
            'Products',
            'Order Qty (Dus)',
            'Paket Id',
            'Bonus Id',
            'Bonus Item',
            'Target Item',
            'Total Customer Target (Dus)',
            //'Last Order',
            'Status',
            'Notes'
        ] ;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
   
                $event->sheet->getDelegate()->getStyle('A1:O1')
                                ->getFont()
                                ->setBold(true);
                $event->sheet->getDelegate()
                                ->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
   
            },
        ];
    }

    function getOrder($id){
        $order = \App\Models\Order::with('products')->with('customers')
								->where('id',$id)->first();
        
        return $order;
    }

    function getTargetItem($customerId,$itemId){
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
                $targetProducts = \App\Models\ProductTarget::where('storeTargetId',$targetStore->id)
                                ->where('productId',$itemId)
                                ->first();
                if($targetProducts){
                    $targetItem = $targetProducts->quantityValues;
                }else{
                    $targetItem = '';
                }
            }else{
                $totalQtyTarget = ' ';
                $targetItem = '';
            }
        }else{
            $totalQtyTarget = '';
            $targetItem = '';
        }

        return [$totalQtyTarget,$targetItem];
    }

    function getResumeOrder($userId,$date){
        $order = \App\Models\Order::where('user_id',$userId)
                                ->whereNotNull('customer_id')
                                ->whereDate('created_at',$date)
                                ->get();
        if($order->count() > 0){
            return $order;
        }else{
            return null;
        }

        
        //return $order;
    }

    function getResumeTargetItem($customerId,$itemId,$date){
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
                $targetProducts = \App\Models\ProductTarget::where('storeTargetId',$targetStore->id)
                                ->where('productId',$itemId)
                                ->first();
                if($targetProducts){
                    $targetItem = $targetProducts->quantityValues;
                }else{
                    $targetItem = '';
                }
            }else{
                $totalQtyTarget = '';
                $targetItem = '';
            }
        }else{
            $totalQtyTarget = '';
            $targetItem = '';
        }

        return [$totalQtyTarget,$targetItem];
    }
}
