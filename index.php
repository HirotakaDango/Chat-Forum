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

// Function to fetch the chat messages
function getChatMessages() {
  global $pdo;
  $stmt = $pdo->prepare("SELECT messages.id, messages.user_id, messages.message, users.username FROM messages INNER JOIN users ON messages.user_id = users.id ORDER BY messages.id DESC");
  $stmt->execute();
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Create a new array for modified messages
  $modified_messages = array();

  // Loop through messages and modify message field to include links
  foreach ($messages as $message) {
    $modified_message = $message;
    $modified_messages[] = $modified_message;
  }

  return $modified_messages;
}

// Get chat messages
$messages = getChatMessages();

// Pagination
$per_page = 300;
$total_rows = count($messages);
$total_pages = ceil($total_rows / $per_page);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Display chat messages based on pagination
$messages_to_display = array_slice($messages, $offset, $per_page);
?>

<!DOCTYPE html>
<html data-bs-theme="dark">
<head>
  <title>Chat Group</title>
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include('header.php'); ?>
  <div class="container-fluid">
    <div id="message-container">
      <?php foreach ($messages_to_display as $message): ?>
        <div class="card border-0">
          <p class="text-white fw-semibold"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($message['username']); ?></p>
          <div style="word-break: break-word;" data-lazyload>
            <p style="word-break: break-word;">
            <?php
              $messageText = $message['message'];
              $messageTextWithoutTags = strip_tags($messageText);
              $pattern = '/\bhttps?:\/\/\S+/i';

              $formattedText = preg_replace_callback($pattern, function ($matches) {
                $url = htmlspecialchars($matches[0]);
                return '<a href="' . $url . '">' . $url . '</a>';
              }, $messageTextWithoutTags);

              $formattedTextWithLineBreaks = nl2br($formattedText);
              echo $formattedTextWithLineBreaks;
            ?>
            </p>
          </div>
          <div>
            <?php if ($message['user_id'] == $_SESSION['user_id']): ?>
              <form method="post" style="display: inline;">
                <div class="btn-group position-absolute top-0 end-0">
                  <a href="edit_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-secondary"><i class="bi bi-pencil-fill"></i></a>
                  <button type="submit" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-secondary"><i class="bi bi-trash-fill"></i></button>
                </div>
                <input type="hidden" name="delete" value="<?php echo $message['id']; ?>">
              </form>
            <?php endif; ?>
          </div>
          <hr>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="pagination justify-content-center" style="margin-bottom: 80px;">
      <?php if ($page > 1): ?>
        <a class="btn btn-sm fw-bold btn-primary me-1" href="?page=<?php echo $page - 1 ?>">Prev</a>
      <?php endif ?>
      <?php if ($page < $total_pages): ?>
        <a class="btn btn-sm fw-bold btn-primary ms-1" href="?page=<?php echo $page + 1 ?>">Next</a>
      <?php endif ?>
    </div>
    <nav class="navbar fixed-bottom bg-dark container-fluid" style="margin-bottom: -15px;">
      <form id="message-form" method="post" class="container-fluid w-100 mb-3">
        <div class="input-group">
          <textarea id="message-input" name="message" class="form-control rounded-3 rounded-end-0" style="height: 40px; max-height: 200px;" placeholder="Type your message..." aria-label="Type a message..." aria-describedby="basic-addon2" 
            onkeydown="if(event.keyCode == 13) { this.style.height = (parseInt(this.style.height) + 10) + 'px'; return true; }"
            onkeyup="this.style.height = '40px'; var newHeight = (this.scrollHeight + 10 * (this.value.split(/\r?\n/).length - 1)) + 'px'; if (parseInt(newHeight) > 200) { this.style.height = '200px'; } else { this.style.height = newHeight; }"></textarea>
          <button type="submit" class="btn btn-primary fw-bold">send</button>
        </div>
      </form>
    </nav> 
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function() {
      // Function to update the chat messages
      function updateChatMessages() {
        $.ajax({
          url: '<?php echo $_SERVER['PHP_SELF']; ?>',
          type: 'GET',
          dataType: 'html',
          success: function(response) {
            $('#message-container').html($(response).find('#message-container').html());
          }
        });
      }

      // Function to send a new message
      function sendMessage(message) {
        $.ajax({
          url: '<?php echo $_SERVER['PHP_SELF']; ?>',
          type: 'POST',
          data: { message: message },
          success: function() {
            updateChatMessages();
          }
        });
      }

      // Handle form submission
      $('#message-form').submit(function(event) {
        event.preventDefault();
        var message = $('#message-input').val().trim();
        if (message !== '') {
          sendMessage(message);
          $('#message-input').val('');
        }
      });

      // Update chat messages periodically
      setInterval(updateChatMessages, 2000);
    });
  </script>
</body>
</html>