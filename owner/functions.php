<?php
session_start();

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

function handle_image_upload($file, $allowed_extensions, $max_file_size) {
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $file['tmp_name'];
        $file_name = basename($file['name']);
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Log detected file extension
        log_error("Detected file extension: " . $file_ext);

        if (!in_array($file_ext, $allowed_extensions)) {
            return ['success' => false, 'message' => 'Invalid image extension. Allowed extensions: jpg, jpeg, png, gif.'];
        }

        if ($file_size > $max_file_size) {
            return ['success' => false, 'message' => 'Image size exceeds the maximum allowed size of 2MB.'];
        }

        // Check if the file is a valid image
        $image_info = getimagesize($file_tmp);
        if ($image_info === false) {
            return ['success' => false, 'message' => 'Uploaded file is not a valid image.'];
        }

        $new_filename = uniqid() . '.' . $file_ext;
        $upload_dir = __DIR__ . '/../img/uploads/';

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }

        if (!is_writable($upload_dir)) {
            return ['success' => false, 'message' => 'Upload directory is not writable.'];
        }

        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $destination)) {
            return ['success' => true, 'filename' => $new_filename];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }
    }

    return ['success' => false, 'message' => 'No image uploaded or there was an upload error.'];
}

function log_error($message) {
    $log_file = __DIR__ . '/error_log.txt';
    $current_time = date('Y-m-d H:i:s');
    $formatted_message = "[{$current_time}] {$message}\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}
?>