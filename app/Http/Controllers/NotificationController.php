<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = DB::table('notifications as n')
            ->select('n.id', 'c.name', 'n.title', 'n.description', 'n.assign_by', 'n.flag', 'c.id as company_id', 'u.name as assign_by_name')
            ->leftJoin('companies as c', 'c.id', '=', 'n.company_id')
            ->leftJoin('users as u', 'n.assign_by', '=', 'u.id')
            ->where('n.user_id', '=', Auth::id());

        if ($request->perPage == null) {
            $notifications = $notifications->paginate(10);
        } else {
            $notifications = $notifications->paginate($request->perPage);
        }
        return Inertia::render('Notification/Index', [
            'notifications' => $notifications
        ]);
    }

    public function getNotifications()
    {
        $notifications = DB::table('notifications as n')
            ->select('n.id', 'c.name', 'n.title', 'n.description', 'n.assign_by', 'n.flag', 'c.id as company_id', 'u.name as assign_by_name')
            ->leftJoin('companies as c', 'c.id', '=', 'n.company_id')
            ->leftJoin('users as u', 'n.assign_by', '=', 'u.id')
            ->where('n.user_id', '=', Auth::id())
            ->where('n.flag', '=', 0)
            ->orderBy('n.id', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function getNotificationCount()
    {
        $notificationsCount = Notification::where('user_id', Auth::id())->where('flag', 0)->count();

        return response()->json($notificationsCount);
    }

    public function updateNotification(Request $request)
    {

        // validations
        $request->validate([
            'notifications' => 'required|array',
        ]);

        if (isset($request->notifications) && !empty($request->notifications)) {

            $updatedNotifications = [];

            foreach ($request->notifications as $key => $val) {
                Notification::where('id', $val)->update(['flag' => 1, 'updated_at' => now()]);

                $updatedNotification = Notification::find($val);

                // Add the updated notification to the array
                array_push($updatedNotifications, $updatedNotification);
            }

            return response()->json(['notifications' => $updatedNotifications]);
        }
    }
}
