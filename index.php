<!DOCTYPE html>
<html>
<head>
    <title>Anmelden</title>
</head>
<body>

<h1>Als Teamchef anmelden</h1>

<?php if (isset($_GET["fehler"])): ?>
    <p style="color:red;">Loginname oder Kennwort falsch. Bitte erneut versuchen.</p>
<?php endif; ?>

<form method="post" action="anton/dashboard.php">
    <label>Loginname:</label><br>
    <input type="text" name="loginname" required><br><br>

    <label>Kennwort:</label><br>
    <input type="password" name="kennwort" required><br><br>

    <input type="submit" name="gesendet" value="Anmelden">
</form>

<br>
<a href="TeamAnlegen.php">Noch kein Team? Jetzt Team anlegen</a>

</body>
</html>