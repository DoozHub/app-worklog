<?php

namespace Dooz\Worklog\Services;

/**
 * Worklog Task State Machine
 * Reference: Worklog1.md
 * 
 * States: NEW, IN_PROGRESS, ON_HOLD, COMPLETED, CANCELLED
 */
class WorklogStateMachine
{
    public const NEW = 'new';
    public const IN_PROGRESS = 'in_progress';
    public const ON_HOLD = 'on_hold';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    /**
     * Valid transitions map
     * From => [allowed destinations]
     */
    protected static array $transitions = [
        self::NEW => [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED],
        self::IN_PROGRESS => [self::ON_HOLD, self::COMPLETED, self::CANCELLED],
        self::ON_HOLD => [self::IN_PROGRESS, self::COMPLETED, self::CANCELLED],
        self::COMPLETED => [], // Terminal state - no transitions allowed
        self::CANCELLED => [], // Terminal state - no transitions allowed
    ];

    /**
     * Human-readable action labels for transitions
     */
    protected static array $actionLabels = [
        self::NEW => [
            self::IN_PROGRESS => 'Start Work',
            self::ON_HOLD => 'Put on Hold',
            self::CANCELLED => 'Cancel Task',
        ],
        self::IN_PROGRESS => [
            self::ON_HOLD => 'Pause Work',
            self::COMPLETED => 'Complete Task',
            self::CANCELLED => 'Cancel Task',
        ],
        self::ON_HOLD => [
            self::IN_PROGRESS => 'Resume Work',
            self::COMPLETED => 'Complete Task',
            self::CANCELLED => 'Cancel Task',
        ],
    ];

    /**
     * Check if a transition is valid
     */
    public static function canTransition(string $from, string $to): bool
    {
        if (!isset(self::$transitions[$from])) {
            return false;
        }
        return in_array($to, self::$transitions[$from], true);
    }

    /**
     * Get available transitions from current state
     * Returns array of ['status' => 'label'] pairs
     */
    public static function getAvailableTransitions(string $currentStatus): array
    {
        $available = [];
        $allowed = self::$transitions[$currentStatus] ?? [];
        $labels = self::$actionLabels[$currentStatus] ?? [];

        foreach ($allowed as $status) {
            $available[] = [
                'status' => $status,
                'label' => $labels[$status] ?? ucwords(str_replace('_', ' ', $status)),
            ];
        }

        return $available;
    }

    /**
     * Check if time logging is allowed for a status
     */
    public static function canLogTime(string $status): bool
    {
        // Per Worklog1.md §7: Allowed on NEW, IN_PROGRESS, ON_HOLD
        // Forbidden on COMPLETED, CANCELLED
        return in_array($status, [self::NEW, self::IN_PROGRESS, self::ON_HOLD], true);
    }

    /**
     * Check if status is terminal (no further transitions)
     */
    public static function isTerminal(string $status): bool
    {
        return in_array($status, [self::COMPLETED, self::CANCELLED], true);
    }

    /**
     * Get all valid states
     */
    public static function getAllStates(): array
    {
        return [self::NEW, self::IN_PROGRESS, self::ON_HOLD, self::COMPLETED, self::CANCELLED];
    }
}
