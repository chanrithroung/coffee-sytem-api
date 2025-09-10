<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Get all system settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get all settings from the settings table
            $settings = DB::table('settings')->get();

            // Convert to associative array
            $settingsArray = [];
            foreach ($settings as $setting) {
                // Try to decode JSON values, fallback to original value
                $value = json_decode($setting->value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $value = $setting->value;
                }
                $settingsArray[$setting->key] = $value;
            }

            return response()->json([
                'status' => 'success',
                'data' => $settingsArray
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch system settings: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch system settings'
            ], 500);
        }
    }

    /**
     * Update system settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $settings = $request->all();

            // Update each setting in the database
            foreach ($settings as $key => $value) {
                // Convert value to JSON string for storage
                $jsonValue = json_encode($value);

                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $jsonValue, 'updated_at' => now()]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update system settings: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update system settings'
            ], 500);
        }
    }
}
