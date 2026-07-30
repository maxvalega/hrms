<?php

namespace App\Http\Controllers;

use App\Services\InAppNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InAppNotificationController extends Controller
{
    public function read($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notification = \App\Models\InAppNotification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($notification) {
            InAppNotifier::markRead((int) $user->id, (int) $id);
            if (!empty($notification->link)) {
                return redirect()->to($notification->link);
            }
        }

        return redirect()->back();
    }

    public function readAll(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        InAppNotifier::markAllRead((int) $user->id);

        if ($request->expectsJson()) {
            return response()->json(['is_success' => true]);
        }

        return redirect()->back()->with('success', __('All notifications marked as read.'));
    }
}
