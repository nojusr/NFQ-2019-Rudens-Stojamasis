<?php
namespace src\entity;

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
    
    public function __construct()
    {
        echo "Hello, I should be an autoloaded client class!";
    }

    
}

?>
