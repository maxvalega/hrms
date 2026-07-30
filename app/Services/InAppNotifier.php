<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class InAppNotifier
{
    public static function enabled(): bool
    {
        return Schema::hasTable('in_app_notifications');
    }

    /**
     * @param  int|string  $userId
     * @param  array{
     *   module:string,
     *   action:string,
     *   title:string,
     *   message?:string|null,
     *   link?:string|null,
     *   data?:array|null,
     *   actor_id?:int|null,
     *   created_by?:int|null
     * }  $payload
     */
    public static function notifyUser($userId, array $payload): ?InAppNotification
    {
        if (!self::enabled() || empty($userId)) {
            return null;
        }

        $userId = (int) $userId;
        if ($userId < 1) {
            return null;
        }

        // Don't notify yourself for your own action
        $actorId = $payload['actor_id'] ?? (Auth::check() ? Auth::id() : null);
        if ($actorId && (int) $actorId === $userId) {
            return null;
        }

        $createdBy = $payload['created_by']
            ?? (Auth::check() ? Auth::user()->creatorId() : 0);

        return InAppNotification::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'module' => $payload['module'] ?? 'general',
            'action' => $payload['action'] ?? 'info',
            'title' => $payload['title'] ?? __('Notification'),
            'message' => $payload['message'] ?? null,
            'link' => $payload['link'] ?? null,
            'data' => $payload['data'] ?? null,
            'is_read' => false,
            'created_by' => (int) $createdBy,
        ]);
    }

    public static function notifyUsers(array $userIds, array $payload): void
    {
        $unique = array_unique(array_filter(array_map('intval', $userIds)));
        foreach ($unique as $userId) {
            self::notifyUser($userId, $payload);
        }
    }

    /**
     * Notify company + HR users for a tenant.
     */
    public static function notifyCompanyHr($creatorId, array $payload): void
    {
        if (!self::enabled() || empty($creatorId)) {
            return;
        }

        $ids = User::where(function ($q) use ($creatorId) {
                $q->where(function ($q2) use ($creatorId) {
                    $q2->where('type', 'company')->where('id', $creatorId);
                })->orWhere(function ($q2) use ($creatorId) {
                    $q2->where('type', 'hr')->where('created_by', $creatorId);
                });
            })
            ->pluck('id')
            ->all();

        self::notifyUsers($ids, $payload);
    }

    /**
     * Notify all employee user accounts under a tenant.
     */
    public static function notifyCompanyEmployees($creatorId, array $payload): void
    {
        if (!self::enabled() || empty($creatorId)) {
            return;
        }

        $ids = User::where('created_by', $creatorId)
            ->where('type', 'employee')
            ->pluck('id')
            ->all();

        self::notifyUsers($ids, $payload);
    }

    public static function markRead(int $userId, int $id): bool
    {
        if (!self::enabled()) {
            return false;
        }

        return InAppNotification::where('user_id', $userId)
            ->where('id', $id)
            ->update(['is_read' => true]) > 0;
    }

    public static function markAllRead(int $userId): int
    {
        if (!self::enabled()) {
            return 0;
        }

        return InAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public static function unreadForUser(int $userId, int $limit = 20)
    {
        if (!self::enabled()) {
            return collect();
        }

        return InAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public static function unreadCount(int $userId): int
    {
        if (!self::enabled()) {
            return 0;
        }

        return InAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}
