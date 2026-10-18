<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->decimal('price_per_day', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'active', 'completed'])->default('pending');
            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('renter_id');
            $table->index('owner_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
