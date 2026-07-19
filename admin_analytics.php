<?php
/**
 * Admin: Analytics Dashboard — Charts and stats
 */
$pageTitle = 'Analytics — NexusDesk';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pdo = getDbConnection();

// Stats
$total = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$open = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('Resolved','Closed')")->fetchColumn();
$resolvedToday = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolved' AND DATE(updated_at) = CURDATE()")->fetchColumn();

// Volume by Status
$byStatus = [];
foreach (['New','Open','In Progress','On Hold','Resolved','Closed'] as $s) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = ?");
    $st->execute([$s]);
    $byStatus[$s] = (int)$st->fetchColumn();
}

// Volume by Category
$catStmt = $pdo->query("SELECT COALESCE(category,'Uncategorized') as cat, COUNT(*) as cnt FROM tickets GROUP BY cat ORDER BY cnt DESC");
$byCategory = [];
while ($row = $catStmt->fetch()) $byCategory[$row['cat']] = (int)$row['cnt'];

// Volume by Priority
$byPriority = [];
foreach (['Low','Medium','High','Urgent'] as $p) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE priority = ?");
    $st->execute([$p]);
    $byPriority[$p] = (int)$st->fetchColumn();
}

// Agent Performance
$agentPerf = $pdo->query("
    SELECT a.name,
           COUNT(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 END) AS resolved,
           COUNT(CASE WHEN t.status NOT IN ('Resolved','Closed') THEN 1 END) AS active,
           ROUND(AVG(CASE WHEN t.status IN ('Resolved','Closed')
                THEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) END), 1) AS avg_hours
    FROM agents a LEFT JOIN tickets t ON a.id = t.agent_id
    GROUP BY a.id, a.name ORDER BY resolved DESC
")->fetchAll();

// Sentiment Trend (last 14 days)
$sentimentTrend = $pdo->query("
    SELECT DATE(created_at) as d,
           SUM(sentiment='Positive') as pos, SUM(sentiment='Neutral') as neu,
           SUM(sentiment='Negative') as neg, SUM(sentiment='Frustrated') as fru
    FROM tickets WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY d ORDER BY d
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-1 animate-in">📈 Analytics Dashboard</h4>
<p class="text-muted mb-4 animate-in delay-1">Overview of your support operations</p>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4 animate-in delay-1">
        <div class="stat-card text-center">
            <div class="stat-icon">📊</div>
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-sm-4 animate-in delay-2">
        <div class="stat-card text-center">
            <div class="stat-icon">📂</div>
            <div class="stat-number"><?= $open ?></div>
            <div class="stat-label">Open Tickets</div>
        </div>
    </div>
    <div class="col-sm-4 animate-in delay-3">
        <div class="stat-card text-center">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?= $resolvedToday ?></div>
            <div class="stat-label">Resolved Today</div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-6 animate-in delay-2">
        <div class="chart-panel">
            <h5>Tickets by Status</h5>
            <p class="chart-subtitle">Distribution across all statuses</p>
            <canvas id="statusChart" height="280"></canvas>
        </div>
    </div>
    <div class="col-lg-6 animate-in delay-3">
        <div class="chart-panel">
            <h5>Tickets by Category</h5>
            <p class="chart-subtitle">AI-generated categories</p>
            <canvas id="categoryChart" height="280"></canvas>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-4 mb-4">
    <div class="col-lg-6 animate-in delay-3">
        <div class="chart-panel">
            <h5>Tickets by Priority</h5>
            <p class="chart-subtitle">Urgency distribution</p>
            <canvas id="priorityChart" height="280"></canvas>
        </div>
    </div>
    <div class="col-lg-6 animate-in delay-4">
        <div class="chart-panel">
            <h5>Sentiment Trend</h5>
            <p class="chart-subtitle">Last 14 days</p>
            <canvas id="sentimentChart" height="280"></canvas>
        </div>
    </div>
</div>

<!-- Agent Performance Table -->
<div class="chart-panel animate-in delay-5">
    <h5>Agent Performance</h5>
    <p class="chart-subtitle">Tickets resolved and average resolution time</p>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead><tr><th>Agent</th><th>Active</th><th>Resolved</th><th>Avg Resolution</th></tr></thead>
            <tbody>
                <?php foreach ($agentPerf as $a): ?>
                <tr>
                    <td class="fw-semibold"><?= e($a['name']) ?></td>
                    <td><span class="badge bg-primary rounded-pill"><?= $a['active'] ?></span></td>
                    <td><span class="badge bg-success rounded-pill"><?= $a['resolved'] ?></span></td>
                    <td><?= $a['avg_hours'] ? $a['avg_hours'] . 'h' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$statusJson    = json_encode($byStatus);
$categoryJson  = json_encode($byCategory);
$priorityJson  = json_encode($byPriority);
$sentimentJson = json_encode($sentimentTrend);

$extraScripts = <<<JS
<script>
const statusColors = {'New':'#3B82F6','Open':'#8B5CF6','In Progress':'#F59E0B','On Hold':'#EF4444','Resolved':'#10B981','Closed':'#6B7280'};
const priorityColors = {'Low':'#10B981','Medium':'#3B82F6','High':'#F59E0B','Urgent':'#EF4444'};
const pieColors = ['#6366F1','#8B5CF6','#3B82F6','#10B981','#F59E0B','#EF4444','#F97316','#EC4899','#14B8A6','#6B7280'];

Chart.defaults.font.family = 'Inter';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

// Status Chart
const sd = {$statusJson};
new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(sd),
        datasets: [{
            data: Object.values(sd),
            backgroundColor: Object.keys(sd).map(k => statusColors[k]),
            borderRadius: 8, borderSkipped: false, barPercentage: 0.6
        }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}} }
});

// Category Chart
const cd = {$categoryJson};
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(cd),
        datasets: [{ data: Object.values(cd), backgroundColor: pieColors.slice(0, Object.keys(cd).length), borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}} }
});

// Priority Chart
const pd = {$priorityJson};
new Chart(document.getElementById('priorityChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(pd),
        datasets: [{
            data: Object.values(pd),
            backgroundColor: Object.keys(pd).map(k => priorityColors[k]),
            borderRadius: 8, borderSkipped: false, barPercentage: 0.5
        }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}} }
});

// Sentiment Trend
const st = {$sentimentJson};
if (st.length > 0) {
    new Chart(document.getElementById('sentimentChart'), {
        type: 'line',
        data: {
            labels: st.map(r => r.d),
            datasets: [
                {label:'Positive',data:st.map(r=>r.pos),borderColor:'#10B981',backgroundColor:'rgba(16,185,129,0.1)',fill:true,tension:0.4},
                {label:'Neutral',data:st.map(r=>r.neu),borderColor:'#6B7280',backgroundColor:'rgba(107,114,128,0.1)',fill:true,tension:0.4},
                {label:'Negative',data:st.map(r=>r.neg),borderColor:'#EF4444',backgroundColor:'rgba(239,68,68,0.1)',fill:true,tension:0.4},
                {label:'Frustrated',data:st.map(r=>r.fru),borderColor:'#F97316',backgroundColor:'rgba(249,115,22,0.1)',fill:true,tension:0.4}
            ]
        },
        options: { responsive:true, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}} }
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
