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
        // Insert drop record with Pending status
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
            'Pending',
            $drop_date,
            $drop_month,
            $drop_year
        ]);
        
        // Get the inserted ID
        $drop_id = $pdo->lastInsertId();
        
        setMessage('success', 'Class card drop request submitted for admin approval. You will be notified once it has been reviewed.');
    } catch (Exception $e) {
        setMessage('error', 'Error submitting class card drop: ' . $e->getMessage());
    }
    
    redirect('/SYSTEM/teacher/dashboard.php');
}

if ($action === 'approve_drop') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/SYSTEM/admin/dashboard.php');
    }
    
    if ($_SESSION['user_role'] !== 'admin') {
        setMessage('error', 'Unauthorized action.');
        redirect('/SYSTEM/admin/dashboard.php');
    }
    
    $admin_id = $_SESSION['user_id'];
    $drop_id = intval($_POST['drop_id'] ?? 0);
    
    if (!$drop_id) {
        setMessage('error', 'Invalid drop record.');
        redirect('/SYSTEM/admin/dropped_cards.php');
    }
    
    try {
        // Get drop details
        $stmt = $pdo->prepare('SELECT * FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        $drop = $stmt->fetch();
        
        if (!$drop) {
            setMessage('error', 'Drop record not found.');
            redirect('/SYSTEM/admin/dropped_cards.php');
        }
        
        // Update status to Dropped and set approval info
        $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ?, approved_by = ?, approved_date = NOW() WHERE id = ?');
        $stmt->execute(['Dropped', $admin_id, $drop_id]);
        
        // Get student and teacher info for email
        $stmt = $pdo->prepare('SELECT student_id, name, email FROM students WHERE id = ?');
        $stmt->execute([$drop['student_id']]);
        $student = $stmt->fetch();
        
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$drop['teacher_id']]);
        $teacher = $stmt->fetch();
        
        // Send approval notification emails
        $emailNotifier = new EmailNotifier();
        $emailData = [
            'student_id' => $student['student_id'],
            'student_name' => $student['name'],
            'subject_no' => $drop['subject_no'],
            'subject_name' => $drop['subject_name'],
            'remarks' => $drop['remarks'],
            'teacher_name' => $teacher['name'],
            'drop_date' => $drop['drop_date'],
            'approved_date' => date('Y-m-d H:i:s')
        ];
        
        // Send email to student if they have an email address
        if ($student['email']) {
            $emailNotifier->notifyStudentApproved($student['email'], $emailData);
        }
        
        // Send email to teacher
        if ($teacher['email']) {
            $emailNotifier->notifyTeacherApproved($teacher['email'], $emailData);
        }
        
        setMessage('success', 'Class card drop has been approved. Student and teacher have been notified.');
    } catch (Exception $e) {
        setMessage('error', 'Error approving class card drop: ' . $e->getMessage());
    }
    
    redirect('/SYSTEM/admin/dropped_cards.php');
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
            setMessage('error', message: 'Drop record not found or you do not have permission to undo it.');
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

