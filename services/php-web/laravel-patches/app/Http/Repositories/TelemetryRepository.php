<?php

namespace App\Http\Repositories;

use Illuminate\Support\Facades\DB;

class TelemetryRepository
{
    public function getCsvData(): array
    {
        try {
            $records = DB::connection('db_csv')
                ->table('telemetry_legacy')
                ->orderBy('recorded_at', 'desc')
                ->get();

            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    'recorded_at' => $record->recorded_at,
                    'voltage' => $record->voltage,
                    'temp' => $record->temp,
                    'is_valid' => $record->is_valid ? 'TRUE' : 'FALSE',
                    'source_file' => $record->source_file,
                ];
            }
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }
}
