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

function displayClientInfo($pdo, $client) {
    if ($client->client_id == -1) {
        //TODO: proper error handling;
    }
    
    // get the approx. wait time as an integer
    $wait_int_time = intval($client->calculateWaitTime($pdo), 10);
    
    if ($wait_int_time === -1){
        // calculateWaitTime returned an error
        $wait_time = "Apytikslio laiko nebuvo įmanoma apskaičiuoti.";
    } else if ($wait_int_time === 0) {
        // it's time for the client to go
        $wait_time = "Jūsų eile: keliaukite link būdelės.";
    } else {
        // everything is good, convert the time into "h:m:s"
        $wait_time = "Apytikslis likęs laikas: ".gmdate("H:i:s", intval($client->calculateWaitTime($pdo), 10));
    }
    
    $queue_num = $client -> calculateQueueNum($pdo);
    
    $assigned_spec = new src\entity\specialist();
    $assigned_spec->generateSpecialistByID($pdo, $client->specialist_id);
    
    
    $output =  "Skaičius: ".$client->client_id."<br>";
    $output .= "Paskirtas specialistas: ".$assigned_spec->name." ".$assigned_spec->surname." (".$assigned_spec->id.")<br>";
    
    if ($client->appointment_day == strtotime('today', time())) {
        $output .= "Eilės numeris: ".($queue_num+1)."<br>";
        $output .= $wait_time."<br>";
    } else {
        $output .= "Susitikimo data: ".gmdate("Y-m-d", $client->appointment_day)."<br>";
    }
    

    
    echo $output;
    
    
}

function displayNewDateInfo($new_date_status, $error_text) {
    
    if ($new_date_status === 0) {
        return;
    } else if ($new_date_status > 0) {
        echo "<p>Data sėkmingai pakeista</p>";
    } else if ($new_date_status < 0) {
        echo $error_text;
    }
    
}

if (isset($_GET["client_id"])) { 

    $current_client = new src\entity\client();
    $current_client->generateClientByRandomLink($pdo, $_GET["client_id"]);
    
    $new_date_status = 0;
    $error_text = "<div class=\"alert alert-danger mt-3\" >Nepavyko pridėti susitikimo: <br>";
    
    if ($current_client->client_id == NULL){
        //TODO: proper error handling;
        die("Client not found");
    }
    
    if (isset($_POST["delete"]) && $current_client->appointment_finished_bool == 0) { 
        $current_client->deleteFromDB($pdo);
        header("Location: /index.php");
        die();
    }
    
    
    if (isset($_POST["new_date"]) && $current_client->appointment_finished_bool == 0) {
        
        if (strtotime($_POST["new_date"]) < strtotime('today', time())){
            $error_text .= "Nauja susitikimo data privalo būti lygi arba vėlesnė už šiandienos datą<br>";
            $new_date_status = -1;
        } else {
            $current_client->appointment_day = strtotime($_POST["new_date"]);
            $current_client->flushToDB($pdo);
            $new_date_status = 1;
        }
        
        $error_text .= "</div>";
        
    }
    
    
?>


<!DOCTYPE HTML>
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
            <?php if ($current_client->appointment_finished_bool == 0) { ?>
                <h3 class="mt-3 mb-3">Jūsų eilės informacija:</h3>
                <p>
                    <?php
                        displayClientInfo($pdo, $current_client);
                        displayNewDateInfo($new_date_status, $error_text);
                    ?>
                </p>
                <form class="mt-1 form-inline" method="post">
                    <div class="form-group">
                        <label for="input_date" class="mr-3 mb-1">Nukelti datą vėliau:</label>
                        <input type="date" class="form-control mr-3 mb-1" value="<?php echo gmdate("Y-m-d", $current_client->appointment_day) ?>" name="new_date" required placeholder="Įveskite susitikimo datą">
                        <button type="submit" class="btn btn-primary">Pateikti</button>
                    </div>                
                    
                </form>
                <form class="mt-1 form-inline" method="post">
                    <div class="form-group">
                        <input type="checkbox" name="delete" checked value="delete" style="display: none;">
                        <button type="submit" class="btn btn-danger">Atšaukti susitikimą</button>
                    </div>                
                    
                </form>
            <?php } else { ?>
                <h3 class="mt-3">Jūsų susitikimas jau įvyko, galite uždaryti šį puslapį.</h3>
            <?php } ?>
        </div>
    </body>
</html>


<?php } else { ?>


<!DOCTYPE HTML>
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
                <input type="text" class="form-control" required name="client_id" placeholder="Įveskite savo kodą">
                <button type="submit" class="btn btn-primary">Pateikti</button>
            </form>
        </div>
    </body>
</html>


<?php } ?>
