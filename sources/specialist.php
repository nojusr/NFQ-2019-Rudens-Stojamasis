<?php
namespace src\entity;

class specialist {
    
    public $id;
    public $name;
    public $surname;
    public $time_added;
    public $clients_served;
    
    
    
    public function __construct() {
        $this->id = -1;
        $this->name = "";
        $this->surname = "";
        $this->time_added = 0;
        $this->clients_served = 0;
    }
    
    public function calcAvgWorkTime($pdo){
        // calculates the average amount of time spent serving a client (in unix time)
        // if there are no clients, returns 0
        
        if ($this->clients_served < 1){
            return -1;
        }
        
        if ($this->id === -1){
            return -1;
        }
        
        // get all of the finished appointments
        $sql = "SELECT * FROM client WHERE specialist_id = :sid AND appointment_finished = 1 LIMIT 50";
        
        $clients = array();
        
        try {
            
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":sid" => $this->id,
            ));
            
            $clients = $query->fetchAll();
            
            if ($chk == false) {
                return -1;
            }
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
        // calculate the average time spent working on a single client
        $client_time_sum = 0;
        
        foreach ($clients as $client) {
            $client_time_sum += $client["appointment_end_time"] - $client["appointment_start_time"];
        }
        
        $avg_time_spent = $client_time_sum/count($clients);
        
        
        return $avg_time_spent;
        
    }
    
    
    public function generateSpecialistByID($pdo, $id) {
        // gets all the values of the object when given a client ID
        
        $sql = "SELECT * FROM specialist WHERE sid = :sid";
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":sid" => $id,
            ));
            
            if ($chk == false) {
                //TODO: proper error handling
            }
            
            $output = $query->fetch();
            
            $this->id = $output["sid"];
            $this->name = $output["name"];
            $this->surname = $output["surname"];
            $this->time_added = $output["time_added"];
            $this->clients_served = $output["clients_served"];
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo 'Exception -> ';
            var_dump($e->getMessage());
        }
    }
    
    public function flushToDB($pdo) {
        // takes all values of current object and updates the DB
        
        // this had me stumped for quite a while, turns out you cannot 
        // use the same variable twice in a prepared statement.
        // I don't know why is it done like this, seems stupid to me,
        // but alteast it's a mountain climbed and a lesson learned.
        // the workaround does look stupid though...
        $sql = " 
            INSERT INTO specialist
            (
                        sid,
                        name,
                        surname,
                        time_added,
                        clients_served
            )
            VALUES
            (
                        :sid,
                        :name,
                        :surname,
                        :time_added,
                        :clients_served
            )
            ON DUPLICATE KEY UPDATE 
                    sid = :sid2,
                    name = :name2,
                    surname = :surname2,
                    time_added = :time_added2,
                    clients_served = :clients_served2;";
        
        try {
            
            $query = $pdo->prepare($sql);
            
            $chk = $query->execute(array(
                ":sid" => $this->id,
                ":name" => $this->name, 
                ":surname" => $this->surname,
                ":time_added" => $this->time_added,
                ":clients_served" => $this->clients_served,
                ":sid2" => $this->id,
                ":name2" => $this->name, 
                ":surname2" => $this->surname,
                ":time_added2" => $this->time_added,
                ":clients_served2" => $this->clients_served
            ));
            
            
            if ($chk === false){
                //TODO: proper error handling
            }
            
            
            // if there is no ID set (like when a new specialist is created)
            // grab it from the database and set it
            if ($this->id == -1){
                $id = $pdo->lastInsertId();
                $this->id = $id;
            }
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
    }

}

?>
