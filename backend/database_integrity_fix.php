<?php
/**
 * Database Integrity Fix Script
 * 
 * Usage: php database_integrity_fix.php
 */

require_once __DIR__ . '/config/db.php';

$errors = [];
$fixes = [];
$warnings = [];

try {
    echo "========================================\n";
    echo "DATABASE INTEGRITY CHECK AND FIX\n";
    echo "========================================\n\n";
    
    // CHECK 1: Verify tables exist
    echo "[1/6] Checking database tables...\n";
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'students'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $errors[] = "Students table not found!";
        echo "  ERROR: Students table not found!\n\n";
    } else {
        echo "  OK: Students table exists\n";
    }
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $errors[] = "Users table not found!";
        echo "  ERROR: Users table not found!\n\n";
    } else {
        echo "  OK: Users table exists\n";
    }
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'philcst_undrop_records'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "  WARNING: philcst_undrop_records table not found\n";
    } else {
        echo "  OK: philcst_undrop_records table exists\n";
    }
    echo "\n";
    
    // CHECK 2: Find and delete orphaned undrop records
    echo "[2/6] Checking undrop records for orphaned student references...\n";
    $query = "SELECT ur.id FROM philcst_undrop_records ur WHERE ur.student_id NOT IN (SELECT id FROM students)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned) > 0) {
        echo "  Found " . count($orphaned) . " orphaned records\n";
        $stmt = $pdo->prepare("DELETE FROM philcst_undrop_records WHERE student_id NOT IN (SELECT id FROM students)");
        $stmt->execute();
        $fixes[] = "Deleted " . count($orphaned) . " undrop records with missing students";
        echo "  OK: Deleted " . count($orphaned) . " records\n";
    } else {
        echo "  OK: No orphaned student references found\n";
    }
    echo "\n";
    
    // CHECK 3: Find and delete orphaned teacher references
    echo "[3/6] Checking undrop records for orphaned teacher references...\n";
    $query = "SELECT ur.id FROM philcst_undrop_records ur WHERE ur.teacher_id NOT IN (SELECT id FROM users)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned) > 0) {
        echo "  Found " . count($orphaned) . " orphaned records\n";
        $stmt = $pdo->prepare("DELETE FROM philcst_undrop_records WHERE teacher_id NOT IN (SELECT id FROM users)");
        $stmt->execute();
        $fixes[] = "Deleted " . count($orphaned) . " undrop records with missing teachers";
        echo "  OK: Deleted " . count($orphaned) . " records\n";
    } else {
        echo "  OK: No orphaned teacher references found\n";
    }
    echo "\n";
    
    // CHECK 4: Find and delete orphaned drop references
    echo "[4/6] Checking undrop records for orphaned drop references...\n";
    $query = "SELECT ur.id FROM philcst_undrop_records ur WHERE ur.drop_id NOT IN (SELECT id FROM class_card_drops)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned) > 0) {
        echo "  Found " . count($orphaned) . " orphaned records\n";
        $stmt = $pdo->prepare("DELETE FROM philcst_undrop_records WHERE drop_id NOT IN (SELECT id FROM class_card_drops)");
        $stmt->execute();
        $fixes[] = "Deleted " . count($orphaned) . " undrop records with missing drop records";
        echo "  OK: Deleted " . count($orphaned) . " records\n";
    } else {
        echo "  OK: No orphaned drop references found\n";
    }
    echo "\n";
    
    // CHECK 5: Identify drop records with orphaned references
    echo "[5/6] Checking drop records for orphaned student/teacher references...\n";
    
    $query = "SELECT ccd.id FROM class_card_drops ccd WHERE ccd.student_id NOT IN (SELECT id FROM students)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orphaned_drops_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned_drops_students) > 0) {
        $warnings[] = "Found " . count($orphaned_drops_students) . " drop records with missing students";
        echo "  WARNING: " . count($orphaned_drops_students) . " drop records reference deleted students\n";
    } else {
        echo "  OK: All drop records reference valid students\n";
    }
    
    $query = "SELECT ccd.id FROM class_card_drops ccd WHERE ccd.teacher_id NOT IN (SELECT id FROM users)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orphaned_drops_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned_drops_teachers) > 0) {
        $warnings[] = "Found " . count($orphaned_drops_teachers) . " drop records with missing teachers";
        echo "  WARNING: " . count($orphaned_drops_teachers) . " drop records reference deleted teachers\n";
    } else {
        echo "  OK: All drop records reference valid teachers\n";
    }
    echo "\n";
    
    // CHECK 6: Verify all foreign keys
    echo "[6/6] Verifying foreign key constraints...\n";
    
    $query = "SELECT COUNT(*) as bad_refs FROM philcst_undrop_records ur WHERE ur.student_id NOT IN (SELECT id FROM students) OR ur.teacher_id NOT IN (SELECT id FROM users) OR ur.drop_id NOT IN (SELECT id FROM class_card_drops)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['bad_refs'] == 0) {
        echo "  OK: All undrop records have valid foreign keys\n";
    } else {
        $errors[] = "Still " . $result['bad_refs'] . " invalid foreign key references";
        echo "  ERROR: Still " . $result['bad_refs'] . " invalid references found\n";
    }
    echo "\n";
    
    // SUMMARY
    echo "========================================\n";
    echo "SUMMARY\n";
    echo "========================================\n\n";
    
    if (count($fixes) > 0) {
        echo "FIXES APPLIED:\n";
        foreach ($fixes as $fix) {
            echo "  - " . $fix . "\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "  - " . $warning . "\n";
        }
        echo "\n";
    }
    
    if (count($errors) > 0) {
        echo "ERRORS:\n";
        foreach ($errors as $error) {
            echo "  - " . $error . "\n";
        }
        echo "\nSTATUS: Issues remain - manual review needed\n";
    } else {
        echo "STATUS: Database is healthy!\n";
        echo "All foreign key constraints are satisfied.\n";
    }
    
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>
