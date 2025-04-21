<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
     public function addSubscription( Request $request ) {
       try
        {
            
         
            $validator = Validator::make($request->all(), [ 
                'transaction_id'     => 'required',   
                'plan'  => 'required',
                'type'       => 'required',
            ]);
    
            if ( $validator->fails() ) {
                
                $response=[
                    "status"=>parent::statusCode('error'),
                    "message"=>$validator->errors()->first()
                ];
                return parent::sendResponse($response);
            }
            
            $user=Auth::user();
            $userID=$user->id;
            $startDate=date('Y-m-d H:i:s');
            $payload=$request->all();
            $payload['start_date']=$startDate;
            $payload['user_id']=$userID;
            $check=$this->subscription::where(['user_id'=>$userID,'is_cancel'=>0])->first();
            if($check)
            {
                $response=[
                    'status'=>parent::statusCode('error'),
                    'message' => "You are already subscribed to a subscription"
                ];
            }
            else
            {

                $subscription=$this->subscription::create($payload);
                if($subscription)
                {   
                    
                    $arr=['subscriptionStatus'=>1,'plan'=>$request->plan,'plan_type'=>$request->type,'isCancel'=>0,'step'=>4];
                    if(empty($user->firstSubscriptionDate) && $user->is_subscription_mail==0){

                     $arr['firstSubscriptionDate']=date('Y-m-d');
 
                    }
                    $this->user::where(['id'=>$userID])->update($arr);
                    
                    $response=[
                        'status'=>parent::statusCode('success'),
                        'message' => "Subscription added successfully"
                    ];
                }
                else
                {
                    $response=[
                        'status'=>parent::statusCode('error'),
                        'message' => "Something went wrong. Please try again"
                    ];
                }
            }
        }
        catch(\Exception $e)
        {
            $response=[
                "status"=>parent::statusCode('error'),
                "message"=>$e->getMessage()
            ];
        }
        return parent::sendResponse($response);
    }
    
    public function androidSubscription(Request $request)
	{
	    try {
	        $data = file_get_contents("php://input");
	        if ( ! empty ( $data ) ) {
	            $res1 = json_decode( $data );
                $res2 = json_decode(base64_decode($res1->message->data));
                if ( isset ( $res1->message->data ) ) {
                    $res2 = json_decode( base64_decode( $res1->message->data ) );
                    $cancel_types = array (12,13); // don't delete entry in case of 3
                    if ( isset ( $res2->subscriptionNotification ) ) {
                        $res = $res2->subscriptionNotification;
                        if ( in_array ( $res->notificationType, $cancel_types ) ) {
                            $purchaseToken = $res->purchaseToken;
                            $row = $this->subscription::select('user_id')->where(['transaction_id' => $purchaseToken])->first();
                            if ($row) {
                               
                    			$userID    = $row['user_id'];
                    			$updateUser = $this->user::where(['id' => $userID])->update(['subscriptionStatus' => 0,'plan' => null,'plan_type' => null,'isCancel' => 1,"updated_at" => date('Y-m-d H:i:s')]);
        		                $updateUser ? $this->subscription::where(['user_id' => $userID])->delete() : "";
        		                
        		                $user=$this->user::where('id',$userID)-first();
                               
        		                $arr=[
                                    'user_id'=>$user->id,
                                    'email'=>$user->email,
                                    'data'=>$data
                                    ];
                                $this->webhook::create($arr);
                            }
                        }
                    }
                }
            }
	    }
	    catch(\Exception $e)
	    {
	        $res2 = ["success" => 0, "message" => $e->getMessage()];  
	        return parent::sendResponse($res2);
	    }
	    
	}
	
    // 	public function androidSubscription(Request $request)
    // {
    //     try {
    //         $data = file_get_contents("php://input");
    
    //         if (!empty($data)) {
    //             $res1 = json_decode($data);
    
    //             if (isset($res1->message->data)) {
    //                 $res2 = json_decode(base64_decode($res1->message->data));
    
    //                 if (isset($res2->subscriptionNotification)) {
    //                     $res = $res2->subscriptionNotification;
    //                     $purchaseToken = $res->purchaseToken;
    
    //                     // Find subscription
    //                     $subscription = $this->subscription::where('transaction_id', $purchaseToken)->first();
    
    //                     if ($res->notificationType == 1) { // Subscription renewed
    //                         if ($subscription) {
    //                             $this->user::where('id', $subscription->user_id)
    //                                 ->update(['subscriptionStatus' => 1, 'isCancel' => 0]);
    //                         }
    //                     } else if ($res->notificationType == 12 || $res->notificationType == 13) { // Subscription canceled
    //                         if ($subscription) {
    //                             $userID = $subscription->user_id;
    //                             $this->user::where('id', $userID)
    //                                 ->update(['subscriptionStatus' => 0, 'isCancel' => 1]);
    
    //                             $this->subscription::where('user_id', $userID)->delete();
    //                         }
    //                     }
    
    //                     // Log webhook event
    //                     $this->webhook::create([
    //                         'user_id' => $subscription->user_id ?? null,
    //                         'email' => $this->user::find($subscription->user_id)->email ?? 'Unknown',
    //                         'data' => $data
    //                     ]);
    //                 }
    //             }
    //         }
    //     } catch (Exception $e) {
    //         return response()->json(["success" => 0, "message" => $e->getMessage()], 500);
    //     }
    // }
	
	
	public function iosSubscription() 
	{
	    try{
	        $data = file_get_contents("php://input");
	       
            if ( ! empty ( $data ) ) {
                $event = json_decode( $data );
                $cancel_types = array ( 'CANCEL', 'DID_FAIL_TO_RENEW', 'REFUND', 'REVOKE' ); // DID_CHANGE_RENEWAL_STATUS Renewal case
                if ( in_array ( $event->notification_type, $cancel_types ) ) {
                    $purchaseToken = $event->original_transaction_id;
                    $row = $this->subscription::select('user_id')->where(['transaction_id' => $purchaseToken])->first();
                    if ($row) {
            			$userID    = $row['user_id'];
            		    $updateUser = $this->user::where(['id' => $userID])->update(['subscriptionStatus' => 0,'isCancel' => 1,"updated_at" => date('Y-m-d H:i:s')]);
        		        $updateUser ? $this->subscription::where(['user_id' => $userID])->delete() : "";
        		        
        		        $user=$this->user::where('id',$userID)-first();
                               
		                $arr=[
                            'user_id'=>$user->id,
                            'email'=>$user->email,
                            'data'=>$data
                            ];
                        $this->webhook::create($arr);
                    }
                }
            }
	    }
	    catch(\Exception $e){
	        return response()->json(["success" => 0, "message" => $e->getMessage()], 500);
	    }
	}
	
// 	public function iosSubscription() 
//     {
//         try {
//             $data = file_get_contents("php://input");
    
//             if (!empty($data)) {
//                 $event = json_decode($data);
//                 $purchaseToken = $event->original_transaction_id;
    
//                 // Fetch user subscription
//                 $subscription = $this->subscription::where('transaction_id', $purchaseToken)->first();
//                 $userID = $subscription->user_id ?? null;
    
//                 if ($userID) {
//                     if ($event->notification_type == 'DID_RENEW') { 
                        
//                         $this->user::where('id', $userID)->update(['subscriptionStatus' => 1, 'isCancel' => 0]);
    
//                     } else if ($event->notification_type == 'DID_CHANGE_RENEWAL_STATUS') {
                      
//                         $this->user::where('id', $userID)->update(['isCancel' => $event->auto_renew_status ? 0 : 1]);
    
//                     } else if (in_array($event->notification_type, ['CANCEL', 'DID_FAIL_TO_RENEW', 'REFUND', 'REVOKE'])) {
                       
//                         $this->user::where('id', $userID)->update(['subscriptionStatus' => 0, 'isCancel' => 1]);
//                         $this->subscription::where('user_id', $userID)->delete();
//                     }
    
                    
//                     $this->webhook::create([
//                         'user_id' => $userID,
//                         'email' => $this->user::find($userID)->email ?? 'Unknown',
//                         'data' => $data
//                     ]);
//                 }
//             }
//         } catch (\Exception $e) {
//             return response()->json(["success" => 0, "message" => $e->getMessage()], 500);
//         }
//     }
}
