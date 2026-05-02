<?php
class Message {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    public function send($s,$r,$msg){
        $sql="INSERT INTO messages (sender_id,receiver_id,message_text)
            VALUES (?,?,?)";
        $stmt=$this->conn->prepare($sql);
        $stmt->bind_param("iis",$s,$r,$msg);
        return $stmt->execute();
    }

    public function getChat($s,$r){
        $sql="SELECT * FROM messages 
            WHERE (sender_id=? AND receiver_id=?)
            OR (sender_id=? AND receiver_id=?)
            ORDER BY message_id ASC";

        $stmt=$this->conn->prepare($sql);
        $stmt->bind_param("iiii",$s,$r,$r,$s);
        $stmt->execute();

        return $stmt->get_result();
    }
}