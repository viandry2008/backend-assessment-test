<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivedRepaymentFactory extends Factory
{
    protected $model = ReceivedRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->numberBetween(1000, 2000),
            'currency_code' => 'VND',
            'received_at' => $this->faker->date(),
        ];
    }
}
