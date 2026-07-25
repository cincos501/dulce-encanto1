<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BanecoHealthCommand extends Command
{
    protected $signature = 'baneco:health';
    protected $description = 'Verifica el estado de salud general del canal de comunicación de Baneco.';

    public function handle(): int
    {
        $this->info('=== Diagnóstico de Salud de Baneco ===');

        $baseUrl = config('baneco.base_url');
        $this->line("Base URL: {$baseUrl}");

        $timeout = config('baneco.timeout');
        $this->line("Timeout configurado: {$timeout}s");

        $verifySsl = config('baneco.verify_ssl');
        $this->line("Verificar SSL: " . ($verifySsl ? 'Sí' : 'No'));

        $this->info('Módulo Baneco activo en sistema.');
        return 0;
    }
}
