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
        $targets = array_values(array_unique(array_values($renames)));
        $migrationNames = array_merge(array_keys($renames), $targets);

        $updated = DB::transaction(function () use ($renames, $targets, $migrationNames): ?int {
            $records = DB::table('migrations')
                ->whereIn('migration', $migrationNames)
                ->lockForUpdate()
                ->get(['migration', 'batch'])
                ->groupBy('migration');

            $pendingRenames = [];

            foreach ($targets as $newName) {
                $aliases = array_keys(array_filter(
                    $renames,
                    fn (string $target): bool => $target === $newName
                ));
                $existingNames = array_merge($aliases, [$newName]);
                $existingRecords = collect($existingNames)
                    ->flatMap(fn (string $name) => $records->get($name, collect()));

                if ($existingRecords->count() > 1) {
                    $this->error("Histórico conflitante para a migration {$newName}.");
                    return null;
                }

                $oldName = collect($aliases)
                    ->first(fn (string $name): bool => $records->has($name));

                if ($oldName !== null) {
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
