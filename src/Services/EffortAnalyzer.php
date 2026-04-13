<?php

namespace Dooz\Worklog\Services;

use Illuminate\Support\Facades\DB;

/**
 * EffortAnalyzer - AI-Native Effort Memory
 * Reference: worklog.md §12.1
 * 
 * Analyzes historical effort for similar tasks and suggests range.
 * AI is observer + advisor, never actor.
 */
class EffortAnalyzer
{
    /**
     * Analyze historical effort for similar tasks
     * Returns suggested effort range based on historical data
     */
    public static function getEstimateForTask(string $taskName, ?int $projectId = null): array
    {
        // Tokenize task name for similarity matching
        $keywords = self::extractKeywords($taskName);
        
        if (empty($keywords)) {
            return self::noDataResponse();
        }

        // Build query to find similar completed tasks
        $query = DB::table('worklog_tasks')
            ->where('status', 'completed')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%");
                }
            });

        // Optionally weight by same project
        if ($projectId) {
            $query->orderByRaw("CASE WHEN project_id = ? THEN 0 ELSE 1 END", [$projectId]);
        }

        $similarTasks = $query->limit(10)->pluck('id');

        if ($similarTasks->isEmpty()) {
            return self::noDataResponse();
        }

        // Get actual effort from time logs
        $effortData = DB::table('worklog_time_logs')
            ->whereIn('task_id', $similarTasks)
            ->groupBy('task_id')
            ->selectRaw('task_id, SUM(duration_minutes) as total_minutes')
            ->get();

        if ($effortData->isEmpty()) {
            return self::noDataResponse();
        }

        $efforts = $effortData->pluck('total_minutes')->toArray();
        $minMinutes = min($efforts);
        $maxMinutes = max($efforts);
        $avgMinutes = array_sum($efforts) / count($efforts);

        return [
            'has_data' => true,
            'sample_size' => count($efforts),
            'range' => [
                'min_hours' => round($minMinutes / 60, 1),
                'max_hours' => round($maxMinutes / 60, 1),
                'avg_hours' => round($avgMinutes / 60, 1),
            ],
            'suggestion' => sprintf(
                'Similar tasks historically took %.1fh – %.1fh to complete.',
                round($minMinutes / 60, 1),
                round($maxMinutes / 60, 1)
            )
        ];
    }

    /**
     * Extract keywords from task name for similarity matching
     */
    protected static function extractKeywords(string $taskName): array
    {
        // Remove common words and tokenize
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'of', 'to', 'for', 'in', 'on', 'with', 'task', 'update', 'fix'];
        $words = preg_split('/[\s\-\_]+/', strtolower($taskName));
        
        return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
    }

    /**
     * Default response when no historical data available
     */
    protected static function noDataResponse(): array
    {
        return [
            'has_data' => false,
            'sample_size' => 0,
            'range' => null,
            'suggestion' => 'No historical data available for similar tasks.'
        ];
    }
}
