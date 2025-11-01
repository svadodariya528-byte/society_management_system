<?php
ob_start();session_start();
include 'a_layout.php';
require_once "../../db_connect.php";

// Ensure $conn is PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection error");
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create_poll') {
            $pollTitle = trim($_POST['pollTitle'] ?? '');
            $pollDescription = trim($_POST['pollDescription'] ?? '');
            $pollStartDate = trim($_POST['pollStartDate'] ?? '');
            $pollEndDate = trim($_POST['pollEndDate'] ?? '');
            $options = $_POST['options'] ?? [];

            if ($pollTitle && $pollStartDate && $pollEndDate && count($options) >= 2) {
                $conn->beginTransaction();

                // Insert into polls table
                $stmt = $conn->prepare("INSERT INTO polls (question, created_by_user_id, created_at, end_date) 
                                       VALUES (?, ?, NOW(), ?)");
                $stmt->execute([
                    $pollTitle . ($pollDescription ? " - " . $pollDescription : ""),
                    1, // Default admin user_id (you can set this from session)
                    $pollEndDate . ' 23:59:59'
                ]);
                $pollId = $conn->lastInsertId();

                // Insert options into poll_options table
                $stmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
                foreach ($options as $option) {
                    if (trim($option) !== '') {
                        $stmt->execute([$pollId, trim($option)]);
                    }
                }

                $conn->commit();
                $_SESSION['message'] = 'Poll created successfully';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Please fill all required fields and add at least 2 options';
                $_SESSION['message_type'] = 'danger';
            }
            
            header("Location: poll-management.php");
            exit;
        }

        if ($action === 'delete_poll') {
            $pollId = (int)($_POST['poll_id'] ?? 0);
            if ($pollId > 0) {
                $conn->beginTransaction();
                
                // Delete votes first
                $conn->prepare("DELETE FROM poll_votes WHERE poll_id = ?")->execute([$pollId]);
                // Delete options
                $conn->prepare("DELETE FROM poll_options WHERE poll_id = ?")->execute([$pollId]);
                // Delete poll
                $conn->prepare("DELETE FROM polls WHERE poll_id = ?")->execute([$pollId]);
                
                $conn->commit();
                $_SESSION['message'] = 'Poll deleted successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: poll-management.php");
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: poll-management.php");
        exit;
    }
}

// Fetch polls for display - FIXED QUERY
try {
    $stmt = $conn->query("
        SELECT 
            p.*,
            (SELECT COUNT(DISTINCT pv.vote_id) FROM poll_votes pv WHERE pv.poll_id = p.poll_id) as total_votes,
            (SELECT COUNT(*) FROM poll_options po WHERE po.poll_id = p.poll_id) as option_count,
            u.name as created_by_name
        FROM polls p
        LEFT JOIN users u ON p.created_by_user_id = u.user_id
        ORDER BY p.created_at DESC
    ");
    $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $polls = [];
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <!-- Status Message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert" style="position:fixed; top:20px; right:20px; z-index:1060;">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-poll me-2"></i>Poll Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal">
                    <i class="fas fa-plus me-1"></i>Create Poll
                </button>
            </div>
        </div>
    </div>

    <!-- Polls Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Poll Records</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="pollsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Poll Title</th>
                            <th>Status</th>
                            <th>Total Votes</th>
                            <th>Options</th>
                            <th>Created By</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($polls)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-3">No polls found. Create your first poll!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($polls as $index => $poll): ?>
                                <?php
                                    $isActive = strtotime($poll['end_date']) > time();
                                    $statusClass = $isActive ? 'bg-success' : 'bg-secondary';
                                    $statusText = $isActive ? 'Active' : 'Ended';
                                    $endDate = date('M d, Y', strtotime($poll['end_date']));
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($poll['question']) ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td><?= $poll['total_votes'] ?></td>
                                    <td><?= $poll['option_count'] ?></td>
                                    <td><?= htmlspecialchars($poll['created_by_name'] ?? 'Admin') ?></td>
                                    <td><?= $endDate ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary view-results-btn" 
                                                    title="View Results" 
                                                    data-poll-id="<?= $poll['poll_id'] ?>"
                                                    data-poll-title="<?= htmlspecialchars($poll['question']) ?>">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this poll? This will also delete all votes.');">
                                                <input type="hidden" name="action" value="delete_poll">
                                                <input type="hidden" name="poll_id" value="<?= $poll['poll_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Poll">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Create Poll Modal -->
<div class="modal fade" id="createPollModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Poll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createPollForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_poll">
                    <div class="mb-3">
                        <label for="pollTitle" class="form-label">Poll Title *</label>
                        <input type="text" class="form-control" id="pollTitle" name="pollTitle" required 
                               placeholder="Enter poll question">
                    </div>
                    <div class="mb-3">
                        <label for="pollDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="pollDescription" name="pollDescription" 
                                  rows="3" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Options * (At least 2 required)</label>
                        <div id="pollOptionsWrapper">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control pollOption" name="options[]" 
                                       placeholder="Option 1" required>
                                <button type="button" class="btn btn-outline-danger remove-option" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control pollOption" name="options[]" 
                                       placeholder="Option 2" required>
                                <button type="button" class="btn btn-outline-danger remove-option">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addPollOption">
                            <i class="fas fa-plus me-1"></i>Add Option
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pollStartDate" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="pollStartDate" name="pollStartDate" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pollEndDate" class="form-label">End Date *</label>
                            <input type="date" class="form-control" id="pollEndDate" name="pollEndDate" 
                                   value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Poll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Results Modal -->
<div class="modal fade" id="viewResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>Poll Results: <span id="pollModalTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <canvas id="dynamicPollChart" height="200"></canvas>
                <div id="resultsDetails" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function(){
    let optionCount = 2;
    let currentChart = null;

    // Add more options dynamically
    $('#addPollOption').click(function(){
        optionCount++;
        $('#pollOptionsWrapper').append(`
            <div class="input-group mb-2">
                <input type="text" class="form-control pollOption" name="options[]" 
                       placeholder="Option ${optionCount}" required>
                <button type="button" class="btn btn-outline-danger remove-option">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
        updateRemoveButtons();
    });

    // Remove option
    $(document).on('click', '.remove-option', function(){
        if ($('.pollOption').length > 2) {
            $(this).closest('.input-group').remove();
            optionCount--;
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        $('.remove-option').prop('disabled', $('.pollOption').length <= 2);
    }

    // Initialize remove buttons state
    updateRemoveButtons();

    // View results for any poll
    $(document).on('click', '.view-results-btn', function(){
        const pollId = $(this).data('poll-id');
        const pollTitle = $(this).data('poll-title');
        viewPollResults(pollId, pollTitle);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});

// Function to view poll results
function viewPollResults(pollId, pollTitle) {
    // Set the poll title in modal
    $('#pollModalTitle').text(pollTitle);
    
    // Show loading state
    $('#dynamicPollChart').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading results...</div></div>');
    $('#resultsDetails').html('');
    
    // Fetch real poll results via AJAX
    fetchPollResults(pollId);
}

// Fetch actual poll results from server
function fetchPollResults(pollId) {
    $.ajax({
        url: 'get_poll_results.php',
        type: 'GET',
        data: { poll_id: pollId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayPollResults(response.data);
            } else {
                $('#dynamicPollChart').html('<div class="text-center text-danger p-4">Error loading results</div>');
                $('#resultsDetails').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#dynamicPollChart').html('<div class="text-center text-danger p-4">Failed to load results</div>');
            $('#resultsDetails').html('<div class="alert alert-danger">Server error occurred while fetching results.</div>');
        }
    });
}

// Display poll results in the modal
function displayPollResults(data) {
    const ctx = document.getElementById('dynamicPollChart').getContext('2d');
    
    // Destroy previous chart if exists
    if (window.currentChart) {
        window.currentChart.destroy();
    }
    
    // Create new chart
    window.currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Votes',
                data: data.votes,
                backgroundColor: ['#3498db', '#e74c3c', '#f1c40f', '#2ecc71', '#9b59b6', '#1abc9c'],
                borderRadius: 6,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            return `Votes: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        stepSize: 1,
                        precision: 0
                    },
                    title: { 
                        display: true, 
                        text: 'Number of Votes' 
                    }
                },
                x: {
                    grid: { display: false },
                    title: { 
                        display: true, 
                        text: 'Poll Options' 
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });

    // Update results details
    let detailsHtml = '<h6 class="mb-3">Vote Breakdown:</h6><div class="row">';
    let totalVotes = data.totalVotes || data.votes.reduce((a, b) => a + b, 0);
    
    data.labels.forEach((label, index) => {
        const votes = data.votes[index];
        const percentage = totalVotes > 0 ? ((votes / totalVotes) * 100).toFixed(1) : 0;
        detailsHtml += `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between">
                    <small>${label}</small>
                    <small><strong>${votes} votes (${percentage}%)</strong></small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: ${percentage}%;" 
                         aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        `;
    });
    
    detailsHtml += `</div>
        <div class="mt-3 p-3 bg-light rounded">
            <div class="row text-center">
                <div class="col-md-6">
                    <h5 class="text-primary mb-0">${totalVotes}</h5>
                    <small class="text-muted">Total Votes</small>
                </div>
                <div class="col-md-6">
                    <h5 class="text-success mb-0">${data.labels.length}</h5>
                    <small class="text-muted">Total Options</small>
                </div>
            </div>
        </div>`;
    
    $('#resultsDetails').html(detailsHtml);

    // Show modal
    new bootstrap.Modal(document.getElementById('viewResultsModal')).show();
}

// Handle modal close to clean up chart
$('#viewResultsModal').on('hidden.bs.modal', function () {
    if (window.currentChart) {
        window.currentChart.destroy();
        window.currentChart = null;
    }
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>