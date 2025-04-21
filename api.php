<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\SubscriptionController;

//Route::post( '/auth/register', [ AuthController::class, 'createUser' ] );

Route::controller( AuthController::class )->group( function() {
    Route::post( '/register', 'registerUser' );
    Route::post( '/login', 'loginUser' );
    Route::post( '/social-login', 'socialLogin' );
    Route::post( '/forgot-password', 'forgotPassword' ); 
    Route::post( '/verify-otp', 'verifyOtp' ); 
    Route::post( '/reset-password', 'resetPassword' ); 
    Route::get( '/inactivity-notification', 'inactivityNotification' );
    Route::get( '/get-quotes', 'getQuotes' );
});

Route::controller( AuthController::class )->middleware([ 'auth:sanctum','lastactivity', 'log','check.suspended' ])->group( function() {
    Route::post( '/logout', 'logOut' );
    Route::post( '/create-account', 'createAccount' );
    Route::post( '/create-profile', 'createProfile' ); 
    Route::get( '/get-account', 'getUsersAccount' );
    Route::post( '/delete-account', 'deleteAccount' );
    Route::post( '/change-password', 'changePassword' );
});

Route::controller( SubscriptionController::class )->middleware([ 'auth:sanctum','lastactivity', 'log','check.suspended' ])->group( function() {
    Route::post( '/addSubscription', 'addSubscription' );
});

Route::controller( HomeController::class )->middleware([ 'auth:sanctum', 'lastactivity', 'log','check.suspended' ])->group( function() {
    Route::post( '/add-bank-account', 'addBankAccount' );
    Route::post( '/edit-bank-account', 'editBankAccount' );
    Route::get( '/get-bank-account', 'getBankAccounts' );
    Route::post( '/delete-bank-account', 'deleteBankAccount' );
    Route::post( '/add-contact', 'addContact' );
    Route::post( '/edit-contact', 'editContact' );
    Route::get( '/get-contact', 'getContact' );
    Route::post( '/delete-contact', 'deleteContact' );
    Route::post( '/add-insurance', 'addInsurance' );
    Route::post( '/edit-insurance', 'editInsurance' );
    Route::get( '/get-insurance', 'getInsurance' );
    Route::post( '/delete-insurance', 'deleteInsurance' );
    Route::post( '/add-real-estate', 'addRealEstate' );
    Route::post( '/edit-real-estate', 'editRealEstate' );
    Route::get( '/get-real-estate', 'getRealEsate' );
    Route::post( '/delete-real-estate', 'deleteRealEstate' );
    Route::post( '/add-brokerage-account', 'addBrokerageAccount' );
    Route::post( '/edit-brokerage-account', 'editBrokerageAccount' );
    Route::get( '/get-brokerage-account', 'getBrokerageAccount' );
    Route::post( '/delete-brokerage-account', 'deleteBrokerageAccount' );
    Route::post( '/add-business-ownership', 'addBusinessOwnership' );
    Route::post( '/edit-business-ownership', 'editBusinessOwnership' );
    Route::get( '/get-business-ownership', 'getBusinessOwnership' ); 
    Route::post( '/delete-business-ownership', 'deleteBusinessOwnership' );
    Route::post( '/add-asset', 'addAsset' );
    Route::post( '/edit-asset', 'editAsset' );
    Route::get( '/get-asset', 'getAsset' );
    Route::post( '/delete-asset', 'deleteAsset' );
    Route::post( '/add-loan', 'addLoan' );
    Route::post( '/edit-loan', 'editLoan' );
    Route::get( '/get-loan', 'getLoan' );
    Route::post( '/delete-loan', 'deleteLoan' );
    Route::post( '/add-credit', 'addCredit' );
    Route::post( '/edit-credit', 'editCredit' );
    Route::get( '/get-credit', 'getCredit' );
    Route::post( '/delete-credit', 'deleteCredit' );
    Route::post( '/add-debt', 'addDebt' );
    Route::post( '/edit-debt', 'editDebt' );
    Route::get( '/get-debt', 'getDebt' );
    Route::post( '/delete-debt', 'deleteDebt' );
    Route::post( '/add-will', 'addWill' );
    Route::post( '/edit-will', 'editWill' );
    Route::get( '/get-will', 'getWill' );
    Route::post( '/delete-will', 'deleteWill' );
    Route::post( '/add-trust', 'addTrust' );
    Route::post( '/edit-trust', 'editTrust' );
    Route::get( '/get-trust', 'getTrust' );
    Route::post( '/delete-trust', 'deleteTrust' );
    Route::post( '/add-attorney', 'addAttorney' );
    Route::post( '/edit-attorney', 'editAttorney' );
    Route::get( '/get-attorney', 'getAttorney' );
    Route::post( '/delete-attorney', 'deleteAttorney' );
    Route::post( '/add-estate', 'addEstate' );
    Route::post( '/edit-estate', 'editEstate' );
    Route::get( '/get-estate', 'getEstate' );
    Route::post( '/delete-estate', 'deleteEstate' );
    Route::post( '/add-business', 'addBusiness' );
    Route::post( '/edit-business', 'editBusiness' );
    Route::get( '/get-business', 'getBusiness' );
    Route::post( '/delete-business', 'deleteBusiness' );
    Route::post( '/add-return', 'addTaxReturn' );
    Route::post( '/edit-return', 'editTaxReturn' );
    Route::get( '/get-return', 'getTaxReturn' );
    Route::post( '/delete-return', 'deleteTaxReturn' );
    Route::post( '/add-legal-document', 'addOtherDocument' );
    Route::post( '/edit-legal-document', 'editOtherDocument' );
    Route::get( '/get-legal-document', 'getOtherDocument' );
    Route::post( '/delete-legal-document', 'deleteOtherDocument' );
    Route::post( '/add-login', 'addLogin' );
    Route::post( '/edit-login', 'editLogin' );
    Route::get( '/get-login', 'getLogin' );
    Route::post( '/delete-login', 'deleteLogin' );
    Route::post( '/add-life-journal', 'addLifeJournal' );
    Route::post( '/edit-life-journal', 'editLifeJournal' );
    Route::get( '/get-life-journal', 'getLifeJournal' );
    Route::post( '/get-shared-life-journal', 'getSharedWithMeJournal' );
    Route::post( '/delete-life-journal', 'deleteLifeJournal' );
    Route::post( '/save-share-document', 'shareDocument' );
    Route::get( '/get-share-document', 'getShareDocument' );
    Route::get( '/get-shared-me', 'getSharedWithMeDocs' );
    Route::post( '/upload-document', 'uploadDocument' );
    Route::get( '/get-document', 'getDocument' );
    Route::post( '/delete-document', 'deleteDocument' );
    Route::get( '/get-categories', 'getCategories' );
    Route::post( '/get-sub-categories', 'getSubCategories' );
    Route::get( '/get-notification', 'getNotification' );
    Route::post( '/unselect-trusted', 'untrustedContact' );
    Route::post( '/shared-access-list', 'sharedAccessList' );
    Route::post( '/shared-access-details', 'sharedAccessDetails' );
    Route::post( '/delete-shared-access', 'deleteSharedAccess' );
    Route::post( '/shared-access-subcategories', 'sharedAccessSubCategories' );
    Route::post( '/shared-access-subcategories-details', 'sharedAccessSubCategoriesDetails' );
    Route::post( '/contact-us', 'contactUs' );
    Route::get( '/get-register-user', 'getRegisterUser' );
    
});

