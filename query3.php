<?php

//add you database connection here
require ($_SERVER['DOCUMENT_ROOT'] . "configs/db_pagination.php" ) ;

try {

    //get datase 
    /*
    from the config file:
    
    $servername
    $username
    $password
    $dbname
    $table

    */
    $dbh = connect($servername, $username, $password, $dbname);
     $q = "";
    
    if(isset($_GET['q'])){
        $q = $_GET['q'];
    }   
    // How many items to list per page
    $limit = 10;
    
    if ($dbh == null){
        die('Unable to connect to database.');
    }
    
    $total = getCount($dbh, $table, $q);

    // How many pages will there be
    $pages = ceil($total / $limit);

    // What page are we currently on?
    $page = min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
        'options' => array(
            'default' => 1,
            'min_range' => 1,
        ),
    )));
    
   
    
    // Calculate the offset for the query
    $offset = ($page - 1) * $limit;

    // Some information to display to the user
    $start = $offset + 1;
    
    $end = min(($offset + $limit), $total);

    // Display the paging information
    echo json_encode(
            array(
                'firstPage'=>1,
                'prevPageLink'=> ($page - 1),
                'nextPageLink'=> ($page + 1),
                'pageCount'=>$pages,
                'end'=>$end,
                'total'=>$total,
                'currentPage'=>$page,
                'data'=>getDataSet($dbh,$limit, $offset, $table, $q)
            )
            );

} catch (Exception $e) {
    echo '<p>', $e->getMessage(), '</p>';
}

function connect($server, $username, $password, $dbname) {

    $s = "";
    $dbh = null;
    try {

        // Create connection
        $dbh = new PDO("mysql:host=$server;dbname=$dbname", $username, $password);

        // set the PDO error mode to exception
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $dbh;
    } catch (PDOException $e) {

        die($e->getMessage());
    }
}

function getCount($dbh, $table, $q){
   
     // Find out how many items are in the table
    $total = $dbh->query("
        SELECT
            COUNT(*)
        FROM `".
            $table . "` WHERE `DESCRIPTION` LIKE '%" .$q. "%'")->fetchColumn();
 
    return $total;
}

function getDataSet($dbh, $limit, $offset, $table, $q){
    
    $result = null;
    
    // Prepare the paged query
    $stmt = $dbh->prepare("
       SELECT
            *
        FROM `".
            $table ."` WHERE `DESCRIPTION` LIKE '%" .$q. "%'
        LIMIT
            :limit
        OFFSET
            :offset
            
    ");


    // Bind the query params
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    // Do we have any results?
    if ($stmt->rowCount() > 0) {
        // Define how we want to fetch the results
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $iterator = new IteratorIterator($stmt);

        return $result;
    } else {
        echo '<p>No results could be displayed.</p>';
    }
}
