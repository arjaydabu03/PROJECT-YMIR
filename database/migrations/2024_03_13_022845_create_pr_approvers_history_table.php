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
        Schema::create("pr_approvers_history", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("pr_id")->index();
            $table
                ->foreign("pr_id")
                ->references("id")
                ->on("pr_transactions");
            $table->string("approver_id");
            $table->string("approver_name");
            $table->timestamp("approved_at")->nullable();
            $table->string("layer");
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
        Schema::dropIfExists("pr_approvers_history");
    }
};
