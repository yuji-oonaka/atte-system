<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                $startTime = $date->copy()->setTime(rand(8, 10), rand(0, 59), 0);
                $endTime = $startTime->copy()->addHours(rand(7, 10));

                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }
        }
    }
}