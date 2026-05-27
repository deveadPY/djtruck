<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Backup automatizado de BD y archivos críticos.
 *
 * Genera:
 *   storage/backups/dumps/dj_trucks_YYYY-MM-DD_HHmmss.sql
 *   storage/backups/uploads_YYYY-MM-DD.zip (opcional)
 *
 * Retención: conserva los últimos N backups (default 7), borra los más viejos.
 *
 * Uso:
 *   php artisan erp:backup --include-uploads --keep=7
 */
class BackupDatabase extends Command
{
    protected $signature = 'erp:backup
                            {--include-uploads : Incluir public_html/uploads en el backup}
                            {--keep=7 : Cantidad de backups a conservar}';

    protected $description = 'Genera dump SQL de la base de datos y comprime uploads. Rota backups antiguos.';

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('backups/dumps');

        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $dbName     = config('database.connections.mysql.database');
        $dbUser     = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbHost     = config('database.connections.mysql.host');
        $dbPort     = (int) config('database.connections.mysql.port', 3306);

        $dumpFile = "{$backupDir}/{$dbName}_{$timestamp}.sql";

        // mysqldump
        $this->info("Creando dump de base de datos: {$dbName} → {$dumpFile}");
        $mysqldumpPath = $this->findMysqldump();

        $args = [
            $mysqldumpPath,
            "--host={$dbHost}",
            "--port={$dbPort}",
            "--user={$dbUser}",
        ];
        if ($dbPassword) {
            $args[] = "--password={$dbPassword}";
        }
        $args = array_merge($args, [
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            $dbName,
        ]);

        $process = new Process($args);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $err = $process->getErrorOutput();
            $this->error("Falló mysqldump: {$err}");
            Log::error('backup.mysqldump.failed', ['error' => $err]);
            return Command::FAILURE;
        }

        File::put($dumpFile, $process->getOutput());
        $sizeMb = round(filesize($dumpFile) / 1048576, 2);
        $this->info("  ✓ Dump generado: {$sizeMb} MB");

        // Uploads opcionales
        if ($this->option('include-uploads')) {
            $this->backupUploads($backupDir, $timestamp);
        }

        // Rotación
        $this->rotateBackups($backupDir, (int) $this->option('keep'));

        Log::info('backup.completed', [
            'file'    => $dumpFile,
            'size_mb' => $sizeMb,
        ]);

        return Command::SUCCESS;
    }

    private function backupUploads(string $backupDir, string $timestamp): void
    {
        $uploadsPath = public_path('uploads');
        if (!File::isDirectory($uploadsPath)) {
            $this->warn('No existe public_html/uploads — se omite backup de uploads');
            return;
        }

        $zipPath = "{$backupDir}/uploads_{$timestamp}.zip";
        $this->info("Comprimiendo uploads → {$zipPath}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->error('No se pudo crear el ZIP');
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            if (!$file->isFile()) continue;
            $relativePath = substr($file->getPathname(), strlen($uploadsPath) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }
        $zip->close();

        $sizeMb = round(filesize($zipPath) / 1048576, 2);
        $this->info("  ✓ Uploads comprimidos: {$sizeMb} MB");
    }

    private function rotateBackups(string $backupDir, int $keep): void
    {
        $files = collect(File::files($backupDir))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->values();

        if ($files->count() <= $keep) {
            return;
        }

        $toDelete = $files->slice($keep);
        foreach ($toDelete as $file) {
            File::delete($file->getRealPath());
            $this->line("  ✗ Eliminado backup antiguo: " . $file->getFilename());
        }
    }

    private function findMysqldump(): string
    {
        // Intentar rutas comunes (XAMPP Windows, Linux)
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'mysqldump',
        ];
        foreach ($candidates as $path) {
            if (file_exists($path) || $path === 'mysqldump') {
                return $path;
            }
        }
        throw new \RuntimeException('mysqldump no encontrado en rutas comunes. Instale MySQL CLI o configure su ruta.');
    }
}
