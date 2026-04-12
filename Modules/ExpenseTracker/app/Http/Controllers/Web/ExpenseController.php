<?php

namespace Modules\ExpenseTracker\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
        return Inertia::render('ExpenseTracker/Index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request, CreateExpenseAction $action)
    {
        $action->handle($request->validated());
        // After creating an expense via web, we typically redirect back to the index
        return redirect()->route('expense-tracker.index');
    }
}
