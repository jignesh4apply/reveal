<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\UserReferenceAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\CustomerIoService;
use Illuminate\Support\Facades\DB;

class CustomerIoController extends Controller
{
    public function sendEmailsToCustomerIo()
    {
        // Fetch emails of users who haven't registered (not in users table)
        $emailsNotRegistered = UserReferenceAnswer::where('key', 'email')
            ->whereNotIn('value', function ($query) {
                $query->select('email')->from('users')->whereNotNull('email');
            })
            ->where('created_at', '>=', now()->subMinutes(30))
            ->pluck('value');

        // Fetch session_ids for these emails
        $sessionIdsNotRegistered = UserReferenceAnswer::where('key', 'email')
            ->whereIn('value', $emailsNotRegistered)
            ->pluck('session_id');

        // Prepare data for users not registered
        $usersNotRegistered = $this->fetchUserData($sessionIdsNotRegistered);

        // Initialize the Customer.io service
        $customerIo = new CustomerIoService();

        foreach ($usersNotRegistered as $user) {
            $customerData = (array) $user;
            $customerIo->addOrUpdateCustomer($user->session_id ?? null, $customerData, 18); //  18 => Abandoned cart
        }
		//\Log::info("Customer.io email sent");
        // // Fetch emails of users who have registered and meet other conditions
        // $registeredUsers = User::where('isQuestionsAttempted', 1)
        //     ->where('isSubscribedUser', 1)
        //     ->pluck('email');

        // // Fetch session_ids for these registered users
        // $sessionIdsRegistered = UserReferenceAnswer::where('key', 'email')
        //     ->whereIn('value', $registeredUsers)
        //     ->pluck('session_id');

        // // Prepare data for registered users who meet the conditions
        // $usersRegistered = $this->fetchUserData($sessionIdsRegistered);

        // // Add registered users to Segment 2
        // foreach ($usersRegistered as $user) {
        //     $customerData = (array) $user;
        //     $customerIo->addOrUpdateCustomer($user->session_id ?? null, $customerData, 19); // 19 =>  subscribed user
        // }
    }

    private function fetchUserData($sessionIds)
    {
        // Fetch key and value records for users with matching session_id
        $userData = UserReferenceAnswer::whereIn('session_id', $sessionIds)
            ->get(['session_id', 'key', 'value'])
            ->groupBy('session_id');

        $users = [];

        // Organize each user's data into an array
        foreach ($userData as $sessionId => $data) {
            $user = ['session_id' => $sessionId];
            $user['resume_link'] = route('questions', ['sessionId' => $sessionId]);
            foreach ($data as $item) {
                if($item->key == 'email' && $item->email_marketing === 1){
                    $user[$item->key] = $item->value;
                    $user['isEmailMarketing'] = $item->email_marketing;
                }else{
                    $user[$item->key] = $item->value;
                }
            }
            $users[] = (object) $user; // Convert to object
        }
        // dd($user);
        return $users;
    }
}