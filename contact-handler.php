<?php
declare(strict_types=1);

// Update this address before going live.
$destinationEmail = 'you@example.com';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.html#contact-error');
    exit;
}

$redirectError = '/index.html#contact-error';
$redirectSuccess = '/thank-you.html';

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$emailConsent = isset($_POST['email_consent']) && $_POST['email_consent'] === 'yes';
$smsConsent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'yes';
$honeypot = trim((string) ($_POST['website'] ?? ''));

if ($honeypot !== '') {
    header('Location: ' . $redirectSuccess);
    exit;
}

if ($firstName === '' || $email === '' || $message === '') {
    header('Location: ' . $redirectError);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $redirectError);
    exit;
}

if ($smsConsent && $phone === '') {
    header('Location: ' . $redirectError);
    exit;
}

$headerPattern = '/(content-type:|bcc:|cc:|to:|mime-version:|multipart\/mixed|\r|\n)/i';
foreach ([$firstName, $lastName, $email, $phone] as $fieldValue) {
    if (preg_match($headerPattern, $fieldValue)) {
        header('Location: ' . $redirectError);
        exit;
    }
}

$sanitizeText = static function (string $value): string {
    return trim(strip_tags($value));
};

$firstName = $sanitizeText($firstName);
$lastName = $sanitizeText($lastName);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$phone = $sanitizeText($phone);
$message = trim(strip_tags($message));

$fullName = trim($firstName . ' ' . $lastName);
$submittedAt = date('Y-m-d H:i:s T');

$emailBody = "Small Business Big AI - Contact Form Submission\n";
$emailBody .= "===========================================\n";
$emailBody .= "Name: {$fullName}\n";
$emailBody .= "Email: {$email}\n";
$emailBody .= "Phone: " . ($phone !== '' ? $phone : 'Not provided') . "\n";
$emailBody .= "Date/Time: {$submittedAt}\n";
$emailBody .= "Email Consent: " . ($emailConsent ? 'Yes' : 'No') . "\n";
$emailBody .= "SMS Consent: " . ($smsConsent ? 'Yes' : 'No') . "\n\n";
$emailBody .= "Message:\n{$message}\n";

$subject = 'New Contact Form Submission - Small Business Big AI';
$headers = [
    'From: Small Business Big AI <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8',
];

$mailSent = mail($destinationEmail, $subject, $emailBody, implode("\r\n", $headers));

if (!$mailSent) {
    header('Location: ' . $redirectError);
    exit;
}

header('Location: ' . $redirectSuccess);
exit;

