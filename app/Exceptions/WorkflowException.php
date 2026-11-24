<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

/**
 * A step the workflow does not allow from the record's current state, e.g.
 * a delivery order before there is a sales order. Shown to the user as a
 * toast on the page they came from.
 */
class WorkflowException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $this->getMessage()]);

        return back()->withErrors(['workflow' => $this->getMessage()]);
    }
}
