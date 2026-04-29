<?php

class Payment {

    private $conn;

    public function __construct($database){
        $this->conn = $database;
    }

    public function makePayment(
        $reservation_id,
        $amount,
        $deposit
    ){

        $sql = "INSERT INTO payments
        (reservation_id,amount,deposit)
        VALUES (?,?,?)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param(
            "idd",
            $reservation_id,
            $amount,
            $deposit
        );

        return $stmt->execute();
    }

    public function getMyPayments($user_id){

        $sql = "SELECT payments.*,
                tools.tool_name
                FROM payments
                JOIN reservations
                ON payments.reservation_id =
                reservations.reservation_id
                JOIN tools
                ON reservations.tool_id = tools.tool_id
                WHERE reservations.user_id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$user_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function refund($reservation_id){

        $sql = "UPDATE payments
                SET payment_status='refunded'
                WHERE reservation_id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$reservation_id);

        return $stmt->execute();
    }
}
?>