<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("jo_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("jo_transaction_id")->index();
            $table
                ->foreign("jo_transaction_id")
                ->references("id")
                ->on("jo_transactions");
            $table->string("description");
            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");

            $table->double("quantity");
            $table->string("remarks")->nullable();
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
        Schema::dropIfExists("jo_items");
    }
};
