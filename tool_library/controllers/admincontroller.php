<?php
class AdminController {
    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    public function approveTool($id){
        $sql = "UPDATE tools
                SET availability_status='available'
                WHERE tool_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$id);
        return $stmt->execute();
    }

    public function suspendUser($id){
        $sql = "UPDATE users
                SET status='suspended'
                WHERE user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$id);
        return $stmt->execute();
    }
}
?>