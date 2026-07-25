<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Baneco\Contracts\EncryptionServiceInterface;
use App\Baneco\Services\BanecoAuthenticationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BanecoTestCommand extends Command
{
    protected $signature = 'baneco:test';
    protected $description = 'Verifica la configuración, encriptación y conectividad inicial con el Banco Económico (Baneco).';

    public function handle(
        EncryptionServiceInterface $encryptionService,
        BanecoAuthenticationService $authService
    ): int {
        $this->info('=== Iniciando verificación del módulo Baneco ===');

        // 1. Verify environment / config variables
        $this->info('1. Cargando configuraciones...');
        $baseUrl = config('baneco.base_url');
        $username = config('baneco.username');
        $password = config('baneco.password');
        $aesKey = config('baneco.aes_key');

        $this->line("Base URL: {$baseUrl}");
        $this->line("Username: " . ($username ? 'Configurado (OK)' : 'No configurado'));
        $this->line("Password: " . ($password ? 'Configurado (OK)' : 'No configurado'));
        $this->line("AES Key: " . ($aesKey ? 'Configurada (OK)' : 'No configurada'));

        // 2. Test AES encryption locally
        $this->info("\n2. Probando módulo de encriptación AES-256...");
        $testText = 'Cuenta Corriente #12345';
        $encrypted = $encryptionService->encrypt($testText);
        $decrypted = $encryptionService->decrypt($encrypted);

        if ($decrypted === $testText) {
            $this->info("Cifrado/Descifrado Local: Exitoso (OK)");
            $this->line("Original: {$testText}");
            $this->line("Cifrado: {$encrypted}");
        } else {
            $this->error("Cifrado Local: Fallido");
            return 1;
        }

        // 3. Test HTTP Client / Auth connectivity
        if (empty($username) || empty($password)) {
            $this->warn("\n3. Credenciales vacías. Saltando pruebas de conectividad de red con el banco.");
            $this->info("\n=== Verificación completada con advertencias (OK localmente) ===");
            return 0;
        }

        $this->info("\n3. Solicitando token de autenticación (Conectividad)...");
        $token = $authService->authenticate();

        if (!empty($token)) {
            $this->info("Conexión con el Banco: Exitosa (OK)");
            $this->info("Token obtenido.");
        } else {
            $this->error("Conexión con el Banco: Fallida (Verificar credenciales y red)");
            return 1;
        }

        $this->info("\n=== Verificación de Baneco completada exitosamente (OK) ===");
        return 0;
    }
}
