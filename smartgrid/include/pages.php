<?php
    $db = new Database();
    function show_cluster_table_by_month($p) {
        $x='';
        switch($p) {
            case '01': $x='Gennaio';
            break;
            case '02': $x='Febbraio';
            break;
            case '03': $x='Marzo';
            break;
            case '04': $x='Aprile';
            break;
            case '05': $x='Maggio';
            break;
            case '06': $x='Giugno';
            break;
            case '07': $x='Luglio';
            break;
            case '08': $x='Agosto';
            break;
            case '09': $x='Settembre';
            break;
            case '10': $x='Ottobre';
            break;
            case '11': $x='Novemnre';
            break;
            case '12': $x='Dicembre';
            break;
        }
        $datas = $GLOBALS['db']->query_cluster_month($p);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Ottenere la classifica dei cluster in un mese</h3>
                <p>Visualizzazione della classifica dei cluster in base alla potenza generata in un mese</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, t.timestamp_finale)) AS potenza_generata 
                FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
                WHERE month(t.timestamp_iniziale) = (?)
                GROUP BY e.cluster
                ORDER BY potenza_generata DESC;</code></pre>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Cluster</th>
                            <th>Potenza generata</th>
                            <th>Mese</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
        if(empty($datas)) {
            echo "<br><h1>Dati non disponibili o inesistenti</h1>";
            echo "<tr><td>X</td><td>X</td><td>X</td></tr>";
        } else {
            foreach ($datas as $data) {
                echo "<tr><td>".$data["cluster"]. "</td><td>" . round($data["potenza_generata"],2) . ' kW</td><td>'.$x.'</td></tr>';
            }
        }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function show_cluster_table_in_interval($t1, $t2) {
        
        $datas = $GLOBALS['db']->query_cluster_interval($t1, $t2);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Ottenere la classifica dei cluster in un intervallo di tempo</h3>
                <p>Visualizzazione della classifica dei cluster in base alla potenza generata in un intervallo di tempo</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT e.cluster, sum(t.quantita)/sum(timestampdiff(hour, t.timestamp_iniziale, t.timestamp_finale)) AS potenza_generata
                FROM trasferimento t INNER JOIN elemento e on t.id_produttore=e.id 
                WHERE t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
                GROUP BY e.cluster
                ORDER BY potenza_generata DESC;</code></pre>
                </div> 
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Cluster</th>
                            <th>Potenza generata</th>
                            <th>Dal</th>
                            <th>Al</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
        if(empty($datas)) {
            echo "<br><h1>Dati non disponibili o inesistenti</h1>";
            echo "<tr><td>X</td><td>X</td><td>X</td><td>X</td></tr>";
        } else {
            foreach ($datas as $data) {
                echo "<tr><td>" . $data["cluster"] . "</td><td>" . round($data["potenza_generata"],2) . " kW</td><td>" . $t1 . "</td><td>" . $t2 . '</td></tr>';
            }
        }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function show_elemento_conusmo($cit, $t1, $t2) {
        
        $datas = $GLOBALS['db']->query_elemento_consumo_tot($cit, $t1, $t2);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Consumo degli elementi</h3>
                <p>Visualizzazione consumo enegertico relativo agli elemtni utilizzatori di una persona</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, SUM(t.quantita) AS quantita_consumata,
                SUM(t.quantita*f.prezzo) AS costo_consumo_totale 
                FROM trasferimento t
                INNER JOIN elemento e ON t.id_utilizzatore = e.id
                INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
                INNER JOIN tariffa f ON t.tariffa = f.fascia
                WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
                GROUP BY t.id_utilizzatore;</code></pre>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Proprietario</th>
                            <th>Id elemento utilizzatore</th>
                            <th>Indirizzo</th>
                            <th>Quantita consumata</th>
                            <th>Costo consumo totale</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
            if(!empty($datas)) {
                $tmp="";
                foreach ($datas as $data) {
                    $str="";
                    $cn = $data["cognome"]." ".$data["nome"];
                    if(strcmp($tmp, $cn) == 0) {
                        $str = "<tr><td>" . $data["id_utilizzatore"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                        if($data["interno"] != NULL){
                            $str = $str ."/". $data["interno"];
                        }
                        $str = $str . "</td><td>" . $data["quantita_consumata"] . " kWh </td><td>" . $data["costo_consumo_totale"] . ' &#8364/h</td></tr>';
                    } else {
                        $tmp = $cn;
                        $str = "<tr><td>" . $cn . "</td><td>" . $data["id_utilizzatore"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                        if($data["interno"] != NULL){
                            $str = $str ."/". $data["interno"];
                        }
                        $str = $str . "</td><td>" . $data["quantita_consumata"] . " kWh </td><td>" . $data["costo_consumo_totale"] . ' &#8364/h</td></tr>';
                    }
                    echo $str;
                }
            } else {
                echo "<br><h1>Dati non disponibili o inesistenti</h1>";
                echo "<tr><td>X</td><td>X</td><td>X</td><td>X</td><td>X</td></tr>";
            }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function show_elemento_conusmo_per_fascia($cit, $t1, $t2) {
        $datas = $GLOBALS['db']->query_elemento_consumo_per_fascia($cit, $t1, $t2);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Consumo per fascia oraria</h3>
                <p>Visualizzazione consumo enegertico relativo agli elemtni utilizzatori di una persona suddiviso per fascia oraria</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT c.cognome, c.nome, t.id_utilizzatore, e.cap, e.via, e.civico, e.interno, t.tariffa, f.prezzo,
                SUM(t.quantita) AS quantita_consumata, SUM(t.quantita)*f.prezzo AS costo_consumo_totale 
                FROM trasferimento t
                INNER JOIN elemento e ON t.id_utilizzatore = e.id
                INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
                INNER JOIN tariffa f ON t.tariffa = f.fascia
                WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
                GROUP BY t.tariffa;</code></pre>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Id elemento utilizzatore</th>
                            <th>Indirizzo</th>
                            <th>Tariffa</th>
                            <th>Prezzo</th>
                            <th>Quantita consumata</th>
                            <th>Costo totale per fascia</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
        if(!empty($datas)) {
            $tmp="";
            foreach ($datas as $data) {
                $str="";
                $iu = $data["id_utilizzatore"];
                if(strcmp($tmp, $iu) == 0) {
                    $str = $str."<tr><td></td><td>" . "</td><td>" . $data["tariffa"] . "</td><td>" . $data["prezzo"] . " &#8364/h</td><td>" . $data["quantita_consumata"] . " kWh</td><td>" . $data["costo_consumo_totale"] . ' &#8364/h</td></tr>';
                } else {
                    $tmp = $iu;
                    $str = $str."<tr><td>" . $data["id_utilizzatore"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                    if($data["interno"] != NULL){
                        $str = $str ."/". $data["interno"];
                    }
                    $str = $str . "</td><td>" . $data["tariffa"] . "</td><td>" . $data["prezzo"] . " &#8364/h</td><td>" . $data["quantita_consumata"] . " kWh</td><td>" . $data["costo_consumo_totale"] . ' &#8364/h</td></tr>';
                }
                echo $str;
            }
        } else {
            echo "<br><h1>Dati non disponibili o inesistenti</h1>";
            echo "<tr><td>X</td><td>X</td><td>X</td><td>X</td><td>X</td><td>X</td></tr>";
        }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function show_provenienza_energia($cit, $t1, $t2) {
        $datas = $GLOBALS['db']->query_provenienza_energia($cit, $t1, $t2);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Info aggiuntive sul consumo</h3>
                <p>Visualizzazione degli elementi produttori da cui è stata prelevata l'nergia in un intervallo di tempo</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT x.prod, x.util, x.qc, el.cap, el.via, el.civico, el.interno 
                FROM (SELECT t.id_produttore AS prod, t.id_utilizzatore AS util, SUM(t.quantita) AS qc
                        FROM trasferimento t
                        INNER JOIN elemento e ON t.id_utilizzatore = e.id
                        INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
                        WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?)
                        GROUP BY t.id_produttore) x 
                INNER JOIN elemento el ON el.id = x.prod;</code></pre>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Id elemento prdouttore</th>
                            <th>Id elemento utilizzatore</th>
                            <th>Indirizzo</th>
                            <th>Quantita consumata</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $str = "<tr><td>" . $data["prod"] . "</td><td>" . $data["util"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                if($data["interno"] != NULL){
                    $str = $str ."/". $data["interno"];
                }
                $str = $str . "</td><td>" . $data["qc"] . ' kWh</td></tr>';
                echo $str;
            }
        } else {
            echo "<br><h1>Dati non disponibili o inesistenti</h1>";
            echo "<tr><td>X</td><td>X</td><td>X</td><td>X</td></tr>";
        }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function show_energia_prodotta_dagli_elementi_di_X($cit, $t1, $t2) {
        $datas = $GLOBALS['db']->query_energia_prodotta_da_X($cit, $t1, $t2);
        echo <<<TXT
            <div class="table_div">
                <h2>Operazione DB</h2>
                <br>
                <h3>Profitto in base al quantitativo di energia venduta</h3>
                <p>Visualizzazione del profitto di una persona in base al quantitativo di energia venduta</p>
                <p>NB. il quantitvao di energia prodotta esclude l'nergia usata per alimentare i propri elementi utilizzatori</p>
                <br>
                <h4>Query</h4>
                <div class = "code_div">
                    <pre>
                    <code>SELECT c.cognome, c.nome, t.id_produttore, e.cap, e.via, e.civico, e.interno, SUM(t.quantita) AS quantita_prodotta, SUM(t.quantita*f.prezzo) AS ricavo 
                FROM trasferimento t
                INNER JOIN elemento e ON t.id_produttore = e.id
                INNER JOIN cittadino c ON e.cittadino = c.codice_fiscale
                INNER JOIN tariffa f ON t.tariffa = f.fascia
                WHERE c.mail = (?) AND t.timestamp_iniziale >= (?) AND t.timestamp_finale <= (?) AND t.tariffa <> 'F0'
                GROUP BY t.id_produttore;</code></pre>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Proprietario</th>
                            <th>Id elemento prdouttore</th>
                            <th>Indirizzo</th>
                            <th>Quantita prodotta</th>
                            <th>Ricavo</th>
                        </tr>
                    </thead>
                    <tbody>
            TXT;
        if (!empty($datas)) {
            $tmp="";
            foreach ($datas as $data) {
                $str="";
                $cn = $data["cognome"]." ".$data["nome"];
                if(strcmp($tmp, $cn) == 0) {
                    $str = $str."<tr><td></td><td>" . $data["id_produttore"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                    if($data["interno"] != NULL){
                        $str = $str ."/". $data["interno"];
                    }
                    $str = $str . "</td><td>" . $data["quantita_prodotta"] . " kWh </td><td>" . $data["ricavo"] . ' &#8364/h</td></tr>';
                } else {
                    $tmp = $cn;
                    $str = $str."<tr><td>" . $cn . "</td><td>" . $data["id_produttore"] . "</td><td>" . $data["cap"] .", ". $data["via"] .", ". $data["civico"];
                    if($data["interno"] != NULL){
                        $str = $str ."/". $data["interno"];
                    }
                    $str = $str . "</td><td>" . $data["quantita_prodotta"] . " kWh </td><td>" . $data["ricavo"] . ' &#8364/h</td></tr>';
                }                
                echo $str;
            }
        } else {
            echo "<br><h1>Dati non disponibili o inesistenti</h1>";
            echo "<tr><td>X</td><td>X</td><td>X</td><td>X</td><td>X</td></tr>";
        }
        echo <<<TXT
                </tbody>
            </table>
        </div>
        TXT;
    }

    function get_page($pg, $cit, $mese, $t1, $t2){        
        if($pg == "query_cluster_1") {
            show_cluster_table_by_month($mese);
        } elseif ($pg == "query_cluster_2") {
            show_cluster_table_in_interval($t1, $t2);
        } elseif ($pg == "query_elemento_1") {
            show_elemento_conusmo($cit, $t1, $t2);
            echo "<br>";
            show_elemento_conusmo_per_fascia($cit, $t1, $t2);
            echo "<br>";
            show_provenienza_energia($cit, $t1, $t2);
        } elseif ($pg == "query_elemento_2") {
            show_energia_prodotta_dagli_elementi_di_X($cit, $t1, $t2);
        } else {
            echo "<h1>HOME</h1>";
        }
    }
?>
