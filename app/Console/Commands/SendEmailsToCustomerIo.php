<?php

namespace App\Console\Commands;

use App\Services\CustomerIoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Testing\CustomerIoController;

class SendEmailsToCustomerIo extends Command
{
    protected $signature = 'app:send-emails-to-customer-io';
    protected $description = 'Send emails to Customer.io.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        \Log::info("Test log : Customer.io email sent");
		$controller = new CustomerIoController();
		$controller->sendEmailsToCustomerIo();
		//$this->info('Emails have been sent to Customer.io.');
    }
}

// $customerIo = new CustomerIoService();
// $customerIo->sendTransactionalEmail('jignesh4monto@yopmail.com', '6', ['name' => 'test']);
// \Log::info("Emails have been sent to Customer.io.!");
