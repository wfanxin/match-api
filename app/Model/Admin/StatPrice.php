<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class StatPrice extends Model
{
    use FormatTrait;
    public $table = 'stat_prices';
}
