<?php

namespace App\Console\Commands\Sales;

use Illuminate\Console\Command;
use App\Models\Sales\Quotes\Quote;

class ExpireQuotes extends Command
{
    protected $signature = 'quotes:expire';
    protected $description = 'Marca como expiradas las cotizaciones que pasaron su fecha de vencimiento';

    public function handle()
    {
        $count = Quote::whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_APPROVED])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => Quote::STATUS_EXPIRED]);

        $this->info("Se han expirado {$count} cotizaciones.");
    }
}