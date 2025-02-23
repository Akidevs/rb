<?php

require_once 'database.php';

Class Customer{
    //attributes

    public $id;
    public $name;
    public $email;
    public $email_verified_at;
    public $password;
    public $remember_token;
    public $created_at;
    public $updated_at;
    public $role;
    protected $db;

    function __construct()
    {
        $this->db = new Database();
    }

    //Methods

    function add(){
        $sql = "INSERT INTO user (name, email, email_verified_at, password, remember_token, created_at, updated_at, role) VALUES 
        (:name, :email, :email_verified_at, :password, :remember_token, :created_at, :updated_at, :role);";

        $query=$this->db->connect()->prepare($sql);
        $query->bindParam(':name', $this->name);
        $query->bindParam(':email', $this->email);
        $query->bindParam(':email_verified_at', $this->email_verified_at);
        $query->bindParam(':password', $this->password);
        $query->bindParam(':remember_token', $this->remember_token);
        $query->bindParam(':created_at', $this->created_at);
        $query->bindParam(':updated_at', $this->updated_at);
        $query->bindParam(':role', $this->role);

        // Hash the password securely using password_hash
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $query->bindParam(':password', $hashedPassword);
        
        if($query->execute()){
            return true;
        }
        else{
            return false;
        }	
    }

    function is_email_exist(){
        $sql = "SELECT * FROM user WHERE email = :email;";
        $query=$this->db->connect()->prepare($sql);
        $query->bindParam(':email', $this->email);
        if($query->execute()){
            if($query->rowCount()>0){
                return true;
            }
        }
        return false;
    }
}

?>