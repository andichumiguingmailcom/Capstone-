<?php
require_once 'includes/config.php';
$db = getDB();

echo "<h2>Database Migration Tool</h2><pre>";

function migrateTable($db, $table, $alterSql, $updateSql, $dropCols) {
    // Check if migration already ran (check for first_name column)
    $res = $db->query("SHOW COLUMNS FROM `$table` LIKE 'first_name'");
    if ($res && $res->num_rows > 0) {
        echo "✅ Table '$table' is already updated.\n";
        return;
    }

    echo "⏳ Migrating table '$table'...\n";
    
    // 1. Add columns
    if (!$db->query($alterSql)) {
        die("❌ Error adding columns to $table: " . $db->error);
    }
    echo "   - Columns added.\n";

    // 2. Migrate data
    if (!$db->query($updateSql)) {
        die("❌ Error migrating data for $table: " . $db->error);
    }
    echo "   - Data split and migrated.\n";

    // 3. Drop old columns
    foreach ($dropCols as $col) {
        $db->query("ALTER TABLE `$table` DROP COLUMN `$col`");
    }
    echo "   - Old columns dropped.\n";
    echo "✅ Table '$table' migration complete.\n\n";
}

// Users Table
migrateTable($db, 'users', 
    "ALTER TABLE users ADD COLUMN first_name VARCHAR(60) NOT NULL DEFAULT '' AFTER password, ADD COLUMN middle_name VARCHAR(60) AFTER first_name, ADD COLUMN last_name VARCHAR(60) NOT NULL DEFAULT '' AFTER middle_name",
    "UPDATE users SET first_name = SUBSTRING_INDEX(full_name, ' ', 1), last_name = CASE WHEN LOCATE(' ', full_name) > 0 THEN SUBSTRING(full_name, LOCATE(' ', full_name) + 1) ELSE '-' END",
    ['full_name']
);

// Members Table
migrateTable($db, 'members',
    "ALTER TABLE members ADD COLUMN first_name VARCHAR(60) NOT NULL DEFAULT '' AFTER member_id, ADD COLUMN middle_name VARCHAR(60) AFTER first_name, ADD COLUMN last_name VARCHAR(60) NOT NULL DEFAULT '' AFTER middle_name, ADD COLUMN street VARCHAR(150) AFTER phone, ADD COLUMN barangay VARCHAR(100) AFTER street, ADD COLUMN city VARCHAR(100) AFTER barangay, ADD COLUMN province VARCHAR(100) AFTER city",
    "UPDATE members SET first_name = SUBSTRING_INDEX(full_name, ' ', 1), last_name = CASE WHEN LOCATE(' ', full_name) > 0 THEN SUBSTRING(full_name, LOCATE(' ', full_name) + 1) ELSE '-' END, street = address",
    ['full_name', 'address']
);

// Pre-applications Table
migrateTable($db, 'pre_applications',
    "ALTER TABLE pre_applications ADD COLUMN first_name VARCHAR(60) NOT NULL DEFAULT '' AFTER id, ADD COLUMN middle_name VARCHAR(60) AFTER first_name, ADD COLUMN last_name VARCHAR(60) NOT NULL DEFAULT '' AFTER middle_name, ADD COLUMN street VARCHAR(150) AFTER phone, ADD COLUMN barangay VARCHAR(100) AFTER street, ADD COLUMN city VARCHAR(100) AFTER barangay, ADD COLUMN province VARCHAR(100) AFTER city",
    "UPDATE pre_applications SET first_name = SUBSTRING_INDEX(full_name, ' ', 1), last_name = CASE WHEN LOCATE(' ', full_name) > 0 THEN SUBSTRING(full_name, LOCATE(' ', full_name) + 1) ELSE '-' END, street = address",
    ['full_name', 'address']
);

echo "\n🎉 Database structure updated successfully! You can now delete this file.";
?>