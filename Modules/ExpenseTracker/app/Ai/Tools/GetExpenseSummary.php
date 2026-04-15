<?php

namespace Modules\ExpenseTracker\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\ExpenseTracker\Models\Expense;
use Stringable;

class GetExpenseSummary implements Tool
{
    public function __construct(private readonly ?int $defaultUserId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get summarized expense insights for a period, including totals, average spend, and top category.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $period = $request['period'] ?? 'month';
        $currency = $request['currency'] ?? 'MMK';
        $userId = $request['user_id'] ?? $this->defaultUserId ?? auth()->id() ?? 1;

        [$startDate, $endDate] = $this->resolveDateRange(
            $period,
            $request['date_from'] ?? null,
            $request['date_to'] ?? null,
        );

        if ($startDate === null || $endDate === null) {
            return 'Unable to summarize expenses. Provide a valid period or valid custom date range.';
        }

        $query = Expense::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString());

        $expenses = $query->get(['amount', 'category', 'currency']);

        $total = (float) $expenses->sum('amount');
        $count = $expenses->count();
        $average = $count > 0 ? $total / $count : 0.0;

        $topCategory = $expenses
            ->groupBy('category')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc()
            ->keys()
            ->first();

        if ($count === 0) {
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
            'currency' => $currency,
            'total' => round($total, 2),
            'count' => $count,
            'average' => round($average, 2),
            'top_category' => $topCategory,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'schema_definition' => $schema->object([
                'period' => $schema->string()->enum(['today', 'week', 'month', 'year', 'custom'])->required(),
                'date_from' => $schema->string()->description('Required when period is custom. Format: YYYY-MM-DD.'),
                'date_to' => $schema->string()->description('Required when period is custom. Format: YYYY-MM-DD.'),
                'currency' => $schema->string()->description('Optional currency code, defaults to MMK.'),
                'user_id' => $schema->integer()->description('Optional user id; defaults to current context user.'),
            ])->required(),
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
