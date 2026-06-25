<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Redirect;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $this->isDatabaseError($e)) {
                return null;
            }

            $message = $this->formatDatabaseError($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 500);
            }

            return Redirect::back()
                ->withInput()
                ->with('error', $message);
        });
    }

    protected function isDatabaseError(Throwable $e): bool
    {
        $message = strtoupper($this->extractErrorMessage($e));

        return str_contains($message, 'SQLSTATE')
            || str_contains($message, 'ORA-')
            || str_contains($message, 'OCI-')
            || str_contains($message, 'PLS-')
            || $e instanceof QueryException
            || $e instanceof PDOException;
    }

    protected function formatDatabaseError(Throwable $e): string
    {
        $message = trim($this->extractErrorMessage($e));
        $normalized = strtoupper($message);
        $objects = $this->extractDatabaseObjects($message);

        $friendly = match (true) {
            str_contains($normalized, 'ORA-00942') => 'Table or view does not exist.',
            str_contains($normalized, 'ORA-04043') => 'Object does not exist.',
            str_contains($normalized, 'ORA-06550') || str_contains($normalized, 'PLS-00201') => 'Procedure, function, or package is missing or invalid.',
            str_contains($normalized, 'ORA-01031') => 'Insufficient privileges to access the database object.',
            default => '',
        };

        if ($friendly !== '') {
            return $this->appendDatabaseObjects($friendly, $objects);
        }

        $cleaned = preg_replace('/^SQLSTATE\[[^\]]+\]:\s*/i', '', $message) ?: $message;
        $cleaned = preg_replace('/^ORA-\d{5}:\s*/i', '', $cleaned) ?: $cleaned;
        $cleaned = trim($cleaned);

        $baseMessage = $cleaned !== '' ? $cleaned : 'A database error occurred.';

        return $this->appendDatabaseObjects($baseMessage, $objects);
    }

    protected function extractErrorMessage(Throwable $e): string
    {
        $message = trim((string) $e->getMessage());

        if ($message !== '') {
            return $message;
        }

        $previous = $e->getPrevious();

        return $previous ? trim((string) $previous->getMessage()) : '';
    }

    protected function extractDatabaseObjects(string $message): array
    {
        $objects = [];
        $candidates = [
            '/\bbegin\s+([a-z0-9_."]+)\s*\(/i',
            '/\bcall\s+([a-z0-9_."]+)\s*\(/i',
            '/\bfrom\s+([a-z0-9_."]+)/i',
            '/\bjoin\s+([a-z0-9_."]+)/i',
            '/\bupdate\s+([a-z0-9_."]+)/i',
            '/\binsert\s+into\s+([a-z0-9_."]+)/i',
            '/\bdelete\s+from\s+([a-z0-9_."]+)/i',
            '/identifier\s+[\'"]([^\'"]+)[\'"]/i',
        ];

        foreach ($candidates as $pattern) {
            if (!preg_match_all($pattern, $message, $matches)) {
                continue;
            }

            foreach ($matches[1] as $match) {
                $object = trim((string) $match, "\"' ");
                $object = preg_replace('/\s+/', ' ', $object) ?: $object;
                if ($object !== '') {
                    $objects[] = $object;
                }
            }
        }

        $objects = array_values(array_unique($objects));

        return $objects;
    }

    protected function appendDatabaseObjects(string $message, array $objects): string
    {
        $objects = array_values(array_filter(array_map('trim', $objects)));

        if ($objects === []) {
            return $message;
        }

        return $message . "\nObjects: " . implode(', ', $objects);
    }
}
