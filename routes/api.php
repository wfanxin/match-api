<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*********************************************/

$dingoApi = app(\Dingo\Api\Routing\Router::class);
$dingoApi->version("v1", [
    "middleware" => ["AdminToken", "CrossHttp"]
], function ($dingoApi) {

    // 公司联盟
    $dingoApi->get("lease/federation/list", \App\Http\Controllers\Admin\Lease\FederationController::class."@list")->name("lease.federation.list");
    $dingoApi->post("lease/federation/add", \App\Http\Controllers\Admin\Lease\FederationController::class."@add")->name("lease.federation.add");
    $dingoApi->post("lease/federation/edit", \App\Http\Controllers\Admin\Lease\FederationController::class."@edit")->name("lease.federation.edit");
    $dingoApi->post("lease/federation/del", \App\Http\Controllers\Admin\Lease\FederationController::class."@del")->name("lease.federation.del");

    // 租赁公司
    $dingoApi->get("lease/company/list", \App\Http\Controllers\Admin\Lease\CompanyController::class."@list")->name("lease.company.list");
    $dingoApi->post("lease/company/add", \App\Http\Controllers\Admin\Lease\CompanyController::class."@add")->name("lease.company.add");
    $dingoApi->post("lease/company/edit", \App\Http\Controllers\Admin\Lease\CompanyController::class."@edit")->name("lease.company.edit");
    $dingoApi->post("lease/company/del", \App\Http\Controllers\Admin\Lease\CompanyController::class."@del")->name("lease.company.del");

    // 租赁合同
    $dingoApi->get("lease/house/list", \App\Http\Controllers\Admin\Lease\HouseController::class."@list")->name("lease.house.list");
    $dingoApi->post("lease/house/add", \App\Http\Controllers\Admin\Lease\HouseController::class."@add")->name("lease.house.add");
    $dingoApi->post("lease/house/edit", \App\Http\Controllers\Admin\Lease\HouseController::class."@edit")->name("lease.house.edit");
    $dingoApi->post("lease/house/submitReview", \App\Http\Controllers\Admin\Lease\HouseController::class."@submitReview")->name("lease.house.submitReview");
    $dingoApi->post("lease/house/del", \App\Http\Controllers\Admin\Lease\HouseController::class."@del")->name("lease.house.del");
    $dingoApi->post("lease/house/exportExcel", \App\Http\Controllers\Admin\Lease\HouseController::class."@exportExcel")->name("lease.house.exportExcel");
    $dingoApi->get("lease/house/detail", \App\Http\Controllers\Admin\Lease\HouseController::class."@detail")->name("lease.house.detail");
    $dingoApi->post("lease/house/preview", \App\Http\Controllers\Admin\Lease\HouseController::class."@preview")->name("lease.house.preview");

    // 合同审批
    $dingoApi->get("lease/approval/list", \App\Http\Controllers\Admin\Lease\ApprovalController::class."@list")->name("lease.approval.list");
    $dingoApi->post("lease/approval/pass", \App\Http\Controllers\Admin\Lease\ApprovalController::class."@pass")->name("lease.approval.pass");
    $dingoApi->post("lease/approval/fail", \App\Http\Controllers\Admin\Lease\ApprovalController::class."@fail")->name("lease.approval.fail");

    // 公告管理
    $dingoApi->get("lease/notice/list", \App\Http\Controllers\Admin\Lease\NoticeController::class."@list")->name("lease.notice.list");
    $dingoApi->get("lease/notice/getNotice", \App\Http\Controllers\Admin\Lease\NoticeController::class."@getNotice")->name("lease.notice.getNotice");
    $dingoApi->post("lease/notice/read", \App\Http\Controllers\Admin\Lease\NoticeController::class."@read")->name("lease.notice.read");

    // 统计数据
    $dingoApi->get("lease/statPrice/getData", \App\Http\Controllers\Admin\Lease\StatPriceController::class."@getData")->name("lease.statPrice.getData");

    // 用户
    $dingoApi->post("users/checkName", \App\Http\Controllers\Admin\System\UserController::class."@checkName")->name("users.checkName");
    $dingoApi->put("users/pwd", \App\Http\Controllers\Admin\System\UserController::class."@changePwd")->name("users.changePwd");
    $dingoApi->delete("users/batch", \App\Http\Controllers\Admin\System\UserController::class."@batchDestroy")->name("users.batchDestroy"); # 非resource应该放在resource上面
    $dingoApi->Resource("users", \App\Http\Controllers\Admin\System\UserController::class);

    // 权限
    $dingoApi->patch("permissions/{id}", \App\Http\Controllers\Admin\System\PermissionController::class."@edit")->name("permissions.edit");
    $dingoApi->get("permissions/total", \App\Http\Controllers\Admin\System\PermissionController::class."@total")->name("permissions.total");
    $dingoApi->get("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@index")->name("permissions.index");
    $dingoApi->put("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@update")->name("permissions.update");

    // 角色
    $dingoApi->get("roles/total", \App\Http\Controllers\Admin\System\RoleController::class."@total")->name("roles.total");
    $dingoApi->delete("roles/batch", \App\Http\Controllers\Admin\System\RoleController::class."@batchDestroy")->name("roles.batchDestroy");
    $dingoApi->Resource("roles", \App\Http\Controllers\Admin\System\RoleController::class);

    // 系统操作日志
    $dingoApi->get("logs", \App\Http\Controllers\Admin\System\LogController::class."@index")->name("logs.index");

    // 用户授权令牌 - 销毁
    $dingoApi->delete("tokens/{role}", \App\Http\Controllers\Admin\System\TokenController::class."@destroy")->name("tokens.destroy");

});

$dingoApi->version("v1", [
    "middleware" => ["CrossHttp"]
], function ($dingoApi) {
    // 用户授权令牌 - 获取
    $dingoApi->post("tokens", \App\Http\Controllers\Admin\System\TokenController::class."@store")->name("tokens.store");
});


