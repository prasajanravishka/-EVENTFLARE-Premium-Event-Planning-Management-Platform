<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/database.php';

// If a POST request is sent directly to Contact.php, save the message then redirect to Home.php#contact
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = htmlspecialchars(trim($_POST['firstname'] ?? ''));
    $lastname = htmlspecialchars(trim($_POST['lastname'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!empty($firstname) && !empty($lastname) && !empty($email) && !empty($phone) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (firstname, lastname, email, phone, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $firstname, $lastname, $email, $phone, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
}
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}

header("Location: Home.php#contact", true, 302);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=Home.php#contact">
    <title>Redirecting to Contact - EVENTFLARE</title>
    <script>window.location.href = "Home.php#contact";</script>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px; background-color: #f7f5fc; color: #1e1538;">
    <p>Redirecting to <a href="Home.php#contact">Contact Concierge on EVENTFLARE</a>...</p>
</body>
</html>
<?php exit(); ?>
