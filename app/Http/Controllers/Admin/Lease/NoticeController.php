<?php

namespace App\Http\Controllers\Admin\Lease;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Notice;
use App\Model\Admin\User;
use Illuminate\Http\Request;

/**
 * @name 公告信息
 * Class NoticeController
 * @package App\Http\Controllers\Admin\Lease
 *
 * @Resource("notices")
 */
class NoticeController extends Controller
{
    use FormatTrait;

    /**
     * @name 公告列表
     * @Get("/lv/lease/notice/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, User $mUser, Notice $mNotice)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];
        $where[] = ['notice_date', '<=', date('Y-m-d H:i:s')];
        $where[] = ['to', '=', $params['userId']];

        if ($params['is_read'] != '') {
            $where[] = ['is_read', '=', $params['is_read']];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mNotice->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        // 发送人
        if (!empty($data->items())) {
            $user_ids = array_column($data->items(), 'from');
            $user_list = $mUser->whereIn('id', $user_ids)->get();
            $user_list = $this->dbResult($user_list);
            $user_list = array_column($user_list, null, 'id');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['from_user_name'] = $user_list[$v->from]['name'] ?? 'system';
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'read_status_options' => config('global.read_status_options')
        ]);
    }

    /**
     * @name 获取公告
     * @Get("/lv/lease/notice/getNotice")
     * @Version("v1")
     * @PermissionWhiteList
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function getNotice(Request $request, User $mUser, Notice $mNotice)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];
        $where[] = ['notice_date', '<=', date('Y-m-d H:i:s')];
        $where[] = ['to', '=', $params['userId']];
        $where[] = ['is_read', '=', 0];

        $orderField = 'id';
        $sort = 'asc';
        $info = $mNotice->where($where)->orderBy($orderField, $sort)->first();
        $info = $this->dbResult($info);

        return $this->jsonAdminResult([
            'data' => $info
        ]);
    }

    /**
     * @name 已读
     * @Post("/lv/lease/notice/read")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function read(Request $request, Notice $mNotice)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $info = $mNotice->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([]);
        }

        if ($info['to'] != $params['userId']) {
            return $this->jsonAdminResult([],10001,'您无权操作');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mNotice->where('id', $id)->update(['is_read' => 1, 'updated_at' => $time]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
