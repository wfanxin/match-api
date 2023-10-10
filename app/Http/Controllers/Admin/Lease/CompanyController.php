<?php

namespace App\Http\Controllers\Admin\Lease;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Company;
use App\Model\Admin\Federation;
use App\Model\Admin\House;
use App\Model\Admin\User;
use Illuminate\Http\Request;

/**
 * @name 租赁公司
 * Class CompanyController
 * @package App\Http\Controllers\Admin\Lease
 *
 * @Resource("companys")
 */
class CompanyController extends Controller
{
    use FormatTrait;

    /**
     * @name 公司列表
     * @Get("/lv/lease/company/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Company $mCompany, Federation $mFederation, User $mUser)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        $userInfo = $mUser->getCurUser($params['userId']);
        if (!in_array('admin', $userInfo['roles'])) { // 不是超级管理员，查看自己创建的公司
            $where[] = ['user_id', '=', $params['userId']];
        }

        // 联盟id
        if (!empty($params['federation_id'])){
            $where[] = ['federation_id', '=', $params['federation_id']];
        }

        // 公司名称
        if (!empty($params['company_name'])){
            $where[] = ['company_name', 'like', '%' . $params['company_name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mCompany->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $user_ids = array_column($data->items(), 'user_id');
            $user_list = $mUser->whereIn('id', $user_ids)->get();
            $user_list = $this->dbResult($user_list);
            $user_list = array_column($user_list, null, 'id');
            $federation_ids = array_column($data->items(), 'federation_id');
            $federation_list = $mFederation->whereIn('id', $federation_ids)->get();
            $federation_list = $this->dbResult($federation_list);
            $federation_list = array_column($federation_list, null, 'id');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['user_name'] = $user_list[$v->user_id]['name'] ?? ''; // 用户名称
                $data->items()[$k]['federation_name'] = $federation_list[$v->federation_id]['federation_name'] ?? ''; // 联盟名称
            }
        }

        $federation_list = $mFederation->get(['id', 'federation_name']);
        $federation_list = $this->dbResult($federation_list);

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'federation_list' => $federation_list
        ]);
    }

    /**
     * @name 添加公司
     * @Post("/lv/lease/company/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Company $mCompany)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $federation_id = $params['federation_id'] ?? 0;
        $company_name = $params['company_name'] ?? '';
        $company_address = $params['company_address'] ?? '';
        $contact_name = $params['contact_name'] ?? '';
        $contact_mobile = $params['contact_mobile'] ?? '';
        $remark = $params['remark'] ?? '';

        if (empty($company_name)){
            return $this->jsonAdminResult([],10001, '公司名称不能为空');
        }

        $info = $mCompany->where('company_name', $company_name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '公司名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mCompany->insert([
            'user_id' => $params['userId'],
            'federation_id' => $federation_id,
            'company_name' => $company_name,
            'company_address' => $company_address,
            'contact_name' => $contact_name,
            'contact_mobile' => $contact_mobile,
            'remark' => $remark,
            'created_at' => $time,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 编辑公司
     * @Post("/lv/lease/company/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Company $mCompany)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $federation_id = $params['federation_id'] ?? 0;
        $company_name = $params['company_name'] ?? '';
        $company_address = $params['company_address'] ?? '';
        $contact_name = $params['contact_name'] ?? '';
        $contact_mobile = $params['contact_mobile'] ?? '';
        $remark = $params['remark'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($company_name)){
            return $this->jsonAdminResult([],10001, '公司名称不能为空');
        }

        $info = $mCompany->where('id', '!=', $id)->where('company_name', $company_name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '公司名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mCompany->where('id', $id)->update([
            'federation_id' => $federation_id,
            'company_name' => $company_name,
            'company_address' => $company_address,
            'contact_name' => $contact_name,
            'contact_mobile' => $contact_mobile,
            'remark' => $remark,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除公司
     * @Post("/lv/lease/company/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Company $mCompany, House $mHouse)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $count = $mHouse->where('company_id', $id)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'租赁公司底下有租赁合同，不能删除');
        }

        $res = $mCompany->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
