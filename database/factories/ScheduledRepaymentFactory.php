<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    protected $model = ScheduledRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->numberBetween(1000, 2000),
            'paid_amount' => 0,
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'] ?? 1000;
            },
            'currency_code' => 'VND',
            'due_date' => $this->faker->date(),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
