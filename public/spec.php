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

function displayUpcomingClients($clients){
    
    foreach( $clients as $client ) {
        $output  = "<tr class=\"table-light\">";
        $output .= "<td>".$client->client_id."</td><td>".$client->name."</td>";
        $output .= "<td>".$client->surname."</td><td>".$client->email."</td>";
        $output .= "<td>".$client->reason."</td></tr>";
        
        echo $output;
    }
    
}


// load the full page when a specialist ID is provided
// otherwise load a specialist selection page
if (isset($_GET["specialist_id"])) { 
    
    $current_spec = new src\entity\specialist();
    
    $current_spec->generateSpecialistByID($pdo, $_GET["specialist_id"]);
    

    
    // if zero is passed to the limit of getCurrentClients,
    // then no limit is applied 
    $clients = $current_spec->getCurrentClients($pdo, 0);
    
?>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
        <title>NR-NFQ-Stojamasis</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
          <span class="navbar-brand mb-0 h1">Sveiki atvykę!</span>
        </nav>
        <div class="container-fluid mt-3">
          <div class="row">
            <div class="col-lg">
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
              <p>Kitas klientas: 329</p>
              <button>Pradeti susitikima</button>
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
        <title>NR-NFQ-Stojamasis</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
          <span class="navbar-brand mb-0 h1">Sveiki atvykę!</span>
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
