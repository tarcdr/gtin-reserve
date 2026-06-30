<?php

namespace App\Http\Controllers;

use App\Models\Proj12Error;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;
use PDO;

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

    protected function generateFgBomIdProcedure(string $suggestId, string $site, ?string $userLogin = null): array
    {
        $suggestId = trim($suggestId);
        $site = trim($site);
        $userLogin = trim((string) ($userLogin ?? ''));

        if ($suggestId === '' || $site === '') {
            return [
                'bomId' => '',
                'error' => '',
            ];
        }

        $pdo = DB::getPdo();
        $bomId = null;
        $error = null;
        $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_BOMID_FG(:p_suggest_id, :p_site, :p_user_login, :p_out_bomid, :p_error); END;');
        $stmt->bindValue(':p_suggest_id', $suggestId, PDO::PARAM_STR);
        $stmt->bindValue(':p_site', $site, PDO::PARAM_STR);
        $stmt->bindValue(':p_user_login', $userLogin, PDO::PARAM_STR);
        $stmt->bindParam(':p_out_bomid', $bomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
        $stmt->execute();

        return [
            'bomId' => trim((string) $bomId),
            'error' => trim((string) $this->resolveProcedureErrorMessage($error)),
        ];
    }
}
