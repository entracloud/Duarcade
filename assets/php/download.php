<?php
$baseurl = "https://localhost/store/";
$filename = "duarcade_app.zip";
$filepath = __DIR__ . '/../app/' . $filename; // points to assets/app/duarcade_app.zip

if (file_exists($filepath)) {
    // Set headers to force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));

    // Clear output buffering
    ob_clean();
    flush();

    // Read the file
    readfile($filepath);
    exit;
} else {
    echo "❌ File not found!";
}
?>
