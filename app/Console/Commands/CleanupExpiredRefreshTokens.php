<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;

class CleanupExpiredRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired refresh tokens from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired refresh tokens...');
        
        $expiredTokens = RefreshToken::where('expires_at', '<', now())->count();
        
        if ($expiredTokens > 0) {
            RefreshToken::where('expires_at', '<', now())->delete();
            $this->info("Deleted {$expiredTokens} expired refresh tokens.");
        } else {
            $this->info('No expired refresh tokens found.');
        }
        
        // Also cleanup revoked tokens older than 7 days
        $oldRevokedTokens = RefreshToken::where('revoked', true)
            ->where('updated_at', '<', now()->subDays(7))
            ->count();
            
        if ($oldRevokedTokens > 0) {
            RefreshToken::where('revoked', true)
                ->where('updated_at', '<', now()->subDays(7))
                ->delete();
            $this->info("Deleted {$oldRevokedTokens} old revoked refresh tokens.");
        }
        
        $this->info('Cleanup completed!');
    }
}
