<?php	
    include "include/database.php";
    include "include/pages.php";

    if(empty($_GET['page'])){
      $pg = 'form';
    } else {
      $pg = $_GET['page'];
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Progetto DB</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <header>
            <a href="index.php?page=home" class="logo">Progetto DB</a>
            <nav class="navbar">
                <ul>
                    <li><a class="nav-link" href="index.php?page=query_cluster_1">Query 1</a></li>
                    <li><a class="nav-link" href="index.php?page=query_cluster_2">Query 2</a></li>
                    <li><a class="nav-link" href="index.php?page=query_elemento_1">Query 3</a></li>
                    <li><a class="nav-link" href="index.php?page=query_elemento_2">Query 4</a></li>
                </ul>
            </nav>
        </header>
        <div class = "content">
        <?php
                  $cit = "rossi.maria@mail.com";
                  $mese = "02";
                  $t1 = "2023-02-01";
                  $t2 = "2023-03-15";
                  get_page($pg, $cit, $mese, $t1, $t2);
        ?>

      </div>
    </body>
</html>
