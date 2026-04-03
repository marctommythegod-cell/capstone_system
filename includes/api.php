<?php
// includes/api.php - API Endpoints

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../email/EmailNotifier.php';

$action = $_GET['action'] ?? '';

// Check if student-subject has active drop request
if ($action === 'check_active_drop') {
    header('Content-Type: application/json');
    
    if ($_SESSION['user_role'] !== 'teacher') {
        echo json_encode(['has_active_drop' => false]);
        exit;
    }
    
    $student_id = intval($_GET['student_id'] ?? 0);
    $subject_no = trim($_GET['subject_no'] ?? '');
    
    if (!$student_id || !$subject_no) {
        echo json_encode(['has_active_drop' => false]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare('
            SELECT id, status FROM class_card_drops 
            WHERE student_id = ? AND subject_no = ? 
            AND status IN ("Pending", "Dropped")
            AND cancelled_date IS NULL
            LIMIT 1
        ');
        $stmt->execute([$student_id, $subject_no]);
        $activeDrop = $stmt->fetch();
        
        if ($activeDrop) {
            echo json_encode([
                'has_active_drop' => true,
                'status' => $activeDrop['status']
            ]);
        } else {
            echo json_encode(['has_active_drop' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['has_active_drop' => false]);
    }
    exit;
}

if ($action === 'drop_class_card') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    $teacher_id = $_SESSION['user_id'];
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_no = trim($_POST['subject_id'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (!$student_id || !$subject_no) {
        setMessage('error', 'Please select both student and subject.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    // Verify student exists
    $stmt = $pdo->prepare('SELECT id FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        setMessage('error', 'Invalid student.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    // Get subject name
    $stmt = $pdo->prepare('SELECT subject_name FROM subjects WHERE subject_no = ?');
    $stmt->execute([$subject_no]);
    $subject = $stmt->fetch();
    if (!$subject) {
        setMessage('error', 'Invalid subject.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    $subject_name = $subject['subject_name'];
    $drop_date = date('Y-m-d H:i:s');
    $drop_month = date('F Y');
    $drop_year = date('Y');
    
    // Check if student has already dropped this subject with Pending or Dropped status (per student validation)
    // Cannot drop again if status is still Pending or Dropped
    // If status is Undropped (which means it's been processed), no record will match and student can drop again
    $stmt = $pdo->prepare('
        SELECT id, status FROM class_card_drops 
        WHERE student_id = ? AND subject_no = ? 
        AND status IN ("Pending", "Dropped")
        LIMIT 1
    ');
    $stmt->execute([$student_id, $subject_no]);
    $existing_drop = $stmt->fetch();
    
    if ($existing_drop) {
        setMessage('error', 'This subject is already in the system with ' . $existing_drop['status'] . ' status. You cannot drop it again until it has been undropped.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    try {
        // Set deadline to end of the same day (11:59 PM)
        $deadline = date('Y-m-d 23:59:59');
        
        // Insert drop record with Pending status and deadline
        $stmt = $pdo->prepare('
            INSERT INTO class_card_drops 
            (teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, deadline, drop_month, drop_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $teacher_id,
            $student_id,
            $subject_no,
            $subject_name,
            $remarks,
            'Pending',
            $drop_date,
            $deadline,
            $drop_month,
            $drop_year
        ]);
        
        // Get the inserted ID
        $drop_id = $pdo->lastInsertId();
        
        setMessage('success', 'Class card drop request submitted for admin approval. You will be notified once it has been reviewed.');
    } catch (Exception $e) {
        setMessage('error', 'Error submitting class card drop: ' . $e->getMessage());
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
}

if ($action === 'approve_drop') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php');
    }
    
    if ($_SESSION['user_role'] !== 'admin') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php');
    }
    
    $admin_id = $_SESSION['user_id'];
    $drop_id = intval($_POST['drop_id'] ?? 0);
    
    if (!$drop_id) {
        setMessage('error', 'Invalid drop record.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
    }
    
    try {
        // Get drop details
        $stmt = $pdo->prepare('SELECT * FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        $drop = $stmt->fetch();
        
        if (!$drop) {
            setMessage('error', 'Drop record not found.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
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
        if ($student && $student['email']) {
            error_log("Attempting to send approval email to student: " . $student['email']);
            $emailNotifier->notifyStudentApproved($student['email'], $emailData);
        } else {
            error_log("Student has no email address: " . ($student['student_id'] ?? 'unknown'));
        }
        
        // Send email to teacher
        if ($teacher && $teacher['email']) {
            error_log("Attempting to send approval email to teacher: " . $teacher['email']);
            $emailNotifier->notifyTeacherApproved($teacher['email'], $emailData);
        } else {
            error_log("Teacher has no email address");
        }
        
        setMessage('success', 'Class card drop has been approved. Student and teacher have been notified.');
    } catch (Exception $e) {
        error_log("Exception in approve_drop: " . $e->getMessage());
        setMessage('error', 'Error approving class card drop: ' . $e->getMessage());
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
}

if ($action === 'cancel_drop') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    $teacher_id = $_SESSION['user_id'];
    $drop_id = intval($_POST['drop_id'] ?? 0);
    
    if (!$drop_id) {
        setMessage('error', 'Invalid drop record.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
    }
    
    try {
        $stmt = $pdo->prepare('SELECT id FROM class_card_drops WHERE id = ? AND teacher_id = ? AND status = ?');
        $stmt->execute([$drop_id, $teacher_id, 'Pending']);
        $drop = $stmt->fetch();
        
        if (!$drop) {
            setMessage('error', 'Drop request not found or cannot be cancelled.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
        }
        
        $stmt = $pdo->prepare('DELETE FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        
        setMessage('success', 'Drop request has been cancelled successfully.');
    } catch (Exception $e) {
        setMessage('error', 'Error cancelling drop request: ' . $e->getMessage());
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php');
}

if ($action === 'undo_drop') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php');
    }
    
    $teacher_id = $_SESSION['user_id'];
    $drop_id = intval($_POST['drop_id'] ?? 0);
    
    if (!$drop_id) {
        setMessage('error', 'Invalid drop record.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php');
    }
    
    try {
        // Verify the drop belongs to this teacher
        $stmt = $pdo->prepare('SELECT id, student_id, subject_no, subject_name FROM class_card_drops WHERE id = ? AND teacher_id = ?');
        $stmt->execute([$drop_id, $teacher_id]);
        $drop = $stmt->fetch();
        
        if (!$drop) {
            setMessage('error', 'Drop record not found or you do not have permission to undo it.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php');
        }
        
        // Delete the drop record
        $stmt = $pdo->prepare('DELETE FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        
        setMessage('success', 'Class card drop has been undone successfully. The student and admin will not be notified.');
    } catch (Exception $e) {
        setMessage('error', 'Error undoing class card drop: ' . $e->getMessage());
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php');
}

if ($action === 'change_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_first_time = isset($_POST['is_first_time']) && $_POST['is_first_time'] === '1';
    
    $errors = [];
    
    // Get current user
    $stmt = $pdo->prepare('SELECT password, password_changed FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setMessage('error', 'User not found.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
    }
    
    // If not first time, verify old password
    if (!$is_first_time) {
        if (empty($old_password)) {
            $errors[] = 'Current password is required.';
        } elseif (!verifyPassword($old_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } else {
        if (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if (strlen($new_password) > 255) {
            $errors[] = 'Password must not exceed 255 characters.';
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = 'Password must contain at least one uppercase letter (A–Z).';
        }
        if (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = 'Password must contain at least one lowercase letter (a–z).';
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'Password must contain at least one number (0–9).';
        }
        if (!preg_match('/[!@#$%]/', $new_password)) {
            $errors[] = 'Password must contain at least one special character (!, @, #, $, %).';
        }
    }
    
    // Confirm password match
    if (empty($confirm_password)) {
        $errors[] = 'Password confirmation is required.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($errors)) {
        setMessage('error', implode('<br>', $errors));
    } else {
        try {
            $hashed_password = securePassword($new_password);
            $stmt = $pdo->prepare('UPDATE users SET password = ?, password_changed = TRUE WHERE id = ?');
            $stmt->execute([$hashed_password, $user_id]);
            setMessage('success', 'Password changed successfully!');
        } catch (Exception $e) {
            setMessage('error', 'Error changing password: ' . $e->getMessage());
        }
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
}

if ($action === 'update_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $errors = [];
    
    if (empty($lastname) || strlen($lastname) < 2) {
        $errors[] = 'Last name must be at least 2 characters.';
    } elseif (strlen($lastname) > 100) {
        $errors[] = 'Last name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $lastname)) {
        $errors[] = 'Last name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($firstname) || strlen($firstname) < 2) {
        $errors[] = 'First name must be at least 2 characters.';
    } elseif (strlen($firstname) > 100) {
        $errors[] = 'First name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $firstname)) {
        $errors[] = 'First name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($middlename) || strlen($middlename) < 2) {
        $errors[] = 'Middle name must be at least 2 characters.';
    } elseif (strlen($middlename) > 100) {
        $errors[] = 'Middle name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $middlename)) {
        $errors[] = 'Middle name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($address) || strlen($address) < 5) {
        $errors[] = 'Complete address must be at least 5 characters.';
    } elseif (strlen($address) > 255) {
        $errors[] = 'Complete address must not exceed 255 characters.';
    }
    
    if (!empty($errors)) {
        setMessage('error', implode('<br>', $errors));
    } else {
        try {
            $name = $lastname . ', ' . $firstname . ', ' . $middlename;
            $stmt = $pdo->prepare('UPDATE users SET name = ?, address = ? WHERE id = ?');
            $stmt->execute([$name, $address, $user_id]);
            setMessage('success', 'Profile updated successfully!');
        } catch (Exception $e) {
            setMessage('error', 'Error updating profile: ' . $e->getMessage());
        }
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
}

if ($action === 'update_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
    }
    
    if ($_SESSION['user_role'] !== 'teacher') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Get current user password
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setMessage('error', 'User not found.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
    }
    
    // Verify current password
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    } elseif (!verifyPassword($current_password, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } else {
        if (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if (strlen($new_password) > 255) {
            $errors[] = 'Password must not exceed 255 characters.';
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = 'Password must contain at least one uppercase letter (A–Z).';
        }
        if (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = 'Password must contain at least one lowercase letter (a–z).';
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'Password must contain at least one number (0–9).';
        }
        if (!preg_match('/[!@#$%]/', $new_password)) {
            $errors[] = 'Password must contain at least one special character (!, @, #, $, %).';
        }
    }
    
    // Confirm password match
    if (empty($confirm_password)) {
        $errors[] = 'Password confirmation is required.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($errors)) {
        setMessage('error', implode('<br>', $errors));
    } else {
        try {
            $hashed_password = securePassword($new_password);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashed_password, $user_id]);
            setMessage('success', 'Password updated successfully!');
        } catch (Exception $e) {
            setMessage('error', 'Error updating password: ' . $e->getMessage());
        }
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php');
}

if ($action === 'update_admin_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
    }
    
    if ($_SESSION['user_role'] !== 'admin') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    $errors = [];
    
    if (empty($lastname) || strlen($lastname) < 2) {
        $errors[] = 'Last name must be at least 2 characters.';
    } elseif (strlen($lastname) > 100) {
        $errors[] = 'Last name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $lastname)) {
        $errors[] = 'Last name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($firstname) || strlen($firstname) < 2) {
        $errors[] = 'First name must be at least 2 characters.';
    } elseif (strlen($firstname) > 100) {
        $errors[] = 'First name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $firstname)) {
        $errors[] = 'First name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($middlename) || strlen($middlename) < 2) {
        $errors[] = 'Middle name must be at least 2 characters.';
    } elseif (strlen($middlename) > 100) {
        $errors[] = 'Middle name must not exceed 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $middlename)) {
        $errors[] = 'Middle name must contain only letters, spaces, hyphens, and apostrophes.';
    }
    
    if (empty($position) || strlen($position) < 2) {
        $errors[] = 'Position must be at least 2 characters.';
    } elseif (strlen($position) > 100) {
        $errors[] = 'Position must not exceed 100 characters.';
    }
    
    if (!empty($errors)) {
        setMessage('error', implode('<br>', $errors));
    } else {
        try {
            $name = $lastname . ', ' . $firstname . ', ' . $middlename;
            $stmt = $pdo->prepare('UPDATE users SET name = ?, department = ? WHERE id = ?');
            $stmt->execute([$name, $position, $user_id]);
            setMessage('success', 'Profile updated successfully!');
        } catch (Exception $e) {
            setMessage('error', 'Error updating profile: ' . $e->getMessage());
        }
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
}

if ($action === 'update_admin_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
    }
    
    if ($_SESSION['user_role'] !== 'admin') {
        setMessage('error', 'Unauthorized action.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Get current user password
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setMessage('error', 'User not found.');
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
    }
    
    // Verify current password
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    } elseif (!verifyPassword($current_password, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } else {
        if (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if (strlen($new_password) > 255) {
            $errors[] = 'Password must not exceed 255 characters.';
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = 'Password must contain at least one uppercase letter (A–Z).';
        }
        if (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = 'Password must contain at least one lowercase letter (a–z).';
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'Password must contain at least one number (0–9).';
        }
        if (!preg_match('/[!@#$%]/', $new_password)) {
            $errors[] = 'Password must contain at least one special character (!, @, #, $, %).';
        }
    }
    
    // Confirm password match
    if (empty($confirm_password)) {
        $errors[] = 'Password confirmation is required.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($errors)) {
        setMessage('error', implode('<br>', $errors));
    } else {
        try {
            $hashed_password = securePassword($new_password);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashed_password, $user_id]);
            setMessage('success', 'Password updated successfully!');
        } catch (Exception $e) {
            setMessage('error', 'Error updating password: ' . $e->getMessage());
        }
    }
    
    redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php');
}

// Get drops by type (total, month, week) for statistics modal
if ($action === 'get_drops') {
    header('Content-Type: application/json');
    
    // Check if user is admin
    if ($_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $type = $_GET['type'] ?? 'total';
    $drops = [];
    
    try {
        if ($type === 'total') {
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                JOIN users u ON ccd.teacher_id = u.id
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute();
        } elseif ($type === 'month') {
            $current_month = date('m');
            $current_year = date('Y');
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                JOIN users u ON ccd.teacher_id = u.id
                WHERE MONTH(ccd.drop_date) = ? AND YEAR(ccd.drop_date) = ?
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute([$current_month, $current_year]);
        } elseif ($type === 'week') {
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                JOIN users u ON ccd.teacher_id = u.id
                WHERE WEEK(ccd.drop_date) = WEEK(NOW()) AND YEAR(ccd.drop_date) = YEAR(NOW())
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll();
        
        foreach ($results as $drop) {
            $drops[] = [
                'id' => $drop['id'],
                'student_id' => $drop['student_id'],
                'student_name' => $drop['student_name'],
                'subject_no' => $drop['subject_no'],
                'subject_name' => $drop['subject_name'],
                'teacher_name' => $drop['teacher_name'],
                'drop_date_formatted' => formatDate($drop['drop_date']),
                'status' => $drop['status']
            ];
        }
        
        echo json_encode(['success' => true, 'drops' => $drops]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get teacher's drops by type (total, month, week) for statistics modal
if ($action === 'get_teacher_drops') {
    header('Content-Type: application/json');
    
    // Check if user is teacher
    if ($_SESSION['user_role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $teacher_id = $_SESSION['user_id'];
    $type = $_GET['type'] ?? 'total';
    $drops = [];
    
    try {
        if ($type === 'total') {
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                WHERE ccd.teacher_id = ?
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute([$teacher_id]);
        } elseif ($type === 'day') {
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                WHERE ccd.teacher_id = ? AND DATE(ccd.drop_date) = DATE(NOW())
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute([$teacher_id]);
        } elseif ($type === 'month') {
            $current_month = date('m');
            $current_year = date('Y');
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                WHERE ccd.teacher_id = ? AND MONTH(ccd.drop_date) = ? AND YEAR(ccd.drop_date) = ?
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute([$teacher_id, $current_month, $current_year]);
        } elseif ($type === 'week') {
            $stmt = $pdo->prepare('
                SELECT ccd.*, s.name as student_name, s.student_id
                FROM class_card_drops ccd
                JOIN students s ON ccd.student_id = s.id
                WHERE ccd.teacher_id = ? AND WEEK(ccd.drop_date) = WEEK(NOW()) AND YEAR(ccd.drop_date) = YEAR(NOW())
                ORDER BY ccd.drop_date DESC
            ');
            $stmt->execute([$teacher_id]);
        }
        
        $results = $stmt->fetchAll();
        
        foreach ($results as $drop) {
            $drops[] = [
                'id' => $drop['id'],
                'student_id' => $drop['student_id'],
                'student_name' => $drop['student_name'],
                'subject_no' => $drop['subject_no'],
                'subject_name' => $drop['subject_name'],
                'drop_date_formatted' => formatDate($drop['drop_date']),
                'status' => $drop['status']
            ];
        }
        
        echo json_encode(['success' => true, 'drops' => $drops]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// If no valid action
redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
?>
