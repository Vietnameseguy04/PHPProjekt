
<!-- Anton Nguyen -->
<!-- Homepage after login with navigation to the management areas of the logged-in team. -->

<?php

session_start();

// Handle login form submission: validate credentials against the database
if (isset($_POST["loginname"])) {
    $loginName = isset($_POST["loginname"]) ? $_POST["loginname"] : "";
    $password  = isset($_POST["kennwort"]) ? $_POST["kennwort"] : "";

    $db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

    if (mysqli_connect_error()) {
        die("Fehler: " . mysqli_connect_error());
    }

    // Join Teamchef with Team to verify credentials and fetch the team name in one query
    $loginQuery = $db->prepare(
        "SELECT t.TName
         FROM Teamchef tc
         JOIN Team t ON tc.Loginname = t.Loginname
         WHERE tc.Loginname = ? AND tc.Kennwort = ?"
    );
    $loginQuery->bind_param("ss", $loginName, $password);
    $loginQuery->execute();
    $result = $loginQuery->get_result();
    $row    = $result->fetch_assoc();
    $loginQuery->close();
    $db->close();

    if ($row) {
        // Generate a new session ID after login to prevent session fixation attacks
        session_regenerate_id(true);
        // Store loginname and team name in session so all pages can access them
        $_SESSION["loginname"] = $loginName;
        $_SESSION["tname"]     = $row["TName"];
        header("Location: dashboard.php");
        exit();
    } else {
        // Wrong credentials: redirect back to login page with error flag
        header("Location: ../niklas/index.php?fehler=1");
        exit();
    }
}

// Guard: redirect unauthenticated users to the login page
if (!isset($_SESSION["tname"])) {
    header("Location: ../niklas/index.php");
    exit();
}
?>
<!DOCTYPE html>

<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Dashboard – Team: <?php echo htmlspecialchars($_SESSION["tname"]); ?></h1>
<p>Eingeloggt als: <strong><?php echo htmlspecialchars($_SESSION["loginname"]); ?></strong> | <a href="logout.php">Abmelden</a></p>

<hr>

<h2>Fahrer</h2>
<?php include "driver.php"; ?>

<hr>

<h2>Trainings</h2>
<?php include "../christina/trainings.php"; ?>

<hr>

<h2>Rennen</h2>
<?php include "races.php"; ?>

<hr>

<h2>Auswertung</h2>
<?php include "../christina/auswertung.php"; ?>




</body>
</html>
