<?php
// SIMPLIFIED view-request.php - FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>View Request - Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .debug-box { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>📋 View Request - Debug Mode</h1>";

// Debug info
echo "<div class='debug-box'>
        <h4>🔍 Debug Information</h4>
        <pre>";

echo "=== SESSION DATA ===\n";
print_r($_SESSION);

echo "\n=== GET PARAMETERS ===\n";
print_r($_GET);

$request_id = $_GET['id'] ?? 0;
echo "\n=== EXTRACTED REQUEST ID ===\n";
echo "Request ID from URL: " . ($request_id ?: 'NOT FOUND OR ZERO');

// Connect to database
echo "\n\n=== DATABASE CONNECTION ===\n";
try {
    require_once '../../app/config/database.php';
    echo "✓ Database connected\n";
    
    if ($request_id) {
        // Get request details
        $sql = "SELECT mr.*, u.full_name as patient_name
                FROM hc_medical_requests mr
                LEFT JOIN hc_users u ON mr.patient_id = u.user_id
                WHERE mr.request_id = :request_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':request_id' => $request_id]);
        $request = $stmt->fetch();
        
        if ($request) {
            echo "\n✓ Request FOUND in database\n";
            echo "Request Details:\n";
            print_r($request);
            
            // Display the request
            echo "</pre></div>";
            
            echo "<div class='debug-box'>
                    <h4>📄 Request Details</h4>
                    <div class='mb-3'>
                        <strong>Request ID:</strong> HC-" . str_pad($request['request_id'], 6, '0', STR_PAD_LEFT) . "
                    </div>
                    <div class='mb-3'>
                        <strong>Title:</strong> " . htmlspecialchars($request['request_title']) . "
                    </div>
                    <div class='mb-3'>
                        <strong>Patient:</strong> " . htmlspecialchars($request['patient_name']) . " (ID: " . $request['patient_id'] . ")
                    </div>
                    <div class='mb-3'>
                        <strong>Your User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not logged in') . "
                    </div>
                    <div class='mb-3'>
                        <strong>Status:</strong> <span class='badge bg-warning'>" . ucfirst($request['request_status']) . "</span>
                    </div>
                    <div class='mb-3'>
                        <strong>Description:</strong>
                        <div class='p-3 bg-light rounded mt-2'>" . nl2br(htmlspecialchars($request['request_description'])) . "</div>
                    </div>
                    <div class='mb-3'>
                        <strong>Location:</strong> " . ($request['patient_location'] ? htmlspecialchars($request['patient_location']) : 'Not specified') . "
                    </div>
                    <div class='mb-3'>
                        <strong>Date:</strong> " . date('F j, Y g:i A', strtotime($request['request_date'])) . "
                    </div>
                  </div>";
            
        } else {
            echo "\n✗ Request NOT FOUND in database\n";
            echo "</pre></div>";
            echo "<div class='alert alert-danger'>❌ Request ID $request_id not found in database!</div>";
        }
    } else {
        echo "\n✗ No request ID provided\n";
        echo "</pre></div>";
        echo "<div class='alert alert-warning'>⚠️ No request ID provided in URL</div>";
    }
    
} catch (Exception $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "</pre></div>";
    echo "<div class='alert alert-danger'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Navigation
echo "<div class='mt-4'>
        <a href='patient-dashboard.php' class='btn btn-primary'>
            ← Back to Dashboard
        </a>
        <a href='create-request.php' class='btn btn-success'>
            ＋ Create New Request
        </a>
      </div>";

echo "</div></body></html>";
?>