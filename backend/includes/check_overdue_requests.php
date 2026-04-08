<?php
// check_and_cancel_overdue_requests.php - Auto-cancel overdue class card drop requests

require_once __DIR__ . '/../config/db.php';

/**
 * Check for overdue pending requests and cancel them automatically
 */
function checkAndCancelOverdueRequests($pdo) {
    try {
        // Get all pending requests past their deadline
        $stmt = $pdo->prepare('
            SELECT id, student_id, subject_no, subject_name 
            FROM class_card_drops 
            WHERE status = "Pending" 
            AND deadline IS NOT NULL
            AND deadline < NOW()
            AND cancelled_date IS NULL
        ');
        $stmt->execute();
        $overdueRequests = $stmt->fetchAll();
        
        if (count($overdueRequests) > 0) {
            foreach ($overdueRequests as $request) {
                // Mark as cancelled
                $updateStmt = $pdo->prepare('
                    UPDATE class_card_drops 
                    SET status = "Cancelled", 
                        cancelled_date = NOW(),
                        cancellation_reason = "Request expired - not processed within the day"
                    WHERE id = ?
                ');
                $updateStmt->execute([$request['id']]);
            }
            
            return [
                'success' => true,
                'message' => count($overdueRequests) . ' overdue request(s) cancelled automatically.',
                'count' => count($overdueRequests)
            ];
        }
        
        return [
            'success' => true,
            'message' => 'No overdue requests found.',
            'count' => 0
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error checking overdue requests: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

// Run if called directly
if (php_sapi_name() === 'cli' || (isset($_GET['action']) && $_GET['action'] === 'check_overdue')) {
    $result = checkAndCancelOverdueRequests($pdo);
    echo $result['message'];
}
?>
