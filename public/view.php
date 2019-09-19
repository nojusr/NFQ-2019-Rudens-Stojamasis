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

function showAllClientNumbers($pdo){
    $sql = "SELECT * FROM client WHERE appointment_finished = 0 ORDER BY time_added DESC LIMIT 10;";
    
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
            
            // if the time difference between now and the supposed arrival of the client
            // is bigger than 3 hours, don't show the client
            if ($client_class->time_added - time() < 10800){
                array_push($clients, $client_class);
            }
            
            
        }
        
        
    } catch(PDOException $e) {
        //TODO: proper error handling 
        echo "Exception -> ";
        var_dump($e->getMessage());
    }
    
    if (count($clients) < 1) {
        echo "<option value=\"-1\" Klientų nėra.";
        return;
    }
    
    $output = "";
    
    foreach ( $clients as $client) {
        $output .= "<option value=\"".$client->client_id."\">".$client->client_id."</option>";
    }
    
    echo $output;
    
}

function displayClientInfo($pdo, $client) {
    
    if ($client->client_id == -1){
        //TODO: proper error handling;
    }
    
    // get the approx. wait time as an integer
    $wait_int_time = intval($client->calculateWaitTime($pdo), 10);
    
    if ($wait_int_time === -1){
        // calculateWaitTime returned an error
        $wait_time = "N/A";
    } else if ($wait_int_time === 0) {
        // it's time for the client to go
        $wait_time = "0:0:0, eikite link būdelės.";
    } else {
        // everything is good, convert the time into "h:m:s"
        $wait_time = gmdate("H:i:s", intval($client->calculateWaitTime($pdo), 10));
    }
    
    $queue_num = $client -> calculateQueueNum($pdo);
    
    $assigned_spec = new src\entity\specialist();
    $assigned_spec->generateSpecialistByID($pdo, $client->specialist_id);
    
    
    $output =  "Skaičius: ".$client->client_id."<br>";
    $output .= "Paskirtas specialistas: ".$assigned_spec->name." ".$assigned_spec->surname." (".$assigned_spec->id.")<br>";
    $output .= "Eilės numeris: ".$queue_num."<br>";
    $output .= "Apytikslis likęs laikas: ".$wait_time."<br>";
    
    echo $output;
    
    
}

if (isset($_GET["client_id"])){ 

    $current_client = new src\entity\client();
    $current_client->generateClientByRandomLink($pdo, $_GET["client_id"]);
    
    if ($current_client->client_id == NULL){
        //TODO: proper error handling;
        die("Client not found");
    }
    
    
    
?>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="5">
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
        <link rel="stylesheet" href="css/custom.css" media="screen">
        <title>NR-NFQ-Stojamasis</title>
    </head>
    <body>
        <nav class="navbar navbar-light bg-primary">
            <input type="checkbox" id="navbar-toggle-cbox">

            <a class="navbar-brand" href="#">NR-Sistema</a>
            <label for="navbar-toggle-cbox" class="navbar-toggler hidden-sm-up" type="button" data-toggle="collapse" data-target="#navbar-header" aria-controls="navbar-header">
                &#9776;
            </label>
            <div class="collapse navbar-toggleable-xs" id="navbar-header">

                <ul class="nav navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Užsakyti susitikimą</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/spec.php">Specialistams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/view.php">Klientams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/board.php">Švieslentė</a>
                    </li>
                </ul>
            </div>
        </nav>
        <div class="container"> 
            
            <h3 class="mt-3 mb-3">Jūsų eilės informacija:</h3>
            <p>
                <?php
                    displayClientInfo($pdo, $current_client);
                ?>
            </p>
        </div>
    </body>
</html>

<?php } else { ?>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
        <link rel="stylesheet" href="css/custom.css" media="screen">
        <title>NR-NFQ-Stojamasis</title>
    </head>
    <body>
        <nav class="navbar navbar-light bg-primary">
            <input type="checkbox" id="navbar-toggle-cbox">

            <a class="navbar-brand" href="#">NR-Sistema</a>
            <label for="navbar-toggle-cbox" class="navbar-toggler hidden-sm-up" type="button" data-toggle="collapse" data-target="#navbar-header" aria-controls="navbar-header">
                &#9776;
            </label>
            <div class="collapse navbar-toggleable-xs" id="navbar-header">

                <ul class="nav navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Užsakyti susitikimą</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/spec.php">Specialistams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/view.php">Klientams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/board.php">Švieslentė</a>
                    </li>
                </ul>
            </div>
        </nav>
        <div class="container"> 
            
            <h3 class="mt-3 mb-3">Kliento kodas:</h3>
            <form class="mt-1 form-inline" action="view.php" method="get">
                <input type="text" class="form-control" required name="client_id" placeholder="Įveskite savo kodą:">
                <button type="submit" class="btn btn-primary">Pateikti</button>
            </form>
        </div>
    </body>
</html>

<?php } ?>
