<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class House extends Model
{
    use FormatTrait;
    public $table = 'houses';

    /**
     * 格式化表格数据
     * @param array $exportData
     * @return array|mixed
     */
    public function formatTableData($exportData = []) {
        // 配置信息
        $increase_type_list = config('global.increase_type_list');
        $increase_type_list = array_column($increase_type_list, 'label', 'value');
        $year_name_list = config('global.year_name_list');
        $year_name_list = array_column($year_name_list, 'label', 'value');
        $pay_method_list = config('global.pay_method_list');
        $pay_method_list = array_column($pay_method_list, 'label', 'value');

        $exportData['increase_type_name'] = $increase_type_list[$exportData['increase_type']] ?? '';
        $exportData['property_increase_type_name'] = $increase_type_list[$exportData['property_increase_type']] ?? '';
        $exportData['pay_method_name'] = $pay_method_list[$exportData['pay_method']] ?? '';
        $exportData['property_pay_method_name'] = $pay_method_list[$exportData['property_pay_method']] ?? '';

        // 租金
        $detail_list = [];
        if ($exportData['increase_type'] == 1) { // 递增
            $increase_content = json_decode($exportData['increase_content'], true);
            $year_price = $exportData['unit_price'] * $exportData['lease_area'] * 365;
            foreach ($increase_content as $key => $value) {
                $index = $key + 1;
                $detail_list[] = [
                    'year' => $year_name_list[$index] ?? "第{$index}年",
                    'area' => $exportData['lease_area'],
                    'price' => sprintf("%.2f", $year_price * (1 + 0.01 * $value['percent']) / $exportData['lease_area'] / 365),
                    'month_price' => sprintf("%.2f", $year_price * (1 + 0.01 * $value['percent']) / $exportData['lease_area'] / 12),
                    'year_price' => sprintf("%.2f", $year_price * (1 + 0.01 * $value['percent'])),
                    'increase' => "{$value['percent']}%",
                    'pay_method' => $pay_method_list[$exportData['pay_method']] ?? '',
                ];
                $year_price = $year_price * (1 + 0.01 * $value['percent']);
            }
        } else { // 自定义
            $increase_content = json_decode($exportData['increase_content'], true);
            foreach ($increase_content as $key => $value) {
                $index = $key + 1;
                $detail_list[] = [
                    'year' => $year_name_list[$index] ?? "第{$index}年",
                    'area' => $exportData['lease_area'],
                    'price' => sprintf("%.2f", $value['unit_price']),
                    'month_price' => sprintf("%.2f", $value['unit_price'] * 365 / 12),
                    'year_price' => sprintf("%.2f", $value['unit_price'] * $exportData['lease_area'] * 365),
                    'increase' => sprintf("%.2f", $value['unit_price']),
                    'pay_method' => $pay_method_list[$exportData['pay_method']] ?? ''
                ];
            }
        }
        if (!empty($detail_list)) {
            $total_price = 0;
            foreach ($detail_list as $value) {
                $total_price += $value['year_price'];
            }
            $detail_list[] = [
                'year' => 'total',
                'area' => $exportData['lease_area'],
                'price' => sprintf("%.2f", $total_price / $exportData['lease_year'] / $exportData['lease_area'] / 365),
                'month_price' => sprintf("%.2f", $total_price / $exportData['lease_year'] / $exportData['lease_area'] / 12),
                'year_price' => $total_price,
                'increase' => '',
                'pay_method' => ''
            ];
        }
        $exportData['detail_list'] = $detail_list;

        // 物业费
        $property_detail_list = [];
        if ($exportData['property_increase_type'] == 1) { // 递增
            $property_increase_content = json_decode($exportData['property_increase_content'], true);
            $year_price = $exportData['property_unit_price'] * $exportData['lease_area'] * 12;
            foreach ($property_increase_content as $key => $value) {
                $index = $key + 1;
                $property_detail_list[] = [
                    'year' => $year_name_list[$index] ?? "第{$index}年",
                    'area' => $exportData['lease_area'],
                    'price' => sprintf("%.2f", $year_price * (1 + 0.01 * $value['percent']) / $exportData['lease_area'] / 12),
                    'year_price' => sprintf("%.2f", $year_price * (1 + 0.01 * $value['percent'])),
                    'increase' => "{$value['percent']}%",
                    'pay_method' => $pay_method_list[$exportData['property_pay_method']] ?? '',
                ];
                $year_price = $year_price * (1 + 0.01 * $value['percent']);
            }
        } else { // 自定义
            $property_increase_content = json_decode($exportData['property_increase_content'], true);
            foreach ($property_increase_content as $key => $value) {
                $index = $key + 1;
                $property_detail_list[] = [
                    'year' => $year_name_list[$index] ?? "第{$index}年",
                    'area' => $exportData['lease_area'],
                    'price' => sprintf("%.2f", $value['unit_price']),
                    'year_price' => sprintf("%.2f", $value['unit_price'] * $exportData['lease_area'] * 12),
                    'increase' => sprintf("%.2f", $value['unit_price']),
                    'pay_method' => $pay_method_list[$exportData['property_pay_method']] ?? '',
                ];
            }
        }
        if (!empty($property_detail_list)) {
            $total_price = 0;
            foreach ($property_detail_list as $value) {
                $total_price += $value['year_price'];
            }
            $property_detail_list[] = [
                'year' => 'total',
                'area' => $exportData['lease_area'],
                'price' => sprintf("%.2f", $total_price / $exportData['lease_year'] / $exportData['lease_area'] / 12),
                'year_price' => $total_price,
                'increase' => '',
                'pay_method' => ''
            ];
        }
        $exportData['property_detail_list'] = $property_detail_list;

        return $exportData;
    }
}
