<?php

namespace Modules\ExpenseTracker\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\ExpenseTracker\Actions\CreateExpenseAction;
use Modules\ExpenseTracker\Http\Requests\StoreExpenseRequest;
use Modules\ExpenseTracker\Models\Expense;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()?->id;
        $baseQuery = Expense::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId));

        $thisWeekExpenses = (clone $baseQuery)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $thisWeekTotal = (float) $thisWeekExpenses->sum('amount');
        $averagePerDay = $thisWeekTotal / 7;

        $topCategory = (clone $baseQuery)
            ->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->first();

        $recentExpenses = (clone $baseQuery)
            ->orderByDesc('date')
            ->latest()
            ->limit(8)
            ->get(['id', 'amount', 'currency', 'description', 'category', 'date']);

        return Inertia::render('ExpenseTracker/Index', [
            'summary' => [
                'weekTotal' => $thisWeekTotal,
                'averagePerDay' => $averagePerDay,
                'topCategory' => $topCategory?->category ?? 'No category',
            ],
            'recentExpenses' => $recentExpenses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request, CreateExpenseAction $action): RedirectResponse
    {
        $action->handle([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        // After creating an expense via web, we typically redirect back to the index
        return redirect()->route('expense-tracker.index');
    }
}
