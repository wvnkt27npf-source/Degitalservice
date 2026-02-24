<?php
header('Content-Type: application/json');
include 'config.php';

$action = $_REQUEST['action'] ?? '';

// --- 1. CHECK TOOL UPDATE (Binary .exe Update) ---
if ($action == 'check_update') {
    // Ye 'tool_updates' table check karega jahan aapne file upload ki hai
    $stmt = $conn->prepare("SELECT version_code, download_link FROM tool_updates ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "status" => "success", 
            "version" => $row['version_code'], 
            "url" => $row['download_link']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No updates"]);
    }
    exit;
}

// --- 2. GET TOOL SETTINGS (Title, Message) ---
if ($action == 'get_tool_data') {
    $data = [];
    $res = $conn->query("SELECT * FROM settings");
    while ($row = $res->fetch_assoc()) {
        $data[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- 3. GET SOFTWARE LIST ---
if ($action == 'get_software') {
    $softwares = [];
    $res = $conn->query("SELECT * FROM software_list ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $softwares[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $softwares]);
    exit;
}

// --- 4. LOGOUT ---
if ($action == 'logout') {
    $key = $_POST['license_key'] ?? '';
    $conn->query("UPDATE users SET last_seen = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE license_key = '$key'");
    echo json_encode(["status" => "success"]);
    exit;
}

// --- 5. LOGIN ---
if ($action == 'login_tool') {
    $key = $_POST['license_key'] ?? '';
    $hwid = $_POST['hwid'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($key)) { echo json_encode(["status" => "error", "message" => "Key required"]); exit; }

    $stmt = $conn->prepare("SELECT id, username, status, license_status, hwid FROM users WHERE license_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'blocked') {
            echo json_encode(["status" => "error", "message" => "Account Blocked"]); exit;
        }
        if ($row['license_status'] === 'suspended') {
            echo json_encode(["status" => "error", "message" => "License Suspended"]); exit;
        }
        if (empty($row['hwid'])) {
            $conn->query("UPDATE users SET hwid = '$hwid' WHERE id = {$row['id']}");
        } elseif ($row['hwid'] !== $hwid) {
            echo json_encode(["status" => "error", "message" => "Invalid HWID (PC Locked)"]); exit;
        }

        $conn->query("INSERT INTO login_logs (user_id, username, ip_address, hwid) VALUES ({$row['id']}, '{$row['username']}', '$ip', '$hwid')");
        $conn->query("UPDATE users SET last_seen = NOW() WHERE id = {$row['id']}");

        echo json_encode(["status" => "success", "username" => $row['username']]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Key"]);
    }
    exit;
}

// --- 6. HEARTBEAT ---
if ($action == 'heartbeat') {
    $key = $_POST['license_key'] ?? '';
    $stmt = $conn->prepare("SELECT status, license_status FROM users WHERE license_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if ($row && $row['status'] == 'active' && ($row['license_status'] ?? 'active') == 'active') {
        $conn->query("UPDATE users SET last_seen = NOW() WHERE license_key = '$key'");
        echo json_encode(["status" => "alive"]);
    } else {
        echo json_encode(["status" => "kill"]);
    }
    exit;
}
?>