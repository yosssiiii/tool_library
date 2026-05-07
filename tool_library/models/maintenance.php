<?php

class Maintenance {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    /* ===============================
       ADD MAINTENANCE REQUEST
    =============================== */
    public function create(
        $tool_id,
        $reported_by,
        $issue_description
    ){

        $sql = "INSERT INTO maintenance
        (
            tool_id,
            reported_by,
            issue_description,
            status
        )
        VALUES
        (
            ?, ?, ?, 'pending'
        )";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param(
            "iis",
            $tool_id,
            $reported_by,
            $issue_description
        );

        return $stmt->execute();
    }

    /* ===============================
       GET ALL REQUESTS
    =============================== */
    public function getAll(){

        $sql = "SELECT 
                    mr.*,
                    t.tool_name,
                    u.full_name
                FROM maintenance mr

                JOIN tools t
                ON mr.tool_id = t.tool_id

                JOIN users u
                ON mr.reported_by = u.user_id

                ORDER BY mr.maintenance_id DESC";

        return $this->conn->query($sql);
    }

    /* ===============================
       GET USER REQUESTS
    =============================== */
    public function getUserRequests($user_id){

        $sql = "SELECT 
                    mr.*,
                    t.tool_name
                FROM maintenance mr

                JOIN tools t
                ON mr.tool_id = t.tool_id

                WHERE mr.reported_by = ?

                ORDER BY mr.maintenance_id DESC";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $user_id);

        $stmt->execute();

        return $stmt->get_result();
    }

    /* ===============================
       GET SINGLE REQUEST
    =============================== */
    public function getById($id){

        $sql = "SELECT * FROM maintenance
                WHERE maintenance_id=?";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $id);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /* ===============================
       UPDATE STATUS
    =============================== */
    public function updateStatus($id, $status){

        $sql = "UPDATE maintenance
                SET status=?
                WHERE maintenance_id=?";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("si", $status, $id);

        return $stmt->execute();
    }

    /* ===============================
       DELETE REQUEST
    =============================== */
    public function delete($id){

        $sql = "DELETE FROM maintenance
                WHERE maintenance_id=?";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

}
?>