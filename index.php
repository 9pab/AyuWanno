<?php
session_start();
require_once 'vendor/autoload.php'; // เรียกใช้งานไลบรารี Line Login
use \LINE\LINELogin\LINELogin;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// กำหนดค่า Client ID และ Client Secret ของแอปพลิเคชันของคุณจากไฟล์ .env
define('CLIENT_ID', $_ENV['CLIENT_ID']);
define('CLIENT_SECRET', $_ENV['CLIENT_SECRET']);
define('REDIRECT_URI', $_ENV['REDIRECT_URI']);

$lineLogin = new LINELogin(
    [
        'channel_id' => CLIENT_ID,
        'channel_secret' => CLIENT_SECRET,
        'callback_url' => REDIRECT_URI
    ]
);

// การกำหนดค่า callback URL
$state = md5(uniqid(rand(), true));
$_SESSION['state'] = $state;
$loginUrl = $lineLogin->makeAuthorizeUrl($state);

// เช็คสถานะการเข้าสู่ระบบ
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['error'])) {
        echo 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . htmlspecialchars($_GET['error']);
    } elseif (isset($_GET['code']) && isset($_GET['state'])) {
        if ($_GET['state'] === $_SESSION['state']) {
            $code = $_GET['code'];
            $accessToken = $lineLogin->requestAccessToken($code);
            if (!isset($accessToken->access_token)) {
                echo 'ไม่สามารถรับ Access Token จาก Line ได้';
                exit;
            }
            // ใช้ Access Token เพื่อเรียกข้อมูลผู้ใช้
            $userInfo = $lineLogin->verifyAccessToken();
            if (isset($userInfo['userId'])) {
                // ข้อมูลผู้ใช้
                $userId = $userInfo['userId'];
                $displayName = $userInfo['displayName'];
                $pictureUrl = $userInfo['pictureUrl'];
                // สามารถทำการบันทึกข้อมูลผู้ใช้ลงในฐานข้อมูลได้ตามต้องการ
                // โดยตรงนี้คุณสามารถเปลี่ยนหน้าไปยังหน้า Welcome หรือหน้าหลังจาก Login ได้
                header('Location: welcome.php');
            }
        } else {
            echo 'การตรวจสอบความถูกต้องของ State ไม่ถูกต้อง';
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Login</title>
</head>
<body>
    <h2>Line Login</h2>
    <a href="<?php echo htmlspecialchars($loginUrl); ?>">Login with Line</a>
</body>
</html>
