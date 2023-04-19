<?php
session_start();

$db_file = 'message';

// Connect to databases
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

// Handle message editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && trim($_POST['message']) !== '') {
  $stmt = $pdo->prepare("UPDATE messages SET message = ? WHERE id = ? AND user_id = ?");
  $message = nl2br(htmlspecialchars($_POST['message']));
  $stmt->execute([$message, $_POST['id'], $_SESSION['user_id']]);
  header('Location: index.php');
  exit();
}

// Get the message to edit
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND user_id = ?");
  $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
  $message = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  header('Location: index.php');
  exit();
}
?>

<!DOCTYPE html>
<html data-bs-theme="dark">
  <head>
    <title>Edit Message</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  </head>
  <body>
    <nav class="navbar mb-2 navbar-expand bg-body-tertiary">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold disabled">Edit Message</a>
        <form method="post">
          <div class="d-flex justify-content-end">
            <a class="btn btn-sm btn-danger fw-bold rounded-pill me-2" href="index.php">Cancel</a>
          </div>
        </form> 
      </div>
    </nav>
    <div class="container">
      <form method="post">
        <div class="form-group">
          <textarea class="form-control" id="message" oninput="stripHtmlTags(this)" name="message" rows="12"><?php echo strip_tags($message['message']); ?></textarea>
        </div>
        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
        <button type="submit" class="btn w-100 mt-2 fw-bold btn-primary">Save</button>
      </form>
    </div>
  </body>
</html>