<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UomController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('uoms/index', [
            'uoms' => Uom::withCount('parts')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Uom::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit added.']);

        return back();
    }

    public function update(Request $request, Uom $uom): RedirectResponse
    {
        $uom->update($this->validated($request, $uom));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit saved.']);

        return back();
    }

    public function destroy(Uom $uom): RedirectResponse
    {
        Gate::authorize('archive-records');

        if ($count = $uom->parts()->withTrashed()->count()) {
            throw new WorkflowException("{$uom->code} is used by {$count} ".str('part')->plural($count).' and can\'t be deleted.');
        }

        $uom->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$uom->code} deleted."]);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Uom $uom = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'code' => ['required', 'regex:/^[A-Z]{1,10}$/', Rule::unique('uoms', 'code')->ignore($uom)],
            'name' => ['required', 'string', 'max:40'],
        ]);
    }
}
