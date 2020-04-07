<?php

// namespace Mailer;
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Load Composer's autoloader
// require __DIR__ . '/vendor/autoload.php';
// Function to validate against any email injection attempts

$config;
$dataDirectory;
$configPath;
$dateNow = new DateTime("now", new DateTimeZone('UTC'));


function run()
{
  global $configPath, $dataDirectory, $configPath;

  $dataDirectory = 'recordedData';
  $configPath = 'contact.json';

  function readConfig()
  {
    global $configPath, $dataDirectory, $configPath;

    if (!file_exists($configPath)) {
      throw new Exception("Please provide" . $configPath . 'config file');
    }
    $cfg = file_get_contents($configPath);
    $config = json_decode(($cfg));
    if ($config->adminEmail || $config->adminPassword) {
      throw new Exception('Either "adminEmail" or "adminPassword" is missing');
    }
    if (isset($config->dataDirectory)) {
      $dataDirectory = $config->dataDirectory;
    }
    if (isset($config->configPath)) {
      $dataDirectory = $config->configPath;
    }
  }
  function checkPreriquisite()
  {
    global $dataDirectory;

    if (!is_dir($dataDirectory)) {
      throw new Exception('Please create "recordData" directory in the calling script folder');
    }
    clearstatcache();
  }
  function isInjected($str)
  {
    $injections = array(
      '(\n+)',
      '(\r+)',
      '(\t+)',
      '(%0A+)',
      '(%0D+)',
      '(%08+)',
      '(%09+)'
    );
    $inject = join('|', $injections);
    $inject = "/$inject/i";
    if (preg_match($inject, $str)) {
      return true;
    } else {
      return false;
    }
  }
  function startMailer($userName, $password)
  {
    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);
    //Server settings
    // $mail->SMTPDebug = 2;                                       // Enable verbose debug output
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = 'localhost';  // Specify main and backup SMTP servers
    $mail->SMTPAuth   = false;
    $mail->SMTPAutoTLS = false; // Enable SMTP authentication
    //   $mail->Username   = $userName;                     // SMTP username
    //   $mail->Password   = $password;                               // SMTP password
    //   $mail->SMTPSecure = false;                                  // Enable TLS encryption, `ssl` also accepted
    $mail->Port       = 25;                                    // TCP port to connect to
    $mail->setFrom($userName, 'POPProbe');
    return $mail;
  }

  function writeToUserList($userLists, $data)
  {
    $userContact = $data[1];
    $userName = $data[1];
    $time = $data[1];
    if (count($data) > 3) {
      $userName = $data[1];
      $userContact = $data[2];
      $time = $data[3];
    } else {
      $userContact = 'NA';
      $userName = 'NA';
    }
    array_unshift($userLists, ["value" => $data[0], 'name' => $userName, 'contact' => $userContact, "time" => $time]);
  }
  function sendToUser($n, $visitor_email)
  {
    global $dateNow, $config;
    if ($n > 99) {
      return false;
    }
    $n = $n + 1;
    try {
      $mail = startMailer($config->adminEmail, $config->adminPassword);
      $smarty = new Smarty();
      $smarty->assign('to', $visitor_email);
      $smarty->assign('monthYear', $dateNow->format('M, Y'));
      $mail->addAddress($visitor_email);     // Add a recipient
      $mail->isHTML(true);                                  // Set email format to HTML
      $mail->Subject = 'Welcome to POPProbe';
      $mail->Body =  $smarty->fetch('extra/emailTemplates/thankYouToUser.tpl');
      $mail->AltBody = "Hi, $visitor_email, Thank you for registering on POPProbe.";
      $mail->send();
    } catch (Exception $e) {
      sendToUser($n, $visitor_email);
    }
  }
  function sendToAdmin($n, $visitor_email, $userLists)
  {
    global  $dateNow, $config;
    if ($n > 99) {
      throw new Exception('Message could not be sent.');
    }
    $n = $n + 1;
    try {
      $mail = startMailer($config->adminEmail, $config->adminPassword);
      $smarty = new Smarty();
      $smarty->assign('to', $config->adminName);
      $smarty->assign('userEmails', $userLists);
      $smarty->assign('monthYear', $dateNow->format('M, Y'));
      //Recipients
      // $mail->addAddress('carolyn.peer@humaxa.com', 'Carolyn');     // Add a recipient
      foreach ($config->adminRecipients as $key => $value) {
        $mail->addAddress($value['email'], $value['name']);     // Add a recipient

      }
      // Attachments

      // $mail->addAttachment($fileName, 'UsersList.csv');         // Add attachments
      $mail->isHTML(true);                                  // Set email format to HTML
      $mail->Subject = 'New Subscription on POPProbe';
      $mail->Body    = $smarty->fetch('extra/emailTemplates/whenUserSendEmail.tpl');
      $mail->AltBody = "Newly added user $visitor_email";
      $mail->send();
      return true;
    } catch (Exception $e) {
      sendToAdmin($n, $visitor_email, $userLists);
    }
  }
  function init()
  {
    global $dataDirectory, $dateNow, $config;

    $res = "";
    if (!isset($_POST['submit'])) {
      //This page should not be accessed directly. Need to submit the form.
      throw new Exception("you need to submit the form!");
    }
    $visitor_email = $_POST['email'];
    $visitor_name = $_POST['user-name'];
    $visitor_number = $_POST['message'];
    if (empty($visitor_email) && empty($visitor_name)) {
      throw new Exception("Please fill the mandatory fields");
    }
    if (isInjected($visitor_email)) {
      throw new Exception("Bad Email");
    }

    $fileName = $dataDirectory . $dateNow->format('Y-M') . ".csv";
    $formattedTime = $dateNow->format('d-M-y H:i:s T');
    $file = fopen($fileName, "a+");
    // write header if file is empty
    clearstatcache();
    if (!filesize($fileName)) {
      fputcsv($file, ['Email', 'Name', 'Contact', 'Time']);
    }
    // rewind($file);
    $userLists = [];
    $isUserDuplicate = false;
    while (($data = fgetcsv($file)) != FALSE) {
      if (strcasecmp($data[0], $visitor_email) === 0) {
        // found duplicate;
        $isUserDuplicate = true;
        break;
      }
      writeToUserList($userLists, $data);
    }

    if (!$isUserDuplicate) {
      // your file is not empty
      array_unshift($userLists, ["value" => $visitor_email, 'name' => $visitor_name, 'contact' => $visitor_number, "time" => $formattedTime]);
      //   echo 'not duplicate';
      array_pop($userLists);
      // to Admin
      $isSent =  sendToAdmin(0, $visitor_email, $userLists);
      $res = $isSent && "Successfully registered.";
    }

    // thank you mail back to user
    if (isset($config->sendGreeting)) {

      $isGreeted = sendToUser(0, $visitor_email);
      $res = $isGreeted && 'Successfully registered but we are unable to send you our greetings.';
    }
    if (!$isUserDuplicate) {
      fputcsv($file, [$visitor_email, $visitor_name, $visitor_number || 'NA', $formattedTime]);
    }
    fclose($file);
    return $res;
  }

  try {
    readConfig();
    checkPreriquisite();
    $res = init();
    echo json_encode([
      "message" => $res,
      "error" => false
    ]);
  } catch (Exception $e) {
    // echo $e->getMessage();
    // var_dump($e);
    echo json_encode([
      "message" => $e->getMessage(),
      "error" => true
    ]);
  }
  exit;
}
