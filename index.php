<?php
session_start();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Webszolgáltatás Kezdőlap</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Üdvözlünk a Webhoszting kereskedő oldalunk között</h1>
    <div class="button-container">
        <form action="sighin.php" method="get" class="button-box">
            <button type="submit">Regisztráció</button>
            <p>Hozz létre új felhasználói fiókot.</p>
        </form>

        <form action="login.php" method="get" class="button-box">
            <button type="submit">Bejelentkezés</button>
            <p>Jelentkezz be meglévő fiókoddal.</p>
        </form>

        <form action="packages.php" method="get" class="button-box">
            <button type="submit">Belépés, mint vendég</button>
            <p>Böngéssz a szolgáltatási csomagjaink között, mint vendég.</p>
        </form>
    </div>
</body>
</html>
