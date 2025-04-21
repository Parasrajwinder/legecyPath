<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Contact;
use App\Models\ContactModule;
use App\Models\ShareDocuments;
use App\Models\LifeInsurance;
use App\Models\RealEstate;
use App\Models\BrokerageAccount;
use App\Models\BusinessOwnership;
use App\Models\OtherAsset;
use App\Models\Loan;
use App\Models\CreditCard;
use App\Models\OtherDebt;
use App\Models\LegalWill;
use App\Models\LegalTrust;
use App\Models\Login;
use App\Models\PowerAttorney;
use App\Models\RealEstateDeed;
use App\Models\BusinessDocument;
use App\Models\TaxReturnDocument;
use App\Models\OtherLegalDocument;
use App\Models\LifeJournal;
use App\Models\Document;
use App\Models\ContactUs;

class HomeController extends Controller
{
    public function addBankAccount ( Request $request ) {
        $response = [];
       
        $validator = Validator::make( $request->all(), [ 
            'category_id'     => 'required|exists:categories,id',  // Ensure category exists     
            'account_number'  => 'required|string',
            'bank_name'       => 'required|string',
            'account_type'    => 'required|string',
            'legal_ownership' => 'required|string',
        ]);
       
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and account details from request
        $categoryId    = $request->input( 'category_id' );
        $accountNumber = $request->input( 'account_number' );
        $bankName      = $request->input( 'bank_name' );
        $accountType   = $request->input( 'account_type' ); 
        $ownership     = $request->input( 'legal_ownership' );
        
        // Check if the bank account already exists for this user
        $existingAccount = BankAccount::where( 'user_id', $user->id )->where( 'account_number', $accountNumber )->first();
    
        if ($existingAccount) {
            // If the account already exists, return an error message
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Account number already added.'
            ]);
        }
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if (!$category) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.' 
            ]);
        }
    
        // Create a new bank account for the user under the given category
        $bankAccount = new BankAccount();
        $bankAccount->user_id           = $user->id;
        $bankAccount->category_id       = $categoryId;
        $bankAccount->account_number    = $accountNumber;
        $bankAccount->bank_name         = $bankName;
        $bankAccount->account_type      = $accountType;
        $bankAccount->legal_ownership   = $ownership;
        $bankAccount->save();
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $bankAccount,
            'message' => 'Bank account added successfully.',
        ]);
    }
    
    public function editBankAccount ( Request $request ) {
        $response = [];
    
        $validator = Validator::make( $request->all(), [
            'id'                => 'required|integer',
            'account_number'    => 'nullable|string',  
            'bank_name'         => 'nullable|string',
            'account_type'      => 'nullable|string',
            'legal_ownership'   => 'nullable|string'
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        // Find the bank account by ID
        $bankAccount = BankAccount::find( $request->id );
        
        // If the bank account does not exist, return an error
        if (!$bankAccount) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Bank account not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'account_number' ) ) {
            $bankAccount->account_number = $request->input( 'account_number' );
        }
        if ( $request->has( 'bank_name' ) ) {
            $bankAccount->bank_name = $request->input( 'bank_name' );
        }
        if ( $request->has( 'account_type' ) ) { 
            $bankAccount->account_type = $request->input( 'account_type' );
        }
         if ( $request->has( 'legal_ownership' ) ) { 
            $bankAccount->legal_ownership = $request->input( 'legal_ownership' );
        }
        // Save the updated bank account details
        $bankAccount->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $bankAccount,
            'message' => 'Bank account updated successfully.',
        ]);
    }
    
    public function getBankAccounts () {
        $response = [];
        
        $user = Auth::user();
        
        // get all bank accounts
        $getAccounts = BankAccount::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
        foreach( $getAccounts as $getAccount ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getAccount->category_id )
            ->where( 'document_id', $getAccount->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getAccount->share_with = $shareWith;
          }
          
        if ( !empty( $getAccounts ) ) {
            
            // Return a success response
            return response()->json([ 
                'success'     => true, 
                'status'      => 200,
                'data'        => $getAccounts,
                'message'     => 'Bank accounts retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Accounts not found' 
            ]);
        }
        
    }

    public function deleteBankAccount ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the bank account by ID
        $bankAccount = BankAccount::find( $request->id );
    
        // If the bank account does not exist, return an error
        if ( !$bankAccount ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Bank account not found.' 
            ]);
        } else {
            // Delete the bank account
            $bankAccount->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Bank account deleted successfully.',
            ]);
        }
    }
    
    public function addContact ( Request $request ) {   
        $response = [];
        
        // Validate the request
        $validator = Validator::make( $request->all(), [
    
            'module_type'    => 'required|string',
            'full_name'      => 'required|string',
            'profile_img'    => 'nullable|image|mimes:jpg,png,jpeg',
            'country_code'   => 'required|string|max:20',
            'contact_number' => 'required|string|max:20',
            'email'          => 'required|email',
            'relation'       => 'required|string',
            'is_trusted'     => 'required|integer|in:0,1', 
        ]);
        
        // Return validation errors if they exist
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        // Check if user is authenticated
        $user = Auth::user();
        
        $existingContact = Contact::where( 'user_id', $user->id )
                                  ->where( 'email', $request->email )
                                  ->first();
                                  
        if ( $existingContact ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Contact is already added.'
            ]);
        }
        // Handle the new profile image upload
        $profileImgFileName = null;
        
        if ($request->hasFile( 'profile_img' )) {
            $file               = $request->file( 'profile_img' );
            $filename           = time() . '.' . $file->getClientOriginalExtension();
            $profileImgPath     = $file->storeAs( '/user_images', $filename, 'public' );
            $profileImgFileName = basename( $profileImgPath );
        }
    
        // Create a new contact
        $contact = new Contact();
        $contact->user_id        = $user->id;  // Assuming 'user_id' is the foreign key to the user table
        $contact->full_name      = $request->full_name;
        $contact->profile_img    = $profileImgFileName;
        $contact->country_code   = $request->country_code;
        $contact->contact_number = $request->contact_number;
        $contact->email          = $request->email;
        $contact->relation       = $request->relation;
        $contact->is_trusted     = $request->is_trusted;
        
        $contact->save();
        
        $data = [
            'contact_number' => $request->country_code.$request->contact_number,
            'message'        => "Hi! $contact->full_name has invited you to join the Legacy Path app and has added you as a contact. Start your journey of preserving and sharing memories today! Follow this link to download the app: https://com.legacypath.app/",
            ];
            
            $response = $this->sendMessage($data);
       
        if( isset( $request->module_type ) ){
            $moduleTypes = explode( ',', $request->module_type );
            
            foreach ( $moduleTypes as $moduleType ) {
              
                $contactModule = new ContactModule();
                $contactModule->contact_id    = $contact->id;
                $contactModule->module_type   = $moduleType;
                $contactModule->save();
            }
        }
        
        // Return a success response
        return response()->json([ 
            'success' => true,
            'status'  => 200,
            'data'    => $contact,
            'message' => 'Contact added successfully.', 
        ]);
    }
    
    public function editContact ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            
            'id'             => 'required|integer',
            'full_name'      => 'nullable|string',
            'profile_img'    => 'nullable|image|mimes:jpg,png,jpeg',
            'country_code'   => 'nullable|string',
            'contact_number' => 'nullable|string',
            'relation'       => 'nullable|string',
            'module_type'    => 'nullable|string',
            'is_trusted'     => 'nullable|integer|in:0,1',
            
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        // Check if user is authenticated
        $user = Auth::user();
        
        // Find the contact by ID
        $contact = Contact::find( $request->id );
        if ( !$contact ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Contact not found.' 
            ]);
        }
        
        // Handle profile image deletion if a new image is uploaded
        if ( $request->hasFile( 'profile_img' ) ) {
            // Delete the old profile image if it exists
            if ( $contact->profile_img ) {
                $oldProfileImgPath = storage_path( 'app/public/' . $contact->profile_img );
                if ( file_exists( $oldProfileImgPath ) ) {
                    unlink( $oldProfileImgPath ); // Remove old image
                }
            }
    
            // Handle the new profile image upload
            $file                 = $request->file( 'profile_img' );
            $filename             = time() . '.' . $file->getClientOriginalExtension(); // Generate a unique filename
            $profileImgPath       = $file->storeAs( 'user_images', $filename, 'public' ); // Store the image
            $contact->profile_img = basename( $profileImgPath ); // Update profile image with the filename
        }
    
        // Update other fields if they are provided
        if ( $request->has( 'full_name' ) ) {
            $contact->full_name = $request->full_name;
        }
    
        if ( $request->has( 'country_code' ) ) {
            $contact->country_code = $request->country_code;
        }
    
        if ( $request->has( 'contact_number' ) ) {
            $contact->contact_number = $request->contact_number;
        }
    
        if ( $request->has( 'relation' ) ) {
            $contact->relation = $request->relation;
        }
    
        if ( $request->has( 'is_trusted' ) ) {
            $contact->is_trusted = $request->is_trusted;
        }
    
        // Save the updated contact
        $contact->save();
      if( isset( $request->module_type ) ){
          
            ContactModule::where( 'contact_id', $contact->id )->delete();
            
            $moduleTypes = explode( ',', $request->module_type );
            
            foreach ( $moduleTypes as $moduleType ) {
              
                $contactModule = new ContactModule();
                $contactModule->contact_id    = $contact->id;
                $contactModule->module_type   = $moduleType;
                $contactModule->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $contact,
            'message' => 'Contact updated successfully.',
        ]);
    }
     
    public function getContact () {
        $response = []; 
        
        $user = Auth::user();
        
        $getContacts = Contact::with( 'module' )->where( 'user_id', $user->id )->orderBy( 'id','desc' )->get();
         
        if( $getContacts ) {
             
            return response()->json([ 
                'success' => true, 
                'status'  => 200,
                'data'    => $getContacts, 
                'message' => 'Contacts retrieved successfully.', 
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'No contact found' 
            ]);
        }
    }
    
    public function deleteContact ( Request $request ) {
        $response  = [];
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        
        // Find the contact by ID
        $contact = Contact::find( $request->id );
        
        if( !$contact ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Contact not found.' 
            ]);
        } else {
            // Delete the contact
            $contact->delete();
            
            // Return the success response
                return response()->json([
                    'success' => true,
                    'status'  => 200,
                    'message' => 'Contact deleted successfully.',
                ]);
        }
    }
    
    public function shareDocument ( Request $request ) {
        $response = [];
       
        // Validate the input
        $validator = Validator::make( $request->all(), [
            'category_id'    => 'required|exists:categories,id',   
            'document_id'    => 'required|integer',               
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        // Get the currently authenticated user
        $user = Auth::user();
        
        // Retrieve the category based on category_id
        $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        
        if(isset($request->contact_id) && !empty($request->contact_id )){
            $contactIds = explode( ',', $request->contact_id );
       
        
        // Loop through each contact_id and process it
        foreach ( $contactIds as $contactId ) {
            
            $contact = Contact::where( 'id',$contactId)->where( 'user_id',$user->id )->first(); 
            if(!$contact){
              return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'ContactID :'.$contactId.' not found.'
            ]);
            }
                
            // Check if the document has already been shared with this contact
            $existDoc = ShareDocuments::where( 'user_id', $user->id )
                            ->where( 'contact_id', $contactId)
                            ->where( 'category_id', $category->id )
                            ->where( 'document_id', $request->document_id )
                            ->first();
    
            // If the document hasn't been shared yet, create a new ShareDocuments entry
            if ( !$existDoc ) {
                $document = new ShareDocuments();
                $document->user_id      = $user->id;
                $document->contact_id   = $contact->id;
                $document->category_id  = $category->id;
                $document->document_id  = $request->document_id;
                $document->save();
                
                $checkUser = $this->user::where( 'email',$contact->email )->first();
                if( $checkUser ){
                    $sharedItem = isset($category->title) ? $category->title : $category->type;
                    
                    $notification = [ "receiverID" => $checkUser->id, "message"=> ''.$user->first_name . ' has shared ' . $sharedItem . ' with you', "type"=> 1 ];
                    $notify = parent::sendNotification( 'notification',$notification );
                }
            }
            
             }
                ShareDocuments::whereNotIn('contact_id',$contactIds)->where(['category_id'=>$category->id,'user_id'=> $user->id,'document_id'=>$request->document_id])->delete();
       
        }else{
               ShareDocuments::where(['category_id'=>$category->id,'user_id'=> $user->id,'document_id'=>$request->document_id])->delete();
       
        }
        
          
        // Return success response
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'message'  => 'Document shared successfully.',
        ]);
    }
    
    public function getShareDocument() {
        $user = Auth::user();
        
        $lifeJournalID=Category::where('type',"Life Journals")->pluck('id')->toArray();
          
        $getDocuments = DB::table( 'share_documents' )
            ->where( 'user_id', $user->id )
            ->whereNotIn('category_id',$lifeJournalID)
            ->select( 'document_id', 'category_id', DB::raw( 'MAX(id) as id' ) )  // Get the latest id for each document_id
            ->groupBy( 'document_id', 'category_id' )  // Group by both document_id and category_id
            ->orderBy( 'id', 'desc' )  // Order by the latest id
            ->get();
        $arr = [];
        foreach( $getDocuments as $document ){
            
            $doc=ShareDocuments::where( 'user_id',$user->id )->where( 'category_id',$document->category_id )->where( 'document_id',$document->document_id )
            ->with([ 'category.bankaccount' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.realasset' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.brokerageaccount' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.lifeinsurance' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.businessownership' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.otherasset' => function( $query ) use ( $document ) {
                $query->where('id', $document->document_id);  
            }])
            ->with([ 'category.loan' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.creditcard' => function( $query ) use ( $document ) {
                $query->where('id', $document->document_id);  
            }])
            ->with([ 'category.otherdebt' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            
            ->with([ 'category.legalwill' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.legaltrust' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.powerattorney' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.realestatedeed' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.businessdocument' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.taxreturndocument' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.otherlegaldocument' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
              ->with([ 'category.logins' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])
            ->with([ 'category.lifejournals' => function( $query ) use ( $document ) {
                $query->where( 'id', $document->document_id );  
            }])->first();
            
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $document->category_id )
            ->where( 'document_id', $document->document_id )
            ->with([ 'contact.user' ]) 
            ->get();
            
            $doc->shareWith = $shareWith;
            $arr[] = $doc;
        }
         
        //Return success response
        return response()->json([
            'success'  => true,
            'data'     => $arr,
            'status'   => 200,
            'message'  => 'Get documents successfully.',
        ]);
    }
    
    public function getSharedWithMeDocs() {
        $user = Auth::user();
        $lifeJournalID=Category::where('type',"Life Journals")->pluck('id')->toArray();
        
        $getDocuments = DB::table( 'share_documents' )
                        ->join( 'contacts', 'share_documents.contact_id', '=', 'contacts.id' ) // Adjust foreign key if needed
                        ->select( 'share_documents.document_id', 'share_documents.category_id', DB::raw('MAX( share_documents.id ) as id' ))
                        ->where( 'contacts.email', $user->email )
                        ->whereNotIn('share_documents.category_id',$lifeJournalID)
                        ->groupBy( 'share_documents.document_id', 'share_documents.category_id' )
                        ->orderByDesc( 'id' )
                        ->get();

        $arr = [];

        foreach( $getDocuments as $document ){
        
        $doc=ShareDocuments::where( 'id', $document->id )->where( 'category_id',$document->category_id )->where( 'document_id',$document->document_id )
        ->with([ 'category.bankaccount' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.realasset' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.brokerageaccount' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.lifeinsurance' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.businessownership' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.otherasset' => function( $query ) use ( $document ) {
            $query->where('id', $document->document_id);  
        }])
        ->with([ 'category.loan' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->with([ 'category.creditcard' => function( $query ) use ( $document ) {
            $query->where('id', $document->document_id);  
        }])
        ->with([ 'category.otherdebt' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        
        ->with([ 'category.legalwill' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.legaltrust' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.powerattorney' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.realestatedeed' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.businessdocument' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.taxreturndocument' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.otherlegaldocument' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
          ->with([ 'category.logins' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])->first();
        
        $shareBy = ShareDocuments::where( 'id', $document->id )->first();
        
        $userShareBy=User::find($shareBy->user_id);
        
        $contact = Contact::where( 'user_id', $user->id )
        ->where( 'email',$userShareBy->email )
        ->first();
        
        $doc->contact = $contact;
        $doc->shareBy = $userShareBy;
      
        $arr[] = $doc;
        
    }
        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $arr,
            'message' => 'All shared documents retrieved successfully.'
        ]);
    }
    
    public function getCategories() {
        $response = []; 
        
         $categories = Category::select( 'type' )  // Assuming you want distinct by 'type'
            ->distinct()
            ->pluck( 'type' )->toArray();
          $arr = [];  
            foreach( $categories as $categorie ){
                $cat        = Category::where( 'type', $categorie )->select( 'id','type','icon' )->first();
                $types      = Category::where( 'type', $categorie )->select( 'type2' )->distinct()->pluck( 'type2' )->filter( fn( $value )=> !empty( $value ))->toArray();
                $cat->type2 = $types;
                $arr[]      = $cat;
            }
            
            if( $categories ) {
                return response()->json([
                    'success'  => true,
                    'status'   => 200,
                    'data'     => $arr,
                    'message'  => 'Retrieved categories successfully.',
                ]);
            }
    }
    
    public function getSubCategories( Request $request ) {
       
        $response = [];
        
        // Validate the input
        $validator = Validator::make( $request->all(), [
            'type'  => 'required|string',  
        ]);

        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        // Retrieve the validated inputs
        $type = $request->input( 'type' );
        
        $query = Category::where( 'type', $type );
       
        if ( isset( $request->type2 ) ) {
            $type2 = $request->input( 'type2' );
            $query->where( 'type2', $type2 );
        } else {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Invalid type' 
            ]);
        }
        
        $subcategories = $query->get(); 
     
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $subcategories,
            'message' => 'Retrieved Subcategories successfully.'
        ]);
    }
    
    public function addInsurance( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'       => 'required|exists:categories,id', 
            'policy_number'     => 'required|string',
            'company_name'      => 'required|string',
            'beneficiary_name'  => 'required|string'
            ]);
            
            
            if ( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400);
            }
            
            $user = Auth::user();
                
            // Retrieve category ID and policy details from request
            $categoryId        = $request->input( 'category_id' );
            $policyNumber      = $request->input( 'policy_number' );
            $companyName       = $request->input( 'company_name' );
            $beneficiaryName   = $request->input( 'beneficiary_name' );
            
            // Check if the bank account already exists for this user
            $existingAccount = LifeInsurance::where( 'user_id', $user->id )
                                          ->where( 'policy_number', $policyNumber )
                                          ->first();
        
            if ( $existingAccount ) {
                // If the account already exists, return an error message
                return response()->json([ 
                    'success' => false,
                    'status'  => 400,
                    'message' => 'Policy number already added.'
                ]);
            }
            $category = Category::find( $categoryId );
            if ( !$category ) {
                return response()->json([ 
                    'success'  => false,
                    'status'   => 400,
                    'message'  => 'Category not found.' 
                ]);
            }
            
            $user = Auth::user();
                
        // Create a new insurance for the user under the given category id
        $lifeInsurance = new LifeInsurance();
        $lifeInsurance->user_id            = $user->id;
        $lifeInsurance->category_id        = $categoryId;
        $lifeInsurance->policy_number      = $policyNumber;
        $lifeInsurance->company_name       = $companyName;
        $lifeInsurance->beneficiary_name   = $beneficiaryName;
        $lifeInsurance->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'data'     => $lifeInsurance,
            'message'  => 'Life insurance added successfully.',
        ]);
    }
    
    public function editInsurance( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                => 'required|integer',
            'policy_number'     => 'nullable|string', 
            'company_name'      => 'nullable|string',
            'beneficiary_name'  => 'nullable|string',
            ] );
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $editInsurance = LifeInsurance::find( $request->id );
        
        // If the Life insurance does not exist, return an error
        if ( !$editInsurance ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Insurance not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'policy_number' ) ) {
            $editInsurance->policy_number = $request->input( 'policy_number' );
        }
        if ( $request->has( 'company_name' ) ) {
            $editInsurance->company_name = $request->input( 'company_name' );
        }
        if ( $request->has( 'beneficiary_name' ) ) {
            $editInsurance->beneficiary_name = $request->input( 'beneficiary_name' );
        }
        // update the Life insurance details
        $editInsurance->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'data'     => $editInsurance,
            'message'  => 'Life insurance updated successfully.',
        ]);
    }
    
    public function getInsurance() {
        $response = [];
        
        $user = Auth::user();
        
        // get all insurance
        $getInsurances = LifeInsurance::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getInsurances as $getInsurance ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getInsurance->category_id )
            ->where( 'document_id', $getInsurance->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getInsurance->share_with = $shareWith;
          }
        
        if ( !empty( $getInsurances ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'     => true, 
                'status'      => 200,
                'data'        => $getInsurances,
                'message'     => 'Insurance retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false, 
                'status'  => 400,
                'message' => 'No insurance found' 
            ]);
        }
    }
    
      public function deleteInsurance( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the bank account by ID
        $lifeInsurance = LifeInsurance::find( $request->id );
    
        // If the bank account does not exist, return an error
        if ( !$lifeInsurance ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Insurance not found.' 
            ]);
        } else {
            // Delete the bank account
            $lifeInsurance->delete();
        
            // Return a success response
            return response()->json([
                'success'  => true,
                'status'   => 200,
                'message'  => 'Life Insurance deleted successfully.',
            ]);
        }
    }
    
    public function addRealEstate( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'      => 'required|exists:categories,id',
            'property_type'    => 'required|string',
            'address'          => 'required|string',
            'legal_ownership'  => 'required|string'
            ] );
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
            
            // Retrieve category ID and real estate detail from request
             
             $categoryId = $request->input( 'category_id' );
             $property   = $request->input( 'property_type' );
             $address    = $request->input( 'address' );
             $ownership  = $request->input( 'legal_ownership' );
             
              $category = Category::find( $categoryId );
                if ( !$category ) {
                    return response()->json([
                        'success'  => false,
                        'status'   => 400,
                        'message'  => 'Category not found.' 
                    ]);
                }
                
            $user = Auth::user();
                
            // Create a new real estate for the user under the given category
            $realEstate = new RealEstate();
            $realEstate->user_id          = $user->id;
            $realEstate->category_id      = $categoryId;
            $realEstate->property_type    = $property;
            $realEstate->address          = $address;
            $realEstate->legal_ownership  = $ownership;
            $realEstate->save();
            
            // Return a success response with the reordered data
            return response()->json([
                'success'  => true,
                'status'   => 200,
                'data'     => $realEstate,
                'message'  => 'Real estate added successfully.',
            ]);
    }
    
     public function editRealEstate( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'               =>  'required|integer',
            'property_type'    =>  'nullable|string', 
            'address'          =>  'nullable|string',
            'legal_ownership'  =>  'nullable|string',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $editRealEsates = RealEstate::find( $request->id );
        
        // If the Life insurance does not exist, return an error
        if ( !$editRealEsates ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Estate not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'property_type' ) ) {
            $editRealEsates->property_type = $request->input( 'property_type' );
        }
        if ( $request->has( 'address' ) ) {
            $editRealEsates->address = $request->input( 'address' );
        }
        if ( $request->has( 'legal_ownership' ) ) {
            $editRealEsates->legal_ownership = $request->input( 'legal_ownership' );
        }
        // update the Life insurance details
        $editRealEsates->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editRealEsates,
            'message' => 'Real estate updated successfully.',
        ]);
    }
    
    public function getRealEsate() {
        $response = [];
        
        $user = Auth::user();
        
        // get all insurance
        $getRealEsates = RealEstate::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
        foreach( $getRealEsates as $getRealEsate ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getRealEsate->category_id )
            ->where( 'document_id', $getRealEsate->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getRealEsate->share_with = $shareWith;
          }
          
        if ( !empty( $getRealEsates ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'  => true, 
                'status'   => 200,
                'data'     => $getRealEsates,
                'message'  => 'Real esates retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'No esates found' 
            ]);
        }
    }
    
    public function deleteRealEstate( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the real estate by ID
        $deleteRealEsate = RealEstate::find( $request->id );
    
        // If the real estate does not exist, return an error
        if ( !$deleteRealEsate ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Estates not found.' 
            ]); 
        } else {
            // Delete the real estate
            $deleteRealEsate->delete();
        
            // Return a success response
            return response()->json([
                'success'  => true,
                'status'   => 200,
                'message'  => 'Real estate deleted successfully.',
            ]);
        }
    }
    
    public function addBrokerageAccount( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make( $request->all(), [ 
            'category_id'           => 'required|exists:categories,id',  // Ensure category exists     
            'account_type'          => 'required|string',
            'account_number'        => 'required|string',
            'brokerage_firm_name'   => 'required|string',
            'legal_ownership'       => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and account details from request
        $categoryId     = $request->input( 'category_id' );
        $accountType    = $request->input( 'account_type' );
        $accountNo      = $request->input( 'account_number' );
        $firm           = $request->input( 'brokerage_firm_name' ); 
        $ownership      = $request->input( 'legal_ownership' );
    
    
        // Check if the bank account already exists for this user
        $existingAccount = BrokerageAccount::where( 'user_id', $user->id )->where( 'account_number', $accountNo )->first();
    
        if ( $existingAccount ) {
            // If the account already exists, return an error message
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Account number already added.'
            ]);
        }
        
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.' 
            ]);
        }
    
        // Create a new bank account for the user under the given category
        $brokerageAccount = new BrokerageAccount();
        $brokerageAccount->user_id              = $user->id;
        $brokerageAccount->category_id          = $categoryId;
        $brokerageAccount->account_type         = $accountType;
        $brokerageAccount->account_number       = $accountNo;
        $brokerageAccount->brokerage_firm_name  = $firm;
        $brokerageAccount->legal_ownership      = $ownership;
        $brokerageAccount->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $brokerageAccount,
            'message' => 'Brokerage account added successfully.',
        ]);
    }
    
   public function editBrokerageAccount( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                   =>  'required|integer',
            'account_type'         =>  'nullable|string', 
            'account_number'       =>  'nullable|string',
            'brokerage_firm_name'  =>  'nullable|string',
            'legal_ownership'      =>  'nullable|string',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editBrokerageAccount = BrokerageAccount::find( $request->id );
        
        // If the Life insurance does not exist, return an error
        if ( !$editBrokerageAccount ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Brokerage account not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'account_type' ) ) {
            $editBrokerageAccount->account_type = $request->input( 'account_type' );
        }
        if ( $request->has( 'account_number' ) ) {
            $editBrokerageAccount->account_number = $request->input( 'account_number' );
        }
        if ( $request->has( 'brokerage_firm_name' ) ) {
            $editBrokerageAccount->brokerage_firm_name = $request->input( 'brokerage_firm_name' );
        }
        if ( $request->has( 'legal_ownership' ) ) {
            $editBrokerageAccount->legal_ownership = $request->input( 'legal_ownership' );
        }
        // update the Life insurance details
        $editBrokerageAccount->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editBrokerageAccount,
            'message' => 'Brokerage account updated successfully.',
        ]);
    }
    
    public function getBrokerageAccount() {
        $response = [];
        
        $user = Auth::user(); 
        
        // get all insurance
        $getBrokerageAccounts = BrokerageAccount::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getBrokerageAccounts as $getBrokerageAccount ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getBrokerageAccount->category_id )
            ->where( 'document_id', $getBrokerageAccount->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getBrokerageAccount->share_with = $shareWith;
          }
        
        if ( !empty( $getBrokerageAccounts ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getBrokerageAccounts,
                'message'   => 'Brokerage account retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'No brokerage account found' 
            ]);
        }
    }
    
     public function deleteBrokerageAccount( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the real estate by ID
        $deleteRealEsate = BrokerageAccount::find( $request->id );
    
        // If the real estate does not exist, return an error
        if ( !$deleteRealEsate ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Brokerage account not found.' 
            ]);
        } else {
            // Delete the real estate
            $deleteRealEsate->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Brokerage account deleted successfully.',
            ]);
        }
    }
    public function addBusinessOwnership( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make( $request->all(), [ 
            'category_id'           => 'required|exists:categories,id',  // Ensure category exists     
            'business_name'         => 'required|string',
            'business_type'         => 'required|string',
            'ownership_percentage'  => 'required|string',
            'legal_ownership'       => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
        
        // Retrieve details from request
        $categoryId      = $request->input( 'category_id' );
        $businessName    = $request->input( 'business_name' );
        $businessType    = $request->input( 'business_type' );
        $percentage      = $request->input( 'ownership_percentage' ); 
        $ownership       = $request->input( 'legal_ownership' );
    
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.' 
            ]);
        }
    
        // Create a new Business ownership for the user under the given category
        $BusinessOwnership = new BusinessOwnership();
        $BusinessOwnership->user_id               = $user->id;
        $BusinessOwnership->category_id           = $categoryId;
        $BusinessOwnership->business_name         = $businessName;
        $BusinessOwnership->business_type         = $businessType;
        $BusinessOwnership->ownership_percentage  = $percentage;
        $BusinessOwnership->legal_ownership       = $ownership;
        $BusinessOwnership->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $BusinessOwnership,
            'message' => 'Business ownership added successfully.',
        ]);
    }
    public function editBusinessOwnership( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                    =>  'required|integer',
            'business_name'         =>  'nullable|string', 
            'business_type'         =>  'nullable|string',
            'ownership_percentage'  =>  'nullable|string',
            'legal_ownership'       =>  'nullable|string',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editBusinessOwnership = BusinessOwnership::find( $request->id );
        
        // If the Business ownership does not exist, return an error
        if ( !$editBusinessOwnership ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Business ownership not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'business_name' )) {
             $editBusinessOwnership->business_name = $request->input( 'business_name' );
        }
        if ( $request->has( 'business_type' )) {
                $editBusinessOwnership->business_type = $request->input( 'business_type' );
        }
        if ( $request->has( 'ownership_percentage' )) {
                $editBusinessOwnership->ownership_percentage = $request->input( 'ownership_percentage' );
        }
        if ( $request->has( 'legal_ownership' )) {
                $editBusinessOwnership->legal_ownership = $request->input( 'legal_ownership' );
        }
        // update the Business ownership details
        $editBusinessOwnership->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editBusinessOwnership,
            'message' => 'Business ownership updated successfully.',
        ]);
    }
    
     public function getBusinessOwnership() {
        $response = [];
        
        $user = Auth::user();
        
        // get all Business ownership
        $getBusinessOwnerships = BusinessOwnership::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getBusinessOwnerships as $getBusinessOwnership ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getBusinessOwnership->category_id )
            ->where( 'document_id', $getBusinessOwnership->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getBusinessOwnership->share_with = $shareWith;
          }
        
        if ( !empty($getBusinessOwnerships) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getBusinessOwnerships,
                'message'   => 'Business ownership retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false, 
                'status'  => 400,
                'message' => 'No Business ownership found' 
            ]);
        }
    }
    
     public function deleteBusinessOwnership( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the Business ownership by ID
        $deleteBusinessOwnership = BusinessOwnership::find( $request->id );
    
        // If the Business ownership does not exist, return an error
        if (!$deleteBusinessOwnership) {
            
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Business ownership not found.' 
            ]);
            
        } else {
            // Delete the Business ownership
            $deleteBusinessOwnership->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Business ownership deleted successfully.',
            ]);
        }
    }
    
    public function addAsset( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make($request->all(), [ 
            'category_id'       => 'required|exists:categories,id',  // Ensure category exists     
            'account_name'      => 'required|string',
            'account_number'    => 'required|string',
            'other_information' => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and account details from request
        $categoryId    = $request->input( 'category_id' );
        $accountName   = $request->input( 'account_name' );
        $accountNo     = $request->input( 'account_number' );
        $info          = $request->input( 'other_information' ); 
    
         // Check if the assets already exists for this user
        $existingAccount = OtherAsset::where( 'user_id', $user->id )
                                      ->where( 'account_number', $accountNo )
                                      ->first();
    
        if ( $existingAccount ) {
            // If the assets already exists, return an error message
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Account number already added.'
            ]);
        }
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([ 
                'status'   => 400,
                'message'  => 'Category not found.' 
            ]);
        }
    
        // Create a new asset for the user under the given category
        $otherAsset = new OtherAsset();
        $otherAsset->user_id             = $user->id;
        $otherAsset->category_id         = $categoryId;
        $otherAsset->account_name        = $accountName;
        $otherAsset->account_number      = $accountNo;
        $otherAsset->other_information   = $info;
        $otherAsset->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $otherAsset,
            'message' => 'Other assets added successfully.',
        ]);
    }
    
    public function editAsset( Request $request) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                 =>  'required|integer',
            'account_name'       =>  'nullable|string', 
            'account_number'     =>  'nullable|string',
            'other_information'  =>  'nullable|string'
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editAsset = OtherAsset::find( $request->id );
        
        // If the assets not exist, return an error
        if ( !$editAsset ) {
            return response()->json([ 
                'status'   => 400,
                'message'  => 'Other assets not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'account_name' ) ) {
            $editAsset->account_name = $request->input( 'account_name' );
        }
        if ( $request->has( 'account_number' ) ) {
            $editAsset->account_number = $request->input( 'account_number' );
        }
        if ( $request->has( 'other_information' ) ) {
            $editAsset->other_information = $request->input( 'other_information' );
        }
        // update the assets details
        $editAsset->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editAsset,
            'message' => 'Other assets updated successfully.',
        ]);
    }
    
    public function getAsset() {
        $response = [];
        
        $user = Auth::user();
        
        // get all assets
        $getAssets = OtherAsset::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getAssets as $getAsset ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getAsset->category_id )
            ->where( 'document_id', $getAsset->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getAsset->share_with = $shareWith;
          }
        
        if ( !empty( $getAssets ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getAssets,
                'message'   => 'Assets retrieved successfully.'
            ]);
                
        } else {
            return response()->json([
                'success'   => false, 
                'status'  => 400,
                'message' => 'No assets found' 
            ]);
        }
    }
    
     public function deleteAsset( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the asset by ID
        $deleteAsset = OtherAsset::find( $request->id );
    
        // If the asset not exist, return an error
        if ( !$deleteAsset ) {
            return response()->json([ 
                'success' => false, 
                'status'  => 400,
                'message' => 'Assets not found.' 
            ]);
        } else {
            // Delete the asset
            $deleteAsset->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Assets deleted successfully.',
            ]);
        }
    }
    
     public function addLoan( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make($request->all(), [ 
            'category_id'   => 'required|exists:categories,id',  // Ensure category exists     
            'loan_type'     => 'required|string',
            'lender_name'   => 'required|string',
            'loan_number'   => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and details from request
        $categoryId  = $request->input( 'category_id' );
        $loanType    = $request->input( 'loan_type' );
        $lenderName  = $request->input( 'lender_name' );
        $loanNo      = $request->input( 'loan_number' ); 
    
        // Check if already exists for this user
        $existingAccount = Loan::where( 'user_id', $user->id )
                                      ->where( 'loan_number', $loanNo )
                                      ->first();
    
        if ( $existingAccount ) {
            // If already exists, return an error message
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Loan number already added.'
            ]);
        }
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.' 
            ]);
        }
    
        // Create a new 
        $addLoans = new Loan();
        $addLoans->user_id          = $user->id;
        $addLoans->category_id      = $categoryId;
        $addLoans->loan_type        = $loanType;
        $addLoans->lender_name      = $lenderName;
        $addLoans->loan_number      = $loanNo;
        $addLoans->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $addLoans,
            'message' => 'Loans added successfully.',
        ]);
    }
    
    public function editLoan( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'             =>  'required|integer',
            'loan_type'      =>  'nullable|string', 
            'lender_name'    =>  'nullable|string',
            'loan_number'    =>  'nullable|string'
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editLoan = Loan::find( $request->id );
        
        // If the Life insurance does not exist, return an error
        if ( !$editLoan ) {
            return response()->json([ 
                'success'  => false, 
                'status'   => 400,
                'message'  => 'No loans found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'loan_type' ) ) {
            $editLoan->loan_type = $request->input( 'loan_type' );
        }
        if ( $request->has( 'lender_name' ) ) {
            $editLoan->lender_name = $request->input( 'lender_name' );
        }
        if ( $request->has( 'loan_number' ) ) {
            $editLoan->loan_number = $request->input( 'loan_number' );
        }
        // update the other asset details
        $editLoan->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editLoan,
            'message' => 'Loans updated successfully.',
        ]);
    }
    
    public function getLoan() {
        $response = [];
        
        $user = Auth::user();
        
        // get all loan records 
        $getLoans = Loan::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getLoans as $getLoan ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getLoan->category_id )
            ->where( 'document_id', $getLoan->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getLoan->share_with = $shareWith;
          }
        
        if ( !empty( $getLoans ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getLoans,
                'message'   => 'Loans retrieved successfully.'
            ]);
                
        } else {
            return response()->json([
                'success' => false, 
                'status'  => 400,
                'message' => 'No loans found' 
            ]);
        }
    }
    
    public function deleteLoan( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the loan by ID
        $deleteLoan = Loan::find( $request->id );
    
        // If the loan does not exist, return an error
        if ( !$deleteLoan ) {
            return response()->json([
                'success'  => false, 
                'status'   => 400,
                'message'  => 'Loans not found.' 
                ]);
        } else {
            // Delete the loan
            $deleteLoan->delete();
        
            // Return a success response
            return response()->json([
                'success'  => true,
                'status'   => 200,
                'message'  => 'Loans deleted successfully.',
            ]);
        }
    }
    
    public function addCredit( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make( $request->all(), [ 
            'category_id'      => 'required|exists:categories,id',  // Ensure category exists     
            'card_type'        => 'required|string',
            'card_name'        => 'required|string',
            'bank_name'        => 'required|string',
            'account_number'   => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and account details from request
        $categoryId     = $request->input( 'category_id' );
        $cardType       = $request->input( 'card_type' );
        $cardName       = $request->input( 'card_name' );
        $bankName       = $request->input( 'bank_name' ); 
        $accountNumber  = $request->input( 'account_number' );
        
        
        $existingAccount = CreditCard::where( 'user_id', $user->id )->where( 'account_number', $accountNumber )->first();
    
        if ( $existingAccount ) {
            // If the account already exists, return an error message
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Account number already added.'
            ]);
        }
    
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.' 
            ]);
        }
    
        // Create a new credit record for the user under the given category
        $addcard = new CreditCard();
        $addcard->user_id          = $user->id;
        $addcard->category_id      = $categoryId;
        $addcard->card_type        = $cardType;
        $addcard->card_name        = $cardName;
        $addcard->bank_name        = $bankName;
        $addcard->account_number   = $accountNumber;
        $addcard->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $addcard,
            'message' => 'Credit card added successfully.',
        ]);
    }
    
    public function editCredit( Request $request) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'             =>  'required|integer',
            'card_type'      =>  'nullable|string', 
            'card_name'      =>  'nullable|string',
            'bank_name'      =>  'nullable|string', 
            'account_number' =>  'nullable|string' 
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editCard = CreditCard::find( $request->id );
        
        // If the credit record does not exist, return an error
        if ( !$editCard ) {
            return response()->json([ 
                'success'  => false, 
                'status'   => 400,
                'message'  => 'No credit card found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'card_type' ) ) {
            $editCard->card_type = $request->input( 'card_type' );
        }
        if ( $request->has( 'card_name' ) ) {
            $editCard->card_name = $request->input( 'card_name' );
        }
        if ( $request->has( 'bank_name' ) ) {
            $editCard->bank_name = $request->input( 'bank_name' );
        }
        if ( $request->has( 'account_number' ) ) {
            $editCard->account_number = $request->input( 'account_number' );
        }
        // update the credit record details
        $editCard->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editCard,
            'message' => 'Credit card updated successfully.',
        ]);
    }
    
    public function getCredit() {
        $response = [];
        
        $user = Auth::user();
        
        // get all credit records
        $getCredits = CreditCard::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getCredits as $getCredit ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getCredit->category_id )
            ->where( 'document_id', $getCredit->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getCredit->share_with = $shareWith;
          }
        
        if ( !empty( $getCredits ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getCredits,
                'message'   => 'Credit card retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'No credit card found' 
            ]);
        }
    }
    
    public function deleteCredit( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the credit record by ID
        $deleteCredit = CreditCard::find( $request->id );
    
        // If the credit card does not exist, return an error
        if ( !$deleteCredit ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Credit card not found.' 
                ]);
        } else {
            // Delete the credit record
            $deleteCredit->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Credit card deleted successfully.',
            ]);
        }
    }
    
    public function addDebt( Request $request ) {
        $response = [];
    
        // Validate input data
        $validator = Validator::make( $request->all(), [ 
            'category_id'         => 'required|exists:categories,id',  // Ensure category exists     
            'loan_type'           => 'required|string',
            'lender_name'         => 'required|string',
            'account_number'      => 'required|string',
            'other_information'   => 'required|string',
        ]);
    
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        // Get an authenticated user
        $user = Auth::user();
    
        // Retrieve category ID and debt record from request
        $categoryId    = $request->input( 'category_id' );
        $loanType      = $request->input( 'loan_type' );
        $lenderName    = $request->input( 'lender_name' );
        $accountNo     = $request->input( 'account_number' );
        $info          = $request->input( 'other_information' ); 
    
        // Check if already exists for this user
        $existingAccount = OtherDebt::where( 'user_id', $user->id )->where( 'account_number', $accountNo )->first();
    
        if ( $existingAccount ) {
            // If already exists, return an error message
            return response()->json([
                'success'  => false,
                'status'  => 400,
                'message' => 'Account number already added.'
            ]);
        }
        // Find the category (it should exist, as validated)
        $category = Category::find( $categoryId );
        if ( !$category ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.' 
            ]);
        }
    
        // Create a new debt for the user under the given category
        $otherDebt = new OtherDebt();
        $otherDebt->user_id            = $user->id;
        $otherDebt->category_id        = $categoryId;
        $otherDebt->loan_type          = $loanType;
        $otherDebt->lender_name        = $lenderName;
        $otherDebt->account_number     = $accountNo;
        $otherDebt->other_information  = $info;
        $otherDebt->save();
    
        // Return a success response with the reordered data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $otherDebt,
            'message' => 'Other debts added successfully.',
        ]);
    }
    
    public function editDebt( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                 =>  'required|integer',
            'loan_type'          =>  'nullable|string', 
            'lender_name'        =>  'nullable|string', 
            'account_number'     =>  'nullable|string', 
            'other_information'  =>  'nullable|string'
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editAsset = OtherDebt::find( $request->id );
        
        // If the debt  not exist, return an error
        if ( !$editAsset ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Other debts not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'loan_type' ) ) {
            $editAsset->loan_type = $request->input( 'loan_type' );
        }
        if ( $request->has( 'lender_name' ) ) {
            $editAsset->lender_name = $request->input( 'lender_name' );
        }
        if ( $request->has( 'account_number' ) ) {
            $editAsset->account_number = $request->input( 'account_number' );
        }
        if ( $request->has( 'other_information' ) ) {
            $editAsset->other_information = $request->input( 'other_information' );
        }
        // update the other debts details
        $editAsset->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editAsset,
            'message' => 'Other debts updated successfully.',
        ]);
    }
    
     public function getDebt() {
        $response = [];
        
        $user = Auth::user();
        
        // get all debts
        $getDebts = OtherDebt::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
        foreach( $getDebts as $getDebt ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getDebt->category_id )
            ->where( 'document_id', $getDebt->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getDebt->share_with = $shareWith;
          }
        
        if ( !empty( $getDebts ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getDebts,
                'message'   => 'Other debts retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'No other debts found' 
            ]);
        }
    }
    
    public function deleteDebt( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the debt by ID
        $deleteAsset = OtherDebt::find( $request->id );
    
        // If the debt not exist, return an error
        if (!$deleteAsset) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Other debts not found.' 
            ]);
        } else {
            // Delete the debt
            $deleteAsset->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Other debts deleted successfully.',
            ]);
        }
    }
    
    public function addWill( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'      => 'required|exists:categories,id',
            'title'            => 'required|string',
            'document'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
          $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid()) {
          
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
            // Create a new legal will record
            $will = new LegalWill();
            $will->category_id     = $request->category_id;
            $will->user_id         = $user->id;
            $will->title           = $request->title;
            $will->document_type   = $file->getClientOriginalExtension();
            $will->document        = $fileNameOnly;
            $will->size            = $size;
            $will->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $will,
                'message' => 'Will document added successfully.',
            ]);
        }
    }
    
    public function editWill( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'           =>  'required|integer',
            'title'        =>  'nullable|string', 
            'document'     =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editWill = LegalWill::find( $request->id );
        
        // If the will  not exist, return an error
        if ( !$editWill ) {
            return response()->json([ 
                'status'   => 400,
                'message'  => 'Will not found.' 
            ]);
        }
        $category = Category::find( $editWill->category_id );
        if ( !$category ) {
            return response()->json([
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        
        if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file      = $request->file( 'document' );
            $filename  = time() . '.' . $file->getClientOriginalExtension();
            $size      =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editWill->document && Storage::disk( 'public' )->exists( 'documents/' . $editWill->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editWill->document );
            }
            
            // Update the model with new document info
            $editWill->document      = $filename;
            $editWill->document_type = $file->getClientOriginalExtension();
            $editWill->size          = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editWill->title = $request->input( 'title' );
        }
       
        // update the other debts details
        $editWill->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editWill,
            'message' => 'Will updated successfully.',
        ]);
    }
    
    public function getWill() {
        $response = [];
        
        $user = Auth::user();
        
        // get all docs
        $getWill = LegalWill::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getWill as $will ){
              
             $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $will->category_id )
            ->where( 'document_id', $will->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $will->share_with=$shareWith;
          }
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getWill,
                'message'   => 'Will retrieved successfully.'
            ]);
    }
    
     public function deleteWill( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the will by ID
        $deleteWill = LegalWill::find( $request->id );
    
        // If the will not exist, return an error
        if ( !$deleteWill ) {
            return response()->json([ 
                'status'  => true,
                'status'  => 400,
                'message' => 'Other debts not found.' 
            ]);
        } else {
            // Delete the will doc or image
            $deleteWill->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Will deleted successfully.',
            ]);
        }
    }
    
    public function addTrust( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string',
            'document'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
        $category = Category::find( $request->category_id );
            if ( !$category ) {
                return response()->json([
                    'success' => false,
                    'status'  => 400,
                    'message' => 'Category not found.'
                ]);
            }
            if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid() ) {
               
                $file      = $request->file( 'document' );
                $filename  = time() . '.' . $file->getClientOriginalExtension();
                
                
                $filePath      = $file->storeAs( 'documents', $filename, 'public' );
                $fileNameOnly  =  $filename; 
                $size          =  $file->getSize(); 
                
                if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Storage limit exceeded'
                    ]);
                }
                
                $trust = new LegalTrust();
                $trust->category_id    = $request->category_id;
                $trust->user_id        = $user->id;
                $trust->title          = $request->title;
                $trust->document_type  = $file->getClientOriginalExtension();
                $trust->document       = $fileNameOnly;
                $trust->size           = $size;
                $trust->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $trust,
                'message' => 'Trust document added successfully.',
            ]);
            }
    }
    
    public function editTrust( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'           =>  'required|integer',
            'title'        =>  'nullable|string', 
            'document'     =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ] );
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editTrust = LegalTrust::find( $request->id );
        
        // If the trust document not exist, return an error
        if ( !$editTrust ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Trust document not found.' 
            ]);
        }
        $category = Category::find( $editTrust->category_id );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        
         if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file      = $request->file( 'document' );
            $filename  = time() . '.' . $file->getClientOriginalExtension();
            $size      =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editTrust->document && Storage::disk( 'public' )->exists( 'documents/' . $editTrust->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editTrust->document );
            }
            
            // Update the model with new document info
            $editTrust->document      = $filename;
            $editTrust->document_type = $file->getClientOriginalExtension();
            $editTrust->size          = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editTrust->title = $request->input( 'title' );
        }
       
        $editTrust->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editTrust,
            'message' => 'Trust document updated successfully.',
        ]);
    }
    
    public function getTrust() {
        $response = [];
        
        $user = Auth::user();
        
        // get all docs
        $getTrust = LegalTrust::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getTrust as $trust ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $trust->category_id )
            ->where( 'document_id', $trust->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $trust->share_with = $shareWith;
          }
        
        if ( !empty( $getTrust ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getTrust,
                'message'   => 'Trust document retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Trust document not found' 
            ]);
        }
    }
    
     public function deleteTrust ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
                // Find the will by ID
                $deleteTrust = LegalTrust::find( $request->id );
            
        // If the doc not exist, return an error
        if ( !$deleteTrust ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Trust document not found.' 
            ]);
        } else {
            // Delete the doc or image
            $deleteTrust->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Trust document deleted successfully.',
            ]);
        }
    }
    
    public function addAttorney ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'   => 'required|exists:categories,id',
            'title'         => 'required|string',
            'document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ] );
        
        if ( $validator->fails()  ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
          $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid()) {
           
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
          
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
          
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
          
            $attorney = new PowerAttorney();
            $attorney->category_id    = $request->category_id;
            $attorney->user_id        = $user->id;
            $attorney->title          = $request->title;
            $attorney->document_type  = $file->getClientOriginalExtension();
            $attorney->document       = $fileNameOnly;
            $attorney->size           = $size;
            
            $attorney->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $attorney,
                'message' => 'Power attorney added successfully.',
            ]);
        }
    }
    
    public function editAttorney ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'          =>  'required|integer',
            'title'       =>  'nullable|string', 
            'document'    =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editAttorney = PowerAttorney::find( $request->id );
        
        // If the document not exist, return an error
        if ( !$editAttorney ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Power attorney not found.' 
            ]);
        }
        $category = Category::find( $editAttorney->category_id );
        if (!$category) {
            return response()->json([
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        
        if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file       = $request->file( 'document' );
            $filename   = time() . '.' . $file->getClientOriginalExtension();
            $size       =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editAttorney->document && Storage::disk( 'public' )->exists( 'documents/' . $editAttorney->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editAttorney->document );
            }
            
            // Update the model with new document info
            $editAttorney->document       = $filename;
            $editAttorney->document_type  = $file->getClientOriginalExtension();
            $editAttorney->size           = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editAttorney->title = $request->input( 'title' );
        }
       
        // update the document details
        $editAttorney->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editAttorney,
            'message' => 'Power attorney updated successfully.',
        ]);
    }
    
    public function getAttorney () {
        $response = [];
        
        $user = Auth::user();
        
        // get document
        $getAttorney = PowerAttorney::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getAttorney as $attorney ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $attorney->category_id )
            ->where( 'document_id', $attorney->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $attorney->share_with = $shareWith;
          }
        
        if ( !empty( $getAttorney ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getAttorney,
                'message'   => 'Power attorney retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Power attorney not found' 
            ]);
        }
    }
    
     public function deleteAttorney ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the doc by ID
        $deleteTrust = PowerAttorney::find( $request->id );
    
        // If the doc not exist, return an error
        if ( !$deleteTrust ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message'   => 'Power attorney not found.' 
            ]);
        } else {
            // Delete the document or image
            $deleteTrust->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Power attorney deleted successfully.',
            ]);
        }
    }
    
    public function addEstate ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string',
            'document'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
         $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid()) {
           
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
           
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
            $estate = new RealEstateDeed();
            $estate->category_id    = $request->category_id;
            $estate->user_id        = $user->id;
            $estate->title          = $request->title;
            $estate->document_type  = $file->getClientOriginalExtension();
            $estate->document       = $fileNameOnly;
            $estate->size           = $size;
            $estate->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $estate,
                'message' => 'Real estate deeds added successfully.',
            ]);
        }
    }
    
    public function editEstate ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'           =>  'required|integer',
            'title'        =>  'nullable|string', 
            'document'     =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editEstate = RealEstateDeed::find( $request->id );
        
        // If the doc not exist, return an error
        if ( !$editEstate ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Real estate deeds not found.' 
            ]);
        }
        $category = Category::find( $editEstate->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        
         if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $size     =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editEstate->document && Storage::disk( 'public' )->exists( 'documents/' . $editEstate->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editEstate->document );
            }
            
            // Update the model with new document info
            $editEstate->document        = $filename;
            $editEstate->document_type   = $file->getClientOriginalExtension();
            $editEstate->size            = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editEstate->title = $request->input( 'title' );
        }
       
        // update the doc details
        $editEstate->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editEstate,
            'message' => 'Real estate deeds updated successfully.',
        ]);
    }
    
    public function getEstate () {
        $response = [];
        
        $user = Auth::user();
        
        // get all debts
        $getEstate = RealEstateDeed::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getEstate as $estate ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $estate->category_id )
            ->where( 'document_id', $estate->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $estate->share_with = $shareWith;
          }
        
        if ( !empty( $getEstate ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getEstate,
                'message'   => 'Real estate deeds retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Real estate deeds not found' 
            ]);
        }
    }
    
    public function deleteEstate ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the doc by ID
        $deleteEstate = RealEstateDeed::find( $request->id );
    
        // If the doc not exist, return an error
        if ( !$deleteEstate ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => ' Real estate deeds not found.' 
            ]);
                
        } else {
            // Delete the doc or image
            $deleteEstate->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Real estate deeds deleted successfully.',
            ]);
        }
    }
    
    public function addBusiness ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'      => 'required|exists:categories,id',
            'title'            => 'required|string',
            'document'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
          $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid() ) {
            
          
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
    
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
           
            $addBusiness = new BusinessDocument();
            $addBusiness->category_id    = $request->category_id;
            $addBusiness->user_id        = $user->id;
            $addBusiness->title          = $request->title;
            $addBusiness->document_type  = $file->getClientOriginalExtension();
            $addBusiness->document       = $fileNameOnly;
            $addBusiness->size           = $size;
            
            $addBusiness->save();
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $addBusiness,
            'message' => 'Business document added successfully.',
        ]);
        }
    }
    
    public function editBusiness ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'           =>  'required|integer',
            'title'        =>  'nullable|string', 
            'document'     =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editBusiness = BusinessDocument::find( $request->id );
        
        // If the document not exist, return an error
        if ( !$editBusiness ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Business document not found.' 
            ]);
        }
        $category = Category::find( $editBusiness->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        
       if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $size     =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editBusiness->document && Storage::disk( 'public' )->exists( 'documents/' . $editBusiness->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editBusiness->document );
            }
            
            // Update the model with new document info
            $editBusiness->document      = $filename;
            $editBusiness->document_type = $file->getClientOriginalExtension();
            $editBusiness->size          = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editBusiness->title = $request->input( 'title' );
        }
       
        $editBusiness->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editBusiness,
            'message' => 'Business document updated successfully.',
        ]);
    }
    
    public function getBusiness () {
        $response = [];
        
        $user = Auth::user();
        
        // get all docs
        $getBusiness = BusinessDocument::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getBusiness as $business ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $business->category_id )
            ->where( 'document_id', $business->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $business->share_with = $shareWith;
          }
        
        if ( !empty( $getBusiness ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getBusiness,
                'message'   => 'Business document retrieved successfully.'
            ]);
                
        } else {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Business document not found' 
            ]);
        }
    }
    
    public function deleteBusiness ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the doc by ID
        $deleteBusiness = BusinessDocument::find( $request->id );
    
        // If the doc not exist, return an error
        if (!$deleteBusiness) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Business document not found.' 
                ]);
        } else {
            // Delete the doc or image
            $deleteBusiness->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Business document deleted successfully.',
            ]);
        }
    }
    
    public function addTaxReturn ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string',
            'document'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
          $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid() ) {
            
            // Store the file in documents directory
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
            // Store the file in the 'documents' folder with the new unique name
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
            $addBusiness = new TaxReturnDocument();
            $addBusiness->category_id    = $request->category_id;
            $addBusiness->user_id        = $user->id;
            $addBusiness->title          = $request->title;
            $addBusiness->document_type  = $file->getClientOriginalExtension();
            $addBusiness->document       = $fileNameOnly;
            $addBusiness->size           = $size;
            $addBusiness->save();
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $addBusiness,
            'message' => 'Tax return document added successfully.',
        ]);
        }
    }
    
    public function editTaxReturn ( Request $request ) {
        $response = [];
        
        $validator = Validator::make($request->all(), [
            'id'           =>  'required|integer',
            'title'        =>  'nullable|string', 
            'document'     =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editReturn = TaxReturnDocument::find( $request->id );
        
        // If the document not exist, return an error
        if ( !$editReturn ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Tax return document not found.' 
            ]);
        }
        $category = Category::find( $editReturn->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        
        if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $size     =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editReturn->document && Storage::disk( 'public' )->exists( 'documents/' . $editReturn->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editReturn->document );
            }
            
            // Update the model with new document info
            $editReturn->document      = $filename;
            $editReturn->document_type = $file->getClientOriginalExtension();
            $editReturn->size          = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editReturn->title = $request->input( 'title' );
        }
       
        $editReturn->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editReturn,
            'message' => 'Tax return document updated successfully.',
        ]);
    }
    
    public function getTaxReturn () {
        $response = [];
        
        $user = Auth::user();
        
        // get all docs
        $getTaxReturn = TaxReturnDocument::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getTaxReturn as $taxReturn ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $taxReturn->category_id )
            ->where( 'document_id', $taxReturn->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $taxReturn->share_with = $shareWith;
          }
        
        if ( !empty( $getTaxReturn ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getTaxReturn,
                'message'   => 'Tax return document retrieved successfully.'
            ]);
                
        } else {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Tax return document not found' 
            ]);
        }
    }
    
    public function deleteTaxReturn ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the doc by ID
        $deleteBusiness = TaxReturnDocument::find( $request->id );
    
        // If the doc not exist, return an error
        if ( !$deleteBusiness ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Tax return document not found.' 
            ]);
        } else {
            // Delete the doc or image
            $deleteBusiness->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Tax return document deleted successfully.',
            ]);
        }
    }
    
    public function addOtherDocument ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'   => 'required|exists:categories,id',
            'title'         => 'required|string',
            'document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
          $category = Category::find( $request->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid()) {
            // Store the file in documents directory
            $file     = $request->file( 'document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            
            // Store the file in the documents folder with the new unique name
            $filePath     = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly =  $filename; 
            $size         =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
            $addDoc = new OtherLegalDocument();
            $addDoc->category_id    = $request->category_id;
            $addDoc->user_id        = $user->id;
            $addDoc->title          = $request->title;
            $addDoc->document_type  = $file->getClientOriginalExtension();
            $addDoc->document       = $fileNameOnly;
            $addDoc->size           = $size;
            $addDoc->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $addDoc,
                'message' => 'Legal document added successfully.',
            ]);
        }
    }
    
    public function editOtherDocument ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'          =>  'required|integer',
            'title'       =>  'nullable|string', 
            'document'    =>  'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
        
        $user = Auth::user();
        
        $editDoc = OtherLegalDocument::find( $request->id );
        
        // If the document not exist, return an error
        if ( !$editDoc ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Legal document not found.' 
            ]);
        }
        $category = Category::find( $editDoc->category_id );
        if ( !$category ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Category not found.'
            ]);
        }
        
        if ( $request->hasFile( 'document' ) ) {
        
            // Store the new file
            $file      = $request->file( 'document' );
            $filename  = time() . '.' . $file->getClientOriginalExtension();
            $size      =  $file->getSize();
            
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
             $filePath = $file->storeAs( 'documents', $filename, 'public' );
             
            if ( $editDoc->document && Storage::disk( 'public' )->exists( 'documents/' . $editDoc->document ) ) {
                // Delete the old file
                Storage::disk( 'public' )->delete( 'documents/' . $editDoc->document );
            }
            
            // Update the model with new document info
            $editDoc->document       = $filename;
            $editDoc->document_type  = $file->getClientOriginalExtension();
            $editDoc->size           = $size;
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' )) {
                $editDoc->title = $request->input( 'title' );
        }
       
        // update the document details
        $editDoc->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editDoc,
            'message' => 'Legal document updated successfully.',
        ]);
    }
    
    public function getOtherDocument () {
        $response = [];
        
        $user = Auth::user();
        
        // get all document
        $getLegalDocument = OtherLegalDocument::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
         foreach( $getLegalDocument as $legalDocument ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $legalDocument->category_id )
            ->where( 'document_id', $legalDocument->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $legalDocument->share_with = $shareWith;
          }
        
        if ( !empty( $getLegalDocument ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getLegalDocument,
                'message'   => 'Legal document retrieved successfully.'
            ]);
                
        } else {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Legal document not found' 
            ]);
        }
    }
    
    public function deleteOtherDocument ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the doc by ID
        $deleteDoc = OtherLegalDocument::find( $request->id );
    
        // If the dic not exist, return an error
        if ( !$deleteDoc ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Legal document not found.' 
            ]);
            
        } else {
            // Delete the document or image
            $deleteDoc->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Legal document deleted successfully.',
            ]);
        }
    }
    
    public function addLogin ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'        => 'required|exists:categories,id',
            'email'              => 'required|string',
            'password'           => 'nullable|string',
            'other_information'  => 'nullable|string',
            'description'        => 'nullable|string',
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
            // Create a new login record
            $login = new Login();
            $login->category_id        = $request->category_id;
            $login->user_id            = $user->id;
            $login->email              = $request->email;
			$login->password           = $request->password; 
			$login->other_information  = $request->other_information;
			$login->description        = $request->description;
            $login->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $login,
                'message' => 'Login details added successfully.',
            ]);        
    }
	
	public function editLogin( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id'                    =>  'required|integer',
            'email'                 =>  'nullable|string', 
            'password'              =>  'nullable|string', 
            'other_information'     =>  'nullable|string',
			'description'           =>  'nullable|string'
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
    
        $editLogins = Login::find( $request->id );
        
        // If the debt  not exist, return an error
        if ( !$editLogins ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'Logins not found.' 
            ]);
        }
        
        // Update only the fields that are provided in the request
        if ( $request->has( 'email' )) {
             $editLogins->email = $request->input( 'email' );
        }
        if ( $request->has( 'password' )) {
             $editLogins->password = $request->input( 'password' );
        }       
        if ( $request->has( 'other_information' )) {
             $editLogins->other_information = $request->input( 'other_information' );
        }
		 if ( $request->has( 'description' )) {
             $editLogins->description = $request->input( 'description' );
        }
        // update the other debts details
        $editLogins->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editLogins,
            'message' => 'Login updated successfully.',
        ]);
    }
    
    public function getLogin () {
        $response = [];
        
        $user = Auth::user();
        
        // get all logins
        $getLogins = Login::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
          foreach( $getLogins as $getLogin ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getLogin->category_id )
            ->where( 'document_id', $getLogin->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getLogin->share_with = $shareWith;
          }
            
             // Return a success response
            return response()->json([ 
                'success'   => true, 
                 'status'   => 200,
                'data'      => $getLogins,
                'message'   => 'Login retrieved successfully.'
            ]);
    }
    
    public function deleteLogin ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find the login by ID
        $deleteLogins = Login::find( $request->id );
    
        // If the logins not exist, return an error
        if ( !$deleteLogins ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Login not found.' 
            ]);
        } else {
            // Delete the logins
            $deleteLogins->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Login deleted successfully.',
            ]);
        }
    }
    
    public function addLifeJournal ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'category_id'     => 'required|exists:categories,id',
            'title'           => 'required|string',
            'message'         => 'nullable|string', 
            'audio_document'  => 'nullable|file|mimes:mp3,wav|max:20000', 
            'video_document'  => 'nullable|file|mimes:mp4,temp,mov|max:100000', 
        ]);
        
        if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
         }
        
        $user = Auth::user();
        
        $category = Category::find( $request->category_id );
        
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        // Initialize file variables
        $audioFile = null;
        $videoFile = null;
        $totalSize = 0; // Initialize total size variable
    
        // Handle audio file upload if exists
        if ( $request->hasFile( 'audio_document' ) && $request->file( 'audio_document' )->isValid() ) {
            $audioFile      = $request->file( 'audio_document' );
            $audioFilename  = time() . '_audio.' . $audioFile->getClientOriginalExtension();
            $audioFilePath  = $audioFile->storeAs( 'documents', $audioFilename, 'public' );
            $totalSize     += $audioFile->getSize(); // Add audio file size to total size
        }
    
        // Handle video file upload if exists
        if ( $request->hasFile( 'video_document' ) && $request->file( 'video_document' )->isValid() ) {
            $videoFile       = $request->file( 'video_document' );
            $videoFilename   = time() . '_video.' . $videoFile->getClientOriginalExtension();
            $videoFilePath   = $videoFile->storeAs( 'documents', $videoFilename, 'public' );
            $totalSize      += $videoFile->getSize(); // Add video file size to total size
        }
    
        // Check if the total uploaded size exceeds the user's storage limit
        if ( parent::totalUploadedSize( $user->id ) + $totalSize > $user->maxStorageLimit() ) {
            return response()->json([
                'status'  => 400,
                'message' => 'Storage limit exceeded.'
            ]);
        }
    
        // Create the LifeJournal record
        $addLifeJournal = new LifeJournal();
        $addLifeJournal->category_id = $request->category_id;
        $addLifeJournal->user_id     = $user->id;
        $addLifeJournal->title       = $request->title;
        $addLifeJournal->message     = $request->message;
        
        // Store audio/video file details
        if ( $audioFile ) {
            $addLifeJournal->audio_document = $audioFilename;
            $addLifeJournal->audio_size     = $audioFile->getSize();
        } 
        if ( $videoFile ) {
            $addLifeJournal->video_document = $videoFilename;
            $addLifeJournal->video_size     = $videoFile->getSize();
        }
    
        $addLifeJournal->save();
    
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'data'     => $addLifeJournal,
            'message'  => 'Life journal added successfully.',
        ]);
    }
    
    public function editLifeJournal ( Request $request ) {
        $response = [];
    
        $validator = Validator::make( $request->all(), [
            'id'               => 'required|integer',
            'title'            => 'nullable|string', 
            'message'          => 'nullable|string', 
            'audio_document'   => 'nullable|file|mimes:mp3,wav|max:20000',
            'video_document'   => 'nullable|file|mimes:mp4,temp,mov|max:100000',
        ]);
       
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
       
        $user = Auth::user();
        
        // Retrieve the LifeJournal entry to be edited
        $editLifeJournal = LifeJournal::find( $request->id );
        
        // If the LifeJournal document does not exist, return an error
        if ( !$editLifeJournal ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Life journal not found.'
            ]);
        }
        
        // Retrieve the category associated with the LifeJournal
        $category = Category::find( $editLifeJournal->category_id );
        if ( !$category ) {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Category not found.'
            ]);
        }
        
        if ( $request->hasFile( 'audio_document' ) ) {
            // Store the new audio file
            $file      = $request->file( 'audio_document' );
            $filename  = time() . '.' . $file->getClientOriginalExtension();
            $size      = $file->getSize();
    
            // Check if the user exceeds their storage limit
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
    
            // Store the file and delete the old one if exists
            $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editLifeJournal->audio_document && Storage::disk( 'public' )->exists( 'documents/' . $editLifeJournal->audio_document ) ) {
                // Delete the old audio file
                Storage::disk( 'public' )->delete( 'documents/' . $editLifeJournal->audio_document );
            }
    
            // Update the model with new document info
            $editLifeJournal->audio_document      = $filename;
            $editLifeJournal->audio_document_type = $file->getClientOriginalExtension();
            $editLifeJournal->audio_size          = $size;
        }
    
        // Handle video document upload
        if ( $request->hasFile( 'video_document' ) ) {
            // Store the new video file
            $file     = $request->file( 'video_document' );
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $size     = $file->getSize();
    
            // Check if the user exceeds their storage limit
            if ( parent::totalUploadedSize( $user->id ) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
    
            // Store the file and delete the old one if exists
            $filePath = $file->storeAs( 'documents', $filename, 'public' );
            if ( $editLifeJournal->video_document && Storage::disk( 'public' )->exists( 'documents/' . $editLifeJournal->video_document ) ) {
                // Delete the old video file
                Storage::disk( 'public' )->delete( 'documents/' . $editLifeJournal->video_document );
            }
    
            // Update the model with new document info
            $editLifeJournal->video_document      = $filename;
            $editLifeJournal->video_document_type = $file->getClientOriginalExtension();
            $editLifeJournal->video_size          = $size;
        }
    
        // Update only the fields that are provided in the request
        if ( $request->has( 'title' ) ) {
            $editLifeJournal->title = $request->input( 'title' );
        }
    
        if ( $request->has( 'message' ) ) {
            $editLifeJournal->message = $request->input( 'message' );
        }
    
        // Save the updated journal entry
        $editLifeJournal->update();
        
        // Return a success response with the updated data
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $editLifeJournal,
            'message' => 'Life journal updated successfully.',
        ]);
    }
    
    public function getLifeJournal () {
        $response = [];
        
        $user = Auth::user();
        
        // get all docs
        $getLifeJournals = LifeJournal::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
        
        foreach( $getLifeJournals as $getLifeJournal ){
              
            $shareWith = ShareDocuments::where( 'user_id', $user->id )
            ->where( 'category_id', $getLifeJournal->category_id )
            ->where( 'document_id', $getLifeJournal->id )
            ->with([ 'contact.user' ]) 
            ->get();
              $getLifeJournal->share_with = $shareWith;
          }
        
        if ( !empty( $getLifeJournals ) ) {
            
             // Return a success response
            return response()->json([ 
                'success'   => true,
                'status'    => 200,
                'data'      => $getLifeJournals,
                'message'   => 'Life journal retrieved successfully.'
                ]);
                
        } else {
            return response()->json([
                'success' => false,
                'status'  => 400,
                'message' => 'Life journal not found' 
            ]);
        }
    }
    
    public function getSharedWithMeJournal ( Request $request ) { 
        $response = [];
          $validator = Validator::make( $request->all(), [
            'category_id'  => 'required|exists:categories,id',
            ]);
            
        // Return validation errors if any
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400 );
        }
      
        $user = Auth::user();
    
        $lifeJournals = ShareDocuments::with( 'contact' )->whereHas( 'contact', function ( $query ) use ( $user ) { 
            $query->where( 'email', $user->email ); 
        })   
        ->where( 'category_id', $request->category_id )
        ->get();   
          $arr = [];
        foreach( $lifeJournals as $document ){
        
        $doc=ShareDocuments::where( 'id',$document->id )
        ->with([ 'category.lifejournals' => function( $query ) use ( $document ) {
            $query->where( 'id', $document->document_id );  
        }])
        ->first();
        
       // $user=User::find($document->user_id);
       $user=Contact::find( $document->contact_id );
        
        $doc->shareBy = $user;
        $arr[] = $doc;
    }
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $arr,
            'message' => 'Life journals retrieved successfully'
        ]);
    }
    
    public function deleteLifeJournal ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
        // Find by ID
        $deleteJournal = LifeJournal::find( $request->id );
    
        // If the doc not exist, return an error
        if ( !$deleteJournal ) {
            return response()->json([ 
                'success' => false,
                'status'  => 400,
                'message' => 'Life journal not found.' 
            ]);
        } else {
            // Delete the doc or image
            $deleteJournal->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Life journal deleted successfully.',
                
            ]);
        }
    }
    
    public function uploadDocument ( Request $request)  {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = Auth::user();
        
        if ( $request->hasFile( 'document' ) && $request->file( 'document' )->isValid()) {
          
            $file          = $request->file( 'document' );
            $filename      = time() . '.' . $file->getClientOriginalExtension();
            $filePath      = $file->storeAs( 'documents', $filename, 'public' );
            $fileNameOnly  =  $filename; 
            $size          =  $file->getSize(); 
            
            if ( parent::totalUploadedSize( $user->id) + $size > $user->maxStorageLimit() ) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Storage limit exceeded'
                ]);
            }
            
            // Create a new record
            $uploadDoc = new Document();
            $uploadDoc->user_id        = $user->id;
            $uploadDoc->document_type  = $file->getClientOriginalExtension();
            $uploadDoc->document       = $fileNameOnly;
            $uploadDoc->size           = $size;
            $uploadDoc->save();
            
            return response()->json([
                'success' => true,
                'status'  => 200,
                'data'    => $uploadDoc,
                'message' => 'Document uploaded successfully.',
            ]);
        }
    }
    
       public function getDocument () {
        $response = [];
        
        $user = Auth::user();
        
        // get all uploads
        $getDocuments = Document::where( 'user_id', $user->id )->orderBy( 'created_at', 'desc' )->get();
          
            return response()->json([ 
                'success'   => true, 
                'status'    => 200,
                'data'      => $getDocuments,
                'message'   => 'Document retrieved successfully.'
            ]);
    }
    
    public function deleteDocument ( Request $request ) {
        $response = [];
        
        $validator = Validator::make( $request->all(), [
            'id' => 'required|integer',
            ]);
            
            if( $validator->fails() ) {
                return response()->json([ 'errors' => $validator->errors() ], 400 );
            }
            // Find the doc by ID
            $deleteDoc = Document::find( $request->id );
        
        // If the document not exist, return an error
        if ( !$deleteDoc ) {
            return response()->json([ 
                'success'  => false,
                'status'   => 400,
                'message'  => 'Document not found.' 
            ]);
        } else {
            // Delete the doc or image
            $deleteDoc->delete();
        
            // Return a success response
            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Document deleted successfully.',
            ]);
        }
    }

    public function getNotification( Request $request ) {
        $user = Auth::user();
        
        $notification = $this->notification::where( 'user_id',$user->id )->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $notification,
            'message' => 'Data found',
        ]);
    }
    
    public function sharedAccessList( Request $request ) {
        try
        { 
            $user = Auth::user();
            
            if( $request->type == 1 ){
                $contect = $this->contact::with( 'user','module:id,contact_id,module_type','module.categories:id,type,icon' )->where( 'user_id',$user->id )->get();
            }
            if($request->type == 2){
                $contect = $this->contact::with( 'module:id,contact_id,module_type','module.categories:id,type,icon' )->where(  'email',$user->email )->get();
                foreach( $contect as $c ){
                    $newuser = $this->user::where( 'id', $c->user_id )->first();
                    $c->user = $newuser;
                    
                    $inMycontect = $this->contact::where( 'user_id',$user->id)->where('email',$newuser->email)->first();
                    $c->contact  = $inMycontect;
                }
            }
            $response=[
                'status'  => parent::statusCode( 'success' ),
                'message' => "Data found",
                'data'    => $contect
            ];
        }
        catch ( \Exception $e ) {
            
            $response = [
                "status"  => parent::statusCode( 'error' ),
                "message" => $e->getMessage()
            ];
        }
        return parent::sendResponse( $response );
    }
    
    public function sharedAccessDetails( Request $request ) {
        try { 
            $user = Auth::user();
            
            if( $request->type == 1){
                $contect = $this->contact::with( 'user','module:id,contact_id,module_type','module.categories:id,type,icon' )->where( 'id',$request->contact_id )->where( 'user_id',$user->id )->first();
            }
            
            if( $request->type == 2 ){
                $contect       = $this->contact::with( 'module:id,contact_id,module_type','module.categories:id,type,icon' )->where( 'user_id',$request->contact_id )->where( 'email',$user->email )->first();
                if($contect){
                    $user          = $this->user::where( 'id',$contect->user_id )->first();
                    $contect->user = $user;
                }
            }
            
            $response=[
                'status'  => parent::statusCode( 'success' ),
                'message' => "Data found",
                'data'    => $contect
            ];
        } catch ( \Exception $e ) {
            
            $response = [
                "status"  => parent::statusCode( 'error' ),
                "message" => $e->getMessage()
            ];
        }
        return parent::sendResponse( $response );
    }
    
    public function deleteSharedAccess( Request $request ) {
        try {
            // Get the authenticated user
            $user = Auth::user();
    
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'contact_id' => 'required|integer',
            ]);
    
            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 400); 
            }
    
            // Find the shared module based on the provided contact_id and user ID
            $deleteModule = ContactModule::where( 'contact_id', $request->contact_id )
                ->delete();
    
            // Check if the shared module exists
            if ( !$deleteModule ) {
                return response()->json([
                    'success' => false,
                    'status'  => 400,
                    'message' => 'Shared module not found.',
                ]); // Not found
            } else {
                return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Shared module removed successfully.',
            ]);
            }
            
    
        } catch ( \Exception $e ) {
            // Handle any exceptions and return an error message
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }
        
    public function sharedAccessSubCategories( Request $request ) {
        try
        { 
            $validator = Validator::make( $request->all(), [
                'type'       => 'required',
                'category'   => 'required',
            ]);
    
            if ( $validator->fails() ) {
                    return response()->json([
                        "status"  => parent::statusCode( 'error' ),
                        "message" => $validator->errors()->first()
                    ], 400);
            }
            
            $user = Auth::user();
            
            if( $request->type == 1 ){
             
             $userID=  $user->id;
            //  $userdata = Category::where('type', $request->category)
            //     ->where(function ($query) use ($userID) {
            //         $query->whereHas('bankaccount', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })
            //         ->orWhereHas('realasset', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('brokerageaccount', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('lifeinsurance', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('businessownership', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('loan', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('creditcard', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('otherasset', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('otherdebt', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('lifejournals', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         })->orWhereHas('logins', function ($q) use ($userID) {
            //             $q->where('user_id', $userID);
            //         });
            //         })->with([
            //             'bankaccount' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'realasset' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'brokerageaccount' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'lifeinsurance' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'businessownership' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'loan' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'creditcard' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'otherasset' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'otherdebt' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'lifejournals' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             },
            //             'logins' => function ($query) use ($userID) {
            //                 $query->where('user_id', $userID);
            //             }
            //         ])->get();
               
               $userdata = Category::where('type', $request->category)->get();
               
                $response=[
                    'status'  => parent::statusCode( 'success' ),
                    'message' => "Data found",
                    'data'    => $userdata
                ];
                    
            }else if( $request->type == 2 ){
                
                if( isset( $request->user_id ) && !empty( $request->user_id ) ){
                    
                    $userID=  $request->user_id;
                    // $userdata = Category::where('type', $request->category)
                    //     ->where(function ($query) use ($userID) {
                    //         $query->whereHas('bankaccount', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })
                    //         ->orWhereHas('realasset', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('brokerageaccount', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('lifeinsurance', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('businessownership', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('loan', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('creditcard', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('otherasset', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('otherdebt', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('lifejournals', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         })->orWhereHas('logins', function ($q) use ($userID) {
                    //             $q->where('user_id', $userID);
                    //         });
                    //     })->with([
                    //             'bankaccount' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'realasset' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'brokerageaccount' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'lifeinsurance' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'businessownership' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'loan' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'creditcard' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'otherasset' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'otherdebt' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'lifejournals' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             },
                    //             'logins' => function ($query) use ($userID) {
                    //                 $query->where('user_id', $userID);
                    //             }
                    //         ])->get();
                        
                        
                    $userdata = Category::where('type', $request->category)->get();    
                        $response=[
                            'status'  => parent::statusCode( 'success' ),
                            'message' => "Data found",
                            'data'    => $userdata
                        ];
                        
                }else{
                    $response = [
                        "status"  => parent::statusCode( 'error' ),
                        "message" => "The user id field is required"
                    ];
                }
                 
            }else{
                $response = [
                    "status"  => parent::statusCode( 'error' ),
                    "message" => "Invaild Type"
                ];
            }
        }
        catch ( \Exception $e ) {
            
            $response = [
                "status"  => parent::statusCode( 'error' ),
                "message" => $e->getMessage()
            ];
        }
        return parent::sendResponse( $response );
    }
    
    public function sharedAccessSubCategoriesDetails ( Request $request ) {
        try
        { 
            $validator = Validator::make( $request->all(), [
                'user_id'       => 'required',
                'category_id'   => 'required',
            ]);
    
            if ( $validator->fails() ) {
                    return response()->json([
                        "status"  => parent::statusCode( 'error' ),
                        "message" => $validator->errors()->first()
                    ], 400);
            }
            
            $user = Auth::user();
            
            $userID=  $request->user_id;
            $userdata = Category::where( 'id', $request->category_id )
                        ->where(function ($query) use ($userID) {
                            $query->whereHas('bankaccount', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })
                            ->orWhereHas('realasset', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('brokerageaccount', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('lifeinsurance', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('businessownership', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('otherasset', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('loan', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('creditcard', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('otherdebt', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('legalwill', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('legaltrust', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('powerattorney', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('realestatedeed', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('businessdocument', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('taxreturndocument', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('otherlegaldocument', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('logins', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })->orWhereHas('lifejournals', function ($q) use ($userID) {
                                $q->where('user_id', $userID);
                            });
                            }) ->with([
                                'bankaccount' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'realasset' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'brokerageaccount' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'lifeinsurance' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'businessownership' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'otherasset' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'loan' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'creditcard' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'otherdebt' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'legalwill' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'legaltrust' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'powerattorney' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'realestatedeed' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'businessdocument' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'taxreturndocument' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'otherlegaldocument' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'logins' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                },
                                'lifejournals' => function ($query) use ($userID) {
                                    $query->where('user_id', $userID);
                                }
                            ])->get();
                        
                        $response=[
                            'status'  => parent::statusCode( 'success' ),
                            'message' => "Data found",
                            'data'    => $userdata
                        ];
            
        }
        catch ( \Exception $e ) {
            
            $response = [
                "status"  => parent::statusCode( 'error' ),
                "message" => $e->getMessage()
            ];
        }
        return parent::sendResponse( $response );
    }
    
    public function untrustedContact ( Request $request ) {
       
        $validator = Validator::make( $request->all(), [
            'id' => 'required|string', // expect 'id' to be a comma-separated string
        ]);
        
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        // Get the comma-separated string of IDs and explode it into an array
        $contactIds    = explode( ',', $request->id );
        $invalidIds    = []; // This array will hold the invalid IDs
        $validContacts = []; // This array will hold the valid contacts for updating
    
        // Loop through each contact_id and process it
        foreach ( $contactIds as $contactId ) {
            // Trim any extra spaces around the ID
            $contactId = trim( $contactId );
    
            // Find the contact by ID
            $contact = Contact::where( 'id', $contactId )->first(); 
    
            // If the contact id doesn't exist, add it to the invalidIds array
            if ( !$contact ) {
                $invalidIds[] = $contactId;
                continue; // Skip to the next ID if not found
            }
    
            // If the id, add it to the validContacts array for updating
            $validContacts[] = $contact;
        }
    
        // If there are invalid IDs, return an error message listing all invalid IDs
        if ( count( $invalidIds ) > 0 ) {
            return response()->json([
                'success'  => false,
                'status'   => 400,
                'message'  => 'The contact IDs not found: ' . implode(', ', $invalidIds)
            ]);
        }
    
        // If there are valid contacts, update them
        foreach ( $validContacts as $contact ) {
            $contact->is_trusted = 0;
            $contact->save();
        }
    
        // Return a success response after all contacts have been processed
        return response()->json([
            'success'  => true,
            'status'   => 200,
            'message'  => 'Set untrusted contact successfully.'
        ]);
    }
    
    public function contactUs ( Request $request ) {
       
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'email'     => 'required|email',
            'subject'   => 'nullable|string',
            'message'   => 'nullable|string',
        ]);
       
        if ( $validator->fails() ) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $saveContact = ContactUs::create([
            'full_name' => $request->input( 'full_name' ),
            'email'     => $request->input( 'email' ),
            'subject'   => $request->input( 'subject', 'No subject' ), // Default 'No subject' if subject not provided
            'message'   => $request->input( 'message', '' ),
        ]);
       
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $saveContact,
            'message' => 'Your message has been sent successfully',
        ]);
    }
    
    public function getRegisterUser () {
        $response = [];
        
        $user = Auth::user();
        
        // get all bank accounts
        $getusers = User::get();
        
        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => $getusers,
            'message' => 'Get registered users successfully',
        ]);
    }
    
}