<?php

namespace Dooz\Worklog\Services;

use Illuminate\Support\Facades\DB;

/**
 * TaskBreakdownSuggester - AI-Native Task Breakdown
 * Reference: worklog.md §12.3
 * 
 * "Similar Quickies earlier resulted in 3–4 tasks. View suggested breakdown?"
 * Never auto-applies - human always chooses.
 */
class TaskBreakdownSuggester
{
    /**
     * Analyze historical data to suggest task breakdown for a Quicky
     */
    public static function suggestBreakdown(string $quickyTitle, ?string $quickyDescription = null): array
    {
        // Extract keywords for similarity matching
        $keywords = self::extractKeywords($quickyTitle . ' ' . ($quickyDescription ?? ''));
        
        if (empty($keywords)) {
            return self::noDataResponse();
        }

        // Find similar Quickies that resulted in multiple tasks
        $similarQuickies = DB::table('worklog_tasks')
            ->whereNotNull('linked_quicky_id')
            ->where('status', 'completed')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%");
                }
            })
            ->get(['linked_quicky_id', 'name']);

        if ($similarQuickies->isEmpty()) {
            return self::noDataResponse();
        }

        // Group by linked_quicky_id to count tasks per Quicky
        $quickyTaskCounts = $similarQuickies->groupBy('linked_quicky_id');
        
        // Filter to only Quickies with multiple tasks
        $multiTaskQuickies = $quickyTaskCounts->filter(fn($tasks) => $tasks->count() > 1);

        if ($multiTaskQuickies->isEmpty()) {
            return self::noDataResponse();
        }

        // Calculate average task count
        $taskCounts = $multiTaskQuickies->map(fn($tasks) => $tasks->count())->values();
        $avgTasks = round($taskCounts->avg());
        $minTasks = $taskCounts->min();
        $maxTasks = $taskCounts->max();

        // Get common task name patterns from similar Quickies
        $suggestedTasks = [];
        $commonPatterns = ['Review', 'Implementation', 'Testing', 'Deployment', 'Documentation'];
        
        // Get actual task names from similar Quickies for better suggestions
        $exampleTasks = $multiTaskQuickies->take(3)->flatten()->pluck('name')->unique()->take(5);
        
        return [
            'has_suggestion' => true,
            'sample_size' => $multiTaskQuickies->count(),
            'task_range' => [
                'min' => $minTasks,
                'max' => $maxTasks,
                'avg' => $avgTasks
            ],
            'message' => sprintf(
                'Similar requests earlier resulted in %d–%d tasks.',
                $minTasks,
                $maxTasks
            ),
            'example_tasks' => $exampleTasks->values()->toArray(),
            'suggested_breakdown' => self::generateSuggestedTasks($quickyTitle, $avgTasks)
        ];
    }

    /**
     * Generate suggested task names based on Quicky title
     */
    protected static function generateSuggestedTasks(string $quickyTitle, int $count): array
    {
        $baseTitle = self::extractBaseTitle($quickyTitle);
        $commonSuffixes = ['- Planning', '- Implementation', '- Review', '- Testing', '- Deployment'];
        
        $suggestions = [];
        for ($i = 0; $i < min($count, count($commonSuffixes)); $i++) {
            $suggestions[] = $baseTitle . ' ' . $commonSuffixes[$i];
        }
        
        return $suggestions;
    }

    /**
     * Extract base title for task naming
     */
    protected static function extractBaseTitle(string $title): string
    {
        // Remove common prefixes
        $title = preg_replace('/^(fix|update|add|implement|create|build)\s+/i', '', $title);
        // Truncate if too long
        return strlen($title) > 30 ? substr($title, 0, 30) . '...' : $title;
    }

    /**
     * Extract keywords for similarity matching
     */
    protected static function extractKeywords(string $text): array
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'of', 'to', 'for', 'in', 'on', 'with', 'task', 'quicky'];
        $words = preg_split('/[\s\-\_]+/', strtolower($text));
        
        return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
    }

    /**
     * Default response when no historical data
     */
    protected static function noDataResponse(): array
    {
        return [
            'has_suggestion' => false,
            'sample_size' => 0,
            'task_range' => null,
            'message' => 'No historical data available for similar requests.',
            'example_tasks' => [],
            'suggested_breakdown' => []
        ];
    }
}
