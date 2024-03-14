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
        Schema::create("pr_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("transaction_id")->index();
            $table
                ->foreign("transaction_id")
                ->references("id")
                ->on("pr_transactions");

            $table->string("item_id");
            $table->string("item_code");
            $table->string("item_name");

            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");

            $table->double("quantity");
            $table->string("remarks");
            $table->timestamp("canvas_po")->nullable();
            $table->timestamp("canvas_at")->nullable();
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
        Schema::dropIfExists("pr_items");
    }
};
