<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use DB;

class ActivityLogController extends Controller
{
    public function activity_logs()
    {
        $activity_logs = DB::table('activity_log')
                           ->join('users', 'activity_log.causer_id', '=', 'users.id')
                           ->select('activity_log.log_name', 'activity_log.description', 'activity_log.properties',
                                    DB::raw("DATE_FORMAT(activity_log.created_at, '%m/%d/%Y') as log_date"),
                                    'users.name', 'users.email', 'activity_log.subject_id')
                           ->orderBy('activity_log.id', 'DESC')
                           ->get();

        return response()->json(['activity_logs' => $activity_logs], 200);
    }
}
