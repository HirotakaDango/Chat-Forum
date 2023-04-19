<?php
session_start();

$db_file = 'message';

// Connect to database
try {
  $pdo = new PDO("sqlite:" . $db_file);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Could not connect to database: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
  header('Location: session.php');
  exit();
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
  $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
  $stmt->execute([$_POST['delete'], $_SESSION['user_id']]);
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && trim($_POST['message']) !== '') {
  $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
  $message = nl2br(htmlspecialchars($_POST['message']));
  $stmt->execute([$_SESSION['user_id'], $message]);
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
  session_unset();
  session_destroy();
  header('Location: session.php');
  exit();
} 

// Display chat messages
$stmt = $pdo->prepare("SELECT messages.id, messages.user_id, messages.message, users.username FROM messages INNER JOIN users ON messages.user_id = users.id ORDER BY messages.id DESC");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="dark">
  <head>
    <title>Chat Group</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container-fluid mt-1">
      <?php foreach ($messages as $message): ?>
        <p><strong><?php echo htmlspecialchars($message['username']); ?>:</strong></p>
        <p><?php echo $message['message']; ?></p>  
        <div>
          <?php if ($message['user_id'] == $_SESSION['user_id']): ?>
            <form method="post" style="display: inline;">
              <div class="dropdown">
                <button class="btn btn-secondary btn-sm position-b float-end" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a href="edit_message.php?id=<?php echo $message['id']; ?>" class="dropdown-item mb-2"><i class="bi bi-pencil-fill"></i> edit message</a></li>
                  <li><button type="submit" onclick="return confirm('Are you sure?')" class="dropdown-item"><i class="bi bi-trash-fill"></i> remove</button></li>
                </ul>
              </div>
              <input type="hidden" name="delete" value="<?php echo $message['id']; ?>">
            </form>
          <?php endif; ?>
        </div>
        <hr>
      <?php endforeach; ?>
      <nav class="navbar fixed-bottom" style="margin-bottom: -15px;">
        <form class="form-control border-0 container" action="" method="POST">
          <div class="input-group mb-2 mt-2">
            <textarea type="text" class="form-control rounded-3" style="height: 40px; max-height: 130px;" name="message" placeholder="Type something..." aria-label="Type a message..." aria-describedby="basic-addon2" 
              onkeydown="if(event.keyCode == 13) { this.style.height = (parseInt(this.style.height) + 10) + 'px'; return true; }"
              onkeyup="this.style.height = '40px'; var newHeight = (this.scrollHeight + 10 * (this.value.split(/\r?\n/).length - 1)) + 'px'; if (parseInt(newHeight) > 130) { this.style.height = '130px'; } else { this.style.height = newHeight; }"></textarea>
            <button class="btn btn-primary ms-1 rounded-3" type="submit" style="width: 40px; height: 40px;"><i class="bi bi-send-fill"></i></button>
          </div>
        </form>
      </nav>
    </div>
    <style>
      .position-b {
        margin-top: -80px;
      }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-mQ93GR66B00ZXjt0YO5KlohRA5SY2XofN4zfuZxLkoj1gXtW8ANNCe9d5Y3eG5eD" crossorigin="anonymous"></script>
  </body>
</html> 