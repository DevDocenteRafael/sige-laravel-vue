<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationHistoryRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renames_history_records_without_changing_their_batch(): void
    {
        $oldName = '2026_06_22_180001_create_categoria_table';
        $newName = config("migration_renames.{$oldName}");
        $record = DB::table('migrations')->where('migration', $newName)->first();

        $this->assertNotNull($record);
        DB::table('migrations')->where('migration', $newName)->update(['migration' => $oldName]);

        $this->artisan('migrations:sync-renamed-history')
            ->assertExitCode(0);

        $this->assertDatabaseHas('migrations', [
            'migration' => $newName,
            'batch' => $record->batch,
        ]);
        $this->assertDatabaseMissing('migrations', ['migration' => $oldName]);

        $this->artisan('migrations:sync-renamed-history')
            ->assertExitCode(0);
    }

    public function test_it_refuses_to_change_conflicting_history_records(): void
    {
        $oldName = '2026_06_22_180002_create_fornecedor_table';
        $newName = config("migration_renames.{$oldName}");
        $record = DB::table('migrations')->where('migration', $newName)->first();

        $this->assertNotNull($record);
        DB::table('migrations')->insert([
            'migration' => $oldName,
            'batch' => $record->batch,
        ]);

        $this->artisan('migrations:sync-renamed-history')
            ->assertExitCode(1);

        $this->assertDatabaseHas('migrations', ['migration' => $oldName]);
        $this->assertDatabaseHas('migrations', ['migration' => $newName]);
    }

    public function test_every_migration_file_is_in_the_rename_map(): void
    {
        $migrationFiles = collect(glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values();
        $mappedFiles = collect(config('migration_renames'))
            ->values()
            ->unique()
            ->sort()
            ->values();

        $this->assertCount(44, $mappedFiles);
        $this->assertEquals($migrationFiles, $mappedFiles);
    }

    public function test_it_syncs_names_from_the_previous_timestamped_migrations(): void
    {
        $previousName = '2026_06_22_180001_criar_tabela_categoria';
        $newName = config("migration_renames.{$previousName}");
        $record = DB::table('migrations')->where('migration', $newName)->first();

        $this->assertNotNull($record);
        DB::table('migrations')->where('migration', $newName)->update(['migration' => $previousName]);

        $this->artisan('migrations:sync-renamed-history')
            ->assertExitCode(0);

        $this->assertDatabaseHas('migrations', [
            'migration' => $newName,
            'batch' => $record->batch,
        ]);
        $this->assertDatabaseMissing('migrations', ['migration' => $previousName]);
    }
}
