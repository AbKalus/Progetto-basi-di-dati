CREATE DATABASE IF NOT EXISTS smartgrid;
USE smartgrid;

-- CREAZIONE TABELLE --

-- Tabella cluster --
CREATE TABLE cluster (
    codice INT PRIMARY KEY AUTO_INCREMENT,
    zona VARCHAR(50) NOT NULL
); 

-- Tabella elemento --
CREATE TABLE elemento (
    id VARCHAR(50) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    cap VARCHAR(5) NOT NULL,
    via VARCHAR(75) NOT NULL,
    civico INT NOT NULL,
    interno INT NULL,
    tipo CHAR(1) NOT NULL,
    cluster INT NOT NULL,
    cittadino VARCHAR(16) NULL,
    UNIQUE(cap, via, civico, interno),
    CONSTRAINT FK_elemento_cluster FOREIGN KEY (cluster) REFERENCES cluster (codice),
    CONSTRAINT FK_elemento_cittadino FOREIGN KEY (cittadino) REFERENCES cittadino (codice_fiscale)
); 

-- Tabella rilevamento potenza --
CREATE TABLE rilevamento_potenza (
    cluster INT NOT NULL,
    anno YEAR NOT NULL,
    mese VARCHAR(10) NOT NULL,
    dataset TEXT NOT NULL,
    PRIMARY KEY(cluster, anno, mese),
    CONSTRAINT FK_rilPotenza_cluster FOREIGN KEY (cluster) REFERENCES cluster (codice)
);

-- Tabella batteria --
CREATE TABLE batteria (
    numero_serie VARCHAR(30) PRIMARY KEY,
    marca VARCHAR(50) NOT NULL,
    capacita FLOAT NOT NULL,
    data_montaggio DATE NOT NULL,
    elemento VARCHAR(50) NOT NULL,
    CHECK(capacita > 0),
    CONSTRAINT FK_batteria_elemento FOREIGN KEY (elemento) REFERENCES elemento (id)
);

-- Tabella stato batteria --
CREATE TABLE stato_batteria (
    batteria VARCHAR(30) NOT NULL,
    timestamp DATETIME NOT NULL,
    stamp_capacita FLOAT NOT NULL,
    CHECK(stamp_capacita > 0),
    PRIMARY KEY(batteria, timestamp),
    CONSTRAINT FK_statobatteria_batteria FOREIGN KEY (batteria) REFERENCES batteria (numero_serie)
);

-- Tabella cittadino --
CREATE TABLE cittadino (
    codice_fiscale VARCHAR(16) PRIMARY KEY,
    mail VARCHAR(100) NOT NULL,
    sha_pwd VARCHAR(128) NOT NULL,
    cognome VARCHAR(60) NOT NULL,
    nome VARCHAR(60) NOT NULL,
    UNIQUE(mail)
);

-- Tabella trasferimento --
CREATE TABLE trasferimento (
    id_produttore VARCHAR(50) NOT NULL,
    id_utilizzatore VARCHAR(50) NOT NULL,
    timestamp_iniziale DATETIME NOT NULL,
    timestamp_finale DATETIME NOT NULL,
    tariffa VARCHAR(3) NOT NULL,
    quantita FLOAT NOT NULL,
    CHECK(quantita > 0),
    CHECK(timestamp_iniziale <= timestamp_finale),
    PRIMARY KEY(id_produttore, id_utilizzatore, timestamp_iniziale, timestamp_finale),
    CONSTRAINT FK_trasferimento_elementoProd FOREIGN KEY (id_produttore) REFERENCES elemento (id),
    CONSTRAINT FK_trasferimento_elementoUtil FOREIGN KEY (id_utilizzatore) REFERENCES elemento (id),
    CONSTRAINT FK_trasferimento_tariffa FOREIGN KEY (tariffa) REFERENCES tariffa (fascia)
);

-- Tabella tariffa --
CREATE TABLE tariffa (
    fascia VARCHAR(3) PRIMARY KEY,
    dalle TIME NOT NULL,
    alle TIME NOT NULL,
    il VARCHAR(30) NOT NULL,
    prezzo FLOAT NOT NULL,
    CHECK(prezzo > 0)
);


--CREAZIONE STORE PROCEDURE --

-- Stored Procedure per ottenere la classifica dei cluster in un mese --
DELIMITER $$
CREATE PROCEDURE sp_cluster_1(IN mese VARCHAR(2))
BEGIN
    SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, t.timestamp_finale)) AS potenza_generata 
    FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
    WHERE month(t.timestamp_iniziale) = mese
    GROUP BY e.cluster
    ORDER BY potenza_generata DESC;    
END
$$
DELIMITER ;

-- Stored Procedure per ottenere la classifica dei cluster in un intervallo di tempo --
DELIMITER $$
CREATE PROCEDURE sp_cluster_2(IN d1 DATETIME, IN d2 DATETIME)
BEGIN
    SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, 
t.timestamp_finale)) AS potenza_generata
    FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
    WHERE t.timestamp_iniziale >= d1 AND t.timestamp_finale <= d2
    GROUP BY e.cluster
    ORDER BY potenza_generata DESC;  
END
$$
DELIMITER ;

-- Stored Procedure per ottenere il consumo degli elementi di una persona --
DELIMITER $$
CREATE PROCEDURE sp_consumo_1(IN cit VARCHAR(100),IN d1 DATETIME, IN d2 DATETIME)
BEGIN
    SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, SUM(t.quantita) AS quantita_consumata,
    SUM(t.quantita*f.prezzo) AS costo_consumo_totale 
    FROM trasferimento t
    INNER JOIN elemento e ON t.id_utilizzatore = e.id
    INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
    INNER JOIN tariffa f ON t.tariffa = f.fascia
    WHERE c.mail = cit AND t.timestamp_iniziale >= d1 AND t.timestamp_finale <= d2
    GROUP BY t.id_utilizzatore;
END
$$
DELIMITER ;

-- Stored Procedure per ottenere il consumo degli elementi di una persona, suddivisi per fascia --
DELIMITER $$
CREATE PROCEDURE sp_consumo_2(IN cit VARCHAR(100),IN d1 DATETIME, IN d2 DATETIME)
BEGIN
    SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, t.tariffa, f.prezzo,
    SUM(t.quantita) AS quantita_consumata, SUM(t.quantita)*f.prezzo AS costo_consumo_totale 
    FROM trasferimento t
    INNER JOIN elemento e ON t.id_utilizzatore = e.id
    INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
    INNER JOIN tariffa f ON t.tariffa = f.fascia
    WHERE c.mail = cit AND t.timestamp_iniziale >= d1 AND t.timestamp_finale <= d2
    GROUP BY t.tariffa;
END
$$
DELIMITER ;

-- Stored Procedure per ottenere i dati relativi agli elementi da cui è stata prelevata l’energia consumata degli elementi di una persona --
DELIMITER $$
CREATE PROCEDURE sp_info_consumo_1(IN cit VARCHAR(100),IN d1 DATETIME, IN d2 DATETIME)
BEGIN
    SELECT x.prod, x.util, x.qc, el.cap, el.via, el.civico, el.interno FROM 
        (SELECT t.id_produttore AS prod,
        t.id_utilizzatore AS util, SUM(t.quantita) AS qc
        FROM trasferimento t
        INNER JOIN elemento e ON t.id_utilizzatore = e.id
        INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
        WHERE c.mail = cit AND t.timestamp_iniziale >= d1 AND t.timestamp_finale <= d2
        GROUP BY t.id_produttore) x 
    INNER JOIN elemento el ON el.id = x.prod;
END
$$
DELIMITER ;

-- Stored Procedure per ottenere i ricavi dai vari elementi di una persona --
DELIMITER $$
CREATE PROCEDURE sp_ricavo_1(IN cit VARCHAR(100),IN d1 DATETIME, IN d2 DATETIME)
BEGIN
    SELECT c.cognome, c.nome, t.id_produttore, e.cap, e.via, e.civico, e.interno,
    SUM(t.quantita) AS quantita_prodotta, SUM(t.quantita*f.prezzo) AS ricavo 
    FROM trasferimento t
    INNER JOIN elemento e ON t.id_produttore = e.id
    INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
    INNER JOIN tariffa f ON t.tariffa = f.fascia
    WHERE c.mail = cit AND t.timestamp_iniziale >= d1 AND t.timestamp_finale <= d2 AND t.tariffa <> 'F0'
    GROUP BY t.id_produttore;
END
$$
DELIMITER ;

-- Stored Procedure per inserire lo stato di una batteria --
DELIMITER $$
CREATE PROCEDURE sp_insert_stato_batteria (IN batteria VARCHAR(30), IN timestamp DATETIME, IN stamp_capacita FLOAT)
BEGIN
    INSERT INTO stato_batteria (batteria, timestamp, stamp_capacita) 
    VALUES (batteria, timestamp, stamp_capacita);
END
$$
DELIMITER ;

-- Stored Procedure per inserire i dataset dei rilevamenti delle potenze --
DELIMITER $$
CREATE PROCEDURE sp_insert_rilevazione (IN elemento VARCHAR(50), IN anno YEAR, IN mese VARCHAR(10), IN dataset TEXT)
BEGIN
    INSERT INTO rilevamento_potenza (elemento, anno, mese, dataset) 
    VALUES (elemento, anno, mese, dataset);
END
$$
DELIMITER ;
