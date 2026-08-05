<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pradeepdev\EnvironmentManager\Authorization\EnvManagerGate;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;
use Pradeepdev\EnvironmentManager\Models\EnvAuditLog;
use Pradeepdev\EnvironmentManager\Models\EnvVersionHistory;
use Pradeepdev\EnvironmentManager\Services\BackupManager;
use Pradeepdev\EnvironmentManager\Services\DiffEngine;
use Pradeepdev\EnvironmentManager\Services\ExportFormatter;
use Pradeepdev\EnvironmentManager\Services\ImportProcessor;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

class UiController extends Controller
{
    public function __construct(
        private readonly EnvManager $manager,
        private readonly EnvManagerGate $gate,
        private readonly BackupManager $backupManager,
        private readonly DiffEngine $diffEngine,
        private readonly ExportFormatter $exporter,
        private readonly ImportProcessor $importer,
        private readonly SensitivityDetector $sensitivityDetector,
    ) {}

    public function index(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $canReveal = $this->gate->check($request->user(), EnvManagerGate::PERMISSION_REVEAL_SECRETS);
        $reveal    = $canReveal && $request->boolean('reveal');

        $variables = $this->manager->all();

        // Filters
        if ($search = $request->input('search')) {
            $variables = $variables->filter(
                fn ($v) => str_contains(strtolower($v->key), strtolower($search))
            );
        }

        if ($category = $request->input('category')) {
            $variables = $variables->filter(fn ($v) => $v->category === $category);
        }

        $sort = $request->input('sort', 'key');
        $dir  = $request->input('dir', 'asc');

        $variables = $dir === 'desc'
            ? $variables->sortByDesc($sort)
            : $variables->sortBy($sort);

        $grouped    = $variables->groupBy('category');
        $categories = $this->manager->all()->pluck('category')->unique()->sort()->values();
        $perPage    = config('environment-manager.per_page', 25);

        return view('environment-manager::index', compact(
            'grouped', 'categories', 'reveal', 'canReveal', 'search', 'category', 'sort', 'dir', 'perPage'
        ));
    }

    public function create(): View
    {
        $this->gate->authorize(request()->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        return view('environment-manager::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $validated = $request->validate([
            'key'    => ['required', 'regex:/^[A-Z_][A-Z0-9_]*$/'],
            'value'  => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->manager->set(
                key: $validated['key'],
                value: $validated['value'] ?? '',
                reason: $validated['reason'] ?? null,
            );

            return redirect()
                ->route('env-manager.index')
                ->with('success', "Variable [{$validated['key']}] saved successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->getErrors())->withInput();
        }
    }

    public function edit(string $key): View
    {
        $this->gate->authorize(request()->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $variable = $this->manager->get($key);

        if ($variable === null) {
            abort(404, "Variable [{$key}] not found.");
        }

        return view('environment-manager::edit', compact('variable'));
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $validated = $request->validate([
            'value'  => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->manager->set(
                key: $key,
                value: $validated['value'] ?? '',
                reason: $validated['reason'] ?? null,
            );

            return redirect()
                ->route('env-manager.index')
                ->with('success', "Variable [{$key}] updated successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->getErrors())->withInput();
        }
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_DELETE_ENV);

        $this->manager->delete($key, $request->input('reason'));

        return redirect()
            ->route('env-manager.index')
            ->with('success', "Variable [{$key}] deleted.");
    }

    public function reveal(Request $request, string $key): \Illuminate\Http\JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_REVEAL_SECRETS);

        $variable = $this->manager->get($key);

        if ($variable === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        // Audit the reveal
        app(\Pradeepdev\EnvironmentManager\Services\AuditLogger::class)->log(
            action: 'reveal',
            key: $key,
            sensitive: true,
            source: 'ui',
        );

        return response()->json([
            'success' => true,
            'value'   => $variable->rawValue,
        ]);
    }

    public function history(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $query = EnvVersionHistory::latest();

        if ($key = $request->input('key')) {
            $query->where('key', $key);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $history = $query->paginate(config('environment-manager.per_page', 25));

        return view('environment-manager::history', compact('history'));
    }

    public function auditLog(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $query = EnvAuditLog::latest();

        if ($key = $request->input('key')) {
            $query->where('key', $key);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(config('environment-manager.per_page', 25));

        return view('environment-manager::audit-log', compact('logs'));
    }

    public function backups(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_BACKUP_ENV);

        $backups = $this->backupManager->list();

        return view('environment-manager::backups', compact('backups'));
    }

    public function createBackup(Request $request): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_BACKUP_ENV);

        $path = $this->backupManager->create($this->manager->getEnvPath());

        return redirect()
            ->route('env-manager.backups')
            ->with('success', 'Backup created: ' . basename($path));
    }

    public function downloadBackup(Request $request, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_BACKUP_ENV);

        $path = rtrim(config('environment-manager.backup_path'), '/') . '/' . $filename;

        if (! file_exists($path)) {
            abort(404, 'Backup file not found.');
        }

        $contents = $this->backupManager->getContents($path);

        return response()->streamDownload(
            fn () => print($contents),
            basename($filename, '.enc'),
            [
                'Content-Type'        => 'text/plain',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function restoreBackup(Request $request, string $filename): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_RESTORE_ENV);

        $path    = rtrim(config('environment-manager.backup_path'), '/') . '/' . $filename;
        $envPath = $this->manager->getEnvPath();

        $this->backupManager->create($envPath); // backup current before restoring
        $this->backupManager->restore($path, $envPath);

        app(\Pradeepdev\EnvironmentManager\Services\VersionHistory::class)->record(
            action: 'restore',
            key: '*',
            oldValue: null,
            newValue: null,
            reason: "Restored from backup: {$filename}",
        );

        return redirect()
            ->route('env-manager.index')
            ->with('success', "Environment restored from [{$filename}].");
    }

    public function deleteBackup(Request $request, string $filename): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_BACKUP_ENV);

        $path = rtrim(config('environment-manager.backup_path'), '/') . '/' . $filename;
        $this->backupManager->delete($path);

        return redirect()
            ->route('env-manager.backups')
            ->with('success', "Backup [{$filename}] deleted.");
    }

    public function diff(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $diff = [];
        $historyA = null;
        $historyB = null;

        if ($idA = $request->input('id_a')) {
            $historyA = EnvVersionHistory::find($idA);
        }
        if ($idB = $request->input('id_b')) {
            $historyB = EnvVersionHistory::find($idB);
        }

        $history = EnvVersionHistory::latest()->take(50)->get();

        return view('environment-manager::diff', compact('diff', 'history', 'historyA', 'historyB'));
    }

    public function importExport(Request $request): View
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        return view('environment-manager::import-export');
    }

    public function export(Request $request, string $format = 'env'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $reveal = $this->gate->check($request->user(), EnvManagerGate::PERMISSION_REVEAL_SECRETS)
            && $request->boolean('reveal');

        $variables = $this->manager->toKeyValueMap();

        [$contents, $mime, $ext] = match ($format) {
            'json' => [$this->exporter->toJson($variables, $reveal), 'application/json', 'json'],
            'yaml' => [$this->exporter->toYaml($variables, $reveal), 'text/yaml', 'yaml'],
            default => [$this->exporter->toEnv($variables, $reveal), 'text/plain', 'env'],
        };

        return response()->streamDownload(
            fn () => print($contents),
            ".env_export_{$format}." . $ext,
            [
                'Content-Type'           => $mime,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function importStore(Request $request): RedirectResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $request->validate([
            'file'   => ['required', 'file', 'max:1024'],
            'format' => ['required', 'in:env,json'],
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());

        try {
            $variables = $request->input('format') === 'json'
                ? $this->importer->processJson($contents)
                : $this->importer->processEnv($contents);

            $result = $this->manager->bulkSet($variables, 'Imported via UI');

            return redirect()
                ->route('env-manager.index')
                ->with('success', "Imported {$result['count']} variables successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->getErrors());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }
}
