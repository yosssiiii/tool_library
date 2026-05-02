<?php
session_start();

// 1. التحقق من تسجيل الدخول
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");
require_once("../models/message.php");
require_once("../models/user.php");

$db = new Database();
$conn = $db->connect();
$msgModel = new Message($conn);
$userModel = new User($conn);

// جلب بيانات المستلم
$receiver_id = isset($_GET['user']) ? intval($_GET['user']) : 0;

if($receiver_id == 0){
    // إذا لم يتم تحديد مستخدم، حوله للداشبورد أو لصفحة قائمة المستخدمين
    header("Location: dashboard.php"); 
    exit();
}

$receiverData = $userModel->getUser($receiver_id);

if(!$receiverData){
    die("<div class='alert alert-danger m-5'>Error: User not found!</div>");
}

// 3. إرسال الرسالة
if(isset($_POST['send']) && !empty(trim($_POST['message']))){
    $msgModel->send($_SESSION['user_id'], $receiver_id, $_POST['message']);
    header("Location: chat.php?user=" . $receiver_id);
    exit();
}

// 4. جلب المحادثة
$chatHistory = $msgModel->getChat($_SESSION['user_id'], $receiver_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo $receiverData['full_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; flex-direction: column; }
        .chat-container { max-width: 800px; margin: 20px auto; background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 80vh; }
        .chat-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; background: #3b82f6; color: white; border-radius: 15px 15px 0 0; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; background: #f8f9fa; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; position: relative; }
        .sent { align-self: flex-end; background: #3b82f6; color: white; border-bottom-right-radius: 2px; margin-left: auto; }
        .received { align-self: flex-start; background: #e4e6eb; color: #050505; border-bottom-left-radius: 2px; }
        .chat-input { padding: 15px; border-top: 1px solid #eee; background: white; border-radius: 0 0 15px 15px; }
        .time { font-size: 0.7rem; opacity: 0.7; margin-top: 5px; display: block; text-align: right; }
    </style>
</head>
<body>

<div class="container flex-grow-1">
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <a href="dashboard.php" class="text-white me-3"><i class="fa fa-arrow-left"></i></a>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($receiverData['full_name']); ?>&background=random" class="rounded-circle me-2" width="40">
            <h6 class="mb-0"><?php echo $receiverData['full_name']; ?></h6>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages d-flex flex-column" id="chatBox">
            <?php if($chatHistory && $chatHistory->num_rows > 0): ?>
                <?php while($row = $chatHistory->fetch_assoc()): 
                    $isMe = ($row['sender_id'] == $_SESSION['user_id']);
                ?>
                    <div class="message-bubble <?php echo $isMe ? 'sent' : 'received'; ?>">
                        <?php echo htmlspecialchars($row['message_text']); ?>
                        <!-- Assuming you have a created_at column, if not, remove the span below -->
                        <span class="time"><?php echo isset($row['created_at']) ? date('H:i', strtotime($row['created_at'])) : ''; ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted mt-5">No messages yet. Say hello!</div>
            <?php endif; ?>
        </div>

        <!-- Input Area -->
        <div class="chat-input">
            <form method="POST" class="d-flex gap-2">
                <input name="message" class="form-control rounded-pill" placeholder="Type a message..." autocomplete="off" required>
                <button name="send" class="btn btn-primary rounded-circle">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // جعل السكرول ينزل لآخر رسالة تلقائياً
    var chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

</body>
</html>