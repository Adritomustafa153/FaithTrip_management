<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) die("Connection failed");

$today = date('m-d');
$today_full = date('Y-m-d');
$six_months_later = date('Y-m-d', strtotime('+6 months'));

// BIRTHDAY EMAIL
$bq = $conn->query("SELECT * FROM passengers WHERE DATE_FORMAT(date_of_birth, '%m-%d') = '$today'");
while ($row = $bq->fetch_assoc()) {
    $msg = <<<EOD
<html>
  <body>
    <h2>🎉 Happy Birthday {$row['name']}! 🎂</h2>
    <img src="https://avatars.githubusercontent.com/u/48399349?v=4&s=400" width="300">
    <p>We wish you a joyful year ahead!</p>
    <table cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 14px; color: #333">
      <tr>
        <td style="vertical-align: top; padding-right: 15px">
          <img src="https://portal.faithtrip.net/companyLogo/gZdfl1728121001.jpg" alt="FaithTrip Logo" style="width: 90px; border-radius: 50%" />
        </td>
        <td style="border-left: 1px solid #ccc; padding-left: 15px">
          <p style="margin: 0; font-size: 16px; font-weight: bold; color: #2c3e50;">Faith Travels and Tours LTD</p>
          <p style="margin: 2px 0">
            <img src="https://cdn-icons-png.flaticon.com/16/841/841364.png" alt="Website" />
            <a href="https://www.faithtrip.net" style="color: #2980b9; text-decoration: none">www.faithtrip.net</a>
          </p>
          <p style="margin: 8px 0">
            <a href="https://www.facebook.com/faithrtip.net/"><img src="https://cdn-icons-png.flaticon.com/24/145/145802.png" alt="Facebook" /></a>
            <a href="https://twitter.com/yourhandle"><img src="https://cdn-icons-png.flaticon.com/24/733/733579.png" alt="X" /></a>
            <a href="https://www.youtube.com/@Faithtrip.net_"><img src="https://cdn-icons-png.flaticon.com/24/1384/1384060.png" alt="YouTube" /></a>
            <a href="https://www.instagram.com/faithtrip_/"><img src="https://cdn-icons-png.flaticon.com/24/2111/2111463.png" alt="Instagram" /></a>
            <a href="https://www.pinterest.com/faithtripnet/"><img src="https://cdn-icons-png.flaticon.com/24/145/145808.png" alt="Pinterest" /></a>
            <a href="https://tiktok.com/@yourprofile"><img src="https://cdn-icons-png.flaticon.com/24/3046/3046121.png" alt="TikTok" /></a>
          </p>
        </td>
        <td style="padding-left: 15px; font-size: 13px; color: #333">
          <p><img src="https://cdn-icons-png.flaticon.com/16/281/281769.png" alt="Email" /> info@faithtrip.net</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/733/733585.png" alt="WhatsApp" /> +8801896459490, +8801896459495</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/455/455705.png" alt="Phone" /> +09647649044</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/684/684908.png" alt="Map" /> Abedin Tower (Level 5),<br>35 Kamal Ataturk Avenue,<br>Banani, Dhaka - 1213, Bangladesh</p>
        </td>
      </tr>
    </table>
  </body>
</html>
EOD;
    sendMail($row['PassengerEmail'], "🎉 Happy Birthday {$row['name']}!", $msg);
}

// PASSPORT EXPIRY EMAIL
$peq = $conn->query("SELECT * FROM passengers WHERE passport_expiry BETWEEN '$today_full' AND '$six_months_later'");
while ($row = $peq->fetch_assoc()) {
    $msg = <<<EOD
<html>
  <body>
    <p>Dear {$row['name']},</p>
    <p>Your passport (No: <strong>{$row['passport_number']}</strong>) will expire on <strong>{$row['passport_expiry']}</strong>.</p>
    <p>Please take necessary action to renew it.</p>
    <p>Best regards,<br>Faith Travels and Tours LTD</p>

        <table cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 14px; color: #333">
      <tr>
        <br><br><td style="vertical-align: top; padding-right: 15px">
          <img src="https://portal.faithtrip.net/companyLogo/gZdfl1728121001.jpg" alt="FaithTrip Logo" style="width: 90px; border-radius: 50%" />
        </td>
        <td style="border-left: 1px solid #ccc; padding-left: 15px">
          <p style="margin: 0; font-size: 16px; font-weight: bold; color: #2c3e50;">Faith Travels and Tours LTD</p>
          <p style="margin: 2px 0">
            <img src="https://cdn-icons-png.flaticon.com/16/841/841364.png" alt="Website" />
            <a href="https://www.faithtrip.net" style="color: #2980b9; text-decoration: none">www.faithtrip.net</a>
          </p>
          <p style="margin: 8px 0">
            <a href="https://www.facebook.com/faithrtip.net/"><img src="https://cdn-icons-png.flaticon.com/24/145/145802.png" alt="Facebook" /></a>
            <a href="https://twitter.com/yourhandle"><img src="https://cdn-icons-png.flaticon.com/24/733/733579.png" alt="X" /></a>
            <a href="https://www.youtube.com/@Faithtrip.net_"><img src="https://cdn-icons-png.flaticon.com/24/1384/1384060.png" alt="YouTube" /></a>
            <a href="https://www.instagram.com/faithtrip_/"><img src="https://cdn-icons-png.flaticon.com/24/2111/2111463.png" alt="Instagram" /></a>
            <a href="https://www.pinterest.com/faithtripnet/"><img src="https://cdn-icons-png.flaticon.com/24/145/145808.png" alt="Pinterest" /></a>
            <a href="https://tiktok.com/@yourprofile"><img src="https://cdn-icons-png.flaticon.com/24/3046/3046121.png" alt="TikTok" /></a>
          </p>
        </td>
        <td style="padding-left: 15px; font-size: 13px; color: #333">
          <p><img src="https://cdn-icons-png.flaticon.com/16/281/281769.png" alt="Email" /> info@faithtrip.net</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/733/733585.png" alt="WhatsApp" /> +8801896459490, +8801896459495</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/455/455705.png" alt="Phone" /> +09647649044</p>
          <p><img src="https://cdn-icons-png.flaticon.com/16/684/684908.png" alt="Map" /> Abedin Tower (Level 5),<br>35 Kamal Ataturk Avenue,<br>Banani, Dhaka - 1213, Bangladesh</p>
        </td>
      </tr>
    </table>

  </body>
</html>
EOD;
    sendMail($row['PassengerEmail'], "Passport Expiry Alert", $msg);
}

// PHPMailer Function
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@faithtrip.net';
        $mail->Password = 'kbjtsnmotgbwhwvw'; // Use Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error to $to: " . $mail->ErrorInfo);
    }
}

$conn->close();
?>
