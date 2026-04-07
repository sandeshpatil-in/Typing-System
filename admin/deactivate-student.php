<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin_students.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('admin_student_message', 'Deactivation must be submitted from the students page.');
    setFlash('admin_student_message_type', 'warning');
    redirect('admin/dashboard.php?page=students');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('admin_student_message', 'Deactivation failed because the session expired. Please try again.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

$id = (int) getSafePost('id', 0);

if ($id <= 0) {
    setFlash('admin_student_message', 'Invalid student selected.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

$student = getStudentById($conn, $id);

if (!$student) {
    setFlash('admin_student_message', 'Student not found.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

if (deactivateStudentAccess($conn, $id)) {
    setFlash('admin_student_message', 'Student access has been deactivated successfully.');
    setFlash('admin_student_message_type', 'success');
} else {
    setFlash('admin_student_message', 'Unable to deactivate this student right now.');
    setFlash('admin_student_message_type', 'error');
}

redirect('admin/dashboard.php?page=students');
