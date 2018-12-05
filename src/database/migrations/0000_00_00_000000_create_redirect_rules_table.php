<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRedirectRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('origin', 512)->unique();
            $table->text('destination');
            $table->unsignedSmallInteger('status_code')->default(301);

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
        Schema::dropIfExists('redirect_rules');
    }
}