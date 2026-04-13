<?php

namespace Dooz\Worklog\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AnomalyDetector - Passive Anomaly Detection
 * Reference: worklog.md §12.2
 * 
 * Flags (silently):
 * - No time logged on active tasks
 * - Extreme deviations
 * - Overloaded users
 * 
 * Surfaces only via Dooz Pulse, never interrupts.
 */
class AnomalyDetector
{
    /**
     * Detect tasks with no activity for X days
     */
    public static function getInactiveTasks(int $daysThreshold = 2): array
    {
        $cutoffDate = Carbon::now()->subDays($daysThreshold);

        // Active tasks (NEW or IN_PROGRESS) with no recent logs
        $activeTasks = DB::table('worklog_tasks')
            ->whereIn('status', ['new', 'in_progress'])
            ->get(['id', 'name', 'status', 'created_at']);

        $inactiveTasks = [];

        foreach ($activeTasks as $task) {
            $lastLog = DB::table('worklog_time_logs')
                ->where('task_id', $task->id)
                ->orderBy('date', 'desc')
                ->first();

            $lastActivity = $lastLog ? Carbon::parse($lastLog->date) : Carbon::parse($task->created_at);
            $daysSince = $lastActivity->diffInDays(Carbon::now());

            if ($daysSince >= $daysThreshold) {
                $inactiveTasks[] = [
                    'task_id' => $task->id,
                    'task_name' => $task->name,
                    'status' => $task->status,
                    'days_inactive' => $daysSince,
                    'last_activity' => $lastActivity->toDateString(),
                    'flag' => 'No activity recorded'
                ];
            }
        }

        return $inactiveTasks;
    }

    /**
     * Detect extreme duration deviations in recent logs
     * Flags sessions that are unusually long or short compared to user average
     */
    public static function getDurationAnomalies(?int $userId = null): array
    {
        // Get user's average session duration
        $query = DB::table('worklog_time_logs')
            ->select('user_id')
            ->selectRaw('AVG(duration_minutes) as avg_duration')
            ->selectRaw('STDDEV(duration_minutes) as std_duration')
            ->groupBy('user_id');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $userStats = $query->get()->keyBy('user_id');

        if ($userStats->isEmpty()) {
            return [];
        }

        // Find logs that deviate more than 2 standard deviations
        $anomalies = [];
        $recentLogs = DB::table('worklog_time_logs')
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->get();

        foreach ($recentLogs as $log) {
            $stats = $userStats->get($log->user_id);
            if (!$stats || !$stats->std_duration) continue;

            $deviation = abs($log->duration_minutes - $stats->avg_duration);
            $threshold = $stats->std_duration * 2;

            if ($deviation > $threshold) {
                $type = $log->duration_minutes > $stats->avg_duration ? 'unusually_long' : 'unusually_short';
                $anomalies[] = [
                    'log_id' => $log->id,
                    'task_id' => $log->task_id,
                    'user_id' => $log->user_id,
                    'date' => $log->date,
                    'duration_minutes' => $log->duration_minutes,
                    'user_avg_minutes' => round($stats->avg_duration),
                    'deviation_type' => $type,
                    'flag' => $type === 'unusually_long' 
                        ? 'Session significantly longer than user average'
                        : 'Session significantly shorter than user average'
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect overloaded users (too many active tasks)
     */
    public static function getOverloadedUsers(int $taskThreshold = 10): array
    {
        // Count active tasks per user (assuming user assignment via time logs)
        $userTaskCounts = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->whereIn('worklog_tasks.status', ['new', 'in_progress'])
            ->select('worklog_time_logs.user_id')
            ->selectRaw('COUNT(DISTINCT worklog_tasks.id) as active_task_count')
            ->groupBy('worklog_time_logs.user_id')
            ->having('active_task_count', '>=', $taskThreshold)
            ->get();

        return $userTaskCounts->map(fn($u) => [
            'user_id' => $u->user_id,
            'active_task_count' => $u->active_task_count,
            'flag' => 'User has many active tasks'
        ])->toArray();
    }

    /**
     * Get all anomalies for Dooz Pulse
     */
    public static function getAllAnomalies(): array
    {
        return [
            'inactive_tasks' => self::getInactiveTasks(),
            'duration_anomalies' => self::getDurationAnomalies(),
            'overloaded_users' => self::getOverloadedUsers(),
            'generated_at' => Carbon::now()->toIso8601String()
        ];
    }
}
