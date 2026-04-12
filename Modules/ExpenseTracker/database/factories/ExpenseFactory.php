<?php

namespace Modules\ExpenseTracker\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExpenseTracker\Models\Expense;
use App\Models\User;

class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 5, 200),
            'currency' => 'MMK',
            'description' => $this->faker->sentence(3),
            'category' => $this->faker->randomElement(['Food', 'Transport', 'Utilities', 'Entertainment', 'Shopping', 'Other']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
