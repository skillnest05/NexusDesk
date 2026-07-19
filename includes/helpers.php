<?php
/**
 * View Helpers — Badge rendering, time formatting, etc.
 */

function statusBadge(string $status): string {
    $class = strtolower(str_replace(' ', '-', $status));
    return "<span class='badge-status badge-{$class}'>{$status}</span>";
}

function priorityBadge(string $priority): string {
    $class = strtolower($priority);
    return "<span class='badge-priority badge-{$class}'>{$priority}</span>";
}

function sentimentBadge(?string $sentiment): string {
    if (!$sentiment) return '';
    $class = strtolower($sentiment);
    $icons = ['Positive' => '😊', 'Neutral' => '😐', 'Negative' => '😟', 'Frustrated' => '😤'];
    $icon = $icons[$sentiment] ?? '😐';
    return "<span class='badge-sentiment badge-{$class}'>{$icon} {$sentiment}</span>";
}

function timeAgo(string $datetime): string {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->days > 30) return $then->format('M j, Y');
    if ($diff->days > 0) return $diff->days . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
