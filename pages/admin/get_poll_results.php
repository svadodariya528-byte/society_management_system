<?php
// get_poll_results.php
session_start();
require_once "../../db_connect.php";

// Ensure $conn is PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die(json_encode(['success' => false, 'message' => 'Database connection error']));
}

header('Content-Type: application/json; charset=utf-8');

$pollId = (int)($_GET['poll_id'] ?? 0);

if ($pollId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
    exit;
}

try {
    // Fetch poll question and basic info
    $stmt = $conn->prepare("SELECT question, created_at, end_date FROM polls WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$poll) {
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit;
    }

    // Fetch poll options with vote counts
    $stmt = $conn->prepare("
        SELECT 
            po.option_id,
            po.option_text, 
            COUNT(pv.vote_id) as vote_count
        FROM poll_options po
        LEFT JOIN poll_votes pv ON po.option_id = pv.option_id
        WHERE po.poll_id = ?
        GROUP BY po.option_id
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$pollId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total votes and prepare data
    $totalVotes = 0;
    $labels = [];
    $votes = [];
    
    foreach ($results as $result) {
        $labels[] = $result['option_text'];
        $votes[] = (int)$result['vote_count'];
        $totalVotes += (int)$result['vote_count'];
    }

    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'labels' => $labels,
            'votes' => $votes,
            'totalVotes' => $totalVotes,
            'question' => $poll['question'],
            'created_at' => $poll['created_at'],
            'end_date' => $poll['end_date']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching poll results: ' . $e->getMessage()]);
}
?>