<?php
// Anton Nguyen
// Shared PHP functions for team and driver management.


function teamExistiert(mysqli $db, string $teamName): bool {
    $stmt = $db->prepare("SELECT TName FROM Team WHERE TName = ?");
    $stmt->bind_param("s", $teamName);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();
    return $exists;
}


function teamAnlegen(mysqli $db, string $teamName, string $loginName, string $vorname, string $nachname, string $kennwort): string {
    $insertChef = $db->prepare(
        "INSERT INTO Teamchef (Loginname, VName, NName, Kennwort) VALUES (?, ?, ?, ?)"
    );
    $insertChef->bind_param("ssss", $loginName, $vorname, $nachname, $kennwort);

    if (!$insertChef->execute()) {
        $error = "Fehler beim Teamchef: " . $insertChef->error;
        $insertChef->close();
        return $error;
    }
    $insertChef->close();

    $insertTeam = $db->prepare(
        "INSERT INTO Team (TName, Loginname) VALUES (?, ?)"
    );
    $insertTeam->bind_param("ss", $teamName, $loginName);

    if (!$insertTeam->execute()) {
        $error = "Fehler beim Team: " . $insertTeam->error;
        $insertTeam->close();
        return $error;
    }
    $insertTeam->close();

    return "";
}


function fahrerSpeichern(mysqli $db, string $id, string $oldId, string $tname, string $name, string $strasse, string $hnr, string $plz, string $ort, string $telNr): string {

    // Auto-generate MID for new drivers
    if ($oldId === "") {
        $maxResult = $db->query("SELECT MAX(MID) AS maxid FROM Fahrer");
        if ($maxResult && ($row = $maxResult->fetch_assoc()) && $row["maxid"] !== null) {
            $id = (string)((int)$row["maxid"] + 1);
            $maxResult->free();
        } else {
            if ($maxResult) $maxResult->free();
            $id = "1";
        }
    }

    if ($oldId !== "") {
        // Update existing driver 
        $stmt = $db->prepare(
            "UPDATE Fahrer SET MID=?, TName=?, Name=?, Strasse=?, HNR=?, PLZ=?, Ort=?, TelNr=? WHERE MID=?"
        );
        $stmt->bind_param("sssssssss", $id, $tname, $name, $strasse, $hnr, $plz, $ort, $telNr, $oldId);

        if (!$stmt->execute()) {
            $error = "Fehler: " . $stmt->error;
            $stmt->close();
            return $error;
        }
        $stmt->close();
    } else {
        // Check if the auto-generated MID is already taken
        $check = $db->prepare("SELECT MID FROM Fahrer WHERE MID = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $idExists = $check->get_result()->fetch_assoc() !== null;
        $check->close();

        if ($idExists) {
            return "Fehler: Mitarbeiter-ID existiert bereits!";
        }

        $stmt = $db->prepare(
            "INSERT INTO Fahrer (MID, TName, Name, Strasse, HNR, PLZ, Ort, TelNr) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssss", $id, $tname, $name, $strasse, $hnr, $plz, $ort, $telNr);

        if (!$stmt->execute()) {
            $error = "Fehler: " . $stmt->error;
            $stmt->close();
            return $error;
        }
        $stmt->close();
    }

    return "";
}
