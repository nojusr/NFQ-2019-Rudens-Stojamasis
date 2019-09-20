<?php


// main boilerplate
define("__ROOT__", dirname(dirname(__FILE__)));

// psr-4 autoloader
require_once(__ROOT__."/vendor/autoload.php"); 

// load project config (config.json)
$config_str_contents = file_get_contents(__ROOT__."/config.json");
$config_array = json_decode($config_str_contents, true);

// setup db connection

try {
    $pdo = new PDO($config_array["dsn"], $config_array["dbuser"], 
                   $config_array["dbpass"], array(
                   PDO::ATTR_EMULATE_PREPARES=>false,
                   PDO::MYSQL_ATTR_DIRECT_QUERY=>false,
                   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
    ));
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


function sortClientsByWaitTime(&$clients, $pdo) {
    usort($clients, function($a, $b) use ($pdo) {
        $wait_time_a = $a->calculateWaitTime($pdo);
        $wait_time_b = $b->calculateWaitTime($pdo);
        
        
        
        // sort by appointment_day if wait time is nowhere to be seen
        if ($wait_time_a == -1 && $wait_time_b == -1) {
            if ($a-> appointment_day == $b->appointment_day) {
                return 0;
            }
            
            return ($a->appointment_day < $b->appointment_day) ? -1 : 1;
        }
        
        if ($wait_time_a == $wait_time_b) {
            return 0;
        }
        
        if ($wait_time_a == -1) {
            return 1;
        }
        
        if ($wait_time_b == -1) {
            return -1;
        }
        
        return ($wait_time_a < $wait_time_b) ? -1 : 1;
    });
}


function outputTableData($pdo) {
    // function used to display all upcoming clients
    
    
    // get all the clients from the db, initate them as objects
    $sql = "SELECT * FROM client WHERE appointment_finished = 0 ORDER BY appointment_day ASC LIMIT 10;";
    
    $clients = array();
    
    try {
        $query = $pdo->prepare($sql);
        $chk = $query->execute();
        
        if ($chk == false) {
            //TODO: proper error handling
        }
        
        $output = $query->fetchall();
        
        foreach($output as $client){
            $client_class = new src\entity\client();
            $client_class->loadFromQueryData($client);
            
            // if the appointment is happening today
            if ($client_class->appointment_day === strtotime('today', time())) {
                array_push($clients, $client_class);
            }
            
            
        }
        
        
    } catch(PDOException $e) {
        //TODO: proper error handling 
        echo "Exception -> ";
        var_dump($e->getMessage());
    }
    
    if (count($clients) < 1) {
        echo "<tr class=\"table-active\"><td colspan=\"4\" class=\"display-1 display-massive\">Eilė tuščia.</td></tr>";
    }
    
    
    sortClientsByWaitTime($clients, $pdo);
    
    // for every client object
    foreach ($clients as $client) {
        
        // get the approx. wait time as an integer
        $wait_int_time = intval($client->calculateWaitTime($pdo), 10);
        
        if ($wait_int_time === -1){
            // calculateWaitTime returned an error
            $wait_time = "N/A";
        } else if ($wait_int_time === 0) {
            $wait_time = "Dabar";
        } else {
            // everything is good, convert the time into "h:m:s"
            $wait_time = gmdate("H:i:s", intval($client->calculateWaitTime($pdo), 10));
        }
        
        // create a row
        $output = "<tr class=\"table-active\">";
        $output .= "<td class=\"display-1 display-massive\">";
        $output .= $client->client_id;
        $output .= "</td><td class=\"display-1 display-massive\">🡲</td>";
        $output .= "<td class=\"display-1 display-massive\">";
        $output .= $client->specialist_id;
        $output .= "</td><td class=\"display-1 display-massive\">";
        $output .= $wait_time;
        $output .= "</td></tr>";
        
        echo $output;
        
    }
    
    
}

?>

<html>
    <head>
        <!--DISCLAIMER: Šis pulsapis yra pritaikytas rodymui ant didelio ekrano bei lengvam skaitymui iš didelio atstumo-->
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="5">
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
        <link rel="stylesheet" href="css/custom.css" media="screen">
        <title>NR-NFQ-Stojamasis</title>
    </head>
    <body class="bg-dark">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th >Kliento Skaičius</th>
                    <th></th>
                    <th>Darbuotojo Skaičius</th>
                    <th>Apytikslis likęs laikas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                outputTableData($pdo);
                ?>
            </tbody>
        </table>
    </body>
</html>
