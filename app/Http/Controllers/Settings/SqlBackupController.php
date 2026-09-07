<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SqlBackupController extends Controller
{
    private const DIRECTORY = 'backups_sql';

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIRECTORY);

        $files = collect($disk->files(self::DIRECTORY))
            ->filter(fn (string $path): bool => $this->isValidBackupFilename(basename($path)))
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => $disk->size($path),
                'last_modified' => $disk->lastModified($path),
            ])
            ->sortByDesc('last_modified')
            ->values();

        return view('settings.backups_sql.index', compact('files'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'backup' => ['required', 'file'],
        ]);

        $file = $data['backup'];
        $originalName = $file->getClientOriginalName();

        if (!$this->isValidBackupFilename($originalName)) {
            return redirect()
                ->route('settings.backups_sql.index')
                ->with('error', 'El respaldo debe ser un archivo .sql o .sql.gz.');
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIRECTORY);

        $safeName = $this->sanitizeBackupFilename($originalName);
        $storedName = $this->uniqueBackupFilename($safeName);
        $file->storeAs(self::DIRECTORY, $storedName, 'local');

        return redirect()
            ->route('settings.backups_sql.index')
            ->with('success', 'Respaldo almacenado correctamente: '.$storedName);
    }

    public function download(Request $request, string $file)
    {
        $this->ensureSuperAdmin($request);

        if (!$this->isValidBackupFilename($file)) {
            abort(404);
        }

        $path = self::DIRECTORY.'/'.$file;
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            abort(404);
        }

        return response()->download($disk->path($path), $file, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureSuperAdmin(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    private function isValidBackupFilename(string $file): bool
    {
        if ($file === '' || strpos($file, '/') !== false || strpos($file, '\\') !== false || strpos($file, "\0") !== false) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/u', $file)) {
            return false;
        }

        return (bool) preg_match('/\.sql(\.gz)?$/iu', $file);
    }

    private function sanitizeBackupFilename(string $file): string
    {
        $file = trim($file);
        $extension = $this->backupExtension($file);
        $name = substr($file, 0, -strlen($extension));

        $name = preg_replace('/[\/\\\\\x00-\x1F\x7F]+/u', '_', $name);
        $name = preg_replace('/\s+/u', '_', $name);
        $name = preg_replace('/[^\pL\pN._-]+/u', '_', $name);
        $name = trim((string) $name, '._-');

        return ($name !== '' ? $name : 'respaldo').$extension;
    }

    private function uniqueBackupFilename(string $file): string
    {
        $disk = Storage::disk('local');

        if (!$disk->exists(self::DIRECTORY.'/'.$file)) {
            return $file;
        }

        $extension = $this->backupExtension($file);
        $base = substr($file, 0, -strlen($extension));
        $suffix = Carbon::now('America/Mexico_City')->format('Ymd_His');
        $candidate = $base.'_'.$suffix.$extension;
        $counter = 2;

        while ($disk->exists(self::DIRECTORY.'/'.$candidate)) {
            $candidate = $base.'_'.$suffix.'_'.$counter.$extension;
            $counter++;
        }

        return $candidate;
    }

    private function backupExtension(string $file): string
    {
        return substr(mb_strtolower($file, 'UTF-8'), -7) === '.sql.gz' ? '.sql.gz' : '.sql';
    }
}
