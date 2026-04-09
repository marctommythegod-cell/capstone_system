-- Link subjects to their respective courses
-- Distribute 5 subjects per course

-- Department 1 (Engineering) - Courses 1 & 2
UPDATE subjects SET course_id = 1 WHERE department_id = 1 AND id IN (1, 2, 3, 4, 5);
UPDATE subjects SET course_id = 2 WHERE department_id = 1 AND id IN (6, 7, 8, 9, 10);

-- Department 2 (Accountancy) - Courses 3 & 4
UPDATE subjects SET course_id = 3 WHERE department_id = 2 AND id IN (11, 12, 13, 14, 15);
UPDATE subjects SET course_id = 4 WHERE department_id = 2 AND id IN (16, 17, 18, 19, 20);

-- Department 3 (Education) - Courses 5 & 6
UPDATE subjects SET course_id = 5 WHERE department_id = 3 AND id IN (21, 22, 23, 24, 25);
UPDATE subjects SET course_id = 6 WHERE department_id = 3 AND id IN (26, 27, 28, 29, 30);

-- Department 4 (Criminal Justice) - Courses 7 & 8
UPDATE subjects SET course_id = 7 WHERE department_id = 4 AND id IN (31, 32, 33, 34, 35);
UPDATE subjects SET course_id = 8 WHERE department_id = 4 AND id IN (36, 37, 38, 39, 40);

-- Department 5 (Maritime Studies) - Courses 9 & 10
UPDATE subjects SET course_id = 9 WHERE department_id = 5 AND id IN (41, 42, 43, 44, 45);
UPDATE subjects SET course_id = 10 WHERE department_id = 5 AND id IN (46, 47, 48, 49, 50);

-- Department 6 (Health Sciences) - Courses 11 & 12
UPDATE subjects SET course_id = 11 WHERE department_id = 6 AND id IN (51, 52, 53, 54, 55);
UPDATE subjects SET course_id = 12 WHERE department_id = 6 AND id IN (56, 57, 58, 59, 60);

-- Department 7 (Information Technology) - Courses 13 & 14
UPDATE subjects SET course_id = 13 WHERE department_id = 7 AND id IN (61, 62, 63, 64, 65);
UPDATE subjects SET course_id = 14 WHERE department_id = 7 AND id IN (66, 67, 68, 69, 70);
