<?php
/**
 * Simple Contact Form Email Handler
 * Perfect for shared hosting - no complex servers needed!
 */

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method not allowed";
    exit();
}

// Get form data
$firstName = trim($_POST['first-name'] ?? '');
$lastName = trim($_POST['last-name'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['comment'] ?? '');

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo "Please fill in all required fields";
    exit();
}

// Basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Please enter a valid email address";
    exit();
}

// Email configuration
$to = 'applications@zanzibaronline.online'; // Your email address
$subjectLine = 'Contact Form Submission: ' . $subject;
$messageBody = "Name: $firstName $lastName\n";
$messageBody .= "Email: $email\n";
$messageBody .= "Telephone: $telephone\n";
$messageBody .= "Subject: $subject\n\n";
$messageBody .= "Message:\n$message";

// Email headers
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
if (mail($to, $subjectLine, $messageBody, $headers)) {
    // Success - redirect back to contact page with success message
    header("Location: index.html?success=1");
    exit();
} else {
    // Failed to send
    http_response_code(500);
    echo "Sorry, there was an error sending your message. Please try again or contact us directly.";
    exit();
}
?>