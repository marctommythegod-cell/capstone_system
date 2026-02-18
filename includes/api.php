<?php
// includes/api.php - API Endpoints

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../email/EmailNotifier.php';

$action = $_GET['action'] ?? '';

if ($action === 'drop_class_card') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    $teacher_id = $_SESSION['user_id'];
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_no = trim($_POST['subject_id'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (!$student_id || !$subject_no) {
        setMessage('error', 'Please select both student and subject.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    // Verify student exists
    $stmt = $pdo->prepare('SELECT id FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        setMessage('error', 'Invalid student.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    // Get subject name
    $stmt = $pdo->prepare('SELECT subject_name FROM subjects WHERE subject_no = ?');
    $stmt->execute([$subject_no]);
    $subject = $stmt->fetch();
    if (!$subject) {
        setMessage('error', 'Invalid subject.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    $subject_name = $subject['subject_name'];
    $drop_date = date('Y-m-d H:i:s');
    $drop_month = date('F Y');
    $drop_year = date('Y');
    
    try {
        // Insert drop record
        $stmt = $pdo->prepare('
            INSERT INTO class_card_drops 
            (teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, drop_month, drop_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $teacher_id,
            $student_id,
            $subject_no,
            $subject_name,
            $remarks,
            'Dropped',
            $drop_date,
            $drop_month,
            $drop_year
        ]);
        
        // Get the inserted ID
        $drop_id = $pdo->lastInsertId();
        
        // Get student and teacher info for email
        $stmt = $pdo->prepare('SELECT student_id, name, email FROM students WHERE id = ?');
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();
        
        // Send email notification
        $emailNotifier = new EmailNotifier();
        $emailData = [
            'student_id' => $student['student_id'],
            'student_name' => $student['name'],
            'subject_no' => $subject_no,
            'subject_name' => $subject_name,
            'remarks' => $remarks,
            'teacher_name' => $teacher['name'],
            'drop_date' => $drop_date
        ];
        
        // Send email to student if they have an email address
        if ($student['email']) {
            $emailNotifier->notifyStudent($student['email'], $emailData);
        }
        
        // Send email to admin
        $emailNotifier->notifyAdmin($emailData);
        
        setMessage('success', 'Class card dropped successfully. Email notifications have been sent.');
    } catch (Exception $e) {
        setMessage('error', 'Error dropping class card: ' . $e->getMessage());
    }
    
    redirect('/SYSTEM/teacher/dashboard.php');
}

if ($action === 'undo_drop') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    $teacher_id = $_SESSION['user_id'];
    $drop_id = intval($_POST['drop_id'] ?? 0);
    
    if (!$drop_id) {
        setMessage('error', 'Invalid drop record.');
        redirect('/SYSTEM/teacher/dashboard.php');
    }
    
    try {
        // Verify the drop belongs to this teacher
        $stmt = $pdo->prepare('SELECT id, student_id, subject_no, subject_name FROM class_card_drops WHERE id = ? AND teacher_id = ?');
        $stmt->execute([$drop_id, $teacher_id]);
        $drop = $stmt->fetch();
        
        if (!$drop) {
            setMessage('error', 'Drop record not found or you do not have permission to undo it.');
            redirect('/SYSTEM/teacher/dashboard.php');
        }
        
        // Delete the drop record
        $stmt = $pdo->prepare('DELETE FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        
        setMessage('success', 'Class card drop has been undone successfully. The student and admin will not be notified.');
    } catch (Exception $e) {
        setMessage('error', 'Error undoing class card drop: ' . $e->getMessage());
    }
    
    redirect('/SYSTEM/teacher/dashboard.php');
}

// If no valid action
redirect('/SYSTEM/index.php');
?>

