<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

// Response array
$response = [
    'success' => false,
    'message' => 'Unknown error occurred.',
    'participants' => 0
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to join a group buy.');
    }

    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['group_buy_id'])) {
        throw new Exception('Invalid group buy ID.');
    }

    $group_buy_id = intval($data['group_buy_id']);
    $user_id = intval($_SESSION['user_id']);

    // Start transaction
    mysqli_begin_transaction($conn);

    // Check if group buy exists and is active
    $check_group_buy_query = "
    SELECT group_buy_id, max_participants, current_participants 
    FROM group_buys 
    WHERE group_buy_id = ? AND status = 'active' AND end_datetime > NOW()
    FOR UPDATE";
    
    $stmt = mysqli_prepare($conn, $check_group_buy_query);
    mysqli_stmt_bind_param($stmt, 'i', $group_buy_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Group buy is no longer available.');
    }

    $group_buy = mysqli_fetch_assoc($result);

    // Check if spots are available
    if ($group_buy['current_participants'] >= $group_buy['max_participants']) {
        throw new Exception('Group buy is full.');
    }

    // Check if user already joined
    $check_participant_query = "
    SELECT 1 FROM group_buy_participants 
    WHERE group_buy_id = ? AND user_id = ?";
    
    $stmt = mysqli_prepare($conn, $check_participant_query);
    mysqli_stmt_bind_param($stmt, 'ii', $group_buy_id, $user_id);
    mysqli_stmt_execute($stmt);
    $participant_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($participant_result) > 0) {
        throw new Exception('You have already joined this group buy.');
    }

    // Insert participant
    $insert_participant_query = "
    INSERT INTO group_buy_participants (group_buy_id, user_id) 
    VALUES (?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_participant_query);
    mysqli_stmt_bind_param($stmt, 'ii', $group_buy_id, $user_id);
    mysqli_stmt_execute($stmt);

    // Update participants count
    $update_participants_query = "
    UPDATE group_buys 
    SET current_participants = current_participants + 1 
    WHERE group_buy_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_participants_query);
    mysqli_stmt_bind_param($stmt, 'i', $group_buy_id);
    mysqli_stmt_execute($stmt);

    // Commit transaction
    mysqli_commit($conn);

    $response['success'] = true;
    $response['message'] = 'Successfully joined group buy!';
    $response['participants'] = $group_buy['current_participants'] + 1;

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    $response['message'] = $e->getMessage();
    error_log($e->getMessage());
} finally {
    // Close statement and connection
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);

    // Send JSON response
    echo json_encode($response);
}
?>
