<?php
// Simple test to verify protocol id reservation and filename formatting
// This script inserts a placeholder row, fetches insert_id, computes filename, then deletes the placeholder.

// Use direct DB credentials to avoid requiring full GLPI bootstrap in tests
$dbhost = 'localhost';
$dbuser = 'glpi';
$dbpassword = 'YourStrongPassword';
$dbdefault = 'glpi';

$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbdefault);
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

$gen_date = date('Y-m-d H:i:s');
$author = 'testscript';
$user_id = 1;
$document_id = 0;
$document_type = 'test';

$insert_sql = "INSERT INTO glpi_plugin_deliveryterms_protocols
    (name, gen_date, author, user_id, document_id, document_type)
    VALUES ('', ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($insert_sql);
if (!$stmt) {
    echo "Prepare failed: " . $mysqli->error . PHP_EOL;
    exit(1);
}
// bind types: s (string) for gen_date, s for author, i (int) for user_id, i for document_id, s for document_type
$stmt->bind_param('ssiis', $gen_date, $author, $user_id, $document_id, $document_type);
if (!$stmt->execute()) {
    echo "Execute failed: " . $stmt->error . PHP_EOL;
    exit(1);
}
$proto_id = $mysqli->insert_id;
$filename = date('Y') . '-' . sprintf('%04d', $proto_id) . '.pdf';

echo "Inserted placeholder id: $proto_id\n";
echo "Computed filename: $filename\n";

// Cleanup: delete the placeholder row we just created
$del_sql = "DELETE FROM glpi_plugin_deliveryterms_protocols WHERE id = ?";
$del_stmt = $mysqli->prepare($del_sql);
$del_stmt->bind_param('i', $proto_id);
$del_stmt->execute();

echo "Placeholder row deleted.\n";

$mysqli->close();

?>