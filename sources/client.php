<?php
namespace src\entity;

use \PDO;

class client {
    
    public $client_id;
    public $specialist_id;
    public $name;
    public $surname;
    public $email;
    public $reason;
    public $time_added;
    public $appointment_start_time;
    public $appointment_end_time;
    public $appointment_finished_bool; 

    public function __construct(){
        // constructor mostly used to set variables
        // that wouldn't upset the db if they ever got
        // sent into it accidentally
        $this->specialist_id = -1;
        $this->name = "";
        $this->surname = "";
        $this->email = "";
        $this->reason = "";
        $this->time_added = time();
        $this->appointment_start_time = time();
        $this->appointment_finished_bool = 0;
    }
    
    
    public function getSpecialist($pdo) {
        // finds the specialistID assigned to client or gives
        // the client to the specialist with the least amount of clients
        
        if ($this->specialist_id === -1){// client already has specialist
            return;                      // no need for a new one
        }
        
        $sql = "CALL `GET_LEAST_BUSY_SPECIALIST`()";
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute();
            
            if ($chk === false){
                //TODO: proper error handling
            }
            
            $output = $query->fetch();
            $this->specialist_id = $output[0];
            
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo 'Exception -> ';
            var_dump($e->getMessage());
        }
        
        

         
    }
    
    public function calculateWaitTime($pdo) {
        // function that calculates the wait time, when taking into
        // consideration the average wait time for the assigned
        // specialist
    }
    
    public function generateClientByID($pdo, $id){
        // gets all the values of the object when given a client ID
        
        $sql = "SELECT * FROM client WHERE cid = :client_id";
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":client_id" => $id,
            ));
            
            if ($chk == false){
                //TODO: proper error handling
            }
            
            $output = $query->fetch();
            
            echo "<br>CLIENT: ";
            var_dump($output);
            echo "<br>";
            
            $this->client_id = $output["cid"];
            $this->specialist_id = $output["specialist_id"];
            $this->name = $output["name"];
            $this->surname = $output["surname"];
            $this->email = $output["email"];
            $this->reason = $output["reason"];
            $this->time_added = $output["time_added"];
            $this->appointment_start_time = $output["appointment_start_time"];
            $this->appointment_end_time = $output["appointment_end_time"];
            $this->appointment_finished_bool = $output["appointment_finished"];
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo 'Exception -> ';
            var_dump($e->getMessage());
        }
        
        
        
    }
    
    public function flushToDB($pdo) {
        // takes all values of current object and updates the DB
        
        if ($this->specialist_id === -1){//cannot flush to db; no specialist assigned
            return;
        }
        
        // this had me stumped for quite a while, turns out you cannot 
        // use the same variable twice in a prepared statement.
        // I don't know why is it done like this, seems stupid to me,
        // but alteast it's a mountain climbed and a lesson learned.
        // the workaround does look stupid though...
        $sql = " 
            INSERT INTO client
            (
                        cid,
                        specialist_id,
                        name,
                        surname,
                        email,
                        reason,
                        appointment_start_time,
                        appointment_end_time,
                        appointment_finished
            )
            VALUES
            (
                        :client_id,
                        :spec_id,
                        :name,
                        :surname,
                        :email,
                        :reason,
                        :ast,
                        :aet,
                        :af
            )
            ON DUPLICATE KEY UPDATE 
                   specialist_id = :spec_id2,
                   name = :name2,
                   surname = :surname2,
                   email = :email2,
                   reason = :reason2,
                   appointment_start_time = :ast2,
                   appointment_end_time = :aet2,
                   appointment_finished = :af2;";
        
        try {
            
            $query = $pdo->prepare($sql);
            
            $chk = $query->execute(array(
                ":client_id" => $this->client_id,
                ":spec_id" => $this->specialist_id, 
                ":name" => $this->name, 
                ":surname" => $this->surname, 
                ":email" => $this->email,
                ":reason" => $this->reason, 
                ":ast" => $this->appointment_start_time, 
                ":aet" => $this->appointment_end_time,
                ":af" => $this->appointment_finished_bool,
                ":spec_id2" => $this->specialist_id, 
                ":name2" => $this->name, 
                ":surname2" => $this->surname, 
                ":email2" => $this->email,
                ":reason2" => $this->reason, 
                ":ast2" => $this->appointment_start_time, 
                ":aet2" => $this->appointment_end_time,
                ":af2" => $this->appointment_finished_bool
            ));
            
            if ($chk === false){
                //TODO: proper error handling
            }
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo 'Exception -> ';
            var_dump($e->getMessage());
        }
    }

    
}





