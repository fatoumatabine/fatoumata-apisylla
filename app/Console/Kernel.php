<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnarchiveExpiredBlockedAccounts;
use App\Jobs\ArchiveWeeklyTransactions;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
    // Archivage des comptes bloqués expirés
    $schedule->job(new ArchiveExpiredBlockedAccounts)->dailyAt('00:00'); // Exécute tous les jours à minuit
        $schedule->job(new UnarchiveExpiredBlockedAccounts)->dailyAt('00:30'); // Exécute tous les jours à 00h30

        // Archivage des transactions de la journée
        $schedule->command('transactions:archive-daily')->dailyAt('01:00'); // Exécute tous les jours à 01h00

        // Archivage hebdomadaire des transactions vers MongoDB
        $schedule->job(new ArchiveWeeklyTransactions)->weeklyOn(1, '02:00'); // Tous les lundis à 02h00
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
