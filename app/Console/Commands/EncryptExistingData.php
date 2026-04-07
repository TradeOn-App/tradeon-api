<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'data:encrypt-existing {--dry-run : Mostra o que seria criptografado sem alterar}';
    protected $description = 'Criptografa dados sensíveis existentes no banco (wallets, hashes, documents)';

    private array $fieldsToEncrypt = [
        'cash_flow_transactions' => ['wallet_origin', 'wallet_destination', 'tx_hash'],
        'p2p_operations' => ['wallet_from', 'wallet_to'],
        'internal_transactions' => ['wallet_destination', 'tx_hash'],
        'clients' => ['document', 'phone'],
        'collaborators' => ['cpf', 'wallet'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('=== DRY RUN - Nenhum dado será alterado ===');
        }

        foreach ($this->fieldsToEncrypt as $table => $fields) {
            $this->info("Processando tabela: {$table}");

            $rows = DB::table($table)->select(array_merge(['id'], $fields))->get();
            $count = 0;

            foreach ($rows as $row) {
                $updates = [];

                foreach ($fields as $field) {
                    $value = $row->$field;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    // Verifica se já está criptografado tentando descriptografar
                    try {
                        Crypt::decryptString($value);
                        continue; // Já está criptografado
                    } catch (\Exception $e) {
                        // Não está criptografado, precisa criptografar
                        $updates[$field] = Crypt::encryptString($value);
                    }
                }

                if (!empty($updates)) {
                    if (!$dryRun) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                    $count++;
                }
            }

            $action = $dryRun ? 'seriam criptografados' : 'criptografados';
            $this->info("  -> {$count} registros {$action}");
        }

        $this->info($dryRun ? 'Dry run concluído.' : 'Criptografia concluída com sucesso!');
        return Command::SUCCESS;
    }
}
