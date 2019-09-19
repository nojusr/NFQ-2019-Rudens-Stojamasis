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




if ($_SERVER["REQUEST_METHOD"] === "POST") {// if the request is a POST request
    
    $input_error = false;
    $error_text = "<div class=\"alert alert-danger\" >Nepavyko pridėti susitikimo: <br>";

    
    if (!isset($_POST["input_name"])){
        $input_error = true;
        $error_text .= "Nepateiktas vardas<br>";
    }
    $error_text .="</div>";
    
    if ($input_error === false) {
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
        
        $success_text =  "<h3 class=\"mt-4 mb-4\">Registrajica įvykdyta<br>";
        $success_text .= "<h5 class=\"mt-3 mb-3\">Jūsų skaičius: ".$new_client->client_id;
        $success_text .= "</h5></h3>";
    }


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
        <div class="container"> 
        <?php
            if ($input_error === true){
                echo $error_text;
            } else {
                echo $success_text;
            }
        ?>
        </div>
    </body>
</html>


<?php
} else {// if the request is a simple GET request
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
