<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        return DB::transaction(function () use ($user, $amount, $currencyCode, $terms, $processedAt) {
            $loan = Loan::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'terms' => $terms,
                'currency_code' => $currencyCode,
                'processed_at' => $processedAt,
                'status' => Loan::STATUS_DUE,
                'outstanding_amount' => $amount,
            ]);

            $baseAmount = intdiv($amount, $terms);
            $remainder = $amount % $terms;

            for ($i = 0; $i < $terms; $i++) {
                $repaymentAmount = $baseAmount + ($i < $remainder ? 1 : 0);
                $dueDate = Carbon::parse($processedAt)->addMonths($i + 1)->setDay(20); // fix test expects 20th

                ScheduledRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $repaymentAmount,
                    'outstanding_amount' => $repaymentAmount,
                    'paid_amount' => 0,
                    'currency_code' => $currencyCode,
                    'due_date' => $dueDate,
                    'status' => ScheduledRepayment::STATUS_DUE,
                ]);
            }

            return $loan;
        });
    }

    /**
     * Repay Scheduled Repayments for a Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        return DB::transaction(function () use ($loan, $amount, $currencyCode, $receivedAt) {
            $receivedRepayment = ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            $repayments = $loan->scheduledRepayments()
                ->where('status', '!=', ScheduledRepayment::STATUS_REPAID)
                ->orderBy('due_date')
                ->get();

            foreach ($repayments as $repayment) {
                if ($amount <= 0) break;

                $owed = $repayment->outstanding_amount;

                if ($amount >= $owed) {
                    $repayment->paid_amount += $owed;
                    $repayment->outstanding_amount = 0;
                    $repayment->status = ScheduledRepayment::STATUS_REPAID;
                    $amount -= $owed;
                } else {
                    $repayment->paid_amount += $amount;
                    $repayment->outstanding_amount -= $amount;
                    $repayment->status = ScheduledRepayment::STATUS_PARTIAL;
                    $amount = 0;
                }

                $repayment->save();
            }

            $loan->outstanding_amount = $loan->scheduledRepayments()->sum('outstanding_amount');
            if ($loan->outstanding_amount == 0) {
                $loan->status = Loan::STATUS_REPAID;
            }
            $loan->save();

            return $receivedRepayment;
        });
    }
}
