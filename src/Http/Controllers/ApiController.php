<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pradeepdev\EnvironmentManager\Authorization\EnvManagerGate;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;
use Pradeepdev\EnvironmentManager\Models\EnvVersionHistory;
use Pradeepdev\EnvironmentManager\Services\BackupManager;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

class ApiController extends Controller
{
    public function __construct(
        private readonly EnvManager $manager,
        private readonly EnvManagerGate $gate,
        private readonly BackupManager $backupManager,
        private readonly SensitivityDetector $sensitivityDetector,
    ) {}

    /**
     * GET /env — list all variables
     */
    public function index(Request $request): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $canReveal = $this->gate->check($request->user(), EnvManagerGate::PERMISSION_REVEAL_SECRETS);
        $reveal    = $canReveal && $request->boolean('reveal');

        $variables = $this->manager->all();

        if ($search = $request->input('search')) {
            $variables = $variables->filter(
                fn ($v) => str_contains(strtolower($v->key), strtolower($search)),
            );
        }

        if ($category = $request->input('category')) {
            $variables = $variables->filter(fn ($v) => $v->category === $category);
        }

        $data = $variables->map(function ($v) use ($reveal) {
            $arr          = $v->toArray();
            $arr['value'] = $v->getDisplayValue($reveal);

            return $arr;
        })->values();

        return $this->success($data);
    }

    /**
     * POST /env — create a new variable
     */
    public function store(Request $request): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $validated = $request->validate([
            'key'    => ['required', 'regex:/^[A-Z_][A-Z0-9_]*$/'],
            'value'  => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->manager->set(
                key: $validated['key'],
                value: $validated['value']   ?? '',
                reason: $validated['reason'] ?? null,
                source: 'api',
            );

            return $this->success($result, 'Variable saved successfully.', 201);
        } catch (ValidationException $e) {
            return $this->validationError($e->getErrors());
        }
    }

    /**
     * PUT /env/{key} — update a variable
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_EDIT_ENV);

        $validated = $request->validate([
            'value'  => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->manager->get($key) === null) {
            return $this->error('Variable not found.', 404);
        }

        try {
            $result = $this->manager->set(
                key: $key,
                value: $validated['value']   ?? '',
                reason: $validated['reason'] ?? null,
                source: 'api',
            );

            return $this->success($result, 'Variable updated successfully.');
        } catch (ValidationException $e) {
            return $this->validationError($e->getErrors());
        }
    }

    /**
     * DELETE /env/{key} — delete a variable
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_DELETE_ENV);

        if ($this->manager->get($key) === null) {
            return $this->error('Variable not found.', 404);
        }

        $this->manager->delete($key, $request->input('reason'), 'api');

        return $this->success(null, "Variable [{$key}] deleted.");
    }

    /**
     * GET /env/history — list version history
     */
    public function history(Request $request): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_VIEW_ENV);

        $query = EnvVersionHistory::latest();

        if ($key = $request->input('key')) {
            $query->where('key', $key);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $history = $query->paginate($perPage);

        return $this->success($history);
    }

    /**
     * GET /env/backups — list backups
     */
    public function backups(Request $request): JsonResponse
    {
        $this->gate->authorize($request->user(), EnvManagerGate::PERMISSION_BACKUP_ENV);

        $backups = $this->backupManager->list();

        return $this->success($backups);
    }

    // ---- Helpers ----

    private function success(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
        ], $status);
    }

    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }
}
