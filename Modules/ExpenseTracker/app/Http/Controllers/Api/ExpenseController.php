<?php

namespace Modules\ExpenseTracker\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ExpenseTracker\Models\Expense;
use Modules\ExpenseTracker\Actions\CreateExpenseAction;
use Modules\ExpenseTracker\Http\Requests\StoreExpenseRequest;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json(
            Expense::where('user_id', $request->user()->id ?? 1)
                ->orderBy('date', 'desc')
                ->latest()
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request, CreateExpenseAction $action)
    {
        $expense = $action->handle($request->validated());
        return response()->json($expense, 201);
    }
}
