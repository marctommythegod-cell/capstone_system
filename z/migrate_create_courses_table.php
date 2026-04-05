<?php
// Migration: Create courses table and populate with new course list

require_once __DIR__ . '/../config/db.php';

try {
    // Check if courses table exists
    $tableExists = false;
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'courses'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
    }

    // Drop existing courses table if it exists
    if ($tableExists) {
        $pdo->exec("DROP TABLE IF EXISTS courses");
        echo "Dropped existing courses table.<br>";
    }

    // Create courses table
    $pdo->exec("
        CREATE TABLE courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_name VARCHAR(100) NOT NULL UNIQUE,
            category VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Created courses table.<br>";

    // Insert all courses
    $courses = [
        ['category' => 'ENGINEERING', 'name' => 'BS IN COMPUTER ENGINEERING'],
        ['category' => 'ENGINEERING', 'name' => 'BS IN ELECTRICAL ENGINEERING'],
        ['category' => 'ENGINEERING', 'name' => 'BS IN ELECTRONICS ENGINEERING'],
        ['category' => 'ENGINEERING', 'name' => 'BS IN MECHANICAL ENGINEERING'],
        ['category' => 'ENGINEERING', 'name' => 'BS IN CIVIL ENGINEERING'],
        ['category' => 'ACCOUNTANCY AND BUSINESS EDUCATION', 'name' => 'BS IN ACCOUNTANCY'],
        ['category' => 'ACCOUNTANCY AND BUSINESS EDUCATION', 'name' => 'BS IN BUSINESS ADMINISTRATION (MAJOR IN MANAGEMENT)'],
        ['category' => 'EDUCATION', 'name' => 'BS IN ELEMENTARY EDUCATION'],
        ['category' => 'EDUCATION', 'name' => 'BS IN SECONDARY EDUCATION (MAJOR IN GENERAL SCIENCE)'],
        ['category' => 'CRIMINAL JUSTICE EDUCATION', 'name' => 'BS IN CRIMINOLOGY'],
        ['category' => 'MARITIME STUDIES', 'name' => 'BS IN MARINE ENGINEERING'],
        ['category' => 'MARITIME STUDIES', 'name' => 'BS IN MARINE TRANSPORTATION'],
        ['category' => 'HOSPITALITY AND TOURISM MANAGEMENT', 'name' => 'BS IN HOSPITALITY MANAGEMENT'],
        ['category' => 'HOSPITALITY AND TOURISM MANAGEMENT', 'name' => 'BS IN TOURISM MANAGEMENT'],
        ['category' => 'COMPUTER STUDIES', 'name' => 'BS IN COMPUTER SCIENCE'],
        ['category' => 'COMPUTER STUDIES', 'name' => 'BS IN INFORMATION TECHNOLOGY'],
    ];

    $stmt = $pdo->prepare("INSERT INTO courses (course_name, category) VALUES (?, ?)");
    
    foreach ($courses as $course) {
        $stmt->execute([$course['name'], $course['category']]);
    }

    echo "Inserted " . count($courses) . " courses into the database.<br>";
    echo "Migration completed successfully!";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
