<?php

namespace App\Http\Controllers\Admin\Lease;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Company;
use App\Model\Admin\House;
use App\Model\Admin\Notice;
use App\Model\Admin\User;
use Illuminate\Http\Request;

/**
 * @name 合同审批
 * Class ApprovalController
 * @package App\Http\Controllers\Admin\Lease
 *
 * @Resource("houses")
 */
class ApprovalController extends Controller
{
    use FormatTrait;

    /**
     * @name 合同审批列表
     * @Get("/lv/lease/approval/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, House $mHouse, User $mUser, Company $mCompany)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];
        $where[] = [function ($query) {
            $query->whereIn('status', [1, 2]);
        }];

        $userInfo = $mUser->getCurUser($params['userId']);
        if (!in_array('admin', $userInfo['roles'])) { // 不是超级管理员，查看自己创建租赁公司底下的合同
            $company_ids = $mCompany->where('user_id', $params['userId'])->get();
            $company_ids = $this->dbResult($company_ids);
            $company_ids = array_column($company_ids, 'id');
            $where[] = [function ($query) use ($company_ids) {
                $query->whereIn('company_id', $company_ids);
            }];
        }

        // 租赁合同
        if (!empty($params['company_name'])){
            $company_ids = $mCompany->where('company_name', 'like', '%' . $params['company_name'] . '%')->get(['id']);
            $company_ids = $this->dbResult($company_ids);
            $company_ids = array_column($company_ids, 'id');
            $where[] = [function ($query) use ($company_ids) {
                $query->whereIn('company_id', $company_ids);
            }];
        }

        // 商铺号
        if (!empty($params['shop_number'])){
            $where[] = ['shop_number', 'like', '%' . $params['shop_number'] . '%'];
        }

        if ($params['status'] != '') {
            $where[] = ['status', '=', $params['status']];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mHouse->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        $company_list = $mCompany->get(['id', 'company_name']);
        $company_list = $this->dbResult($company_list);

        // 租赁公司
        if (!empty($data->items())) {
            $company_arr = array_column($company_list, null, 'id');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['company_name'] = $company_arr[$v->company_id]['company_name'] ?? '';
                $data->items()[$k]['increase_content'] = json_decode($v->increase_content, true) ?? [];
                $data->items()[$k]['property_increase_content'] = json_decode($v->property_increase_content, true) ?? [];
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'company_list' => $company_list,
            'pay_method_list' => config('global.pay_method_list'),
            'increase_type_list' => config('global.increase_type_list'),
            'approval_status_options' => config('global.approval_status_options')
        ]);
    }

    /**
     * @name 合同审批通过
     * @Post("/lv/lease/approval/pass")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function pass(Request $request, House $mHouse, Notice $mNotice)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $info = $mHouse->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        if (!in_array($info['status'], [1])) {
            return $this->jsonAdminResult([],10001,'不是待审批状态');
        }

        $mNotice->addRent($info); // 添加租金和物业费通知

        $time = date('Y-m-d H:i:s');
        $res = $mHouse->where('id', $id)->update(['status' => 2, 'updated_at' => $time]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 合同审批失败
     * @Post("/lv/lease/approval/fail")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function fail(Request $request, House $mHouse, Notice $mNotice)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $id = $params['id'] ?? 0;
        $fail_reason = $params['fail_reason'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        if (empty($fail_reason)) {
            return $this->jsonAdminResult([],10001,'审批失败原因不能为空');
        }

        $info = $mHouse->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        if (!in_array($info['status'], [1])) {
            return $this->jsonAdminResult([],10001,'不是待审批状态');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mHouse->where('id', $id)->update(['status' => 3, 'fail_reason' => $fail_reason , 'updated_at' => $time]);

        if ($res) {
            $mNotice->insert([
                'title' => '审批不通过',
                'source_table' => 'houses',
                'source_id' => $id,
                'from' => $params['userId'],
                'to' => $info['user_id'],
                'content' => $fail_reason,
                'notice_date' => $time,
                'type' => 1,
                'created_at' => $time,
                'updated_at' => $time
            ]);
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
