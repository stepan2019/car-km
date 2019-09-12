<?php

class Db
{

    private $server = "localhost";

    private $username = "carkm_Fouad";

    private $password = "1?U}=aQ5U27M";
    private $dbname = "carkm_test";
    private $connect_db;

    function __construct()
    {

        $con = new mysqli($this->server, $this->username, $this->password, $this->dbname);
        if ($con->connect_error) {
            die("connection failed");
        }else{
            $con->set_charset("utf8");
            $this->connect_db = $con;
        }
        return $this->connect_db;
    }
    public function query($query){
        return $this->connect_db->query($query);
    }
}
$connectDb = new Db();
