<?php
echo "<pre>";

// 1. Does /data exist?
echo "Checking /data directory...\n";
if (is_dir('/data')) {
    echo "/data exists.\n";
} else {
    echo "/data does NOT exist.\n";
}

// 2. Try to create a file
echo "\nAttempting to write to /data...\n";
$testFile = '/data/volume_test.txt';
$result = @file_put_contents($testFile, "Volume test at " . date('Y-m-d H:i:s'));

if ($result !== false) {
    echo "Write SUCCESSFUL! File created: $testFile\n";
} else {
    echo "Write FAILED. /data is not mounted or not writable.\n";
}

// 3. List /data contents
echo "\nListing /data contents:\n";
@system('ls -lah /data');

echo "</pre>";
