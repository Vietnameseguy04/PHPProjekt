-- Anton Nguyen
-- Trigger: startnummer_vergabe_Anton_Nguyen
-- Purpose:
-- Automatically assigns a sequential start number (StartNr)
-- for each new participant within a race (RID).

DELIMITER $$

CREATE TRIGGER startnummer_vergabe_Anton_Nguyen
BEFORE INSERT ON Teilnahme
FOR EACH ROW
BEGIN

    DECLARE maxNr INT;

    -- COALESCE replaces NULL with 0, so the first start number begins at 1 (0 + 1)
    SELECT COALESCE(MAX(StartNr), 0)
    INTO maxNr
    FROM Teilnahme
    WHERE RID = NEW.RID;

    SET NEW.StartNr = maxNr + 1;

END$$

DELIMITER ;