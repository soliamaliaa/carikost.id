<?php
require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

$serviceAccountPath = __DIR__.'/firebase_key.json';

if (!file_exists($serviceAccountPath)) {
    die("Error: File firebase_key.json tidak ditemukan.");
}

$factory = (new Factory)
    ->withServiceAccount($serviceAccountPath)
    // Pastikan URL Database Anda benar
    ->withDatabaseUri('https://carikost-id-5a759-default-rtdb.asia-southeast1.firebasedatabase.app');

$database = $factory->createDatabase();
?>