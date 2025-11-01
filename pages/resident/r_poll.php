<?php
session_start();
require_once "../../db_connect.php";

// Check if user is logged in and is a resident
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Initialize variables
$current_polls = [];
$poll_options = [];
$user_votes = [];
$poll_results = [];
$voted_polls = [];
$message = '';

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote'])) {
    $poll_id = intval($_POST['poll_id']);
    $option_id = intval($_POST['option_id']);
    
    try {
        // Check if user already voted for this poll
        $check_stmt = $conn->prepare("
            SELECT vote_id FROM poll_votes 
            WHERE poll_id = ? AND voter_user_id = ?
        ");
        $check_stmt->execute([$poll_id, $user_id]);
        
        if ($check_stmt->fetch()) {
            $message = "You have already voted in this poll!";
        } else {
            // Record the vote
            $vote_stmt = $conn->prepare("
                INSERT INTO poll_votes (poll_id, option_id, voter_user_id) 
                VALUES (?, ?, ?)
            ");
            $vote_stmt->execute([$poll_id, $option_id, $user_id]);
            $message = "Thank you for voting! Your response has been recorded.";
            
            // Refresh the page to show updated status
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $message = "Error submitting vote: " . $e->getMessage();
    }
}

try {
    $current_date = date('Y-m-d H:i:s');
    
    // Get ALL current active polls (end_date in future)
    $poll_stmt = $conn->prepare("
        SELECT p.poll_id, p.question, p.created_at, p.end_date, u.name as created_by
        FROM polls p 
        JOIN users u ON p.created_by_user_id = u.user_id 
        WHERE p.end_date > ? 
        ORDER BY p.created_at DESC
    ");
    $poll_stmt->execute([$current_date]);
    $all_current_polls = $poll_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter to get only polls that user hasn't voted on
    foreach ($all_current_polls as $poll) {
        // Check if user already voted in this poll
        $vote_check_stmt = $conn->prepare("
            SELECT vote_id 
            FROM poll_votes 
            WHERE poll_id = ? AND voter_user_id = ?
        ");
        $vote_check_stmt->execute([$poll['poll_id'], $user_id]);
        
        if (!$vote_check_stmt->fetch()) {
            // User hasn't voted, add to current polls
            $current_polls[] = $poll;
            
            // Get poll options for this poll
            $options_stmt = $conn->prepare("
                SELECT option_id, option_text 
                FROM poll_options 
                WHERE poll_id = ? 
                ORDER BY option_id
            ");
            $options_stmt->execute([$poll['poll_id']]);
            $poll_options[$poll['poll_id']] = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get poll results for this poll
            $results_stmt = $conn->prepare("
                SELECT 
                    o.option_id,
                    o.option_text,
                    COUNT(v.vote_id) as vote_count,
                    (SELECT COUNT(*) FROM poll_votes WHERE poll_id = ?) as total_votes
                FROM poll_options o 
                LEFT JOIN poll_votes v ON o.option_id = v.option_id AND v.poll_id = ?
                WHERE o.poll_id = ?
                GROUP BY o.option_id, o.option_text
                ORDER BY o.option_id
            ");
            $results_stmt->execute([$poll['poll_id'], $poll['poll_id'], $poll['poll_id']]);
            $poll_results[$poll['poll_id']] = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // User has voted, add to voted polls (for current active polls that user voted on)
            $voted_option_stmt = $conn->prepare("
                SELECT o.option_text, o.option_id
                FROM poll_votes v 
                JOIN poll_options o ON v.option_id = o.option_id 
                WHERE v.poll_id = ? AND v.voter_user_id = ?
            ");
            $voted_option_stmt->execute([$poll['poll_id'], $user_id]);
            $voted_option = $voted_option_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get vote counts for this poll
            $vote_counts_stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_votes,
                    SUM(CASE WHEN v.option_id = ? THEN 1 ELSE 0 END) as option_votes
                FROM poll_votes v 
                WHERE v.poll_id = ?
            ");
            $vote_counts_stmt->execute([$voted_option['option_id'], $poll['poll_id']]);
            $vote_counts = $vote_counts_stmt->fetch(PDO::FETCH_ASSOC);
            
            $poll['voted_option'] = $voted_option['option_text'];
            $poll['total_votes'] = $vote_counts['total_votes'] ?? 0;
            $poll['option_votes'] = $vote_counts['option_votes'] ?? 0;
            $poll['is_active'] = true; // Mark as active but voted
            $voted_polls[] = $poll;
        }
    }

    // Get polls that the resident has voted on (past ended polls)
    $ended_polls_stmt = $conn->prepare("
        SELECT 
            p.poll_id,
            p.question,
            p.end_date,
            p.created_at,
            u.name as created_by,
            o.option_text as voted_option,
            o.option_id as voted_option_id,
            (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.poll_id) as total_votes,
            (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.poll_id AND option_id = o.option_id) as option_votes
        FROM polls p
        JOIN poll_votes v ON p.poll_id = v.poll_id
        JOIN poll_options o ON v.option_id = o.option_id
        JOIN users u ON p.created_by_user_id = u.user_id
        WHERE v.voter_user_id = ? 
        AND p.end_date <= ?
        ORDER BY p.end_date DESC
        LIMIT 10
    ");
    $ended_polls_stmt->execute([$user_id, $current_date]);
    $ended_voted_polls = $ended_polls_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure all required fields are present for ended polls
    foreach ($ended_voted_polls as &$poll) {
        $poll['total_votes'] = $poll['total_votes'] ?? 0;
        $poll['option_votes'] = $poll['option_votes'] ?? 0;
        $poll['is_active'] = false;
    }
    unset($poll); // Break the reference
    
    // Merge both active (voted) and ended voted polls
    $voted_polls = array_merge($voted_polls, $ended_voted_polls);
    
    // Sort voted polls by end date (most recent first)
    usort($voted_polls, function($a, $b) {
        return strtotime($b['end_date']) - strtotime($a['end_date']);
    });

} catch (PDOException $e) {
    error_log("Poll page error: " . $e->getMessage());
    $message = "Error loading polls: " . $e->getMessage();
}

include 'r_layout.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-poll me-2"></i>Resident Polls</h1>
        <p class="text-muted">Participate in the society polls and share your opinion.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Current Polls Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Current Polls
                <?php if (!empty($current_polls)): ?>
                    <span class="badge bg-primary ms-2"><?php echo count($current_polls); ?> available</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($current_polls)): ?>
                <!-- No Active Polls Available -->
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h4>No New Polls Available</h4>
                    <p class="text-muted">There are currently no new polls available for you to vote on.</p>
                </div>
            <?php else: ?>
                <!-- Display All Current Polls -->
                <?php foreach ($current_polls as $current_poll): ?>
                    <div class="poll-card mb-4 border-bottom pb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><?php echo htmlspecialchars($current_poll['question']); ?></h4>
                            <span class="badge bg-success">New</span>
                        </div>
                        <p class="text-muted mb-3">
                            <strong>Created by:</strong> <?php echo htmlspecialchars($current_poll['created_by']); ?> &nbsp; | &nbsp; 
                            <strong>Start:</strong> <?php echo date('d M Y', strtotime($current_poll['created_at'])); ?> &nbsp; | &nbsp; 
                            <strong>End:</strong> <?php echo date('d M Y', strtotime($current_poll['end_date'])); ?>
                        </p>

                        <!-- Voting Form - Show only if user hasn't voted -->
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>You haven't voted in this poll yet!</strong> Please cast your vote below.
                        </div>

                        <form method="POST" class="mb-4">
                            <input type="hidden" name="poll_id" value="<?php echo $current_poll['poll_id']; ?>">
                            <div class="mb-3">
                                <?php foreach ($poll_options[$current_poll['poll_id']] as $option): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="option_id" 
                                               id="option_<?php echo $current_poll['poll_id']; ?>_<?php echo $option['option_id']; ?>" 
                                               value="<?php echo $option['option_id']; ?>" required>
                                        <label class="form-check-label" for="option_<?php echo $current_poll['poll_id']; ?>_<?php echo $option['option_id']; ?>">
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" name="vote" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i>Submit Vote
                            </button>
                        </form>

                        <!-- Current Results (if available) -->
                        <?php if (!empty($poll_results[$current_poll['poll_id']])): ?>
                            <hr>
                            <h6><i class="fas fa-chart-bar me-2"></i>Current Results</h6>
                            <?php 
                            $total_votes = $poll_results[$current_poll['poll_id']][0]['total_votes'] ?? 0;
                            ?>
                            <div class="poll-results">
                                <?php foreach ($poll_results[$current_poll['poll_id']] as $result): ?>
                                    <div class="result-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?php echo htmlspecialchars($result['option_text']); ?></span>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php echo $result['vote_count']; ?> votes
                                                <?php if ($total_votes > 0): ?>
                                                    (<?php echo round(($result['vote_count'] / $total_votes) * 100, 1); ?>%)
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php if ($total_votes > 0): ?>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" 
                                                     role="progressbar" 
                                                     style="width: <?php echo ($result['vote_count'] / $total_votes) * 100; ?>%"
                                                     aria-valuenow="<?php echo ($result['vote_count'] / $total_votes) * 100; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-3 text-center text-muted">
                                    <small>Total votes: <?php echo $total_votes; ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Past Votes Section -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>
                My Past Votes
                <?php if (!empty($voted_polls)): ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($voted_polls); ?> polls</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($voted_polls)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-vote-yea fa-2x text-muted mb-3"></i>
                    <h6 class="text-muted">No Past Votes</h6>
                    <p class="text-muted small">You haven't voted in any polls yet. When you vote in active polls, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($voted_polls as $poll): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border <?php echo isset($poll['is_active']) && $poll['is_active'] ? 'border-warning' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title"><?php echo htmlspecialchars($poll['question']); ?></h6>
                                        <?php if (isset($poll['is_active']) && $poll['is_active']): ?>
                                            <span class="badge bg-warning">Still Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ended</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php if (isset($poll['is_active']) && $poll['is_active']): ?>
                                                Ends: <?php echo date('d M Y', strtotime($poll['end_date'])); ?>
                                            <?php else: ?>
                                                Ended: <?php echo date('d M Y', strtotime($poll['end_date'])); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="badge bg-light text-dark ms-1">
                                            <i class="fas fa-user me-1"></i>
                                            By: <?php echo htmlspecialchars($poll['created_by']); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Your Vote:</strong> 
                                            <span class="text-primary">"<?php echo htmlspecialchars($poll['voted_option']); ?>"</span>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            // Safely display vote counts
                                            $option_votes = $poll['option_votes'] ?? 0;
                                            $total_votes = $poll['total_votes'] ?? 0;
                                            echo $option_votes . '/' . $total_votes . ' votes'; 
                                            ?>
                                        </small>
                                    </div>
                                    <?php 
                                    $option_votes = $poll['option_votes'] ?? 0;
                                    $total_votes = $poll['total_votes'] ?? 0;
                                    if ($total_votes > 0): 
                                    ?>
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar bg-info" 
                                                 style="width: <?php echo ($option_votes / $total_votes) * 100; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo round(($option_votes / $total_votes) * 100, 1); ?>% voted like you
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($voted_polls) >= 10): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">Showing last 10 polls. You've been very active!</small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function(){
    // Client-side validation for poll forms
    $('form').on('submit', function(e){
        const selectedOption = $(this).find('input[name="option_id"]:checked').val();
        if(!selectedOption){
            e.preventDefault();
            alert('Please select an option before voting!');
            return false;
        }
        return true;
    });

    // Auto-hide alert messages after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
</body>
</html>