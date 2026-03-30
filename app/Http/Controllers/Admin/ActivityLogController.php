<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
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
            $search = $request->search;

            // Ищем пользователей, у которых есть магазин с таким названием
            $userIdsFromSellers = Seller::where('name', 'like', '%' . $search . '%')
                ->pluck('user_id');

            $query->where(function ($q) use ($search, $userIdsFromSellers) {
                $q->where('description', 'like', '%' . $search . '%')
                  ->orWhereIn('causer_id', $userIdsFromSellers);
            });
        }

        if ($request->filled('causer')) {
            $query->where('causer_id', $request->causer);
        }

        $logs = $query->paginate(30)->appends($request->all());

        $logNames = Activity::distinct()->pluck('log_name')->sort()->values();

        return view('admin.activity-log.index', compact('logs', 'logNames'));
    }
}
