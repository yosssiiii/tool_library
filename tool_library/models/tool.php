<?php

class Tool {

    private $conn;

    public function __construct($database){
        $this->conn = $database;
    }

    public function addTool($owner,$name,$category,$price,$description){

        $sql = "INSERT INTO tools
        (owner_id,tool_name,category,price_per_day,description)
        VALUES (?,?,?,?,?)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param(
            "issds",
            $owner,
            $name,
            $category,
            $price,
            $description
        );

        return $stmt->execute();
    }

    public function getMyTools($id){

        $sql = "SELECT * FROM tools WHERE owner_id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$id);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function getAllTools(){

        return $this->conn->query(
            "SELECT * FROM tools"
        );
    }

public function getToolById($id) {
        // استخدمنا price بدلاً من price_per_hour بناءً على الخطأ السابق
        $query = "SELECT * FROM tools WHERE tool_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function search($keyword){

        $sql = "SELECT * FROM tools
                WHERE tool_name LIKE ?";

        $stmt = $this->conn->prepare($sql);

        $key = "%".$keyword."%";

        $stmt->bind_param("s",$key);

        $stmt->execute();

        return $stmt->get_result();
    }
}
?>