<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->comment('用户id');
            $table->bigInteger('federation_id')->comment('公司联盟id');
            $table->string('company_name', 255)->comment('公司名称');
            $table->string('company_address', 500)->comment('公司地址');
            $table->string('contact_name', 100)->comment('联系人');
            $table->string('contact_mobile', 100)->comment('联系电话');
            $table->text('remark')->comment('备注');
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
        Schema::dropIfExists('companys');
    }
}
