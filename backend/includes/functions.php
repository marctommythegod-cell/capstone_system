<?php
// includes/functions.php - Utility Functions

function securePassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function getUserName($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown';
}

function getUserInfo($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT name, department, department_id, role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result : ['name' => 'Unknown', 'department' => '', 'department_id' => NULL, 'role' => ''];
}

function getStudentName($pdo, $student_id) {
    $stmt = $pdo->prepare('SELECT name FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown';
}

function getSubjectName($pdo, $subject_code) {
    $stmt = $pdo->prepare('SELECT subject_name FROM subjects WHERE subject_code = ?');
    $stmt->execute([$subject_code]);
    $result = $stmt->fetch();
    return $result ? $result['subject_name'] : 'Unknown Subject';
}

function formatDate($date) {
    $tz = new DateTimeZone('Asia/Manila');
    $dt = new DateTime($date, $tz);
    return $dt->format('F d, Y h:i A');
}

function getMonthYear($date) {
    return date('F Y', strtotime($date));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setMessage($type, $message) {
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Pagination Functions
function getPaginationData($total_items, $items_per_page = 10) {
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = min($current_page, max(1, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'limit' => $items_per_page,
        'total_items' => $total_items
    ];
}

function renderPaginationControls($pagination_data, $base_url = '') {
    $current = $pagination_data['current_page'];
    $total = $pagination_data['total_pages'];
    
    if ($total <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination-container" aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($current > 1) {
        $prev_page = $current - 1;
        $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($base_url) . '?page=' . $prev_page . '" class="pagination-link">← Previous</a></li>';
    } else {
        $html .= '<li class="pagination-item disabled"><span class="pagination-link">← Previous</span></li>';
    }
    
    // Page numbers
    $start_page = max(1, $current - 2);
    $end_page = min($total, $current + 2);
    
    if ($start_page > 1) {
        $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($base_url) . '?page=1" class="pagination-link">1</a></li>';
        if ($start_page > 2) {
            $html .= '<li class="pagination-item disabled"><span class="pagination-link">...</span></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current) {
            $html .= '<li class="pagination-item active"><span class="pagination-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($base_url) . '?page=' . $i . '" class="pagination-link">' . $i . '</a></li>';
        }
    }
    
    if ($end_page < $total) {
        if ($end_page < $total - 1) {
            $html .= '<li class="pagination-item disabled"><span class="pagination-link">...</span></li>';
        }
        $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($base_url) . '?page=' . $total . '" class="pagination-link">' . $total . '</a></li>';
    }
    
    // Next button
    if ($current < $total) {
        $next_page = $current + 1;
        $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($base_url) . '?page=' . $next_page . '" class="pagination-link">Next →</a></li>';
    } else {
        $html .= '<li class="pagination-item disabled"><span class="pagination-link">Next →</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// Generate unique 8-digit Student Number
function generateStudentNumber($pdo) {
    $max_attempts = 100;
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $student_number = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare('SELECT id FROM students WHERE student_id = ?');
        $stmt->execute([$student_number]);
        
        if (!$stmt->fetch()) {
            return $student_number;
        }
    }
    throw new Exception('Could not generate unique student number after ' . $max_attempts . ' attempts.');
}

// Generate unique 8-digit Teacher Number
function generateTeacherNumber($pdo) {
    $max_attempts = 100;
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $teacher_number = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare('SELECT id FROM users WHERE teacher_id = ? AND role = "teacher"');
        $stmt->execute([$teacher_number]);
        
        if (!$stmt->fetch()) {
            return $teacher_number;
        }
    }
    throw new Exception('Could not generate unique teacher number after ' . $max_attempts . ' attempts.');
}
?>

