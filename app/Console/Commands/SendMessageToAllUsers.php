<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AdminMessageNotification;
use Illuminate\Console\Command;

class SendMessageToAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:message-to-all-users';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a message to all users every 10 minutes';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all()->take(5);
        foreach ($users as $user) {
            $user->notify(new AdminMessageNotification('Your message content here'));
        }

        $this->info('Messages sent to all users successfully.');
    }
}
