<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\Webhook;
use App\Models\Contact;
use App\Models\LegalWill;
use App\Models\LegalTrust;
use App\Models\PowerAttorney;
use App\Models\RealEstateDeed;
use App\Models\BusinessDocument;
use App\Models\TaxReturnDocument;
use App\Models\OtherLegalDocument;
use App\Models\Document;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCustomMail;



abstract class Controller
{
    
     public function __construct()
    {
        $this->user                 = new User();
        $this->notification         = new Notification();
        $this->subscription         = new Subscription();
        $this->webhook              = new Webhook();
        $this->contact              = new Contact();
        $this->legalwill            = new LegalWill();
        $this->legaltrust           = new LegalTrust();
        $this->powerattorney        = new PowerAttorney();
        $this->realestatedeed       = new RealEstateDeed();
        $this->businessdocument     = new BusinessDocument();
        $this->taxreturndocument    = new TaxReturnDocument();
        $this->otherlegaldocument   = new OtherLegalDocument();
        $this->document            = new Document();
    }

    
    public function statusCode($message)
    {
        return $message == 'success' ? (int) 200 : (int) 400;
    }

    public function sendResponse($result)
    {
        return response()->json($result, $result['status']);
    }
    
    public function sendNotification($type, $data)
    {
        try {
            $title = "LegacyPath";
            $msg = $data['message'];
    
            if ($type == "notification") {
                $notifyData = [
                    'message' => $msg,
                    'user_id' => $data['receiverID'],
                    'type' => $data['type']
                ];
            }
            
            $userData = $this->user::find($data['receiverID']);
            if (!$userData) {
                throw new \Exception("User not found.");
            }
    
            $result = $this->notification::create($notifyData);
    
            
            $payload = [
                'title' => $title,
                'message' => $msg,
                'notificationID' => (string) $result->id,
                'receiverId' => (string) $data['receiverID'],
                'type' => (string) $data['type']
            ];
    
            
    
            $deviceType = $userData->device_type;
            $deviceToken = $userData->device_token;
            
            if (empty($deviceToken)) {
                throw new \Exception("Device token is missing for the user.");
            }
            
            if ($deviceType === "android") {
                $message = [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $msg
                    ],
                    'data' => $payload,
                ];
            } else {
                $message = [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $msg
                    ],
                    'data' => $payload,
                ];
            }
            
            
            // Generate Firebase JWT
            $serviceAccountPath = public_path('Assets/files/firebase.json');
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    
            $clientEmail = $serviceAccount['client_email'];
            $privateKey = $serviceAccount['private_key'];
            $projectId = $serviceAccount['project_id'];
    
            $token = [
                'iss' => $clientEmail,
                'sub' => $clientEmail,
                'aud' => 'https://fcm.googleapis.com/',
                'iat' => time(),
                'exp' => time() + 3600,
            ];
    
            $jwt = \Firebase\JWT\JWT::encode($token, $privateKey, 'RS256');
    
          
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
           
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'message' => $message
            ]);
            
            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Notification sent successfully.',
                    'response' => $response->json()
                ];
            }
    
            throw new \Exception("Failed to send notification: " . $response->body());
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function totalUploadedSize($userId)
    {
        
        $willSize      = $this->legalwill::where('user_id',$userId)->sum('size');
        $trustSize     = $this->legaltrust::where('user_id',$userId)->sum('size');
        $attorneySize  = $this->powerattorney::where('user_id',$userId)->sum('size');
        $estateSize    = $this->realestatedeed::where('user_id',$userId)->sum('size');
        $businessSize  = $this->businessdocument::where('user_id',$userId)->sum('size');
        $returnSize    = $this->taxreturndocument::where('user_id',$userId)->sum('size');
        $legalDocSize  = $this->otherlegaldocument::where('user_id',$userId)->sum('size');
        $document      = $this->document::where('user_id',$userId)->sum('size');
        
        $totalDocSize = $willSize + $trustSize + $attorneySize + $estateSize + $businessSize + $returnSize + $legalDocSize + $document;
        
        return $totalDocSize;
    }
    
    public function sendMessage($data) { 
        try {
            $account_sid = env("TWILIO_SID");
            $auth_token = env("TWILIO_AUTH_TOKEN");
            $twilio_number = env("TWILIO_PHONE_NUMBER");
            
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($data['contact_number'], [
                'from' => $twilio_number, 
                'body' => $data['message']
            ]);
            $response = ["success" => 1];
        }
        catch(\Exception $e)
        {
            $response=["success"=>0, "message"=>$e->getMessage()];
        }
        return $response;
    }
    
        public function sendreply($subject, $body, $mailto)
        {
            try {
                Mail::to($mailto)->send(new SendCustomMail($subject, $body));
    
                return ["success" => 1, "message" => 'Email sent successfully'];
            } catch (\Exception $e) {
                
                \Log::error('Error sending email: ' . $e->getMessage());
                
                return ["success" => 0, "message" => 'Failed to send email. Please try again.'];
            }
        }

}
