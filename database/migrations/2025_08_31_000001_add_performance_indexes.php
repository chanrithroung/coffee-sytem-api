<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for performance optimization
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Indexes for common query patterns
            $table->index(['is_active'], 'categories_is_active_index');
            $table->index(['sort_order'], 'categories_sort_order_index');
            $table->index(['is_active', 'sort_order'], 'categories_active_sort_index');
            
            // Full-text search index if using MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['name', 'description'], 'categories_search_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            // Indexes for common query patterns
            $table->index(['is_active'], 'products_is_active_index');
            $table->index(['category_id'], 'products_category_index');
            $table->index(['is_active', 'category_id'], 'products_active_category_index');
            $table->index(['stock_quantity'], 'products_stock_index');
            $table->index(['price'], 'products_price_index');
            $table->index(['created_at'], 'products_created_at_index');
            
            // Composite indexes for filtering
            $table->index(['is_active', 'stock_quantity'], 'products_active_stock_index');
            $table->index(['category_id', 'is_active'], 'products_category_active_index');
            $table->index(['price', 'is_active'], 'products_price_active_index');
            
            // Low stock threshold comparison index
            $table->index(['stock_quantity', 'low_stock_threshold'], 'products_low_stock_index');
            
            // Full-text search index if using MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['name', 'description', 'sku'], 'products_search_index');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Indexes for order queries
            $table->index(['status'], 'orders_status_index');
            $table->index(['user_id'], 'orders_user_index');
            $table->index(['table_id'], 'orders_table_index');
            $table->index(['created_at'], 'orders_created_at_index');
            $table->index(['updated_at'], 'orders_updated_at_index');
            
            // Composite indexes
            $table->index(['status', 'created_at'], 'orders_status_created_index');
            $table->index(['user_id', 'status'], 'orders_user_status_index');
            $table->index(['table_id', 'status'], 'orders_table_status_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Indexes for order item queries
            $table->index(['order_id'], 'order_items_order_index');
            $table->index(['product_id'], 'order_items_product_index');
            $table->index(['created_at'], 'order_items_created_at_index');
            
            // Composite indexes for analytics
            $table->index(['product_id', 'created_at'], 'order_items_product_date_index');
            $table->index(['order_id', 'product_id'], 'order_items_order_product_index');
        });

        Schema::table('tables', function (Blueprint $table) {
            // Indexes for table queries
            $table->index(['status'], 'tables_status_index');
            $table->index(['is_active'], 'tables_active_index');
            $table->index(['capacity'], 'tables_capacity_index');
            
            // Composite indexes
            $table->index(['status', 'is_active'], 'tables_status_active_index');
        });

        Schema::table('users', function (Blueprint $table) {
            // Indexes for user queries
            $table->index(['role'], 'users_role_index');
            $table->index(['is_active'], 'users_active_index');
            $table->index(['created_at'], 'users_created_at_index');
            
            // Composite indexes
            $table->index(['role', 'is_active'], 'users_role_active_index');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_is_active_index');
            $table->dropIndex('categories_sort_order_index');
            $table->dropIndex('categories_active_sort_index');
            
            if (config('database.default') === 'mysql') {
                $table->dropFullText('categories_search_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_category_index');
            $table->dropIndex('products_active_category_index');
            $table->dropIndex('products_stock_index');
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_active_stock_index');
            $table->dropIndex('products_category_active_index');
            $table->dropIndex('products_price_active_index');
            $table->dropIndex('products_low_stock_index');
            
            if (config('database.default') === 'mysql') {
                $table->dropFullText('products_search_index');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_user_index');
            $table->dropIndex('orders_table_index');
            $table->dropIndex('orders_created_at_index');
            $table->dropIndex('orders_updated_at_index');
            $table->dropIndex('orders_status_created_index');
            $table->dropIndex('orders_user_status_index');
            $table->dropIndex('orders_table_status_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_index');
            $table->dropIndex('order_items_product_index');
            $table->dropIndex('order_items_created_at_index');
            $table->dropIndex('order_items_product_date_index');
            $table->dropIndex('order_items_order_product_index');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex('tables_status_index');
            $table->dropIndex('tables_active_index');
            $table->dropIndex('tables_capacity_index');
            $table->dropIndex('tables_status_active_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_active_index');
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_role_active_index');
        });
    }
};