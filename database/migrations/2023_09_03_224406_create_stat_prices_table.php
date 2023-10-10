<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type')->comment('类型：1、租金；2、物业费');
            $table->bigInteger('company_id')->comment('租赁公司id');
            $table->bigInteger('house_id')->comment('租赁合同id');
            $table->string('year', 10)->comment('年份');
            $table->decimal('price', 10, 2)->comment('金额');
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
        Schema::dropIfExists('stat_prices');
    }
}
