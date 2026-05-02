<?php
class Notification {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    public function add($user,$msg){
        $sql="INSERT INTO notifications (user_id,message)
            VALUES (?,?)";
        $stmt=$this->conn->prepare($sql);
        $stmt->bind_param("is",$user,$msg);
        return $stmt->execute();
    }

    public function get($user){
        $sql="SELECT * FROM notifications WHERE user_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->bind_param("i",$user);
        $stmt->execute();
        return $stmt->get_result();
    }
}
