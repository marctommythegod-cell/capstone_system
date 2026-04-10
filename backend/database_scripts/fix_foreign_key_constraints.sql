-- Database Integrity Fix Script
-- This script fixes all foreign key constraint violations

-- ============================================
-- 1. Delete orphaned undrop records (student not found)
-- ============================================
DELETE FROM philcst_undrop_records
WHERE student_id NOT IN (SELECT id FROM students WHERE id IS NOT NULL);

-- ============================================
-- 2. Delete orphaned undrop records (teacher not found)
-- ============================================
DELETE FROM philcst_undrop_records
WHERE teacher_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);

-- ============================================
-- 3. Delete orphaned undrop records (drop record not found)
-- ============================================
DELETE FROM philcst_undrop_records
WHERE drop_id NOT IN (SELECT id FROM class_card_drops WHERE id IS NOT NULL);

-- ============================================
-- 4. Verify all remaining undrop records have valid references
-- ============================================
-- This query will show any issues that remain
SELECT 
    'Student Missing' as issue_type,
    ur.id as undrop_id,
    ur.drop_id,
    ur.student_id,
    ur.teacher_id
FROM philcst_undrop_records ur
LEFT JOIN students s ON ur.student_id = s.id
WHERE s.id IS NULL

UNION ALL

SELECT 
    'Teacher Missing' as issue_type,
    ur.id as undrop_id,
    ur.drop_id,
    ur.student_id,
    ur.teacher_id
FROM philcst_undrop_records ur
LEFT JOIN users u ON ur.teacher_id = u.id
WHERE u.id IS NULL

UNION ALL

SELECT 
    'Drop Record Missing' as issue_type,
    ur.id as undrop_id,
    ur.drop_id,
    ur.student_id,
    ur.teacher_id
FROM philcst_undrop_records ur
LEFT JOIN class_card_drops ccd ON ur.drop_id = ccd.id
WHERE ccd.id IS NULL;

-- ============================================
-- 5. Check drop records with invalid references
-- ============================================
-- Show drop records with missing students
SELECT 'Drop Records with Missing Students' as check_type;
SELECT 
    ccd.id as drop_id,
    ccd.student_id,
    ccd.status
FROM class_card_drops ccd
LEFT JOIN students s ON ccd.student_id = s.id
WHERE s.id IS NULL;

-- Show drop records with missing teachers
SELECT 'Drop Records with Missing Teachers' as check_type;
SELECT 
    ccd.id as drop_id,
    ccd.teacher_id,
    ccd.status
FROM class_card_drops ccd
LEFT JOIN users u ON ccd.teacher_id = u.id
WHERE u.id IS NULL;

-- ============================================
-- 6. Verify foreign key constraints exist
-- ============================================
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND (TABLE_NAME = 'philcst_undrop_records' OR TABLE_NAME = 'class_card_drops')
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
