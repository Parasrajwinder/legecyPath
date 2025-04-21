<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;
use App\Models\User;
use App\Models\Quote;
use Mail;
use App\Mail\forgotPassword;
use App\Mail\signupEmail;

class AuthController extends Controller
{
    public function registerUser ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'device_token' => 'nullable|string',
            'device_type'  => 'required|string',
            ],
            [
                'email.unique' => 'The email has already been taken.',
        ]);
            
           if ( $validator->fails() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => $validator->errors()->first(),
                ]);
            }
             $otp = rand( 1000, 9999 );
    
        // Prepare email data
        $mailData = [
            'title' => 'signUp User Otp',
            'body'  => 'This is to test signup user otp.',
            'token' => $otp,
        ];
    
        // Save OTP and expiration time to the database
        DB::table( 'password_reset_tokens' )->updateOrInsert(
            [ 'email' => $request->email ],
            [ 'token' => $otp ]
        );
    
        // Send OTP email
        Mail::to( $request->email )->send( new signupEmail( $mailData ) );
            $user = User::create([
                'email'          => $request->email,
                'step'           => 1,
                'password'       => Hash::make( $request->password ),
                'device_token'   => $request->device_token,
                'device_type'    => $request->device_type,
                ]);
                
                //delete old token before creating new one
                $user->tokens()->delete();
                $token = $user->createToken( 'legecypath' )->plainTextToken;
                
                return response()->json( [ 
                    'success' => true,
                    'status'  => 200,
                    'data'    => $user,
                    'token'   => $token,
                    'message' => 'User registered successsfully.', 
                    ]);
    }
    
    public function loginUser ( Request $request ) {
        
        $validator = Validator::make( $request->all(), [
            
            'email'     => 'required|email',
            'password'  => 'required|string'
            
            ]);
            
            if ( $validator->fails() ) {
                return response()->json( [ 'errors' => $validator->errors() ], 400 );
            }
            if( Auth::attempt([ 'email' => $request->email, 'password' => $request->password ]) ) {
                $user = Auth::user();
                
                  // Check if the user's account is suspended
                if ($user->status == "Suspended") {
                    
                    return response()->json([
                        'success' => false,
                        'status'  => 403,
                        'message' => 'Your account has been suspended by the admin.',
                    ], 403 );
                }
                
                //delete old token before creating new one
                $user->tokens()->delete();
                $token = $user->createToken( 'legecypath' )->plainTextToken;
                
                return response()->json([ 
                    'success' => true,
                    'status'  => 200,
                    'token'   => $token,
                    'data'    => $user,
                    'message' => 'User login successfully.'  
                ]);
            }
            return response()->json([ 
                'success' => true,
                'status'  => 400,
                'message' => 'Invalid email or password.' 
            ], 400 );
    }
    
    public function socialLogin( Request $request ) {
        try {
            $socialId = $request->social_id; 
            $userData = User::where([ 'social_id' => $socialId ])->first();
            
            if( $userData ) 
            {
                if( $userData->status == "Active" ) 
                {
                    $payload = $request->only([ 'device_type','device_token' ]);
                    User::where([ 'id' => $userData->id ])->update( $payload );
                             
                    $customerData = User::where([ 'id'=>$userData[ 'id' ] ])->first();
                   
                    $token = $customerData->createToken( 'legecypath' )->plainTextToken;
                    return response()->json([ 
                        'success'  => true,
                        'status'  => 200,
                        'token'   => $token,
                        'data'    => $customerData,
                        'message' => 'User login successfully.',
                    ]);
                } else {
                    return response()->json([ 
                        'success'  => false,
                        'status'   => 400,
                        'message'  => 'Your account suspended by admin' 
                        ], 400 );
                }    
            } else {
                 $payload = $request->all();
                 $validate   = Validator::make( $payload, [
                    'social_id'       => 'required',
                    'social_type'     => 'required',
                    'device_token'    => 'nullable|string',
                    'device_type'     => 'required|string',
                ]);
               
                if( $validate->fails() )
                {
                    $message = $validate->messages()->first();
                    return response()->json( [ 
                        'status'  => 400,
                        'message' => $message 
                        ]);
                        
                } else {
                    if( isset( $request->email ) ){
                         $userEmail = User::where([ 'email' => $request->email ])->first();
                         if( $userEmail ){
                            return response()->json([
                                'success' => false,
                                'status'  => 400,
                                'message' => 'The email is already exist' 
                            ], 400 );
                         }
                         
                    }
                    $pwd = "legecypath".$socialId."@*&)($%#";

                    $payload[ 'password' ]           = bcrypt( $pwd );
                    $payload[ 'is_verified' ]        = 1;
                    $payload[ 'email_verified_at' ]  = now();
                    $payload[ 'step' ]  = 2;
                    
                    $user  = User::create( $payload ); 
                    $find  = User::find( $user->id );
                    $token = $find->createToken( 'legecypath' )->plainTextToken;
                    
                    return response()->json([ 
                    'success' => true,
                    'status'  => 200,
                    'token'   => $token,
                    'data'    => $find,
                    'message' => 'User register successfully.',
                    ]);
                    
                }
            }
            
        } catch ( Exception $e ) {
             return response()->json( [ 
                'status'  => 400,
                'message' => $e->getMessage() 
                ]);
        }       
    }
    
    public function logOut( Request $request ) {
        $response = [];
        try {
            $accessToken = $request->bearerToken();
            $token       = PersonalAccessToken::findToken( $accessToken );
            if ( !empty ( $token ) ) {
                $tokenable_id = $token->tokenable_id;
                $token        = PersonalAccessToken::where( 'tokenable_id', $token->tokenable_id )->delete();
            }
            $response = [
                'success'    => true,
                'status'     => 200,
                'message'    => 'User logged out successfully.',
            ];
        } catch ( \Throwable $th ) {
            $response = [
                'status'        => 0,
                'msg'           => $th->getMessage(),
                'http_status'   => 500
            ];
        }
        return $response;
    }
    
    public function createAccount( Request $request ) { 
        
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            
            'first_name'      => 'required|string',
            'last_name'       => 'required|string',
            'profile_img'     => 'nullable|image|mimes:jpg,png,jpeg,webp',
            'gender'          => 'nullable|string|max:20',
            'date_of_birth'   => 'nullable|date|date_format:d-m-Y', 
            'country'         => 'nullable|string',
            'type'            => 'nullable|string',
            'country_code'    => 'nullable|string|max:20',
            'phone_number'    => 'nullable|string|max:20|unique:users,phone_number',
            
        ]);
        
         if ( $validator->fails() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => $validator->errors()->first()
                ]);
            }
        
        $userID = Auth::user()->id; 
        $user   = User::find( $userID );
    
        if ( $user && $user->profile_img ) {
            $oldProfileImgPath = storage_path( 'app/public/' . $user->profile_img );
            if ( file_exists( $oldProfileImgPath )) {
                unlink( $oldProfileImgPath );
            }
        }
    
        $profileImgFileName = null;
        if ( $request->hasFile( 'profile_img' ) ) {
            $file                = $request->file( 'profile_img' );
            $filename            = time() . '.' . $file->getClientOriginalExtension();
            $profileImgPath      = $file->storeAs( '/user_images', $filename, 'public' );
            $profileImgFileName  = basename( $profileImgPath );
        }
        
        $dateOfBirth = $request->date_of_birth ? Carbon::parse( $request->date_of_birth )->format( 'd-m-Y' ) : null;
        
        $user->update([
            
            'first_name'      => $request->first_name,
            'last_name'       => $request->last_name,
            'profile_img'     => $profileImgFileName,
            'gender'          => $request->gender,
            'date_of_birth'   => $dateOfBirth,
            'country'         => $request->country,
            'type'            => $request->type,
            'step'            => ( $user->step==2 )?3:$user->step,
            'country_code'    => $request->country_code,
            'phone_number'    => $request->phone_number,
            
        ]);
    
        return response()->json([ 
            'success' => true,
            'status'  => 200,
            'data'    => $user,
            'message' => 'Account updated successfully.', 
        ]);
    }
    
   public function createProfile( Request $request ) { 
        
    $response = [];
    
    // Validation rules
    $validator = Validator::make( $request->all(), [
        'first_name'      => 'required|string',
        'last_name'       => 'required|string',
        'profile_img'     => 'nullable|image|mimes:jpg,png,jpeg,webp',
        'gender'          => 'nullable|string|max:20',
        'date_of_birth'   => 'nullable|date|date_format:d-m-Y', 
        'type'            => 'nullable|string',
        'country'         => 'nullable|string',
        'country_code'    => 'nullable|string|max:20',
        'phone_number'    => 'nullable|string|max:20',
    ]);
    
    // Check validation errors
    if ( $validator->fails() ) {
        return response()->json([
            'status'  => 400,
            'message' => $validator->errors()->first()
        ]);
    }

    $userID = Auth::user()->id; 
    $user   = User::find( $userID );
    
    if ( $user ) {
        // If user has a profile image, delete it from storage if it's being updated
        if ( $request->hasFile( 'profile_img' ) ) {
            if ($user->profile_img) {
                $oldProfileImgPath = storage_path( 'app/public/' . $user->profile_img );
                if ( file_exists( $oldProfileImgPath ) ) {
                    unlink( $oldProfileImgPath ); // Delete the old image if it exists
                }
            }

            // Save new profile image
            $file                = $request->file( 'profile_img' );
            $filename            = time() . '.' . $file->getClientOriginalExtension();
            $profileImgPath      = $file->storeAs( '/user_images', $filename, 'public' );
            $profileImgFileName  = basename( $profileImgPath );
        } else {
            // If no new profile image is uploaded, keep the old one
            $profileImgFileName = $user->profile_img; // Keep the previous image
        }

        // Format date of birth
        $dateOfBirth = $request->date_of_birth ? Carbon::parse( $request->date_of_birth )->format( 'd-m-Y' ) : null;

        // Update user profile with the new data
        $user->update([
            'first_name'      => $request->first_name,
            'last_name'       => $request->last_name,
            'profile_img'     => $profileImgFileName,
            'gender'          => $request->gender,
            'date_of_birth'   => $dateOfBirth,
            'country'         => $request->country,
            'type'            => $request->type,
            'step'            => ( $user->step == 2 ) ? 3 : $user->step,
            'country_code'    => $request->country_code,
            'phone_number'    => $request->phone_number,
        ]);

        // Return success response
        return response()->json([ 
            'success' => true,
            'status'  => 200,
            'data'    => $user,
            'message' => 'Profile updated successfully.',
        ]);
    }
    
        return response()->json([
            'success'  => false,
            'status'  => 400,
            'message' => 'User not found.',
        ], 400 );
    }
    public function getUsersAccount( Request $request ) {
        $response = [];
        
        $user = Auth::user();
        if( !empty ( $user ) ) { 
            return response()->json([ 
                'success'  => true,
                'status'   => 200,
                'data'     => $user,
                'message'  => 'Users retrieved successfully.', 
                ]);
        } else {
            return response()->json([ 
                'success'  => false,
                'status'  => 400,
                'message' => 'Users not found.' 
                ]);
        }
    }
    
    public function deleteAccount( Request $request ) {
         $response = [];
    
        $validator = Validator::make( $request->all(), [
            'password' => 'required|string',
        ]);
        
         if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        // Get the currently authenticated user
        $user = Auth::user();
    
        if ( $user ) {
            if ( Hash::check( $request->password, $user->password ) ) {
                // Delete the user if password is correct
                $user->delete();
                // success mmessage
                return response()->json([ 
                    'success'  => true,
                    'status'   => 200,
                    'message'  => 'User deleted successfully.',
                ]);
            } else {
                // Password does not match
                return response()->json([ 
                    'success'  => false,
                    'status'  => 400,
                    'message' => 'Incorrect password. Account deletion failed.',
                ]);
            }
        } else {
            return response()->json([ 
                'success'  => false,
                'status'  => 400,
                'message' => 'No user found.'
            ]);
        }
    }

    public function forgotPassword( Request $request ) {
        $response = []; 
          
        // Validate email
        $validator  = Validator::make( $request->all(), [
            'email' => 'required|email|exists:users,email',
        ],
        [
        'email.exists' => 'Entered email not associated with any account.',
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'status' => 400, 'message' => $validator->errors()->first( 'email' ) ]);  
        }
                   
        // Get user
        $user = User::where( 'email', $request->email )->first();
    
        // Generate OTP
        $otp = rand( 1000, 9999 );
    
        // Prepare email data
        $mailData = [
            'title' => 'Forgot Password',
            'body'  => 'This is to reset your password.',
            'token' => $otp,
        ];
    
        // Save OTP to the database
        DB::table( 'password_reset_tokens' )->updateOrInsert(
            [ 'email' => $request->email ],
            [ 'token' => $otp ]
        );
    
        // Send OTP email
        Mail::to( $request->email )->send( new ForgotPassword( $mailData ) );
    
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'message'  => 'OTP sent successfully to your email.',
        ]);
    }
    
    public function verifyOtp( Request $request ) {
            $response = [];
         
        // Validate request
        $validator = Validator::make( $request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|numeric',
        ]);
        if ( $validator->fails() ) {
            return response()->json( [ 'errors' => $validator->errors() ], 400 );
        }
        
        // Retrieve OTP record from the database
        $otpRecord = DB::table( 'password_reset_tokens' )
            ->where( 'email', $request->email )
            ->where( 'token', $request->otp )
            ->first();
    
        // Check if OTP exists
        if ( !$otpRecord ) {
            return response()->json([
                'status'   => 400,
                'message'  => 'Invalid OTP.',
            ]);
        }
        
         DB::table( 'password_reset_tokens' )
            ->where( 'email', $request->email )
            ->where( 'token', $request->otp )
            ->delete();
            
        $user  = User::where( 'email', $request->email )->first(); 
        $token = $user->createToken( 'legecypath' )->plainTextToken;
        if( $user ) {
            if($user->step == 1){
                $user->step = 2;
            }
            $user->is_verified = 1;
            $user->update();
        }
        
        // OTP is valid
        return response()->json([
            'success'  => true,
            'token'    => $token,
            'status'   => 200,
            'message'  => 'OTP verified successfully.',
        ]);
    }

    public function resetPassword( Request $request ) {
      $response = [];
    
        $validator = Validator::make( $request->all(), [
            
            'email'    => 'required|email|exists:users,email', 
            'password' => 'required|string|min:8|confirmed', 
            
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
       
        $user = User::where( 'email', $request->email )->first();
        if ( !$user ) {
            return response()->json([ 
                'status'  => 400,
                'message' => 'User not found.' 
                ]);
        }
        
        $user->password = Hash::make( $request->password );
        $user->save();
        
        DB::table( 'password_reset_tokens' )->where( 'email', $request->email )->delete();
       
        return response()->json([ 
            'success' => true,
            'status'  => 200,
            'message' => 'Password has been reset successfully.',
            ]);
    }
    
    public function changePassword( Request $request ) {  
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            
            'old_password' => 'required',
            'password'     => 'required|confirmed|min:8',
            
            ]);
            
            $auth = Auth::user();
            if( $validator->fails() ) {
                
                return response()->json([ 'errors' => $validator->errors()], 400);
                
            } else if ( !Hash::check ( $request->old_password, $auth->password ) ) {
                
                return response()->json([ 
                     'status'   => 400,
                     'message'  => 'The current password is incorrect.' 
                    ]);
                
            } else {
                $user_id = $request->user()->id;
                $user    = User::where( 'id', $user_id )->first(); 
                if( !empty ( $user ) ) {
                    $update = User::where( 'id', $user_id )->update( array ( 'password' => Hash::make( $request->password ) ) );
                    if( $update ) {
                        return response()-> json([
                            'success'  => true,
                            'status'   => 200,
                            'message'  => 'Password changed successfully.',
                            ]);
                    }
                } else {
                    return response()-> json([ 
                        'status'  => 400,
                        'message' => 'Invalid user' 
                        ]);
                }
            }
    }
    
    public function inactivityNotification( Request $request ) {  
    
        $inactiveSince = Carbon::now()->subDays(15)->toDateString();
        $now = Carbon::now()->toDateString();
        
        $users = User::whereDate( 'last_activity',  $inactiveSince )
                     ->get();
                        
      
        foreach ( $users as $user ) {
                
            $lastNotification = $this->notification::where( 'user_id', $user->id )
                            ->where( 'type', 2 )
                            ->whereDate( 'created_at',$now )
                            ->first();
            
            if ( $lastNotification ) {
                continue;
            }

            $notification = [ "receiverID"=>$user->id,"message"=>"We noticed you haven’t been active on Legacy Path for the past 15 days. Log in now to keep your documents safe and secure!","type"=>2 ];
            $notify = parent::sendNotification( 'notification',$notification );
            
            
            $is_trusted_contacts = $this->contact::where( 'user_id',$user->id )->where( 'is_trusted',1 )->get();
            foreach ( $is_trusted_contacts as $contact ) {
                
                $check = $this->user::where( 'email',$contact->email )->first();
                if( $check ){
                    $sendNotification = [ "receiverID"=>$check->id,"message"=>"".$user->first_name." hasn't been active on Legacy Path for 15 days.","type"=>3 ];
                    $notify = parent::sendNotification( 'notification',$sendNotification );
                }
            }
        }
        
            return response()-> json([
                'success'        => true,
                'status'         => 200,
                'inactive since' => $inactiveSince,
                'now'            => $now,
                'message'        => 'Notification send successfully.',
                ]);
    }
    
    public function getQuotes() {
       
        $quoteCount = Quote::count(); 
        if ($quoteCount === 0) {
            return response()->json([ 
            'status'   => 400,
            'message'  => 'No quotes available.' 
        ]);
        }
         
        // Get a new quote based on the current day
        $quoteIndex = Carbon::now()->dayOfYear % $quoteCount;
        $quote = Quote::skip( $quoteIndex )->first();
         
        return response()->json([ 
            'success'   => true, 
            'data'      => $quote,
            'status'    => 200,
            'message'   => 'Quotes retrieved successfully.'
        ]);
    }     

    
}