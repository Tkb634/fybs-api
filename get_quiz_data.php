<?php
session_start();
include "config.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Quiz ID required']);
    exit();
}

$quiz_id = (int)$_GET['id'];

// Get quiz details
$quiz_sql = "SELECT * FROM bible_quizzes WHERE id = ? AND is_active = 1";
$stmt = $conn->prepare($quiz_sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz_result = $stmt->get_result();

if ($quiz_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Quiz not found']);
    exit();
}

$quiz = $quiz_result->fetch_assoc();

// Get questions
$questions_sql = "SELECT * FROM bible_quiz_questions WHERE quiz_id = ? ORDER BY order_index, id";
$stmt = $conn->prepare($questions_sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions_result = $stmt->get_result();

$questions = [];
while ($question = $questions_result->fetch_assoc()) {
    $options = json_decode($question['options'], true);
    $questions[] = [
        'id' => $question['id'],
        'question' => $question['question'],
        'options' => $options,
        'correct_answer' => $question['correct_answer'],
        'points' => $question['points']
    ];
}

echo json_encode([
    'success' => true,
    'quiz' => [
        'id' => $quiz['id'],
        'title' => $quiz['title'],
        'questions' => $questions,
        'time_limit' => $quiz['time_limit']
    ]
]);
?>