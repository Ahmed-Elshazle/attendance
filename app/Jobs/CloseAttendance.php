<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\AcademicSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CloseAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scheduleId;
    protected $sessionType;

    public function __construct(int $scheduleId, string $sessionType)
    {
        $this->scheduleId = $scheduleId;
        $this->sessionType = $sessionType;
    }

    public function handle()
    {
        $academicSchedule = AcademicSchedule::find($this->scheduleId);
        if ($academicSchedule) {
            $academicSchedule->update(['is_'.$this->sessionType.'_attendance_open' => 0]);
        }
    }
}
