<?php

namespace App\Http\Controllers;

use App\Services\ExistingComponentService;
use App\Services\ExistingLevelService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExistingComponentController extends Controller
{
    public function __construct(private ExistingComponentService $components, private ExistingLevelService $levels) {}

    private function input(Request $request, string $scope): array
    {
        return $request->validate([
            'fgMaterialId' => ['required', 'string', 'max:1000'],
            'fgBomId' => ['required', 'string', 'max:1000'],
            'semiFgLv1BomId' => [in_array($scope, ['semi1', 'raw4']) ? 'required' : 'nullable', 'string', 'max:1000'],
            'semiFgLv2BomId' => [in_array($scope, ['semi2', 'level1']) ? 'required' : 'nullable', 'string', 'max:1000'],
            'brand' => ['nullable', 'string', 'max:1000'],
            'productSubCat' => ['nullable', 'string', 'max:1000'],
            'masterMaterialId' => [$scope === 'raw4' ? 'required' : 'nullable', 'string', 'max:1000'],
            'mattype' => ['nullable', 'string', 'max:10'],
            'subMattype' => ['nullable', 'string', 'max:10'],
            'componentId' => ['nullable', 'string', 'max:1000'],
            'no' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function back(string $scope, array $context): string
    {
        if (in_array($scope, ['fg', 'level1', 'level2', 'semi1', 'semi2'], true)) {
            return route('product.view', ['materialId' => $context['fgMaterialId']]);
        }
        return route('packmaterial.semi-fg-lv1-bom.new', [
            'actionMode' => 'view', 'levelMaterialId' => $context['levelMaterialId'],
            'referentMaterialId' => $context['fgMaterialId'], 'componentId' => $context['masterMaterialId'],
        ]);
    }

    private function isLevel(string $scope): bool { return in_array($scope, ['level1', 'level2'], true); }

    private function context(string $scope, array $input): array
    {
        if ($this->isLevel($scope)) return $this->levels->context((int) substr($scope, -1), $input);
        $this->components->requireReady($scope);
        return $this->components->context($scope, $input);
    }

    private function rows(string $scope, array $context): array
    {
        if (!$this->isLevel($scope)) return $this->components->rows($scope, $context);
        return array_map(fn ($r) => $r + ['code' => $r['bomId'], 'label' => $r['desc'], 'status' => $r['statusRow']], $this->levels->rows((int) substr($scope, -1), $context));
    }

    public function index(Request $request, string $scope)
    {
        $input = $this->input($request, $scope);
        $context = $this->context($scope, $input);
        $view = $request->validate(['mode' => ['nullable', 'in:create,view']])['mode'] ?? 'create';
        $selected = null;
        if ($view === 'view') {
            $selected = collect($this->rows($scope, $context))->first(fn ($row) => $row['no'] === ($input['no'] ?? '') && $row['code'] === ($input['componentId'] ?? ''));
            abort_unless($selected, 404);
        }
        return Inertia::render('ExistingComponents/Select', [
            'scope' => $scope, 'context' => $context, 'mode' => $view, 'selected' => $selected,
            'filters' => $view === 'create' && $scope !== 'raw4' ? ($this->isLevel($scope) ? $this->levels->filters((int) substr($scope, -1)) : $this->components->filters($scope)) : null,
            'backUrl' => $this->back($scope, $context),
        ]);
    }

    public function options(Request $request, string $scope)
    {
        $input = $this->input($request, $scope);
        $context = $this->context($scope, $input);
        return response()->json([
            'context' => $context,
            'options' => $this->isLevel($scope) ? $this->levels->options((int) substr($scope, -1), $input) : $this->components->options($scope, $input),
            'rows' => $this->rows($scope, $context),
            'newRows' => $scope === 'raw4' ? $this->components->rawNewRows($context) : [],
        ]);
    }

    public function save(Request $request, string $scope)
    {
        $input = $this->input($request, $scope);
        $request->validate(['componentId' => ['required', 'string'], 'mattype' => [$scope !== 'raw4' ? 'required' : 'nullable'], 'subMattype' => [$scope !== 'raw4' ? 'required' : 'nullable']]);
        if ($this->isLevel($scope)) $this->levels->save((int) substr($scope, -1), $input, (string) $request->user()->user_login, (string) $request->user()->role);
        else $this->components->save($scope, $input, (string) $request->user()->user_login, (string) $request->user()->role);
        return redirect($this->back($scope, $this->context($scope, $input)))->with('message', 'Added existing component.');
    }

    public function delete(Request $request, string $scope)
    {
        $input = $this->input($request, $scope);
        $request->validate(['componentId' => ['required', 'string'], 'no' => ['required', 'string']]);
        if ($this->isLevel($scope)) $this->levels->delete((int) substr($scope, -1), $input, (string) $request->user()->user_login, (string) $request->user()->role);
        else $this->components->delete($scope, $input, (string) $request->user()->user_login, (string) $request->user()->role);
        return redirect($this->back($scope, $this->context($scope, $input)))->with('message', 'Deleted existing component link.');
    }
}
