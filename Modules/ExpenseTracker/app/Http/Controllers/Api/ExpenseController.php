<?php

namespace Modules\ExpenseTracker\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ExpenseTracker\Actions\CreateExpenseAction;
use Modules\ExpenseTracker\Http\Requests\StoreExpenseRequest;
use Modules\ExpenseTracker\Models\Expense;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $expense = Expense::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->latest()
            ->paginate(10);

        return response()->json($expense);
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
