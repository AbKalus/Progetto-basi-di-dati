<?php

	class Database {

		protected $connection;
		protected $dbhost = 'localhost';
		protected $dbuser = 'root';
		protected $dbpwd = '';
		protected $dbname = '';
		protected $charset = 'utf8';

		public function __construct() {
			$this->dbhost = 'localhost';
			$this->dbuser = 'root';
			$this->dbpwd = '';
			$this->dbname = 'smartgrid';
			$this->charset = 'utf8';
		}

		private function connect() {
			$this->connection = new mysqli($this->dbhost, $this->dbuser, $this->dbpwd, $this->dbname);
			if ($this->connection->connect_error) {
				$this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
			}
		}

		public function query_cluster_month($month){
			$this->connect();
			$stmt = "SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, t.timestamp_finale)) AS potenza_generata 
			FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
			WHERE month(t.timestamp_iniziale) = (?)
			GROUP BY e.cluster
			ORDER BY potenza_generata DESC;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("s", $month);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}

		public function query_cluster_interval($d1,$d2){
			$this->connect();
			$stmt = "SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, t.timestamp_finale)) AS potenza_generata
			FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
			WHERE t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
			GROUP BY e.cluster
			ORDER BY potenza_generata DESC;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("ss", $d1, $d2);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}
		
		public function query_elemento_consumo_tot($cit,$d1,$d2){
			$this->connect();
			$stmt = "SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, 
			SUM(t.quantita) AS quantita_consumata, SUM(t.quantita*f.prezzo) AS costo_consumo_totale 
			FROM trasferimento t
			INNER JOIN elemento e ON t.id_utilizzatore = e.id
			INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
			INNER JOIN tariffa f ON t.tariffa = f.fascia
			WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
			GROUP BY t.id_utilizzatore;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("sss", $cit,$d1, $d2);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}

		public function query_elemento_consumo_per_fascia($cit,$d1,$d2){
			$this->connect();
			$stmt = "SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, t.tariffa, f.prezzo,
			SUM(t.quantita) AS quantita_consumata, SUM(t.quantita)*f.prezzo AS costo_consumo_totale 
			FROM trasferimento t
			INNER JOIN elemento e ON t.id_utilizzatore = e.id
			INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
			INNER JOIN tariffa f ON t.tariffa = f.fascia
			WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
			GROUP BY t.tariffa;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("sss", $cit, $d1, $d2);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}

		public function query_provenienza_energia($cit,$d1,$d2){
			$this->connect();
			$stmt = "SELECT x.prod, x.util, x.qc, el.cap, el.via, el.civico, el.interno FROM (SELECT t.id_produttore AS prod, t.id_utilizzatore AS util, SUM(t.quantita) AS qc
			FROM trasferimento t
			INNER JOIN elemento e ON t.id_utilizzatore = e.id
			INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
			WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
			GROUP BY t.id_produttore) x INNER JOIN elemento el ON el.id = x.prod;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("sss", $cit, $d1, $d2);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}

		public function query_energia_prodotta_da_X($cit,$d1,$d2){
			$this->connect();
			$stmt = "SELECT c.cognome, c.nome, t.id_produttore, e.cap, e.via, e.civico, e.interno, SUM(t.quantita) AS quantita_prodotta, SUM(t.quantita*f.prezzo) AS ricavo 
			FROM trasferimento t
			INNER JOIN elemento e ON t.id_produttore = e.id
			INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
			INNER JOIN tariffa f ON t.tariffa = f.fascia
			WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?) AND t.tariffa <> 'F0'
			GROUP BY t.id_produttore;";
			
			$result = "";
			$stmt = $this->connection->prepare($stmt);
			$stmt->bind_param("sss", $cit, $d1, $d2);
			$stmt->execute();
			$result = $stmt->get_result();
			$datas = array();
			
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$datas[] = $row;
				}
			}
			$this->close();
			return $datas;
		}

		public function close() {
			return $this->connection->close();
		}
	}
?>
