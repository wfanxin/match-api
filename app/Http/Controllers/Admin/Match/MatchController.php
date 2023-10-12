<?php

namespace App\Http\Controllers\Admin\Match;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Match;
use App\Model\Admin\Tag;
use Illuminate\Http\Request;

/**
 * @name 比赛管理
 * Class MatchController
 * @package App\Http\Controllers\Admin\Match
 *
 * @Resource("matchs")
 */
class MatchController extends Controller
{
    use FormatTrait;

    /**
     * @name 比赛列表
     * @Get("/lv/match/match/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Match $mMatch, Tag $mTag)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 标签大类id
        if (!empty($params['pid'])){
            $where[] = ['pid', '=', $params['pid']];
        }

        // 子类名称
        if (!empty($params['name'])){
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mMatch->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        $tag_sub_list = $mTag->get(['pid', 'id', 'name']);
        $tag_sub_list = $this->dbResult($tag_sub_list);

        if (!empty($data->items())) {
            $tag_sub_list_arr = array_column($tag_sub_list, 'pid', 'id');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['ptag_id'] = $tag_sub_list_arr[$v->tag_id] ?? 0;
                $data->items()[$k]['match_data'] = json_decode($v->match_data, true) ?? [];
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'tag_list' => config('global.tag_list'),
            'tag_sub_list' => $tag_sub_list,
            'platform_list' => config('global.platform_list')
        ]);
    }

    /**
     * @name 添加比赛
     * @Post("/lv/match/match/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Match $mMatch)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $tag_id = $params['tag_id'] ?? 0;
        $match_play = trim($params['match_play'] ?? '');
        $match_score = trim($params['match_score'] ?? '');
        $match_result = trim($params['match_result'] ?? '');
        $match_half_audience = trim($params['match_half_audience'] ?? '');
        $match_type = trim($params['match_type'] ?? '');
        $match_data = $params['match_data'] ?? [];

        if (empty($tag_id)) {
            return $this->jsonAdminResult([],10001, '请选择标签子类');
        }

        if (empty($match_play)) {
            return $this->jsonAdminResult([],10001, '比赛场次不能为空');
        }

        if (empty($match_score)) {
            return $this->jsonAdminResult([],10001, '比分不能为空');
        }

        if (empty($match_result)) {
            return $this->jsonAdminResult([],10001, '比赛结果不能为空');
        }

        if (empty($match_half_audience)) {
            return $this->jsonAdminResult([],10001, '半全场不能为空');
        }

        if (empty($match_type)) {
            return $this->jsonAdminResult([],10001, '比赛类型不能为空');
        }

        if (empty($match_data)) {
            return $this->jsonAdminResult([],10001, '数据不能为空');
        }

        foreach ($match_data as $key => $value) {
            foreach ($value['list'] as $k => $v) {
                foreach ($v as  $valueKey => $valueData) {
                    $v[$valueKey] = trim($valueData);
                    if (empty($v[$valueKey])) {
                        return $this->jsonAdminResult([],10001, '数据不能为空');
                    }
                }
                $value['list'][$k] = $v;
            }
            $match_data[$key] = $value;
        }

        $time = date('Y-m-d H:i:s');
        $res = $mMatch->insert([
            'tag_id' => $tag_id,
            'match_play' => $match_play,
            'match_score' => $match_score,
            'match_result' => $match_result,
            'match_half_audience' => $match_half_audience,
            'match_type' => $match_type,
            'match_data' => json_encode($match_data),
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
     * @name 修改比赛
     * @Post("/lv/match/match/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Tag $mTag)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $pid = $params['pid'] ?? 0;
        $name = $params['name'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($pid)) {
            return $this->jsonAdminResult([],10001, '请选择标签大类');
        }

        if (!in_array($pid, array_column(config('global.tag_list'), 'value'))) {
            return $this->jsonAdminResult([],10001, '标签大类错误');
        }

        if (empty($name)){
            return $this->jsonAdminResult([],10001, '子类名称不能为空');
        }

        $info = $mTag->where('id', '!=', $id)->where('pid', $pid)->where('name', $name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '子类名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mTag->where('id', $id)->update([
            'pid' => $pid,
            'name' => $name,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除比赛
     * @Post("/lv/match/match/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Tag $mTag)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mTag->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
