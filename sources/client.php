<?php
namespace src\entity;

use \PDO;

class client {
    
    public $client_id;
    public $specialist_id;
    public $random_id;
    public $name;
    public $surname;
    public $email;
    public $reason;
    public $appointment_day;
    public $appointment_start_time;
    public $appointment_end_time;
    public $appointment_finished_bool; 
    
    public function __construct() {
        // constructor mostly used to set variables
        // that wouldn't upset the db if they ever got
        // sent into it accidentally
        $this->specialist_id = -1;
        $this->random_id = substr(md5(time()), 0, 7);
        $this->name = "Nepateikta";
        $this->surname = "Nepateikta";
        $this->email = "Nepateikta";
        $this->reason = "Nepateikta";
        $this->appointment_day = strtotime('today', time());// unix time rounded to day
        $this->appointment_start_time = 0;
        $this->appointment_end_time = 0;
        $this->appointment_finished_bool = 0;
    }
    
    
    public function getSpecialist($pdo) {
        // gets the client to the specialist with the least amount of clients
        
        if ($this->specialist_id !== -1){// client already has specialist
            return;                      // no need for a new one
        }
        
        $sql = "CALL `GET_LEAST_BUSY_SPECIALIST`()";
        
        try {
            $query = $pdo->prepare($sql);
            $query->execute();
            
            $output = $query->fetch();
            $this->specialist_id = $output[0];
            
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
        

         
    }
    
    public function calculateQueueNum($pdo) {
        // get the queue number of the client
        
        // sql to get the list of all clients assigned to this client's 
        // specialist (with row numbers)
        $sql = "SELECT * FROM client WHERE specialist_id = :sid AND appointment_finished = 0 ORDER BY appointment_day ASC;";
        
        $client_list = array();
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":sid" => $this->specialist_id,
            ));
            
            if ($chk == false) {
                //TODO: proper error handling
            }
            
            $client_list = $query->fetchAll();
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
        $queue_num = -1;
        
        $todays_clients = array();
        
        foreach ($client_list as $client_item) {
            if ($client_item["appointment_day"] === strtotime('today', time())) {
                array_push($todays_clients, $client_item);
            } 
        }
        
        foreach ($todays_clients as $index=>$client_item) {
            if ($client_item["cid"] == $this->client_id){
                $queue_num = $index;
                break;
            }
        }
        
        return $queue_num;
        
    }
    
    
    public function calculateWaitTime($pdo) {
        // function that calculates the wait time, when taking into
        // consideration the average wait time for the assigned
        // specialist
        

        
        // get the average time spent on a client
        $assigned_spec = new specialist();
        $assigned_spec->generateSpecialistByID($pdo, $this->specialist_id);
        $avg_time = $assigned_spec->calcAvgWorkTime($pdo);
        
        $queue_num = $this->calculateQueueNum($pdo);
        
        // if average time is impossible to calculate
        // (db error, specialist has 0 clients served, etc)
        // return -1 to indicate that the time is not available
        
        if ($queue_num == 0 and $avg_time == -1){
            return 0;
        }
        
        if ($avg_time == -1) {
            return -1 ;
        }

        
        // otherwise multiply both and return
        return $queue_num * $avg_time;
        
    }
    
    public function generateClientByRandomLink($pdo, $link) {
        // gets all the values of the object when given a random link ID
        
        // in case there ever is a duplicate random ID, grab the newest one
        $sql = "SELECT * FROM client WHERE random_viewlink = :random_id ORDER BY appointment_day ASC LIMIT 1";
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":random_id" => $link,
            ));
            
            if ($chk == false) {
                //TODO: proper error handling
            }
            
            $output = $query->fetch();
            $this->loadFromQueryData($output);
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
    }
    
    public function generateClientByID($pdo, $id) {
        // gets all the values of the object when given a client ID
        
        $sql = "SELECT * FROM client WHERE cid = :client_id";
        
        try {
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":client_id" => $id,
            ));
            
            if ($chk == false) {
                //TODO: proper error handling
            }
            
            $output = $query->fetch();
            
            $this->loadFromQueryData($output);
            
        } catch(PDOException $e) {
            //TODO: proper error handling 
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
        
        
    }
    
    public function loadFromQueryData($data){
        // sets all values of this object from an associative array which is
        // generated by an SQL query
        
        $this->client_id = $data["cid"];
        $this->specialist_id = $data["specialist_id"];
        $this->random_id = $data["random_viewlink"];
        $this->name = $data["name"];
        $this->surname = $data["surname"];
        $this->email = $data["email"];
        $this->reason = $data["reason"];
        $this->appointment_day = $data["appointment_day"];
        $this->appointment_start_time = $data["appointment_start_time"];
        $this->appointment_end_time = $data["appointment_end_time"];
        $this->appointment_finished_bool = $data["appointment_finished"];
    }
    
    public function deleteFromDB($pdo) {
        $sql = "DELETE FROM client WHERE cid = :client_id";
        
        try {
            
            $query = $pdo->prepare($sql);
            $chk = $query->execute(array(
                ":client_id" => $this->client_id,
            ));
            
            
            if ($chk === false){
                //TODO: proper error handling
            }
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
        
    }
    
    public function flushToDB($pdo) {
        // takes all values of current object and updates/inserts into the DB
        
        if ($this->specialist_id === -1) {//cannot flush to db; no specialist assigned
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
                        random_viewlink,
                        surname,
                        email,
                        reason,
                        appointment_day,
                        appointment_start_time,
                        appointment_end_time,
                        appointment_finished
            )
            VALUES
            (
                        :client_id,
                        :spec_id,
                        :name,
                        :random_id,
                        :surname,
                        :email,
                        :reason,
                        :ad,
                        :ast,
                        :aet,
                        :af
            )
            ON DUPLICATE KEY UPDATE 
                   specialist_id = :spec_id2,
                   random_viewlink = :random_id2,
                   name = :name2,
                   surname = :surname2,
                   email = :email2,
                   reason = :reason2,
                   appointment_day = :ad2,
                   appointment_start_time = :ast2,
                   appointment_end_time = :aet2,
                   appointment_finished = :af2;";
        
        try {
            
            $query = $pdo->prepare($sql);
            
            
            
            $chk = $query->execute(array(
                ":client_id" => $this->client_id,
                ":spec_id" => $this->specialist_id, 
                ":random_id" => $this->random_id,
                ":name" => $this->name, 
                ":surname" => $this->surname, 
                ":email" => $this->email,
                ":reason" => $this->reason, 
                ":ad" => $this->appointment_day,
                ":ast" => $this->appointment_start_time, 
                ":aet" => $this->appointment_end_time,
                ":af" => $this->appointment_finished_bool,
                ":spec_id2" => $this->specialist_id, 
                ":random_id2" => $this->random_id,
                ":name2" => $this->name, 
                ":surname2" => $this->surname, 
                ":email2" => $this->email,
                ":reason2" => $this->reason, 
                ":ad2" => $this->appointment_day,
                ":ast2" => $this->appointment_start_time, 
                ":aet2" => $this->appointment_end_time,
                ":af2" => $this->appointment_finished_bool
            ));
            
            
            if ($chk === false){
                //TODO: proper error handling
            }
            
            
            // if there is no ID set (like when a new client is created)
            // grab it from the database and set it
            if ($this->client_id == NULL){
                $id = $pdo->lastInsertId();
                $this->client_id = $id;
            }
            
        } catch(PDOException $e) {
            //TODO: proper error handling
            echo "Exception -> ";
            var_dump($e->getMessage());
        }
    }

    
}





