<?php

namespace App\Http\Controllers\Admin\Match;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Match;
use App\Model\Admin\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

//        $tag_sub_list = $mTag->get(['pid', 'id', 'name']);
//        $tag_sub_list = $this->dbResult($tag_sub_list);
        $tag_sub_list = DB::select('SELECT pid, id, name FROM `tags` WHERE 1 order by convert(name using gbk) asc');

        $where = [];

        if (!empty($params['pid'])) {
            $where[] = ['ptag_id', '=', $params['pid']];
        }

        // 标签大类id
        if (!empty($params['ids'])){
            $tag_id = $params['ids'];
            sort($tag_id);
            $before_tag_id = [];
            $after_tag_id = [];
            foreach ($tag_id as $value) {
                $value_arr = json_decode($value, true);
                if ($value_arr[0] == 1) {
                    $before_tag_id[] = $value;
                } else {
                    $after_tag_id[] = $value;
                }
            }

//            $where[] = [function ($query) use ($tag_id, $before_tag_id, $after_tag_id) {
//                $query = $query->where('tag_id', '=', '[' . implode(',', $tag_id) . ']');
//                foreach ($after_tag_id as $value) {
//                    $query->orWhere(function ($query) use ($before_tag_id, $value) {
//                        $query->where('tag_id', 'like', '[' . implode(',', $before_tag_id) . ',[2,%')->where('tag_id', 'like', '%' . $value . '%');
//                    });
//                }
//            }];

            $where[] = [function ($query) use ($tag_id, $before_tag_id, $after_tag_id) {
                $query = $query->where('tag_id', '=', '[' . implode(',', $tag_id) . ']');
                foreach ($before_tag_id as $before_value) {
                    foreach ($after_tag_id as $after_value) {
                        $query->orWhere(function ($query) use ($before_value, $after_value) {
                            $query->where('tag_id', 'like', '%' . $before_value . '%')->where('tag_id', 'like', '%' . $after_value . '%');
                        });
                    }
                }
            }];
        }

        $total = $mMatch->where($where)->count();

        if (!empty($params['id'])) {
            $where[] = ['id', '<', $params['id']];
        }

        $orderField = 'id';
        $sort = 'desc';
        $list = $mMatch->where($where)
            ->orderBy('is_top', 'desc')
            ->orderBy($orderField, $sort)
            ->limit(config('global.page_size'))
            ->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $platform_arr = array_column(config('global.platform_list'), 'label', 'value');
            foreach ($list as $k => $v){
                $list[$k]['tag_id'] = json_decode($v['tag_id'], true) ?? [];
                $match_data = json_decode($v['match_data'], true) ?? [];
                foreach ($match_data as $match_key => $match_value) {
                    if (empty($match_value['name'])) {
                        $match_data[$match_key]['name'] = $platform_arr[$match_value['value']] ?? '';
                    }
                }
                $list[$k]['match_data'] = $match_data;
            }
        }

        return $this->jsonAdminResult([
            'total' => $total,
            'data' => $list,
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

        $ptag_id = $params['ptag_id'] ?? 0;
        $tag_id = $params['tag_id'] ?? [];
        $is_top = $params['is_top'] ?? 0;
        $match_play = trim($params['match_play'] ?? '');
        $match_score = trim($params['match_score'] ?? '');
        $match_result = trim($params['match_result'] ?? '');
        $match_half_audience = trim($params['match_half_audience'] ?? '');
        $match_type = trim($params['match_type'] ?? '');
        $match_data = $params['match_data'] ?? [];

        if (empty($tag_id)) {
            return $this->jsonAdminResult([],10001, '请选择标签');
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
        sort($tag_id);
        $res = $mMatch->insert([
            'ptag_id' => $ptag_id,
            'tag_id' => json_encode($tag_id),
            'is_top' => $is_top,
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
    public function edit(Request $request, Match $mMatch)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $ptag_id = $params['ptag_id'] ?? 0;
        $tag_id = $params['tag_id'] ?? [];
        $is_top = $params['is_top'] ?? 0;
        $match_play = trim($params['match_play'] ?? '');
        $match_score = trim($params['match_score'] ?? '');
        $match_result = trim($params['match_result'] ?? '');
        $match_half_audience = trim($params['match_half_audience'] ?? '');
        $match_type = trim($params['match_type'] ?? '');
        $match_data = $params['match_data'] ?? [];

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($tag_id)) {
            return $this->jsonAdminResult([],10001, '请选择标签');
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
        sort($tag_id);
        $res = $mMatch->where('id', $id)->update([
            'ptag_id' => $ptag_id,
            'tag_id' => json_encode($tag_id),
            'is_top' => $is_top,
            'match_play' => $match_play,
            'match_score' => $match_score,
            'match_result' => $match_result,
            'match_half_audience' => $match_half_audience,
            'match_type' => $match_type,
            'match_data' => json_encode($match_data),
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
    public function del(Request $request, Match $mMatch)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mMatch->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 保存比赛标签
     * @Post("/lv/match/match/saveTogether")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function saveTogether(Request $request)
    {
        $params = $request->all();

        $together_name = $params['together_name'] ?? '';
        $match_ids = $params['match_ids'] ?? [];

        if (empty($together_name)) {
            return $this->jsonAdminResult([],10001,'标签名不能为空');
        }

        if (empty($match_ids)) {
            return $this->jsonAdminResult([],10001,'比赛不能为空');
        }

        $count = DB::table('togethers')->where('name', $together_name)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'标签名已存在');
        }

        $time = date('Y-m-d H:i:s');
        $res = DB::table('togethers')->insert([
            'name' => $together_name,
            'match_ids' => json_encode($match_ids),
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
     * @name 统计
     * @Get("/lv/match/match/stat")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function stat(Request $request, Match $mMatch, Tag $mTag)
    {
        $params = $request->all();

        $tag_sub_list = DB::select('SELECT pid, id, name FROM `tags` WHERE pid = 1 order by convert(name using gbk) asc');
        $tag_sub_list = $this->dbResult($tag_sub_list);

        $match_list = $mMatch->get(['tag_id', 'created_at']);
        $match_list = $this->dbResult($match_list);

        $match_stat = [];
        $total_num = count($match_list);
        $today_num = 0;
        $today_date = date('Y-m-d 00:00:00');
        foreach ($match_list as $value) {
            if ($value['created_at'] >= $today_date) { // 今日
                $today_num++;
            }
            $tag_id = json_decode($value['tag_id'], true);
            foreach ($tag_id as $tags) {
                if (is_array($tags)) {
                    if (!isset($match_stat[$tags[1]])) {
                        $match_stat[$tags[1]] = 0;
                    }
                    $match_stat[$tags[1]]++;
                }
            }
        }
        $stat_list = [];
        $stat_list[] = '比赛总数：' . $total_num;
        $stat_list[] = '今日入场：' . $today_num;
        foreach ($tag_sub_list as $value) {
            $stat_list[] = $value['name'] . '：' . ($match_stat[$value['id']] ?? 0);
        }

        // 标签名列表
        $together_list = DB::table('togethers')->get(['id', 'name', 'match_ids']);
        $together_list = $this->dbResult($together_list);
        foreach ($together_list as $key => $value) {
            $together_list[$key]['match_ids'] = json_decode($value['match_ids'], true);
        }

        return $this->jsonAdminResult([
            'stat_list' => $stat_list,
            'together_list' => $together_list
        ]);
    }

    /**
     * @name 标签比赛列表
     * @Get("/lv/match/match/getTogether")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function getTogether(Request $request, Match $mMatch, Tag $mTag)
    {
        $params = $request->all();

        $orderField = 'id';
        $sort = 'desc';
        $list = $mMatch->whereIn('id', $params['match_ids'])
            ->orderBy('is_top', 'desc')
            ->orderBy($orderField, $sort)
            ->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $platform_arr = array_column(config('global.platform_list'), 'label', 'value');
            foreach ($list as $k => $v){
                $list[$k]['tag_id'] = json_decode($v['tag_id'], true) ?? [];
                $match_data = json_decode($v['match_data'], true) ?? [];
                foreach ($match_data as $match_key => $match_value) {
                    if (empty($match_value['name'])) {
                        $match_data[$match_key]['name'] = $platform_arr[$match_value['value']] ?? '';
                    }
                }
                $list[$k]['match_data'] = $match_data;
            }
        }

        $tag_sub_list = DB::select('SELECT pid, id, name FROM `tags` WHERE 1 order by convert(name using gbk) asc');
        return $this->jsonAdminResult([
            'data' => $list,
            'tag_sub_list' => $tag_sub_list
        ]);
    }

    /**
     * @name 修改标签比赛
     * @Post("/lv/match/match/editTogether")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function editTogether(Request $request)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $name = $params['name'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($name)){
            return $this->jsonAdminResult([],10001, '标签名不能为空');
        }

        $info = DB::table('togethers')->where('id', '!=', $id)->where('name', $name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '标签名已存在');
        }

        $time = date('Y-m-d H:i:s');
        $res = DB::table('togethers')->where('id', $id)->update([
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
     * @name 删除标签比赛
     * @Post("/lv/match/match/delTogether")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function delTogether(Request $request)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = DB::table('togethers')->where('id', $id)->delete();

        if ($res !== false) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
