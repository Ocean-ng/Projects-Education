<?php
$target_dir = "uploads/";
// Create directory if it does not exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

// Vulnerability: Move the uploaded file directly without checking the file extension
if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    // Target file saved. Do not redirect - the attacker must find the /uploads/ folder.
    echo "File saved successfully.";
} else {
    echo "Upload failed.";
}
?>