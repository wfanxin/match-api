<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTogethersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('togethers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->default('')->comment('名称');
            $table->string('match_ids', 500)->default('')->comment('比赛id');
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
        Schema::dropIfExists('togethers');
    }
}
