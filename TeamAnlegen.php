<!-- Anton Nguyen -->
<!-- Form for creating a new team and its team manager (Teamchef) in the database. -->

<?php
require_once "anton/functions.php";

$fehler  = "";
$erfolg  = false;

if (isset($_POST["submit"])) {
    $teamname  = trim($_POST["teamname"]);
    $vorname   = trim($_POST["firstname"]);
    $nachname  = trim($_POST["lastname"]);
    $loginname = trim($_POST["loginname"]);
    $kennwort  = $_POST["password"];

    $db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

    if (mysqli_connect_error()) {
        $fehler = "Datenbankverbindung fehlgeschlagen: " . mysqli_connect_error();
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);

        if (teamExistiert($db, $teamname)) {
            $fehler = "Teamname \"" . htmlspecialchars($teamname) . "\" ist bereits vergeben.";
        } else {
            $result = teamAnlegen($db, $teamname, $loginname, $vorname, $nachname, $kennwort);
            if ($result === "") {
                $erfolg = true;
            } else {
                $fehler = $result;
            }
        }

        $db->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Team Anlegen</title>
    <link rel="stylesheet" href="anton/style.css">
</head>
<body>

<h1>Team anlegen</h1>

<?php if ($erfolg): ?>
    <p style="color: green;">Team wurde erfolgreich angelegt!</p>
<?php elseif ($fehler !== ""): ?>
    <p style="color: red;"><?php echo htmlspecialchars($fehler); ?></p>
<?php endif; ?>

<?php if (!$erfolg): ?>
<form method="post" action="">

    <label>Teamname:</label><br>
    <input type="text" name="teamname" value="<?php echo htmlspecialchars($_POST["teamname"] ?? ""); ?>" required>
    <br><br>

    <label>Vorname Teamchef:</label><br>
    <input type="text" name="firstname" value="<?php echo htmlspecialchars($_POST["firstname"] ?? ""); ?>" required>
    <br><br>

    <label>Nachname Teamchef:</label><br>
    <input type="text" name="lastname" value="<?php echo htmlspecialchars($_POST["lastname"] ?? ""); ?>" required>
    <br><br>

    <label>Loginname:</label><br>
    <input type="text" name="loginname" value="<?php echo htmlspecialchars($_POST["loginname"] ?? ""); ?>" required>
    <br><br>

    <label>Passwort:</label><br>
    <input type="password" name="password" required>
    <br><br>

    <input type="submit" name="submit" value="Team anlegen">

</form>
<?php endif; ?>

<br>
<a href="niklas/index.php">Zurück zur Startseite</a>

</body>
</html>
