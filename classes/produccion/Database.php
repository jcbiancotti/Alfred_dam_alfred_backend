<?php
class Database{
    
    private $produccion = true;

    public $db_host = '';
    public $db_name = '';
    public $db_username = '';
    public $db_password = '';

    public function dbConnection(){


            // APUNTAR A LA BBDD DE PRODUCCION O DESARROLLO (LOCAL)
        if($this->produccion == true) {
    
            $this->db_host = 'PMYSQL139.dns-servicio.com:3306';
            $this->db_name = '7957127_dam_alfred';
            $this->db_username = 'jcbiancotti';
            $this->db_password = '#Catalitico2021#';
        
        } else {
    
            $this->db_host = 'localhost';
            $this->db_name = 'dam_alfred';
            $this->db_username = 'jcbiancotti';
            $this->db_password = '#Catalitico2021#';
        
        }
        try{
            $conn = new PDO('mysql:host='.$this->db_host.';dbname='.$this->db_name,$this->db_username,$this->db_password,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;
        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
          
    }
}