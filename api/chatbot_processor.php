<?php
session_start();
header('Content-Type: application/json');
include "../config.php";

$user_id = $_SESSION['user_id'] ?? 0;

// Emergency keywords that trigger immediate human help
$emergency_keywords = [
    'suicide', 'kill myself', 'end my life', 'want to die', 'taking pills',
    'overdose', 'cutting', 'bleeding', 'emergency', 'help me now', '911',
    'hurting myself', 'self harm', 'danger', 'crisis', 'immediate help'
];

// Contextual responses based on keywords
$response_patterns = [
    'anxiety' => [
        'keywords' => ['anxious', 'anxiety', 'worry', 'worried', 'stress', 'stressed', 'panic', 'nervous'],
        'responses' => [
            "I understand you're feeling anxious. Let's try some deep breathing together. Inhale for 4 seconds... hold for 7... exhale for 8.",
            "Anxiety can be overwhelming. Remember Philippians 4:6-7: 'Do not be anxious about anything...' Would you like to pray together?",
            "It's okay to feel anxious sometimes. Try focusing on something you can control right now. Would you like some grounding exercises?"
        ]
    ],
    'depression' => [
        'keywords' => ['depressed', 'depression', 'sad', 'hopeless', 'empty', 'worthless', 'no reason', 'tired'],
        'responses' => [
            "I hear that you're feeling low. Remember that your feelings are valid. Psalms 34:18 says God is close to the brokenhearted.",
            "Depression can make everything feel heavy. Have you considered speaking with a counselor? I can connect you with resources.",
            "You're not alone in this. Many people experience these feelings. Let's explore some small steps you can take today."
        ]
    ],
    'addiction' => [
        'keywords' => ['addiction', 'addicted', 'drinking', 'drugs', 'sober', 'sobriety', 'relapse', 'craving'],
        'responses' => [
            "Recovery is a journey, not a destination. Take it one day at a time. Would you like to talk about your support system?",
            "Addiction struggles are real. Remember 1 Corinthians 10:13 about God providing a way out. Have you tried the Addiction Breaker program?",
            "Cravings can be powerful. Try distracting yourself with a different activity or calling a support person. You're stronger than you think."
        ]
    ],
    'prayer' => [
        'keywords' => ['pray', 'prayer', 'praying', 'God', 'Jesus', 'Lord', 'amen'],
        'responses' => [
            "Let's pray together: Dear Lord, please provide peace and strength to {{name}} in this situation. Amen.",
            "Prayer is powerful. Would you like me to suggest a specific Bible verse to meditate on today?",
            "I'm here to pray with you. You can also post your prayer request in the GYC prayer room for community support."
        ]
    ],
    'loneliness' => [
        'keywords' => ['lonely', 'alone', 'isolated', 'no friends', 'no one', 'by myself'],
        'responses' => [
            "Loneliness can be painful. Have you considered joining the GYC chat rooms? There are many youth ready to connect.",
            "Remember that God is always with you. Psalm 23:4 - 'Even though I walk through the darkest valley, I will fear no evil, for you are with me.'",
            "Community is important. The CYIC fellowship groups meet weekly. Would you like information about joining?"
        ]
    ]
];

// Get resources from database
function getResource($category, $conn) {
    $sql = "SELECT * FROM counseling_resources WHERE category = '$category' AND is_active = 1 ORDER BY RAND() LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function containsAny($string, $array) {
    foreach($array as $word) {
        if (stripos($string, $word) !== false) {
            return true;
        }
    }
    return false;
}

function containsAll($string, $array) {
    foreach($array as $word) {
        if (stripos($string, $word) === false) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message = strtolower(trim($data['message']));
    $session_id = $data['session_id'];
    $user_id = $data['user_id'];
    
    // Check for emergency
    $is_emergency = 0;
    foreach ($emergency_keywords as $keyword) {
        if (stripos($message, $keyword) !== false) {
            $is_emergency = 1;
            break;
        }
    }
    
    // Get user's name for personalization
    $user_name = "friend";
    $user_sql = "SELECT full_name FROM users WHERE id = $user_id";
    $user_result = mysqli_query($conn, $user_sql);
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_row = mysqli_fetch_assoc($user_result);
        $user_name = explode(' ', $user_row['full_name'])[0];
    }
    
    // Determine response based on message content
    $response = "";
    $category = "general";
    
    if ($is_emergency) {
        $response = "🚨 EMERGENCY ALERT: I detect you may be in crisis. Please immediately call 911 or one of the emergency contacts. You can also press the emergency button for immediate help. Your life is valuable and there are people who want to help you.";
    } else {
        // Check which pattern matches
        foreach ($response_patterns as $cat => $pattern) {
            if (containsAny($message, $pattern['keywords'])) {
                $category = $cat;
                $responses = $pattern['responses'];
                $response = $responses[array_rand($responses)];
                $response = str_replace('{{name}}', $user_name, $response);
                
                // Add resource if available
                $resource = getResource($category, $conn);
                if ($resource) {
                    $response .= "\n\n📚 Resource: " . $resource['title'] . " - " . substr($resource['content'], 0, 100) . "...";
                }
                break;
            }
        }
        
        // Default response if no pattern matches
        if (empty($response)) {
            $default_responses = [
                "Thank you for sharing. How are you feeling about this situation?",
                "I'm here to listen. Could you tell me more about what's on your mind?",
                "That sounds challenging. Would you like to explore this further or try some coping strategies?",
                "I understand. Sometimes just talking about things can help. What support do you have around you?",
                "Thank you for trusting me with this. Remember, God cares about every detail of your life."
            ];
            $response = $default_responses[array_rand($default_responses)];
        }
    }
    
    // Add scripture suggestion for spiritual topics
    if (stripos($message, 'god') !== false || stripos($message, 'bible') !== false || stripos($message, 'faith') !== false) {
        $scriptures = [
            "Philippians 4:13 - 'I can do all things through Christ who strengthens me.'",
            "Jeremiah 29:11 - 'For I know the plans I have for you, declares the LORD...'",
            "Psalm 46:1 - 'God is our refuge and strength, an ever-present help in trouble.'",
            "Matthew 11:28 - 'Come to me, all you who are weary and burdened, and I will give you rest.'"
        ];
        $response .= "\n\n📖 Consider this verse: " . $scriptures[array_rand($scriptures)];
    }
    
    // Save conversation to database
    $clean_message = mysqli_real_escape_string($conn, $message);
    $clean_response = mysqli_real_escape_string($conn, $response);
    
    $save_sql = "INSERT INTO ai_counseling_conversations (user_id, session_id, user_message, bot_response, is_emergency) 
                 VALUES ($user_id, '$session_id', '$clean_message', '$clean_response', $is_emergency)";
    mysqli_query($conn, $save_sql);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'is_emergency' => $is_emergency,
        'category' => $category
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>