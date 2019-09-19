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

// QR code library
use Endroid\QrCode\QrCode;

function displayNewClientInfo($pdo, $new_client, $error_text, $has_error) {
    
    if ($has_error === true){
        echo $error_text;
        return;
    } 
    
    $assigned_specialist = new src\entity\specialist();
    $assigned_specialist->generateSpecialistByID($pdo, $new_client->specialist_id);
    
    $view_link = "/view.php?client_id=".$new_client->random_id;
    
    
    $success_text =  "<h3 class=\"mt-4 mb-4\">Registrajica įvykdyta<br>";
    $success_text .= "<h5 class=\"mt-3 mb-3\">Jūsų skaičius: <b>".$new_client->client_id."</b><br>";
    $success_text .= "Jus aptarnaus: <b>".$assigned_specialist->name." ".$assigned_specialist->surname;
    $success_text .= " (".$assigned_specialist->id.")";
    $success_text .= "</b><br></h5></h3>";
    $success_text .= "<p>Galite sekti savo eilę paspaudus ";
    $success_text .= "<a href=\"".$view_link."\">šią</a>";
    $success_text .= " nuoruodą arba nuskenavus QR koda apačioje.<br></p>";
    echo $success_text;

    
}

function generateLinkQRCode($new_client, $has_error) {
    
    if ($has_error) {
        return;
    }
    $full_link = "https://".$_SERVER["SERVER_NAME"]."/view.php?client_id=".$new_client->random_id;
    $qr_code = new QrCode($full_link);
    
    
    echo "<img src=\"data:image/png;base64,".base64_encode($qr_code->writeString())."\" />";
    
}



if ($_SERVER["REQUEST_METHOD"] === "POST") {// if the request is a POST request
    
    $has_error = false;
    $error_text = "<div class=\"alert alert-danger\" >Nepavyko pridėti susitikimo: <br>";

    
    if (!isset($_POST["input_name"])){
        $has_error = true;
        $error_text .= "Nepateiktas vardas<br>";
    }
    $error_text .="</div>";
    
    if ($has_error === false) {
        $new_client = new src\entity\client();
        
        $new_client->getSpecialist($pdo);// find the least busy specialist
        
        // inputs are sanitized and processed when flushing to db
        $new_client->name = $_POST["input_name"];
        
        if ($_POST["input_surname"] != "") {
            $new_client->surname = $_POST["input_surname"];
        }

        if ($_POST["input_email"] != "") {
            $new_client->email = $_POST["input_email"];
        }
        
        if ($_POST["input_reason"] != "") {
            $new_client->reason = $_POST["input_reason"];
        }
        
        $new_client->flushToDB($pdo);
    }


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
        <div class="container">
            <?php
                displayNewClientInfo($pdo, $new_client, $error_text, $has_error);
                generateLinkQRCode($new_client, $has_error);
            ?>
        </div>
    </body>

</html>


<?php } else {// if the request is a simple GET request ?>
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
            <h3 class="mt-3 mb-3">Susitikimo forma:</h3>
            <form class="mt-1" action="index.php" method="post">
                <div class="form-group">
                    <label for="input_name">Vardas</label>
                    <input type="text" class="form-control" required name="input_name" placeholder="Įveskite vardą">
                </div>
                <div class="form-group">
                    <label for="input_surname">Pavardė (neprivaloma)</label>
                    <input type="text" class="form-control" name="input_surname" placeholder="Įveskite pavardę">
                </div>              
                <div class="form-group">
                    <label for="input_email">E. Paštas (neprivalomas)</label>
                    <input type="email" class="form-control" name="input_email" placeholder="Įveskite e.paštą">
                </div>              
                <div class="form-group">
                    <label for="input_reason">Kodėl norite susitikti? (neprivaloma)</label>
                    <input type="text" class="form-control" name="input_reason" placeholder="Įveskite susitikimo temą">
                </div>
                <button type="submit" class="btn btn-primary">Pateikti</button>
            </form>
        </div>
    </body>
</html>
<?php } ?>
