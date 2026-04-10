# Database Integrity Maintenance Guide

## Quick Start

### Run Database Health Check
```bash
php backend/database_integrity_fix.php
```

This will automatically:
1. Check all tables exist
2. Find and delete orphaned undrop records (missing student references)
3. Find and delete orphaned undrop records (missing teacher references)
4. Find and delete orphaned undrop records (missing drop references)
5. Identify drop records with missing references (for manual review)
6. Verify all foreign key constraints are satisfied

### Expected Output
```
========================================
DATABASE INTEGRITY CHECK AND FIX
========================================

[1/6] Checking database tables...
  OK: Students table exists
  OK: Users table exists
  OK: philcst_undrop_records table exists

[2/6] Checking undrop records for orphaned student references...
  OK: No orphaned student references found

[3/6] Checking undrop records for orphaned teacher references...
  OK: No orphaned teacher references found

[4/6] Checking undrop records for orphaned drop references...
  OK: No orphaned drop references found

[5/6] Checking drop records for orphaned student/teacher references...
  OK: All drop records reference valid students
  OK: All drop records reference valid teachers

[6/6] Verifying foreign key constraints...
  OK: All undrop records have valid foreign keys

========================================
SUMMARY
========================================

STATUS: Database is healthy!
All foreign key constraints are satisfied.
```

## Manual SQL Cleanup (Alternative)

If you prefer to run SQL directly:

1. Open MySQL client or phpMyAdmin
2. Run commands from `backend/database_scripts/fix_foreign_key_constraints.sql`

### Key SQL Commands

**Delete undrop records with missing students:**
```sql
DELETE FROM philcst_undrop_records 
WHERE student_id NOT IN (SELECT id FROM students);
```

**Delete undrop records with missing teachers:**
```sql
DELETE FROM philcst_undrop_records 
WHERE teacher_id NOT IN (SELECT id FROM users);
```

**Delete undrop records with missing drop records:**
```sql
DELETE FROM philcst_undrop_records 
WHERE drop_id NOT IN (SELECT id FROM class_card_drops);
```

**Check for remaining issues:**
```sql
SELECT 
    'Missing Student' as issue,
    ur.id, ur.drop_id, ur.student_id
FROM philcst_undrop_records ur
WHERE ur.student_id NOT IN (SELECT id FROM students)

UNION ALL

SELECT 
    'Missing Teacher' as issue,
    ur.id, ur.drop_id, ur.student_id
FROM philcst_undrop_records ur
WHERE ur.teacher_id NOT IN (SELECT id FROM users);
```

## Monitoring & Maintenance Schedule

### Weekly
- Monitor error logs for "Student ID not found" or "Teacher ID not found" messages
- Check if any undrop operations are failing

### Monthly
- Run `php backend/database_integrity_fix.php`
- Review any warnings or issues reported
- Clean up as needed

### After Data Imports or Student/Teacher Deletions
- Run `php backend/database_integrity_fix.php` immediately
- Verify all references are still valid

## Understanding the Foreign Key Structure

```
students table
    ↓
    ├─ class_card_drops.student_id → students.id (ON DELETE CASCADE)
    └─ philcst_undrop_records.student_id → students.id (ON DELETE CASCADE)

users table (teachers)
    ↓
    ├─ class_card_drops.teacher_id → users.id (ON DELETE CASCADE)
    └─ philcst_undrop_records.teacher_id → users.id (ON DELETE CASCADE)

class_card_drops table
    ↓
    └─ philcst_undrop_records.drop_id → class_card_drops.id (ON DELETE CASCADE)
```

## Troubleshooting

### If Database Check Fails
1. Review the error messages reported
2. If there are warnings about drop records with missing students/teachers:
   - These require manual investigation
   - Do not delete drop records without reviewing their status
   - Contact system administrator for guidance

### If Undrop Operations Still Fail
1. Check application error logs
2. Run `php backend/database_integrity_fix.php` to identify the issue
3. Verify the student and teacher still exist in the system
4. Contact system administrator if issue persists

### If You Deleted Students/Teachers
1. Immediately run `php backend/database_integrity_fix.php`
2. This will clean up all related undrop records
3. Drop records will remain (for historical record-keeping)

## Code Changes Made

The code in `backend/includes/api.php` now includes validation before inserting undrop records:

```php
// Verify that the student still exists
$stmt = $pdo->prepare('SELECT id FROM students WHERE id = ?');
$stmt->execute([$drop['student_id']]);
if (!$stmt->fetch()) {
    error_log("Student ID " . $drop['student_id'] . " not found for drop $drop_id");
    $errorCount++;
    continue;
}

// Verify that the teacher still exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$drop['teacher_id']]);
if (!$stmt->fetch()) {
    error_log("Teacher ID " . $drop['teacher_id'] . " not found for drop $drop_id");
    $errorCount++;
    continue;
}
```

This prevents foreign key constraint violations by:
1. Checking references before insertion
2. Skipping invalid records instead of failing
3. Logging errors for investigation
4. Continuing with valid records
