<?php

namespace App\Http\Controllers\Admin\Lease;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Company;
use App\Model\Admin\Federation;
use App\Model\Admin\User;
use Illuminate\Http\Request;

/**
 * @name 公司联盟
 * Class FederationController
 * @package App\Http\Controllers\Admin\Lease
 *
 * @Resource("federations")
 */
class FederationController extends Controller
{
    use FormatTrait;

    /**
     * @name 联盟列表
     * @Get("/lv/lease/federation/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Federation $mFederation, User $mUser)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        $userInfo = $mUser->getCurUser($params['userId']);
        if (!in_array('admin', $userInfo['roles'])) { // 不是超级管理员，查看自己创建的公司
            $where[] = ['user_id', '=', $params['userId']];
        }

        // 联盟名称
        if (!empty($params['federation_name'])){
            $where[] = ['federation_name', 'like', '%' . $params['federation_name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mFederation->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        // 用户名称
        if (!empty($data->items())) {
            $user_ids = array_column($data->items(), 'user_id');
            $user_list = $mUser->whereIn('id', $user_ids)->get();
            $user_list = $this->dbResult($user_list);
            $user_list = array_column($user_list, null, 'id');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['user_name'] = $user_list[$v->user_id]['name'] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 添加联盟
     * @Post("/lv/lease/federation/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Federation $mFederation)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $federation_name = $params['federation_name'] ?? '';
        $contact_name = $params['contact_name'] ?? '';
        $contact_mobile = $params['contact_mobile'] ?? '';

        if (empty($federation_name)){
            return $this->jsonAdminResult([],10001, '联盟名称不能为空');
        }

        $info = $mFederation->where('federation_name', $federation_name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '联盟名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mFederation->insert([
            'user_id' => $params['userId'],
            'federation_name' => $federation_name,
            'contact_name' => $contact_name,
            'contact_mobile' => $contact_mobile,
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
     * @name 编辑联盟
     * @Post("/lv/lease/federation/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Federation $mFederation)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $federation_name = $params['federation_name'] ?? '';
        $contact_name = $params['contact_name'] ?? '';
        $contact_mobile = $params['contact_mobile'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($federation_name)){
            return $this->jsonAdminResult([],10001, '联盟名称不能为空');
        }

        $info = $mFederation->where('id', '!=', $id)->where('federation_name', $federation_name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '联盟名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mFederation->where('id', $id)->update([
            'federation_name' => $federation_name,
            'contact_name' => $contact_name,
            'contact_mobile' => $contact_mobile,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除联盟
     * @Post("/lv/lease/federation/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Federation $mFederation, Company $mCompany)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $count = $mCompany->where('federation_id', $id)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'公司联盟底下有租赁公司，不能删除');
        }

        $res = $mFederation->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
