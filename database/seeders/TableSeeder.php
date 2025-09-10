<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Table;
use Illuminate\Support\Str;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            // Main dining area
            ['table_number' => 'T01', 'name' => 'Window Table 1', 'capacity' => 2, 'location' => 'Main Floor', 'position_x' => 10.0, 'position_y' => 10.0],
            ['table_number' => 'T02', 'name' => 'Window Table 2', 'capacity' => 2, 'location' => 'Main Floor', 'position_x' => 10.0, 'position_y' => 15.0],
            ['table_number' => 'T03', 'name' => 'Corner Table', 'capacity' => 4, 'location' => 'Main Floor', 'position_x' => 15.0, 'position_y' => 20.0],
            ['table_number' => 'T04', 'name' => 'Center Table 1', 'capacity' => 4, 'location' => 'Main Floor', 'position_x' => 20.0, 'position_y' => 15.0],
            ['table_number' => 'T05', 'name' => 'Center Table 2', 'capacity' => 4, 'location' => 'Main Floor', 'position_x' => 20.0, 'position_y' => 10.0],
            ['table_number' => 'T06', 'name' => 'Wall Table 1', 'capacity' => 2, 'location' => 'Main Floor', 'position_x' => 25.0, 'position_y' => 5.0],
            ['table_number' => 'T07', 'name' => 'Wall Table 2', 'capacity' => 2, 'location' => 'Main Floor', 'position_x' => 30.0, 'position_y' => 5.0],
            ['table_number' => 'T08', 'name' => 'Large Table', 'capacity' => 6, 'location' => 'Main Floor', 'position_x' => 35.0, 'position_y' => 15.0],
            
            // Outdoor patio
            ['table_number' => 'P01', 'name' => 'Patio Table 1', 'capacity' => 4, 'location' => 'Patio', 'position_x' => 5.0, 'position_y' => 30.0],
            ['table_number' => 'P02', 'name' => 'Patio Table 2', 'capacity' => 4, 'location' => 'Patio', 'position_x' => 10.0, 'position_y' => 30.0],
            ['table_number' => 'P03', 'name' => 'Patio Table 3', 'capacity' => 2, 'location' => 'Patio', 'position_x' => 15.0, 'position_y' => 30.0],
            ['table_number' => 'P04', 'name' => 'Patio Corner', 'capacity' => 2, 'location' => 'Patio', 'position_x' => 20.0, 'position_y' => 35.0],
            
            // Private area
            ['table_number' => 'PR1', 'name' => 'Private Room Table', 'capacity' => 8, 'location' => 'Private Room', 'position_x' => 50.0, 'position_y' => 20.0],
            
            // Bar area
            ['table_number' => 'B01', 'name' => 'Bar Stool 1-2', 'capacity' => 2, 'location' => 'Bar', 'position_x' => 5.0, 'position_y' => 5.0],
            ['table_number' => 'B02', 'name' => 'Bar Stool 3-4', 'capacity' => 2, 'location' => 'Bar', 'position_x' => 7.0, 'position_y' => 5.0],
            ['table_number' => 'B03', 'name' => 'Bar Stool 5-6', 'capacity' => 2, 'location' => 'Bar', 'position_x' => 9.0, 'position_y' => 5.0],
            
            // High-top tables
            ['table_number' => 'H01', 'name' => 'High-top 1', 'capacity' => 3, 'location' => 'Main Floor', 'position_x' => 40.0, 'position_y' => 10.0],
            ['table_number' => 'H02', 'name' => 'High-top 2', 'capacity' => 3, 'location' => 'Main Floor', 'position_x' => 40.0, 'position_y' => 20.0],
        ];

        foreach ($tables as $tableData) {
            Table::create([
                'table_number' => $tableData['table_number'],
                'name' => $tableData['name'],
                'capacity' => $tableData['capacity'],
                'status' => 'available',
                'location' => $tableData['location'],
                'position_x' => $tableData['position_x'],
                'position_y' => $tableData['position_y'],
                'qr_code' => 'QR-' . $tableData['table_number'] . '-' . strtoupper(Str::random(6)),
                'is_active' => true,
                'metadata' => [
                    'has_power_outlet' => rand(0, 1) === 1,
                    'near_window' => str_contains($tableData['name'], 'Window'),
                    'outdoor' => $tableData['location'] === 'Patio',
                    'accessible' => rand(0, 1) === 1,
                ]
            ]);
        }
    }
}
