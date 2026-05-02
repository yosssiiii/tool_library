class Review {
    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    public function addReview($reviewer,$reviewed,$rating,$comment){
        $sql = "INSERT INTO reviews (reviewer_id,reviewed_user_id,rating,comment)
                VALUES (?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiis",$reviewer,$reviewed,$rating,$comment);
        return $stmt->execute();
    }

    public function getReviews($user_id){
        $sql = "SELECT * FROM reviews WHERE reviewed_user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}