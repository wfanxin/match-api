<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title', 255)->comment('标题');
            $table->string('source_table', 50)->comment('来源表');
            $table->bigInteger('source_id')->default(0)->comment('来源id');
            $table->integer('from')->default(0)->comment('发送人id');
            $table->integer('to')->default(0)->comment('接收人id');
            $table->text('content')->comment('内容');
            $table->dateTime('notice_date')->comment('通知时间');
            $table->tinyInteger('type')->comment('类型：1、审批；2、租金；3、物业费');
            $table->tinyInteger('is_read')->comment('是否已读：0、未读；1、已读');

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
        Schema::dropIfExists('notices');
    }
}
