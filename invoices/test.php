<?php
$testFile = 'invoices/test.txt';
file_put_contents($testFile, 'Test content');
echo file_exists($testFile) ? "File created successfully" : "Failed to create file";
?>