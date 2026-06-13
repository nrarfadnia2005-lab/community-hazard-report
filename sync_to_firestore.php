<?php
// Script to migrate existing MySQL database records to Firebase Firestore.
require_once 'config/db.php';
require_once 'config/firebase.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}

echo "Starting synchronization of existing MySQL database records to Firebase Firestore...\n\n";

try {
    $dbc = getDB();
    $firestore = getFirebaseFirestore();

    // 1. Sync Civilians to users collection
    echo "Syncing civilians...\n";
    $civilians = $dbc->query("SELECT * FROM civilians")->fetchAll();
    $civilianCount = 0;
    foreach ($civilians as $row) {
        $userId = (int)$row['user_id'];
        $userData = [
            'user_id' => $userId,
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => 'civilians',
            'district' => $row['district'],
            'created_at' => $row['created_at']
        ];
        $firestore->collection('users')->document((string)$userId)->set($userData);
        $civilianCount++;
    }
    echo "Successfully synced $civilianCount civilians to the 'users' collection.\n\n";

    // 1b. Sync Officers to users collection
    echo "Syncing officers...\n";
    $officers = $dbc->query("SELECT * FROM officers")->fetchAll();
    $officerCount = 0;
    foreach ($officers as $row) {
        $officerId = (int)$row['officer_id'];
        $userData = [
            'user_id' => $officerId,
            'username' => $row['name'],
            'email' => $row['email'],
            'role' => 'officers',
            'district' => $row['district'],
            'created_at' => $row['created_at']
        ];
        $firestore->collection('users')->document((string)$officerId)->set($userData);
        $officerCount++;
    }
    echo "Successfully synced $officerCount officers to the 'users' collection.\n\n";

    // 2. Sync Reports to reports collection
    echo "Syncing reports...\n";
    $reports = $dbc->query("SELECT * FROM reports")->fetchAll();
    $reportCount = 0;
    foreach ($reports as $row) {
        $reportId = $row['report_id'];
        $reportData = [
            'report_id' => $reportId,
            'user_id' => (int)$row['user_id'],
            'report_subject' => $row['report_subject'],
            'status' => $row['status'],
            'latitude' => $row['latitude'] !== null ? (float)$row['latitude'] : null,
            'longitude' => $row['longitude'] !== null ? (float)$row['longitude'] : null,
            'location_details' => $row['location_details'],
            'area_type' => $row['area_type'],
            'district' => $row['district'],
            'state' => $row['state'],
            'hazard_category' => $row['hazard_category'],
            'hazard_cause' => $row['hazard_cause'],
            'description_text' => $row['description_text'],
            'metadata_status' => $row['metadata_status'],
            'trust_score' => $row['trust_score'] !== null ? (float)$row['trust_score'] : null,
            'photo_file_local' => $row['photo_file'],
            'created_at' => $row['created_at']
        ];
        if ($row['officer_id']) {
            $reportData['officer_id'] = (int)$row['officer_id'];
        }
        if ($row['admin_id']) {
            $reportData['admin_id'] = (int)$row['admin_id'];
        }
        if ($row['priority']) {
            $reportData['priority'] = $row['priority'];
        }
        
        $firestore->collection('reports')->document($reportId)->set($reportData);
        $reportCount++;
    }
    echo "Successfully synced $reportCount reports to the 'reports' collection.\n\n";

    // 3. Sync Updates to subcollections of respective reports
    echo "Syncing updates/logs...\n";
    $updates = $dbc->query("SELECT * FROM updates")->fetchAll();
    $updateCount = 0;
    foreach ($updates as $row) {
        $reportId = $row['report_id'];
        $updateId = (int)$row['update_id'];
        
        $updateData = [
            'update_id' => $updateId,
            'author_id' => (int)$row['author_id'],
            'author_role' => $row['author_role'],
            'notes' => $row['notes'],
            'photo_file_local' => $row['photo_file'],
            'created_at' => $row['created_at']
        ];
        
        $firestore->collection('reports')->document($reportId)->collection('updates')->document((string)$updateId)->set($updateData);
        $updateCount++;
    }
    echo "Successfully synced $updateCount updates to the respective reports subcollections.\n\n";

    echo "Synchronization complete! All existing MySQL data has been successfully mirrored to Firebase Firestore.\n";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
