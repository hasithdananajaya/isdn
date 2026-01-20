<?php
function isdn_log_email($line) {
  $dir = __DIR__ . '/logs';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);
  $file = $dir . '/email.log';
  @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
}

function send_isdn_mail($to, $subject, $html) {
  $to = trim((string)$to);
  $subject = trim((string)$subject);
  if ($to === '' || $subject === '') {
    isdn_log_email("SKIPPED to=$to subject=$subject (empty)");
    return false;
  }

  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    isdn_log_email("SKIPPED to=$to subject=$subject (invalid email)");
    return false;
  }

  $headers = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8\r\n";
  $headers .= "From: ISDN Notifications <no-reply@isdn.local>\r\n";
  $headers .= "Reply-To: ISDN Support <support@isdn.local>\r\n";
  $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

  $ok = false;
  $errorMsg = '';
  try {
    $ok = @mail($to, $subject, $html, $headers);
    if (!$ok) {
      $errorMsg = error_get_last() ? error_get_last()['message'] : 'Unknown error';
    }
  } catch (Throwable $e) {
    $ok = false;
    $errorMsg = $e->getMessage();
  }

  isdn_log_email(($ok ? 'SENT' : 'FAILED') . " to=$to subject=" . $subject . ($errorMsg ? " error=$errorMsg" : ''));
  return $ok;
}

function render_email_template($title, $bodyHtml) {
  $titleEsc = htmlspecialchars((string)$title);
  return '
  <div style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;padding:24px">
    <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e8e9f2">
      <div style="background:linear-gradient(135deg,#10b981 0%,#059669 50%,#047857 100%);padding:18px 20px;color:#fff;background-size:200% 200%">
        <div style="font-weight:900;font-size:16px;letter-spacing:.2px">IslandLink (ISDN)</div>
        <div style="opacity:.9">Sales Distribution Network</div>
      </div>
      <div style="padding:18px 20px;color:#121624">
        <h2 style="margin:0 0 10px 0">' . $titleEsc . '</h2>
        <div style="line-height:1.6">' . $bodyHtml . '</div>
      </div>
      <div style="padding:14px 20px;border-top:1px solid #eef0f7;color:#6a6f85;font-size:12px">
        This is an automated message. Please do not reply.
      </div>
    </div>
  </div>';
}

