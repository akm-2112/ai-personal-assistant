import { Head, useForm, usePage } from '@inertiajs/react';
import { Search, Wallet } from 'lucide-react';
import { index, store } from '@/actions/Modules/ExpenseTracker/Http/Controllers/Web/ExpenseController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type ExpenseItem = {
    id: number;
    amount: string | number;
    currency: string;
    description: string | null;
    category: string;
    date: string;
};

type ExpenseTrackerPageProps = {
    summary: {
        weekTotal: number;
        averagePerDay: number;
        topCategory: string;
    };
    recentExpenses: ExpenseItem[];
};

type ExpenseFormData = {
    amount: string;
    currency: string;
    category: string;
    date: string;
    description: string;
};

const moduleTabs = ['Expense Tracker', 'Tasks', 'Habits', 'Goals'];

const formatCurrency = (amount: number, currency: string): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
};

const formatDateLabel = (date: string): string => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
};

export default function ExpenseTrackerIndex() {
    const { summary, recentExpenses } = usePage<ExpenseTrackerPageProps>().props;
    const form = useForm<ExpenseFormData>({
        amount: '',
        currency: 'MMK',
        category: 'Meals',
        date: new Date().toISOString().slice(0, 10),
        description: '',
    });

    const onSubmit = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        form.submit(store(), {
            onSuccess: () => {
                form.reset('amount', 'description');
            },
        });
    };

    return (
        <>
            <Head title="Modules - Expense Tracker" />

            <div className="flex h-full flex-1 flex-col bg-muted/30">
                <div className="border-b border-border bg-card px-4 py-4 md:px-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="font-sans text-xl font-semibold text-foreground">Modules</h1>
                            <p className="text-sm text-muted-foreground">Expense tracker workspace</p>
                        </div>

                        <div className="flex w-full items-center gap-2 sm:w-auto">
                            <div className="relative w-full sm:w-72">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input placeholder="Search expenses" className="pl-9" />
                            </div>
                            <Button type="button" className="shrink-0">
                                Add expense
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="border-b border-border px-4 py-3 md:px-6">
                    <div className="flex flex-wrap items-center gap-2">
                        {moduleTabs.map((tab, tabIndex) => {
                            const isActive = tabIndex === 0;

                            return (
                                <button
                                    key={tab}
                                    type="button"
                                    className={[
                                        'rounded-full px-3 py-1.5 text-sm transition-colors',
                                        isActive
                                            ? 'border border-ring bg-card text-foreground shadow-sm'
                                            : 'border border-transparent text-muted-foreground hover:bg-card hover:text-foreground',
                                    ].join(' ')}
                                >
                                    {tab}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div className="flex-1 p-4 md:p-6">
                    <div className="grid h-full gap-4 xl:grid-cols-[420px_minmax(0,1fr)]">
                        <div className="flex h-full flex-col gap-4">
                            <Card className="rounded-2xl">
                                <CardHeader>
                                    <CardTitle className="font-mono text-lg">Quick Add Expense</CardTitle>
                                    <CardDescription>Capture spending in seconds with clean structured data.</CardDescription>
                                </CardHeader>

                                <CardContent>
                                    <form onSubmit={onSubmit} className="space-y-3">
                                        <div>
                                            <Input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="Amount"
                                                value={form.data.amount}
                                                onChange={(event) => form.setData('amount', event.target.value)}
                                            />
                                            <InputError message={form.errors.amount} />
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <Input
                                                    type="date"
                                                    value={form.data.date}
                                                    onChange={(event) => form.setData('date', event.target.value)}
                                                />
                                                <InputError message={form.errors.date} />
                                            </div>
                                            <div>
                                                <select
                                                    className="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-background px-3 py-1 text-sm outline-none focus-visible:ring-[3px]"
                                                    value={form.data.category}
                                                    onChange={(event) => form.setData('category', event.target.value)}
                                                >
                                                    <option>Meals</option>
                                                    <option>Transport</option>
                                                    <option>Software</option>
                                                    <option>Utilities</option>
                                                    <option>Other</option>
                                                </select>
                                                <InputError message={form.errors.category} />
                                            </div>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-[120px_minmax(0,1fr)]">
                                            <div>
                                                <Input
                                                    maxLength={3}
                                                    placeholder="MMK"
                                                    value={form.data.currency}
                                                    onChange={(event) =>
                                                        form.setData('currency', event.target.value.toUpperCase())
                                                    }
                                                />
                                                <InputError message={form.errors.currency} />
                                            </div>
                                            <Input
                                                placeholder="Notes"
                                                value={form.data.description}
                                                onChange={(event) => form.setData('description', event.target.value)}
                                            />
                                        </div>

                                        <div className="flex justify-end gap-2 pt-2">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() => form.reset()}
                                                disabled={form.processing}
                                            >
                                                Reset
                                            </Button>
                                            <Button type="submit" disabled={form.processing}>
                                                Save expense
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>

                            <div className="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                                <Card className="rounded-2xl">
                                    <CardContent className="space-y-1 pt-6">
                                        <p className="text-xs text-muted-foreground">This week</p>
                                        <p className="font-mono text-xl font-bold text-foreground">
                                            {formatCurrency(summary.weekTotal, 'USD')}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card className="rounded-2xl">
                                    <CardContent className="space-y-1 pt-6">
                                        <p className="text-xs text-muted-foreground">Top category</p>
                                        <p className="font-mono text-xl font-bold text-foreground">{summary.topCategory}</p>
                                    </CardContent>
                                </Card>
                                <Card className="rounded-2xl">
                                    <CardContent className="space-y-1 pt-6">
                                        <p className="text-xs text-muted-foreground">Avg/day</p>
                                        <p className="font-mono text-xl font-bold text-foreground">
                                            {formatCurrency(summary.averagePerDay, 'USD')}
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <div className="flex h-full flex-col gap-4">
                            <Card className="rounded-2xl">
                                <CardHeader>
                                    <CardTitle className="font-mono text-lg">Filters</CardTitle>
                                    <CardDescription>Refine recent expenses by date, category, and amount.</CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-3 md:grid-cols-3">
                                    <select className="border-input h-9 rounded-md border bg-background px-3 py-1 text-sm">
                                        <option>Last 30 days</option>
                                        <option>Last 7 days</option>
                                        <option>This month</option>
                                    </select>
                                    <select className="border-input h-9 rounded-md border bg-background px-3 py-1 text-sm">
                                        <option>All categories</option>
                                        <option>Meals</option>
                                        <option>Transport</option>
                                        <option>Software</option>
                                    </select>
                                    <Input placeholder="Min - Max" />
                                </CardContent>
                                <CardFooter className="justify-end gap-2">
                                    <Button type="button" variant="ghost">
                                        Reset
                                    </Button>
                                    <Button type="button">Apply filters</Button>
                                </CardFooter>
                            </Card>

                            <Card className="flex-1 rounded-2xl">
                                <CardHeader>
                                    <CardTitle className="font-mono text-lg">Recent Expenses</CardTitle>
                                    <CardDescription>Latest transactions synced from your assistant.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {recentExpenses.length > 0 ? (
                                        recentExpenses.map((expense) => (
                                            <div
                                                key={expense.id}
                                                className="flex items-center justify-between rounded-xl bg-muted px-3 py-3 text-sm"
                                            >
                                                <p className="text-foreground">
                                                    {formatDateLabel(expense.date)} · {expense.category}
                                                </p>
                                                <p className="font-mono font-semibold text-foreground">
                                                    {formatCurrency(Number(expense.amount), expense.currency || 'USD')}
                                                </p>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                                            No expenses found. Add a new expense to start tracking.
                                        </div>
                                    )}

                                    <div className="space-y-2 rounded-xl bg-secondary p-3">
                                        <p className="text-xs font-medium text-muted-foreground">Loading state preview</p>
                                        <div className="h-2 w-full rounded-full bg-border" />
                                    </div>

                                    <div className="flex items-center justify-center gap-2 rounded-xl bg-secondary px-3 py-4">
                                        <Wallet className="size-4 text-muted-foreground" />
                                        <p className="text-xs text-muted-foreground">Empty state: no expenses in selected filter</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

ExpenseTrackerIndex.layout = {
    breadcrumbs: [
        {
            title: 'Modules',
            href: index(),
        },
        {
            title: 'Expense Tracker',
            href: index(),
        },
    ],
};
