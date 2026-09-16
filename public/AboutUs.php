<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Location: Home.php#about", true, 302);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=Home.php#about">
    <title>Redirecting to About Us - EVENTFLARE</title>
    <script>window.location.href = "Home.php#about";</script>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px; background-color: #f7f5fc; color: #1e1538;">
    <p>Redirecting to <a href="Home.php#about">About Us on EVENTFLARE</a>...</p>
</body>
</html>
<?php exit(); ?>
