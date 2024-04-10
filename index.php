<?php
session_start();
require_once 'vendor/autoload.php'; // เรียกใช้งานไลบรารี Line Login
require_once 'vendor/google/apiclient/src/Google/autoload.php'; // เรียกใช้งานไลบรารี Google API Client

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// กำหนดค่า Line Login
define('LINE_CLIENT_ID', $_ENV['LINE_CLIENT_ID']);
define('LINE_CLIENT_SECRET', $_ENV['LINE_CLIENT_SECRET']);
define('LINE_REDIRECT_URI', $_ENV['LINE_REDIRECT_URI']);

// กำหนดค่า Google Sign-In
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID']);
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET']);
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI']);

// กำหนดข้อมูลสำหรับ Line Client
$lineLogin = new \LINE\LINELogin(
    [
        'channel_id' => LINE_CLIENT_ID,
        'channel_secret' => LINE_CLIENT_SECRET,
        'callback_url' => LINE_REDIRECT_URI
    ]
);

// กำหนดข้อมูลสำหรับ Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

// การกำหนดค่า callback URL
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['error'])) {
        echo 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . htmlspecialchars($_GET['error']);
    } elseif (isset($_GET['code']) && isset($_GET['state'])) {
        if ($_GET['state'] === $_SESSION['state']) {
            $code = $_GET['code'];
            // ตรวจสอบว่าเป็นการเข้าสู่ระบบผ่าน Line หรือ Google
            if (isset($_GET['provider']) && $_GET['provider'] === 'line') {
                // เข้าสู่ระบบผ่าน Line
                $accessToken = $lineLogin->requestAccessToken($code);
                if (!isset($accessToken->access_token)) {
                    echo 'ไม่สามารถรับ Access Token จาก Line ได้';
                    exit;
                }
                // ใช้ Access Token เพื่อเรียกข้อมูลผู้ใช้จาก Line
                $userInfo = $lineLogin->verifyAccessToken();
            } elseif (isset($_GET['provider']) && $_GET['provider'] === 'google') {
                // เข้าสู่ระบบผ่าน Google
                $token = $client->fetchAccessTokenWithAuthCode($code);
                $client->setAccessToken($token['access_token']);
                $oauth = new Google_Service_Oauth2($client);
                $userInfo = $oauth->userinfo->get();
            }
            
            // ทำตามต้องการกับข้อมูลผู้ใช้
            // เช่น สร้าง session, บันทึกลงฐานข้อมูล, หรือ redirect ไปยังหน้า Welcome
            header('Location: welcome.php');
            exit;
        } else {
            echo 'การตรวจสอบความถูกต้องของ State ไม่ถูกต้อง';
            exit;
        }
    }
}

// การกำหนดค่า callback URL สำหรับ Line Login
$state = md5(uniqid(rand(), true));
$_SESSION['state'] = $state;
$lineLoginUrl = $lineLogin->makeAuthorizeUrl($state);

// การกำหนดค่า callback URL สำหรับ Google Sign-In
$googleAuthUrl = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <p>Line Login:</p>
    <a href="<?php echo htmlspecialchars($lineLoginUrl . '&provider=line'); ?>">Login with Line</a>
    <p>Google Sign-In:</p>
    <a href="<?php echo htmlspecialchars($googleAuthUrl . '&provider=google'); ?>">Login with Google</a>
</body>
</html>
