<?php
/**
 * Analytics Summary Endpoint
 * GET /api/analytics/summary — Aggregated analytics data
 */

/**
 * GET /api/analytics/summary
 * Returns combined analytics object with:
 *   - volume_by_status
 *   - volume_by_category
 *   - volume_by_priority
 *   - agent_performance
 *   - sentiment_trend (last 30 days)
 *   - total_tickets, total_open, total_resolved_today
 */
function handleAnalyticsSummary(): void {
    $pdo = getDbConnection();
    
    $result = [];
    
    // ---- Volume by Status ----
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM tickets 
        GROUP BY status
    ");
    $statusCounts = [];
    $allStatuses = ['New', 'Open', 'In Progress', 'On Hold', 'Resolved', 'Closed'];
    foreach ($allStatuses as $s) {
        $statusCounts[$s] = 0;
    }
    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    $result['volume_by_status'] = $statusCounts;
    
    // ---- Volume by Category ----
    $stmt = $pdo->query("
        SELECT COALESCE(category, 'Uncategorized') as category, COUNT(*) as count 
        FROM tickets 
        GROUP BY category
    ");
    $categoryCounts = [];
    foreach ($stmt->fetchAll() as $row) {
        $categoryCounts[$row['category']] = (int)$row['count'];
    }
    $result['volume_by_category'] = $categoryCounts;
    
    // ---- Volume by Priority ----
    $stmt = $pdo->query("
        SELECT priority, COUNT(*) as count 
        FROM tickets 
        GROUP BY priority
    ");
    $priorityCounts = [];
    $allPriorities = ['Low', 'Medium', 'High', 'Urgent'];
    foreach ($allPriorities as $p) {
        $priorityCounts[$p] = 0;
    }
    foreach ($stmt->fetchAll() as $row) {
        $priorityCounts[$row['priority']] = (int)$row['count'];
    }
    $result['volume_by_priority'] = $priorityCounts;
    
    // ---- Agent Performance ----
    $stmt = $pdo->query("
        SELECT 
            a.name AS agent_name,
            COALESCE(
                (SELECT COUNT(*) FROM tickets t 
                 WHERE t.agent_id = a.id 
                 AND t.status IN ('Resolved', 'Closed')),
                0
            ) AS tickets_resolved,
            COALESCE(
                (SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)), 1) 
                 FROM tickets t 
                 WHERE t.agent_id = a.id 
                 AND t.resolved_at IS NOT NULL),
                0
            ) AS avg_resolution_hours
        FROM agents a
        ORDER BY tickets_resolved DESC
    ");
    $agentPerf = $stmt->fetchAll();
    foreach ($agentPerf as &$ap) {
        $ap['tickets_resolved'] = (int)$ap['tickets_resolved'];
        $ap['avg_resolution_hours'] = (float)$ap['avg_resolution_hours'];
    }
    $result['agent_performance'] = $agentPerf;
    
    // ---- Sentiment Trend (last 30 days) ----
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) AS date,
            SUM(CASE WHEN sentiment = 'Positive' THEN 1 ELSE 0 END) AS positive,
            SUM(CASE WHEN sentiment = 'Neutral' THEN 1 ELSE 0 END) AS neutral,
            SUM(CASE WHEN sentiment = 'Negative' THEN 1 ELSE 0 END) AS negative,
            SUM(CASE WHEN sentiment = 'Frustrated' THEN 1 ELSE 0 END) AS frustrated
        FROM tickets
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $sentimentTrend = $stmt->fetchAll();
    foreach ($sentimentTrend as &$st) {
        $st['positive']   = (int)$st['positive'];
        $st['neutral']    = (int)$st['neutral'];
        $st['negative']   = (int)$st['negative'];
        $st['frustrated'] = (int)$st['frustrated'];
    }
    $result['sentiment_trend'] = $sentimentTrend;
    
    // ---- Totals ----
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $result['total_tickets'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM tickets 
        WHERE status NOT IN ('Resolved', 'Closed')
    ");
    $result['total_open'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM tickets 
        WHERE status IN ('Resolved', 'Closed') 
        AND DATE(resolved_at) = CURDATE()
    ");
    $result['total_resolved_today'] = (int)$stmt->fetch()['total'];
    
    echo json_encode($result);
}
