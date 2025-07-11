<?php
header('Content-Type: application/json');

// Start logging
error_log("========== FORM SUBMISSION RECEIVED ==========");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw POST Data: " . file_get_contents('php://input'));

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check honeypot field for bots
if (!empty($_POST['website'])) {
    error_log("Spam detected - honeypot field was filled");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Spam detected']);
    exit;
}

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'address'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    error_log("Missing fields: " . implode(', ', $missing_fields));
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// Sanitize input data
$name = htmlspecialchars(trim($_POST['name']));
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(trim($_POST['phone']));
$address = htmlspecialchars(trim($_POST['address']));
$note = isset($_POST['note']) ? htmlspecialchars(trim($_POST['note'])) : '';

// Log the received data
error_log("Received Data:");
error_log("Name: $name");
error_log("Email: $email");
error_log("Phone: $phone");
error_log("Address: $address");
error_log("Note: $note");

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email address: $email");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Email configuration
$to = 'info@barristershub.co.uk';
$subject = 'New Appointment Request from ' . $name;

$message = "
<html>
<head>
    <title>New Appointment Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { color: #333; font-size: 18px; font-weight: bold; }
        .label { font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <h2 class='header'>New Appointment Request</h2>
    <p><span class='label'>Name:</span> $name</p>
    <p><span class='label'>Email:</span> $email</p>
    <p><span class='label'>Phone:</span> $phone</p>
    <p><span class='label'>Address:</span> $address</p>
    <p><span class='label'>Case Description:</span><br>" . nl2br($note) . "</p>
</body>
</html>
";

// Email headers
$headers = "From: $name <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Log the email content that will be sent
error_log("Email to be sent:");
error_log("To: $to");
error_log("Subject: $subject");
error_log("Headers: " . str_replace("\r\n", " | ", $headers));
error_log("Message: $message");

// Send email
try {
    $mail_sent = mail($to, $subject, $message, $headers);
    
    if ($mail_sent) {
        error_log("Email sent successfully");
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your appointment request. We will contact you soon.'
        ]);
    } else {
        error_log("Failed to send email");
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email. Please try again later.'
        ]);
    }
} catch (Exception $e) {
    error_log("Exception while sending email: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

error_log("========== PROCESSING COMPLETE ==========");
?>