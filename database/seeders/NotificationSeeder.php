<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $adminUser = User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->warn('No admin user found. Skipping notification seeding.');
            return;
        }

        // Sample notifications
        $notifications = [
            [
                'type' => 'system',
                'title' => 'System Started',
                'message' => 'Coffee Shop Management System has been started successfully.',
                'data' => ['component' => 'system', 'action' => 'startup'],
                'user_id' => null, // System notification
                'priority' => 'low',
                'created_at' => now()->subHours(2),
            ],
            [
                'type' => 'order',
                'title' => 'New Order Received',
                'message' => 'Order #ORD-001 has been placed by customer.',
                'data' => ['order_id' => 'ORD-001', 'customer_name' => 'John Doe'],
                'user_id' => $adminUser->id,
                'priority' => 'medium',
                'created_at' => now()->subHour(),
            ],
            [
                'type' => 'stock',
                'title' => 'Low Stock Alert',
                'message' => 'Americano Coffee is running low on stock (5 units remaining).',
                'data' => ['product_name' => 'Americano Coffee', 'current_stock' => 5, 'threshold' => 10],
                'user_id' => $adminUser->id,
                'priority' => 'high',
                'created_at' => now()->subMinutes(30),
            ],
            [
                'type' => 'payment',
                'title' => 'Payment Received',
                'message' => 'Payment of $25.50 received for Order #ORD-001.',
                'data' => ['order_id' => 'ORD-001', 'amount' => 25.50, 'payment_method' => 'cash'],
                'user_id' => $adminUser->id,
                'priority' => 'medium',
                'created_at' => now()->subMinutes(15),
            ],
            [
                'type' => 'table',
                'title' => 'Table Status Update',
                'message' => 'Table 3 has been marked as occupied.',
                'data' => ['table_number' => 3, 'status' => 'occupied'],
                'user_id' => $adminUser->id,
                'priority' => 'low',
                'created_at' => now()->subMinutes(10),
            ],
            [
                'type' => 'success',
                'title' => 'Backup Completed',
                'message' => 'Daily database backup has been completed successfully.',
                'data' => ['backup_type' => 'daily', 'size' => '15.2 MB'],
                'user_id' => null, // System notification
                'priority' => 'low',
                'created_at' => now()->subMinutes(5),
            ],
        ];

        foreach ($notifications as $notificationData) {
            Notification::create($notificationData);
        }

        $this->command->info('Notifications seeded successfully!');
    }
}
