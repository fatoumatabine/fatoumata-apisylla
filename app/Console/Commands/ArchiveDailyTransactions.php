<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ArchiveDailyTransactions;
use Carbon\Carbon;

class ArchiveDailyTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:archive-daily
                            {--date= : Date spécifique à archiver (format: Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archiver les transactions de la journée précédente vers la base d\'archivage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))
            : Carbon::yesterday();

        $this->info("Archivage des transactions du {$targetDate->toDateString()}...");

        // Dispatcher le job
        ArchiveDailyTransactions::dispatch($targetDate);

        $this->info('Job d\'archivage envoyé à la queue.');

        return Command::SUCCESS;
    }
}
