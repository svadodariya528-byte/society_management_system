<?php
session_start();
include 'g_layout.php';

// Get today's stats
$today = date('Y-m-d');
$stats_query = "SELECT 
    COUNT(*) as total_visitors,
    SUM(CASE WHEN exit_time IS NULL THEN 1 ELSE 0 END) as currently_inside,
    SUM(CASE WHEN DATE(entry_time) = '$today' THEN 1 ELSE 0 END) as checkins_today,
    SUM(CASE WHEN DATE(exit_time) = '$today' THEN 1 ELSE 0 END) as checkouts_today,
    SUM(CASE WHEN purpose LIKE '%delivery%' AND DATE(entry_time) = '$today' THEN 1 ELSE 0 END) as delivery_vehicles
FROM visitors";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);

// Get currently inside visitors
$current_visitors_query = "SELECT v.*, u.name as resident_name, rd.block_name, rd.flat_no 
    FROM visitors v 
    LEFT JOIN users u ON v.visiting_resident_user_id = u.user_id 
    LEFT JOIN resident_details rd ON u.user_id = rd.resident_id 
    WHERE v.exit_time IS NULL 
    ORDER BY v.entry_time DESC 
    LIMIT 8";
$current_visitors = $conn->query($current_visitors_query)->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities (both check-ins and check-outs)
$recent_activities_query = "SELECT 
    v.*, 
    u.name as resident_name, 
    rd.block_name, 
    rd.flat_no,
    CASE 
        WHEN v.exit_time IS NULL THEN 'check-in'
        ELSE 'check-out'
    END as activity_type,
    CASE 
        WHEN v.exit_time IS NULL THEN v.entry_time
        ELSE v.exit_time
    END as activity_time
FROM visitors v 
LEFT JOIN users u ON v.visiting_resident_user_id = u.user_id 
LEFT JOIN resident_details rd ON u.user_id = rd.resident_id 
WHERE DATE(v.entry_time) = '$today' OR (v.exit_time IS NOT NULL AND DATE(v.exit_time) = '$today')
ORDER BY activity_time DESC 
LIMIT 6";
$recent_activities = $conn->query($recent_activities_query)->fetchAll(PDO::FETCH_ASSOC);

// Get visitor types for chart
$visitor_types_query = "SELECT 
    CASE 
        WHEN purpose LIKE '%delivery%' THEN 'Delivery'
        WHEN purpose LIKE '%food%' OR purpose LIKE '%swiggy%' OR purpose LIKE '%zomato%' THEN 'Food Delivery'
        WHEN purpose LIKE '%maintenance%' OR purpose LIKE '%plumber%' OR purpose LIKE '%electrician%' THEN 'Maintenance'
        ELSE 'Guests'
    END as visitor_type,
    COUNT(*) as count
FROM visitors 
WHERE DATE(entry_time) = '$today' 
GROUP BY visitor_type";
$visitor_types_result = $conn->query($visitor_types_query);
$visitor_types = $visitor_types_result->fetchAll(PDO::FETCH_ASSOC);

// Get overdue visitors (inside for more than 4 hours)
$four_hours_ago = date('Y-m-d H:i:s', strtotime('-4 hours'));
$overdue_visitors_query = "SELECT v.*, u.name as resident_name, rd.block_name, rd.flat_no 
    FROM visitors v 
    LEFT JOIN users u ON v.visiting_resident_user_id = u.user_id 
    LEFT JOIN resident_details rd ON u.user_id = rd.resident_id 
    WHERE v.exit_time IS NULL 
    AND v.entry_time < '$four_hours_ago'
    ORDER BY v.entry_time ASC 
    LIMIT 5";
$overdue_visitors = $conn->query($overdue_visitors_query)->fetchAll(PDO::FETCH_ASSOC);

// Prepare visitor types chart data
$chart_labels = [];
$chart_data = [];

foreach ($visitor_types as $type) {
    $chart_labels[] = $type['visitor_type'];
    $chart_data[] = $type['count'];
}

// If no data, show default
if (empty($chart_data)) {
    $chart_labels = ['Guests', 'Delivery', 'Food Delivery', 'Maintenance'];
    $chart_data = [0, 0, 0, 0];
}
?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <!-- Top Navigation -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-shield-alt me-2"></i>
            Security Dashboard
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="visitor-entry.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>
                Add New Visitor
            </a>
        </div>
    </div>

    <!-- Today's Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-icon text-primary mb-2">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $stats['total_visitors'] ?? 0; ?></div>
                    <div class="stat-label">Today's Visitors</div>
                    <small class="text-muted"><?php echo $stats['currently_inside'] ?? 0; ?> currently inside</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-icon text-success mb-2">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $stats['checkins_today'] ?? 0; ?></div>
                    <div class="stat-label">Check-ins</div>
                    <small class="text-success">Today's total</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-icon text-info mb-2">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $stats['checkouts_today'] ?? 0; ?></div>
                    <div class="stat-label">Check-outs</div>
                    <small class="text-muted">Today's total</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-icon text-warning mb-2">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $stats['delivery_vehicles'] ?? 0; ?></div>
                    <div class="stat-label">Delivery Vehicles</div>
                    <small class="text-muted">Today's total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Visitors & Alerts -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>
                        Currently Inside (<?php echo count($current_visitors); ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if(!empty($current_visitors)): ?>
                            <?php foreach($current_visitors as $visitor): 
                                $duration = time() - strtotime($visitor['entry_time']);
                                $hours = floor($duration / 3600);
                                $minutes = floor(($duration % 3600) / 60);
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($visitor['name']); ?></strong><br>
                                    <small class="text-muted">
                                        Visiting <?php echo $visitor['block_name'] . '-' . $visitor['flat_no']; ?>
                                        <?php if(!empty($visitor['purpose'])): ?>
                                            <br><?php echo htmlspecialchars($visitor['purpose']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-success">In: <?php echo date('g:i A', strtotime($visitor['entry_time'])); ?></small><br>
                                    <small class="text-muted">Duration: <?php echo $hours . 'h ' . $minutes . 'm'; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                No visitors currently inside
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="visitor-entery.php" class="btn btn-sm btn-outline-primary">Manage Visitors</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Overdue Checkouts (<?php echo count($overdue_visitors); ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if(!empty($overdue_visitors)): ?>
                            <?php foreach($overdue_visitors as $visitor): 
                                $duration = time() - strtotime($visitor['entry_time']);
                                $hours = floor($duration / 3600);
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($visitor['name']); ?></strong><br>
                                    <small class="text-muted">
                                        <?php echo $visitor['block_name'] . '-' . $visitor['flat_no']; ?>
                                        <?php if(!empty($visitor['purpose'])): ?>
                                            <br><?php echo htmlspecialchars($visitor['purpose']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?php echo $hours; ?>+ hours</span><br>
                                    <small class="text-muted">Since: <?php echo date('g:i A', strtotime($visitor['entry_time'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                                No overdue checkouts
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="visitor-entery.php" class="btn btn-sm btn-outline-warning">Process Checkouts</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Activities -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Visitor Types Today
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="visitorTypesChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php if(!empty($recent_activities)): ?>
                            <?php foreach($recent_activities as $activity): 
                                $is_checkout = ($activity['activity_type'] == 'check-out');
                                $icon = $is_checkout ? 'sign-out-alt' : 'sign-in-alt';
                                $color = $is_checkout ? 'info' : 'success';
                                $action = $is_checkout ? 'checked out' : 'checked in';
                                $time = $is_checkout ? $activity['exit_time'] : $activity['entry_time'];
                            ?>
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-<?php echo $color; ?> text-white rounded-circle me-3">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Visitor <?php echo ucfirst($action); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($activity['name']); ?> 
                                        <?php echo $action; ?> 
                                        <?php echo $is_checkout ? 'after visiting' : 'to visit'; ?> 
                                        <?php echo $activity['block_name'] . '-' . $activity['flat_no']; ?> - 
                                        <?php echo date('g:i A', strtotime($time)); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-history fa-2x mb-2"></i><br>
                                No activities today
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 border-top">
        <small class="text-muted">
            &copy; 2024 Greenwood Society. All rights reserved. | Security Portal
        </small>
    </footer>
</main>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        initializeCharts();
    });

    function initializeCharts() {
        // Visitor Types Chart - Dynamic Data
        const ctx = document.getElementById('visitorTypesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($chart_data); ?>,
                        backgroundColor: ['#3498db', '#f39c12', '#9b59b6', '#27ae60', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
</script>
</body>
</html>