# Course Database Update Summary

## Changes Made

### 1. Created Courses Table
- A new `courses` table has been created in the database with the following fields:
  - `id` (Primary Key)
  - `course_name` (VARCHAR 100, UNIQUE)
  - `category` (VARCHAR 100)
  - `created_at` (TIMESTAMP)

### 2. Populated Courses
All 16 new courses have been added to the database, organized by category:

**ENGINEERING**
- BS IN COMPUTER ENGINEERING
- BS IN ELECTRICAL ENGINEERING
- BS IN ELECTRONICS ENGINEERING
- BS IN MECHANICAL ENGINEERING
- BS IN CIVIL ENGINEERING

**ACCOUNTANCY AND BUSINESS EDUCATION**
- BS IN ACCOUNTANCY
- BS IN BUSINESS ADMINISTRATION (MAJOR IN MANAGEMENT)

**EDUCATION**
- BS IN ELEMENTARY EDUCATION
- BS IN SECONDARY EDUCATION (MAJOR IN GENERAL SCIENCE)

**CRIMINAL JUSTICE EDUCATION**
- BS IN CRIMINOLOGY

**MARITIME STUDIES**
- BS IN MARINE ENGINEERING
- BS IN MARINE TRANSPORTATION

**HOSPITALITY AND TOURISM MANAGEMENT**
- BS IN HOSPITALITY MANAGEMENT
- BS IN TOURISM MANAGEMENT

**COMPUTER STUDIES**
- BS IN COMPUTER SCIENCE
- BS IN INFORMATION TECHNOLOGY

### 3. Updated Admin Pages

#### admin/cancelled_class_card.php
- Updated course query to fetch from the new `courses` table
- Changed course filter from LIKE operator to exact match (=)
- Course dropdown now displays all courses from the database

#### admin/students.php
- Updated course query to fetch from the new `courses` table
- Course submenu now displays all courses organized in the filter

### 4. Migration File
- Created: `z/migrate_create_courses_table.php`
- This file contains the migration that creates the courses table and inserts all course data
- It can be run again if needed to reset the courses table

## Notes
- The existing students table still uses the `course` field to store the course name
- The new `courses` table serves as a reference/master list for all available courses
- Students can still have any course value, but the filters will only show courses from the master list
- All queries use prepared statements for security

## Files Modified
1. admin/cancelled_class_card.php
2. admin/students.php
3. z/migrate_create_courses_table.php (created)
