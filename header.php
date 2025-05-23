<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <h1>WebHosting</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Kezdőlap</a></li>
            <li><a href="webszolgaltatasok.php">Domain vásárlás</a></li>
            <li><a href="tudastar.php">Tudástár</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="profile.php">Profilom</a></li>
                <li><a href="logout.php">Kijelentkezés</a></li>
            <?php else: ?>
                <li><a href="login.php">Bejelentkezés</a></li>
            <?php endif; ?>
            
			<?php if (isset($_SESSION['user_id'], $_SESSION['szerep']) && $_SESSION['szerep'] === 'szerkeszto'): ?>
				<li><a href="admin.php">Admin</a></li>
			<?php endif; ?>
            <li><a href="stat.php">Statisztikák</a></li>
        </ul>
    </nav>
</header>
</body>
</html>
