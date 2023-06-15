-- CREAZIONE TRIGGER --

-- Trigger per controllare che lo stamp capacità sia inferire alla capacità massima della batteria --
DELIMITER $$
CREATE TRIGGER trg_beforeInsertStatoBatteria BEFORE INSERT ON stato_batteria FOR EACH ROW BEGIN
    DECLARE tmp_capacita FLOAT;
    SET tmp_capacita = (SELECT capacita FROM batteria WHERE numero_serie = NEW.batteria);
    IF tmp_capacita < NEW.stamp_capacita THEN
        SIGNAL sqlstate '45001' SET message_text = "Stamp capacità superiore alla capacità massima!";
    END IF;
END
$$
DELIMITER ;

-- Trigger per controllare che il produttore inserito sia effettivamente un produttore e l’utilizzatore sia effettivamente un utilizzatore --
DELIMITER $$
CREATE TRIGGER trg_beforeInsertProdUtil BEFORE INSERT ON trasferimento FOR EACH ROW BEGIN
	DECLARE tP CHAR(1);
    DECLARE tU CHAR(1);
    SET tP = (SELECT tipo FROM elemento WHERE id = NEW.id_produttore);
    SET tU = (SELECT tipo FROM elemento WHERE id = NEW.id_utilizzatore);
    IF (tP = 'U' AND tU ='P') THEN
        SIGNAL sqlstate '45001' SET message_text = "Elemento produttore ed elemento utilizzatore errati";
    ELSEIF (tP = 'U') THEN
        SIGNAL sqlstate '45001' SET message_text = "Stai cercando di inserire un elemento utilizzatore come produttore";
    ELSEIF (tU = 'P') THEN
        SIGNAL sqlstate '45001' SET message_text = "Stai cercando di inserire un elemento produttore come utilizzatore";
    END IF;
END
$$
DELIMITER ;

-- Trigger per controllare che la tariffa utilizzato per un trasferimento energetico tra elementi aventi lo stesso proprietario sia F0, se la tariffa è sbagliata viene corretta --
DELIMITER $$
CREATE TRIGGER trg_fascia_F0 BEFORE INSERT ON trasferimento FOR EACH ROW BEGIN
    DECLARE t VARCHAR(3);
    DECLARE tP VARCHAR(16);
    DECLARE tU VARCHAR(16);
    SET t = "F0";
    SET tP = (SELECT cittadino FROM elemento WHERE id = NEW.id_produttore);
    SET tU = (SELECT cittadino FROM elemento WHERE id = NEW.id_utilizzatore);
    IF (tP = tU) THEN
        IF (t <> NEW.tariffa) THEN
            INSERT INTO trasferimento SET tariffa = "F0";
        END IF;
    END IF;
END
$$
DELIMITER ;
