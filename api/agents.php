<?php
/**
 * Agents Endpoint
 * GET /api/agents — List agents with performance stats
 */

/**
 * GET /api/agents
 * Returns array of agents enriched with:
 *   - active_tickets_count (status NOT IN ('Resolved', 'Closed'))
 *   - resolved_count (status IN ('Resolved', 'Closed'))
 *   - avg_resolution_time_hours (AVG of TIMESTAMPDIFF for resolved tickets)
 */
function handleListAgents(): void {
    $pdo = getDbConnection();
    
    $sql = "
        SELECT 
            a.id,
            a.name,
            a.email,
            COALESCE(
                (SELECT COUNT(*) FROM tickets t 
                 WHERE t.agent_id = a.id 
                 AND t.status NOT IN ('Resolved', 'Closed')),
                0
            ) AS active_tickets_count,
            COALESCE(
                (SELECT COUNT(*) FROM tickets t 
                 WHERE t.agent_id = a.id 
                 AND t.status IN ('Resolved', 'Closed')),
                0
            ) AS resolved_count,
            COALESCE(
                (SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)), 1) 
                 FROM tickets t 
                 WHERE t.agent_id = a.id 
                 AND t.resolved_at IS NOT NULL),
                0
            ) AS avg_resolution_time_hours
        FROM agents a
        ORDER BY a.id ASC
    ";
    
    $stmt = $pdo->query($sql);
    $agents = $stmt->fetchAll();
    
    // Cast numeric fields to proper types
    foreach ($agents as &$agent) {
        $agent['id'] = (int)$agent['id'];
        $agent['active_tickets_count'] = (int)$agent['active_tickets_count'];
        $agent['resolved_count'] = (int)$agent['resolved_count'];
        $agent['avg_resolution_time_hours'] = (float)$agent['avg_resolution_time_hours'];
    }
    
    echo json_encode($agents);
}
