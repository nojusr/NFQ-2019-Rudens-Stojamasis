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

function loadSpecialistSelelction($pdo) {
    // get all specialists in an 
    // get all the clients from the db, initate them as objects
    $sql = "SELECT * FROM specialist;";
    
    $specialists = array();
    
    try {
        $query = $pdo->prepare($sql);
        $chk = $query->execute();
        
        if ($chk == false) {
            //TODO: proper error handling
        }
        
        $output = $query->fetchall();
        
        foreach($output as $data){
            $spec_class = new src\entity\specialist();
            $spec_class->loadFromQueryData($data);
            
            array_push($specialists, $spec_class);
        }
        
        
    } catch(PDOException $e) {
        //TODO: proper error handling 
        echo "Exception -> ";
        var_dump($e->getMessage());
    }
    
    
    foreach ($specialists as $spec) {
        
        $output = "<option value=\"".$spec->id."\">";
        $output .= $spec->name." ".$spec->surname." (".$spec->id.")";
        $output .= "</option>";
        
        echo $output;
    }
    
}


// TODO on the specialist page
// add a list of upcoming clients
// show current (upcoming) client
// add buttons to start and stop the appointment
// (show one button to start, when clicked, show another button to stop)

function displayUpcomingClients($clients) {
    // function to display info of upcoming clients
    
    if (count($clients) < 2){
        echo "<tr class=\"table-active\" ><td colspan=5>Nėra būsimų klientų.</td></tr>";
        return;
    }
    
    foreach( $clients as $index=>$client ) {
        
        // skip the first client, as it is shown in the current
        // client portion of the page
        if ($index === 0){
            continue;
        }
        $output  = "<tr class=\"table-active\">";
        $output .= "<td>".$client->client_id."</td><td>".$client->name."</td>";
        $output .= "<td>".$client->surname."</td><td>".$client->email."</td>";
        $output .= "<td>".$client->reason."</td></tr>";
        
        echo $output;
    }
    
}

function displayCurrentClient($clients) {
    // function used to display info of current client
    
    if (count($clients) < 1){
        echo "Dabartinio kliento nėra.";
        return;
    }
    
    $current_client = $clients[0];
    
    $output =  "Skaičius: <b>".$current_client->client_id."</b><br>";
    $output .= "Vardas: <b>".$current_client->name." ".$current_client->surname."</b><br>";
    $output .= "E.paštas: <b>".$current_client->email."</b><br> Susitikimo tema: <b>".$current_client->reason."</b><br>";
    
    echo $output;
    
}

function showActions($clients) {
    
    if (count($clients) < 1){
        return;
    }
    
    if (isset($_POST["currently_serving"])) {
        $output =  "<form method=\"post\" >";
        $output .= "<input type=\"number\" name=\"finished_serving\" style=\"display: none;\" value=\"".$clients[0]->client_id."\"/>";
        $output .= "<button type=\"submit\" class=\"btn btn-danger\">Pabaigti susitikimą</button>";
        $output .= "</form>";
    } else {
        $output =  "<form method=\"post\" >";
        $output .= "<input type=\"number\" name=\"currently_serving\" style=\"display: none;\" value=\"".$clients[0]->client_id."\"/>";
        $output .= "<button type=\"submit\" class=\"btn btn-info\">Pradėti susitikimą</button>";
        $output .= "</form>";
    }
    
    echo $output;
}

function showLastClientInfo($pdo) {
    $last_client = new src\entity\client();
    
    if (!isset($_POST["finished_serving"])) {
        return;
    }
    
    $last_client->generateClientByID($pdo, $_POST["finished_serving"]);
    
    // gets the time spent on last client in seconds
    $time_spent = $last_client->appointment_end_time - $last_client->appointment_start_time; 
    $time_spent_str = gmdate("H:i:s", intval($time_spent, 10));
    
    
    $output =  "Praeito kliento informacija: <br>";
    $output .=  "Skaičius: <b>".$last_client->client_id."</b><br>";
    $output .= "Vardas: <b>".$last_client->name." ".$last_client->surname."</b><br>";
    $output .= "E.paštas: <b>".$last_client->email."</b><br> Susitikimo tema: <b>".$last_client->reason."</b><br>";
    $output .= "Praleistas laikas aptarnaujant: <b>".$time_spent_str."</b><br>";
    
    echo $output;
}

// load the full page when a specialist ID is provided
// otherwise load a specialist selection page
if (isset($_GET["specialist_id"])) { 
    
    $current_spec = new src\entity\specialist();
    $current_spec->generateSpecialistByID($pdo, $_GET["specialist_id"]);
    
    if (isset($_POST["currently_serving"])) {
        
        $current_client = new src\entity\client();
        $current_client->generateClientByID($pdo, $_POST["currently_serving"]);
        $current_client->appointment_start_time = time();
        $current_client->flushToDB($pdo);

    } else if (isset($_POST["finished_serving"])) {
        
        $finished_client = new src\entity\client();
        $finished_client->generateClientByID($pdo, $_POST["finished_serving"]);
        $finished_client->appointment_end_time = time();
        $finished_client->appointment_finished_bool = 1;
        
        $current_spec->clients_served += 1;
        
        $current_spec->flushToDB($pdo);
        $finished_client->flushToDB($pdo);
        
    }

    // if zero is passed to the limit of getCurrentClients,
    // then no limit is applied 
    $clients = $current_spec->getCurrentClients($pdo, 15);
    
?>

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
        <div class="container-fluid mt-3">
            <div class="row">
                <div class="col-lg">
                    <p>Būsimi klientai:</p>
                    <table class="table">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">Kliento Skaičius</th>
                                <th scope="col">Vardas</th>
                                <th scope="col">Pavardė</th>
                                <th scope="col">E.Paštas</th>
                                <th scope="col">Susitikimo tema</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                displayUpcomingClients($clients);
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md">
                    <p>Dabartinio kliento informacija:</p>
                    <h5>
                        <?php
                            displayCurrentClient($clients);
                        ?>
                    </h5>
                        <?php
                            showActions($clients);
                        ?>
                    </form>
                    <p>
                        <?php
                            showLastClientInfo($pdo);
                        ?>
                    </p>
                </div>
            </div>
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
            
            <h3 class="mt-3 mb-3">Pasirinkite specialistą:</h3>
            <form class="mt-1 form-inline" action="spec.php" method="get">
                <select class="form-control mt-3" name="specialist_id">
                    <?php
                        loadSpecialistSelelction($pdo);
                    ?>
                </select>
                <button type="submit" class="btn btn-primary mt-3 ml-3">Pateikti</button>
            </form>
        </div>
    </body>
</html>
<?php } ?>
