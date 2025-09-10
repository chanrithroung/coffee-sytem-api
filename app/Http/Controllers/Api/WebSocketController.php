<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebSocketController extends Controller
{
    /**
     * Send test notification via WebSocket
     */
    public function sendTestNotification(): JsonResponse
    {
        $user = Auth::user();
        
        $notification = Notification::create([
            'type' => 'order',
            'title' => 'New Order Received',
            'message' => 'Order #ORD-' . time() . ' has been placed by customer',
            'data' => [
                'order_id' => 'ORD-' . time(),
                'customer_name' => 'Test Customer',
                'amount' => 25.50
            ],
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

    /**
     * Send order notification
     */
    public function sendOrderNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_number' => 'required|string',
            'customer_name' => 'nullable|string',
            'amount' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        $notification = Notification::create([
            'type' => 'order',
            'title' => 'New Order Received',
            'message' => "Order {$request->order_number} has been placed" . 
                        ($request->customer_name ? " by {$request->customer_name}" : ''),
            'data' => [
                'order_number' => $request->order_number,
                'customer_name' => $request->customer_name,
                'amount' => $request->amount
            ],
            'user_id' => $user->id,
            'priority' => 'medium',
        ]);

        // Broadcast the notification event
        broadcast(new NotificationSent($notification));

        return response()->json([
            'status' => 'success',
            'message' => 'Order notification sent successfully',
            'data' => $notification
        ]);
    }
}

