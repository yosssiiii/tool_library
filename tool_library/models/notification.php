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


        public function countUnread(int $userId): int {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['c'];
    }
 
    public function markAllRead(int $userId): void {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}    