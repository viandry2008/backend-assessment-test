<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->constrained() // default ke loans.id
                ->onUpdate('cascade')
                ->onDelete('cascade'); // bisa kamu ubah jadi restrict jika perlu

            $table->integer('amount');
            $table->integer('outstanding_amount')->default(0);
            $table->integer('paid_amount')->default(0);
            $table->string('currency_code');
            $table->date('due_date');
            $table->string('status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('scheduled_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
