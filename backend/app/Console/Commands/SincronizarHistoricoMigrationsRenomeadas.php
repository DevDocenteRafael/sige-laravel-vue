<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SincronizarHistoricoMigrationsRenomeadas extends Command
{
    protected $signature = 'migrations:sync-renamed-history';

    protected $description = 'Atualiza os nomes renomeados no histórico sem executar migrations novamente';

    public function handle(): int
    {
        if (!Schema::hasTable('migrations')) {
            $this->info('Histórico ainda não existe; nenhuma atualização necessária.');
            return self::SUCCESS;
        }

        $renames = config('migration_renames');
        $migrationNames = array_merge(array_keys($renames), array_values($renames));

        $updated = DB::transaction(function () use ($renames, $migrationNames): ?int {
            $records = DB::table('migrations')
                ->whereIn('migration', $migrationNames)
                ->lockForUpdate()
                ->get(['migration', 'batch'])
                ->groupBy('migration');

            $pendingRenames = [];

            foreach ($renames as $oldName => $newName) {
                $oldRecords = $records->get($oldName, collect());
                $newRecords = $records->get($newName, collect());

                if ($oldRecords->count() > 1 || $newRecords->count() > 1 || ($oldRecords->isNotEmpty() && $newRecords->isNotEmpty())) {
                    $this->error("Histórico conflitante para a migration {$oldName}.");
                    return null;
                }

                if ($oldRecords->isNotEmpty()) {
                    $pendingRenames[$oldName] = $newName;
                }
            }

            foreach ($pendingRenames as $oldName => $newName) {
                $affected = DB::table('migrations')
                    ->where('migration', $oldName)
                    ->update(['migration' => $newName]);

                if ($affected !== 1) {
                    throw new RuntimeException("Não foi possível atualizar o registro {$oldName}.");
                }
            }

            return count($pendingRenames);
        });

        if ($updated === null) {
            return self::FAILURE;
        }

        $this->info($updated === 0
            ? 'Histórico já sincronizado; nenhuma alteração necessária.'
            : "{$updated} registro(s) do histórico atualizado(s).");

        return self::SUCCESS;
    }
}
