<?php

namespace Modules\ExpenseTracker\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\ExpenseTracker\Models\Expense;
use Stringable;

class GetExpenseList implements Tool
{
    public function __construct(private readonly ?int $defaultUserId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get a concise list of expenses for a period, optionally filtered by category, with capped results.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $period = $request['period'] ?? 'month';
        $category = $request['category'] ?? null;
        $limit = max(1, min((int) ($request['limit'] ?? 5), 10));
        $userId = $request['user_id'] ?? $this->defaultUserId ?? auth()->id() ?? 1;

        [$startDate, $endDate] = $this->resolveDateRange(
            $period,
            $request['date_from'] ?? null,
            $request['date_to'] ?? null,
        );

        if ($startDate === null || $endDate === null) {
            return 'Unable to retrieve expenses. Provide a valid period or valid custom date range.';
        }

        $query = Expense::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->when(
                is_string($category) && $category !== '',
                fn ($builder) => $builder->where('category', $category),
            );

        $totalCount = (clone $query)->count();
        $expenses = (clone $query)
            ->orderByDesc('date')
            ->latest()
            ->limit($limit)
            ->get(['id', 'amount', 'currency', 'description', 'category', 'date']);

        if ($totalCount === 0) {
            return sprintf(
                'No expenses found from %s to %s.',
                $startDate->toDateString(),
                $endDate->toDateString(),
            );
        }

        return json_encode([
            'period' => $period,
            'date_from' => $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
            'count' => $totalCount,
            'limit' => $limit,
            'truncated' => $totalCount > $limit,
            'items' => $expenses->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'date' => $expense->date?->toDateString(),
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'currency' => $expense->currency,
                'description' => $expense->description,
            ])->values(),
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->enum(['today', 'yesterday', 'week', 'month', 'year', 'custom'])->required(),
            'date_from' => $schema->string()->description('Required when period is custom. Format: YYYY-MM-DD.'),
            'date_to' => $schema->string()->description('Required when period is custom. Format: YYYY-MM-DD.'),
            'category' => $schema->string()->description('Optional category filter, e.g. Food or Transport.'),
            'limit' => $schema->integer()->description('Optional max number of items to return. Default 5, max 10.'),
            'user_id' => $schema->integer()->description('Optional user id; defaults to current context user.'),
        ];
    }

    /**
     * Resolve the date range from period or custom dates.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveDateRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => $this->resolveCustomRange($dateFrom, $dateTo),
            default => [null, null],
        };
    }

    /**
     * Resolve custom date range.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveCustomRange(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom === null || $dateTo === null) {
            return [null, null];
        }

        try {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = Carbon::parse($dateTo)->endOfDay();
        } catch (\Throwable) {
            return [null, null];
        }

        if ($startDate->greaterThan($endDate)) {
            return [null, null];
        }

        return [$startDate, $endDate];
    }
}
