<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 20);

        $notifications = Notification::where('user_id', $user->id)
            ->orWhereNull('user_id') // System notifications
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Store a new notification
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:order,stock,system,payment,table,user,error,success,warning,info',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'user_id' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
            'priority' => 'nullable|string|in:low,medium,high',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $notification = Notification::create([
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'data' => $request->data,
            'user_id' => $request->user_id ?? Auth::id(),
            'related_type' => $request->related_type,
            'related_id' => $request->related_id,
            'priority' => $request->priority ?? 'medium',
            'expires_at' => $request->expires_at,
        ]);

        // Broadcast the notification event
        broadcast(new NotificationSent($notification));

        return response()->json([
            'status' => 'success',
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $user = Auth::user();

        // Check if user can access this notification
        if ($notification->user_id && $notification->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->update(['read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::id();

        Notification::where('user_id', $user)
            ->orWhereNull('user_id')
            ->update(['read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $user = Auth::user();

        // Check if user can delete this notification
        if ($notification->user_id && $notification->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings(): JsonResponse
    {
        $user = Auth::user();

        // Get user's notification settings or create default ones
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => true,
                'show_desktop' => true,
                'show_in_app' => true,
                'sound' => true,
                'types' => [
                    'orders' => true,
                    'stock' => true,
                    'system' => true,
                    'errors' => true,
                    'payments' => true,
                    'tables' => true,
                    'users' => true,
                ]
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => $settings->enabled,
                'show_desktop' => $settings->show_desktop,
                'show_in_app' => $settings->show_in_app,
                'sound' => $settings->sound,
                'types' => $settings->types
            ]
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'show_desktop' => 'boolean',
            'show_in_app' => 'boolean',
            'sound' => 'boolean',
            'types' => 'array',
            'types.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Update or create user's notification settings
        $settings = NotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => $request->input('enabled', true),
                'show_desktop' => $request->input('show_desktop', true),
                'show_in_app' => $request->input('show_in_app', true),
                'sound' => $request->input('sound', true),
                'types' => $request->input('types', [
                    'orders' => true,
                    'stock' => true,
                    'system' => true,
                    'errors' => true,
                    'payments' => true,
                    'tables' => true,
                    'users' => true,
                ])
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'data' => [
                'enabled' => $settings->enabled,
                'show_desktop' => $settings->show_desktop,
                'show_in_app' => $settings->show_in_app,
                'sound' => $settings->sound,
                'types' => $settings->types
            ]
        ]);
    }

    /**
     * Create system notification (for admin use)
     */
    public function createSystemNotification(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:order,stock,system,payment,table,user,error,success,warning,info',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'priority' => 'nullable|string|in:low,medium,high',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $notification = Notification::create([
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'data' => $request->data,
            'user_id' => null, // System notification
            'priority' => $request->priority ?? 'medium',
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'System notification created successfully',
            'data' => $notification
        ], 201);
    }

    /**
     * Test notification endpoint (for development)
     */
    public function testNotification(): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::create([
            'type' => 'order',
            'title' => 'Test Order Notification',
            'message' => 'This is a test notification for real-time functionality',
            'data' => ['test' => true, 'timestamp' => now()],
            'user_id' => $user->id,
            'priority' => 'medium',
        ]);

        // Broadcast the notification event
        broadcast(new NotificationSent($notification));

        return response()->json([
            'status' => 'success',
            'message' => 'Test notification sent successfully',
            'data' => $notification
        ]);
    }
}
