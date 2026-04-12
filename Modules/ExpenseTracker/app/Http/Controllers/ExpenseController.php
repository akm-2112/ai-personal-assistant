<?php

namespace Modules\ExpenseTracker\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ExpenseTracker\Models\Expense;

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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('expensetracker::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'date' => 'required|date',
        ]);

        $expense = Expense::create([
            'user_id' => $request->user()->id ?? 1,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'MMK',
            'description' => $validated['description'],
            'category' => $validated['category'],
            'date' => $validated['date'],
        ]);

        return response()->json($expense, 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('expensetracker::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('expensetracker::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
