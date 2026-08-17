<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SyncSubscriptionStatus extends Command
{
    protected $signature   = 'subscription:sync';
    protected $description = 'Set subscription_status = expired untuk user yang masa aktifnya sudah habis';

    public function handle(): int
    {
        $expired = User::where('subscription_status', 'active')
            ->whereDoesntHave('subscriptions', fn($q) => $q->where('ends_at', '>', now()))
            ->get();

        foreach ($expired as $user) {
            $user->update(['subscription_status' => 'expired']);
        }

        $this->info("Sync selesai: {$expired->count()} akun diset expired.");
        return self::SUCCESS;
    }
}
