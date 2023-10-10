<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('user_id')->comment('用户id');

            $table->bigInteger('company_id')->comment('租赁公司id');
            $table->string('shop_number', 100)->comment('商铺号');
            $table->decimal('lease_area', 8, 2)->comment('租赁面积㎡');
            $table->string('begin_lease_date', 50)->comment('起始租期');
            $table->string('stat_lease_date', 50)->comment('计租日期');
            $table->integer('lease_year')->comment('租赁年限');
            $table->string('repair_period', 255)->comment('装修期');
            $table->string('free_period', 255)->comment('免租经营期');
            $table->string('category', 255)->comment('业态/品类');
            $table->string('contract_number', 100)->comment('租赁合同编号');
            $table->decimal('unit_price', 8, 2)->comment('租金单价元/㎡/日');
            $table->decimal('performance_bond', 10, 2)->comment('履约保证金');
            $table->tinyInteger('pay_method')->comment('租金支付方式');
            $table->integer('rent_day')->comment('租金计租日');
            $table->tinyInteger('increase_type')->comment('租金涨幅方式：1、递增；2、自定义');
            $table->text('increase_content')->comment('租金涨幅详情');

            $table->string('property_contract_number', 100)->comment('物业合同编号');
            $table->string('property_safety_person', 100)->comment('安全责任人');
            $table->string('property_contact_info', 100)->comment('联系方式');
            $table->decimal('property_unit_price', 8, 2)->comment('物业费单价元/㎡/月');
            $table->tinyInteger('property_pay_method')->comment('物业支付方式');
            $table->integer('property_rent_day')->comment('物业计租日');
            $table->tinyInteger('property_increase_type')->comment('物业涨幅方式：1、递增；2、自定义');
            $table->text('property_increase_content')->comment('物业涨幅详情');

            $table->tinyInteger('status')->default(0)->comment('状态：0、待提交；1、审核中；2、审核通过；3、审核失败');
            $table->text('fail_reason')->comment('失败原因');

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
        Schema::dropIfExists('houses');
    }
}
