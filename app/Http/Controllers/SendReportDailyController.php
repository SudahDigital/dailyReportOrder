<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\dailyReport;
use Maatwebsite\Excel\Facades\Excel;

class SendReportDailyController extends Controller
{
    public function index(){
        $userSpv = \App\Models\User::where('roles','SUPERVISOR')
                ->where('status','ACTIVE')
                ->get();
        //dd($userSpv);
        $dateNow = date('Y-m-d');
        //$dateString = date('d F Y', strtotime($dateNow));
        foreach($userSpv as $spv){
            $email_spv = $spv->email;
            $spvId = $spv->id;
            $spvName = $spv->name;
            $today = date('w', strtotime($dateNow));
            if($today == '0'){
                $beginDay = date('d', strtotime('-6 day', strtotime($dateNow)));
                $beginMonth = date('F', strtotime('-6 day', strtotime($dateNow)));
                $beginYear = date('Y', strtotime('-6 day', strtotime($dateNow)));

                $endDay = date('d', strtotime('-1 day', strtotime($dateNow)));
                $endMonth = date('F', strtotime('-1 day', strtotime($dateNow)));
                $endYear = date('Y', strtotime('-1 day', strtotime($dateNow)));

                $dateString = '('.$beginMonth.' '. $beginDay.', '.$beginYear.' until '.$endMonth.' '. $endDay.', '.$endYear.')' ;
                \Mail::send('summaryDailyMail',['spvName'=>$spvName,'dateString'=>$dateString], 
                        function ($message) use ($spvId,$email_spv,$dateString) {
                                $message->to($email_spv)
                                        ->cc('admin@sudahdigital.com')
                                        ->subject('Summary Daily Report '.$dateString);
                                        //->setBody($emailDetail, 'text/html');
                                $message->attach(
                                    Excel::download(
                                        new dailyReport($spvId), 
                                        'Summary Daily Report '.$dateString.'.xlsx'
                                    )->getFile(), ['as' => 'Summary Daily Report '.$dateString.'.xlsx']
                                );
                        });
            }else{
                $dateString = date('F d, Y', strtotime($dateNow));
                \Mail::send('dailyMail',['spvName'=>$spvName,'dateString'=>$dateString], 
                            function ($message) use ($spvId,$email_spv,$dateString) {
                                    $message->to($email_spv)
                                            ->cc('admin@sudahdigital.com')
                                            ->subject('Daily Report '.$dateString);
                                            //->setBody($emailDetail, 'text/html');
                                    $message->attach(
                                        Excel::download(
                                            new dailyReport($spvId), 
                                            'Daily Report '.$dateString.'.xlsx'
                                        )->getFile(), ['as' => 'Daily Report '.$dateString.'.xlsx']
                                    );
                });
            }
            
        }
    }

    /*public function test(){
        $userSpv = \App\Models\User::where('roles','SUPERVISOR')
                ->where('status','ACTIVE')
                ->first();
        $date_now = date('Y-m-d');
        $idSpv = $userSpv->id;
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

        dd($user);

        foreach($user as $usr){
            echo $usr->created_at;
        };
    }*/
}
