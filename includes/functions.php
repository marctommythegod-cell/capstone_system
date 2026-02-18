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

function getStudentName($pdo, $student_id) {
    $stmt = $pdo->prepare('SELECT name FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown';
}

function getSubjectName($pdo, $subject_no) {
    $stmt = $pdo->prepare('SELECT subject_name FROM subjects WHERE subject_no = ?');
    $stmt->execute([$subject_no]);
    $result = $stmt->fetch();
    return $result ? $result['subject_name'] : 'Unknown Subject';
}

function formatDate($date) {
    return date('F d, Y H:i', strtotime($date));
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
?>
