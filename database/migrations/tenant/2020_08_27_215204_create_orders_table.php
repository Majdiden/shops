<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->BigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->enum('status', ['pending', 'processing', 'completed', 'declined'])->default('pending');
            $table->decimal('grand_total');
            $table->unsignedInteger('item_count');
            $table->boolean('payment_status')->default(1);
            $table->string('payment_method')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->string('number');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
