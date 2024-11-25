<?php
require 'vendor/autoload.php';
use SendGrid\Mail\Mail;

// Configuration
$emailTo = "security@example.com";
$emailFrom = "csp-reports@example.com";
$emailSubject = "Content Security Policy Violation Report";
$sendgridApiKey = 'YOUR_SENDGRID_API_KEY';
$allowedOrigin = 'https://example.com';

// Rate limiting configuration
$rateLimitFile = '/tmp/csp_rate_limit.json';
$maxRequests = 10;
$timeWindow = 60;

// Validate origin
$headers = getallheaders();
if (!isset($headers['Origin']) || $headers['Origin'] !== $allowedOrigin) {
    error_log("Invalid origin: " . ($headers['Origin'] ?? 'none'));
    http_response_code(403);
    exit();
}

// Check Content-Type
if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/csp-report') {
    error_log("Invalid content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'none'));
    http_response_code(415);
    exit();
}

// Implement rate limiting
$rateData = [];
if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true);
}

// Clean old entries and check rate limit
$rateData = array_filter($rateData, fn($time) => $time > time() - $timeWindow);
if (count($rateData) >= $maxRequests) {
    error_log("Rate limit exceeded for IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(429);
    exit();
}

// Add current request to rate limit tracking
$rateData[] = time();
file_put_contents($rateLimitFile, json_encode($rateData));

// Get and validate input
$jsonData = file_get_contents('php://input');
if (strlen($jsonData) > 50000) {
    error_log("Payload too large from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(413);
    exit();
}

// Decode and validate JSON
$report = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($report['csp-report'])) {
    error_log("Invalid JSON from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(400);
    exit();
}

// Format and sanitize report data
$reportData = "CSP Violation Report\n";
$reportData .= "===================\n\n";

foreach ($report['csp-report'] as $key => $value) {
    if (is_string($value)) {
        $key = htmlspecialchars($key);
        $value = htmlspecialchars($value);
        $reportData .= ucfirst($key) . ": " . $value . "\n";
    }
}

$reportData .= "\nServer Details:\n";
$reportData .= "Time: " . date('Y-m-d H:i:s') . "\n";
$reportData .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$reportData .= "Origin: " . $headers['Origin'] . "\n";

// Create and send email
$email = new Mail();
$email->setFrom($emailFrom);
$email->setSubject($emailSubject);
$email->addTo($emailTo);
$email->addContent("text/plain", $reportData);

$sendgrid = new \SendGrid($sendgridApiKey);
try {
    $response = $sendgrid->send($email);
    http_response_code(204);
} catch (Exception $e) {
    error_log("SendGrid Error: " . $e->getMessage());
    http_response_code(500);
    exit();
}
