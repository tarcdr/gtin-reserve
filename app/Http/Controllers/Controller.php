<?php

namespace App\Http\Controllers;

use App\Models\Proj12Error;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function resolveProcedureErrorMessage(?string $error): string
    {
        $code = trim((string) $error);

        if ($code === '') {
            return '';
        }

        try {
            $message = Proj12Error::query()
                ->selectRaw('TRIM(TEXT) as message')
                ->whereRaw('TRIM(CODE_ERR) = TRIM(?)', [$code])
                ->value('message');

            $message = trim((string) $message);

            return $message !== '' ? $message : $code;
        } catch (\Throwable $e) {
            Log::warning('resolveProcedureErrorMessage.failed', [
                'error' => $code,
                'exception' => $e->getMessage(),
            ]);

            return $code;
        }
    }
}
