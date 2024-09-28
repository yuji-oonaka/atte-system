<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimeSeeder extends Seeder
{
    public function run()
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            $breakCount = rand(1, 3); // 1から3回の休憩をランダムに設定
            for ($i = 0; $i < $breakCount; $i++) {
                $startTime = Carbon::parse($attendance->start_time)->addHours(rand(1, 6));
                $endTime = $startTime->copy()->addMinutes(rand(15, 60));

                if ($endTime < Carbon::parse($attendance->end_time)) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ]);
                }
            }
        }
    }
}