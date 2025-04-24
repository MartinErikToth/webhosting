<?php
session_start();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webszolgáltatás Kezdőlap</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<header>
    <nav>
        <div class="logo">
            <h1>WebHosting</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Kezdőlap</a></li>
            <li><a href="services.php">Szolgáltatások</a></li>
            <li><a href="about.php">Rólunk</a></li>
            <li><a href="contact.php">Kapcsolat</a></li>
        </ul>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <h2>Üdvözlünk a WebHosting platformon!</h2>
        <p>A legjobb hely a webhelyed számára, gyors, megbízható és biztonságos szolgáltatásokkal.</p>
        <div class="cta-buttons">
            <a href="singup.php" class="cta-button">Regisztráció</a>
            <a href="login.php" class="cta-button">Bejelentkezés</a>
            <a href="packages.php" class="cta-button">Belépés, mint vendég</a>
        </div>
    </div>
</section>

<section class="features">
    <h2>Miért válassz minket?</h2>
    <div class="features-container">
        <div class="feature-box">
            <h3>Gyors és Biztonságos</h3>
            <p>A legújabb technológia, amely biztosítja, hogy a weboldalad gyorsan és biztonságosan működjön.</p>
        </div>
        <div class="feature-box">
            <h3>Felhasználóbarát</h3>
            <p>Egyszerű kezelőfelület, amely lehetővé teszi, hogy könnyedén kezelhesd fiókodat és szolgáltatásaidat.</p>
        </div>
        <div class="feature-box">
            <h3>24/7 Ügyfélszolgálat</h3>
            <p>Mindig elérhető vagyunk, ha szükséged van segítségre. Profi támogatás, bármikor!</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2025 WebHosting | Minden jog fenntartva</p>
</footer>

</body>
</html>
