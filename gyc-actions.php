<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_prayer':
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $is_anonymous = $_POST['anonymous'] == '1' ? 1 : 0;
        
        $sql = "INSERT INTO prayer_requests (user_id, title, content, is_anonymous) 
                VALUES ($user_id, '$title', '$content', $is_anonymous)";
        
        if (mysqli_query($conn, $sql)) {
            // Log activity - FIXED: Use admin_id instead of user_id
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                       VALUES ($user_id, 'add_prayer', 'Added prayer request: $title')";
            mysqli_query($conn, $log_sql);
            
            echo json_encode(['success' => true, 'message' => 'Prayer request added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'add_testimony':
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        
        $sql = "INSERT INTO testimonies (user_id, title, content, category) 
                VALUES ($user_id, '$title', '$content', '$category')";
        
        if (mysqli_query($conn, $sql)) {
            // Log activity - FIXED: Use admin_id instead of user_id
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                       VALUES ($user_id, 'add_testimony', 'Added testimony: $title')";
            mysqli_query($conn, $log_sql);
            
            echo json_encode(['success' => true, 'message' => 'Testimony added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'pray_for':
        $prayer_id = intval($_POST['prayer_id']);
        
        // Check if already prayed
        $check_sql = "SELECT id FROM prayer_interactions 
                     WHERE prayer_id = $prayer_id AND user_id = $user_id AND interaction_type = 'prayed'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Already prayed for this request']);
            exit();
        }
        
        // Add interaction
        $sql = "INSERT INTO prayer_interactions (prayer_id, user_id, interaction_type) 
                VALUES ($prayer_id, $user_id, 'prayed')";
        
        if (mysqli_query($conn, $sql)) {
            // Update prayer count
            $update_sql = "UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = $prayer_id";
            mysqli_query($conn, $update_sql);
            
            echo json_encode(['success' => true, 'message' => 'Prayed for request']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'encourage':
        $prayer_id = intval($_POST['prayer_id']);
        
        // Check if already encouraged
        $check_sql = "SELECT id FROM prayer_interactions 
                     WHERE prayer_id = $prayer_id AND user_id = $user_id AND interaction_type = 'encouraged'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Already encouraged this request']);
            exit();
        }
        
        // Add interaction
        $sql = "INSERT INTO prayer_interactions (prayer_id, user_id, interaction_type) 
                VALUES ($prayer_id, $user_id, 'encouraged')";
        
        if (mysqli_query($conn, $sql)) {
            // Update encourage count
            $update_sql = "UPDATE prayer_requests SET encourage_count = encourage_count + 1 WHERE id = $prayer_id";
            mysqli_query($conn, $update_sql);
            
            echo json_encode(['success' => true, 'message' => 'Encouragement sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'like_testimony':
        $testimony_id = intval($_POST['testimony_id']);
        
        // Check if already liked
        $check_sql = "SELECT id FROM testimony_likes 
                     WHERE testimony_id = $testimony_id AND user_id = $user_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Already blessed this testimony']);
            exit();
        }
        
        // Add like
        $sql = "INSERT INTO testimony_likes (testimony_id, user_id) 
                VALUES ($testimony_id, $user_id)";
        
        if (mysqli_query($conn, $sql)) {
            // Update like count
            $update_sql = "UPDATE testimonies SET like_count = like_count + 1 WHERE id = $testimony_id";
            mysqli_query($conn, $update_sql);
            
            echo json_encode(['success' => true, 'message' => 'Testimony blessed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'send_message':
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit();
        }
        
        $sql = "INSERT INTO gyc_messages (user_id, message, room) 
                VALUES ($user_id, '$message', 'general')";
        
        if (mysqli_query($conn, $sql)) {
            // Log activity - FIXED: Use admin_id instead of user_id
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                       VALUES ($user_id, 'send_message', 'Sent GYC message')";
            mysqli_query($conn, $log_sql);
            
            echo json_encode(['success' => true, 'message' => 'Message sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'get_stats':
        // Get prayer count
        $prayer_sql = "SELECT COUNT(*) as count FROM prayer_requests";
        $prayer_result = mysqli_query($conn, $prayer_sql);
        $prayer_count = $prayer_result ? mysqli_fetch_assoc($prayer_result)['count'] : 0;
        
        // Get testimony count
        $testimony_sql = "SELECT COUNT(*) as count FROM testimonies";
        $testimony_result = mysqli_query($conn, $testimony_sql);
        $testimony_count = $testimony_result ? mysqli_fetch_assoc($testimony_result)['count'] : 0;
        
        // Get answered prayers count
        $answered_sql = "SELECT COUNT(*) as count FROM prayer_requests WHERE status = 'answered'";
        $answered_result = mysqli_query($conn, $answered_sql);
        $answered_count = $answered_result ? mysqli_fetch_assoc($answered_result)['count'] : 0;
        
        // Get online users - FIXED: Use admin_id instead of user_id
        $online_sql = "SELECT COUNT(DISTINCT admin_id) as count FROM admin_logs 
                      WHERE created_at >= NOW() - INTERVAL 5 MINUTE";
        $online_result = mysqli_query($conn, $online_sql);
        $online_count = $online_result ? mysqli_fetch_assoc($online_result)['count'] : 1;
        
        echo json_encode([
            'success' => true,
            'prayers' => $prayer_count,
            'testimonies' => $testimony_count,
            'answered' => $answered_count,
            'online' => $online_count
        ]);
        break;
        
    case 'get_prayers':
        // Load prayer requests for AJAX
        $prayers_sql = "SELECT p.*, u.full_name FROM prayer_requests p 
                       LEFT JOIN users u ON p.user_id = u.id 
                       ORDER BY p.created_at DESC LIMIT 10";
        $prayers_result = mysqli_query($conn, $prayers_sql);
        
        $html = '';
        if (mysqli_num_rows($prayers_result) > 0) {
            while ($prayer = mysqli_fetch_assoc($prayers_result)) {
                $is_anonymous = $prayer['is_anonymous'] == 1;
                $author_name = $is_anonymous ? 'Anonymous' : htmlspecialchars($prayer['full_name'] ?? 'User');
                $time_ago = timeAgo($prayer['created_at']);
                $status_class = $prayer['status'] == 'answered' ? 'answered' : 'pending';
                $status_text = $prayer['status'] == 'answered' ? 'Answered' : 'Needs Prayer';
                
                // Get interaction counts
                $pray_count_sql = "SELECT COUNT(*) as count FROM prayer_interactions 
                                  WHERE prayer_id = {$prayer['id']} AND interaction_type = 'prayed'";
                $pray_result = mysqli_query($conn, $pray_count_sql);
                $pray_count = $pray_result ? mysqli_fetch_assoc($pray_result)['count'] : 0;
                
                $encourage_count_sql = "SELECT COUNT(*) as count FROM prayer_interactions 
                                       WHERE prayer_id = {$prayer['id']} AND interaction_type = 'encouraged'";
                $encourage_result = mysqli_query($conn, $encourage_count_sql);
                $encourage_count = $encourage_result ? mysqli_fetch_assoc($encourage_result)['count'] : 0;
                
                $html .= '
                <div class="content-card prayer" data-id="' . $prayer['id'] . '">
                    <div class="content-header">
                        <div>
                            <div class="content-title">' . htmlspecialchars($prayer['title']) . '</div>
                            <div class="content-meta">By: ' . $author_name . ' • ' . $time_ago . '</div>
                        </div>
                        <span class="badge-status ' . $status_class . '">' . $status_text . '</span>
                    </div>
                    <div class="content-body">
                        ' . nl2br(htmlspecialchars($prayer['content'])) . '
                        ' . ($prayer['praise_report'] ? '
                        <div class="alert alert-success mt-3 mb-0">
                            <strong>🙏 Praise Report:</strong> ' . htmlspecialchars($prayer['praise_report']) . '
                        </div>' : '') . '
                    </div>
                    <div class="content-footer">
                        <div class="content-actions">
                            <button class="btn-action pray" onclick="handlePray(' . $prayer['id'] . ')">
                                <i class="fas fa-hands-praying"></i> Pray (' . $pray_count . ')
                            </button>
                            <button class="btn-action encourage" onclick="handleEncourage(' . $prayer['id'] . ')">
                                <i class="fas fa-heart"></i> Encourage (' . $encourage_count . ')
                            </button>
                        </div>
                        ' . ($prayer['comment_count'] > 0 ? '
                        <button class="btn-action" onclick="showComments(' . $prayer['id'] . ')">
                            <i class="fas fa-comment"></i> Comments (' . $prayer['comment_count'] . ')
                        </button>' : '') . '
                    </div>
                </div>';
            }
        } else {
            $html = '<div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-hands-praying fa-3x text-muted"></i>
                        </div>
                        <p class="text-muted">No prayer requests yet. Be the first to share!</p>
                    </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        break;
        
    case 'get_testimonies':
        // Load testimonies for AJAX
        $testimonies_sql = "SELECT t.*, u.full_name FROM testimonies t 
                           JOIN users u ON t.user_id = u.id 
                           ORDER BY t.created_at DESC LIMIT 10";
        $testimonies_result = mysqli_query($conn, $testimonies_sql);
        
        $html = '';
        if (mysqli_num_rows($testimonies_result) > 0) {
            while ($testimony = mysqli_fetch_assoc($testimonies_result)) {
                $time_ago = timeAgo($testimony['created_at']);
                
                // Get like count
                $like_count_sql = "SELECT COUNT(*) as count FROM testimony_likes 
                                  WHERE testimony_id = {$testimony['id']}";
                $like_result = mysqli_query($conn, $like_count_sql);
                $like_count = $like_result ? mysqli_fetch_assoc($like_result)['count'] : 0;
                
                $html .= '
                <div class="content-card testimony" data-id="' . $testimony['id'] . '">
                    <div class="content-header">
                        <div>
                            <div class="content-title">' . htmlspecialchars($testimony['title']) . '</div>
                            <div class="content-meta">By: ' . htmlspecialchars($testimony['full_name']) . ' • ' . $time_ago . '</div>
                        </div>
                        <span class="badge bg-warning">' . ucfirst($testimony['category']) . '</span>
                    </div>
                    <div class="content-body">
                        ' . nl2br(htmlspecialchars($testimony['content'])) . '
                    </div>
                    <div class="content-footer">
                        <div class="content-actions">
                            <button class="btn-action like" onclick="handleLike(' . $testimony['id'] . ')">
                                <i class="fas fa-heart"></i> Bless This (' . $like_count . ')
                            </button>
                            <button class="btn-action" onclick="shareTestimony(' . $testimony['id'] . ')">
                                <i class="fas fa-share"></i> Share
                            </button>
                        </div>
                    </div>
                </div>';
            }
        } else {
            $html = '<div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-star fa-3x text-muted"></i>
                        </div>
                        <p class="text-muted">No testimonies yet. Share your story of God\'s faithfulness!</p>
                    </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        break;
        
    case 'get_messages':
        // Load messages for AJAX
        $messages_sql = "SELECT m.*, u.full_name FROM gyc_messages m 
                        JOIN users u ON m.user_id = u.id 
                        ORDER BY m.created_at DESC LIMIT 50";
        $messages_result = mysqli_query($conn, $messages_sql);
        
        $html = '';
        if (mysqli_num_rows($messages_result) > 0) {
            $messages = [];
            while ($msg = mysqli_fetch_assoc($messages_result)) {
                $messages[] = $msg;
            }
            $messages = array_reverse($messages); // Show oldest first
            
            foreach ($messages as $msg) {
                $is_self = $msg['user_id'] == $user_id;
                $time_ago = timeAgo($msg['created_at']);
                
                $html .= '
                <div class="chat-message ' . ($is_self ? 'self' : '') . '">
                    <div class="chat-message-content">
                        <div class="chat-message-header">
                            <span class="chat-message-sender">' . ($is_self ? 'You' : htmlspecialchars($msg['full_name'])) . '</span>
                            <span class="chat-message-time">' . $time_ago . '</span>
                        </div>
                        <p class="mb-0">' . htmlspecialchars($msg['message']) . '</p>
                    </div>
                </div>';
            }
        } else {
            $html = '<div class="text-center py-5">
                        <p class="text-muted">No messages yet. Start the conversation!</p>
                    </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        break;
        
    case 'add_confession':
        // This would create a new table for confessions
        // For now, just return success
        echo json_encode(['success' => true, 'message' => 'Confession feature coming soon']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $time_difference = time() - $time;
    
    if ($time_difference < 1) { return 'just now'; }
    
    $condition = [
        12 * 30 * 24 * 60 * 60  => 'year',
        30 * 24 * 60 * 60       => 'month',
        24 * 60 * 60            => 'day',
        60 * 60                 => 'hour',
        60                      => 'minute',
        1                       => 'second'
    ];
    
    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}
?>