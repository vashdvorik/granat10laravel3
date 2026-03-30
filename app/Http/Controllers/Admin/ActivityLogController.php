<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('causer')) {
            $query->where('causer_id', $request->causer);
        }

        $logs = $query->paginate(30)->appends($request->all());

        $logNames = Activity::distinct()->pluck('log_name')->sort()->values();

        return view('admin.activity-log.index', compact('logs', 'logNames'));
    }
}
