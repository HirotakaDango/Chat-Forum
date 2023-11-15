<?php
session_start();
$db_file = 'message';

try {
  $pdo = new PDO("sqlite:" . $db_file);
} catch (PDOException $e) {
  die("Could not connect to database: " . $e->getMessage());
}

// Create users and messages tables if they don't exist
try {
  $pdo = new PDO("sqlite:" . $db_file);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, message TEXT NOT NULL, time DATETIME, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)");
} catch (PDOException $e) {
  die("Could not connect to database: " . $e->getMessage());
}

if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit();
} else {
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);

    if (isset($_POST['login'])) {
      $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND password = ?");
      $stmt->execute([$username, $password]);
      $user_id = $stmt->fetchColumn();
      if ($user_id) {
        $_SESSION['user_id'] = $user_id;
        header('Location: index.php');
        exit();
      } else {
        $error = 'Invalid username or password';
      }
    } elseif (isset($_POST['register'])) {
      $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
      $stmt->execute([$username]);
      $user_id = $stmt->fetchColumn();
      if ($user_id) {
        echo '<div class="alert alert-danger">Username already taken</div>';
      } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        header('Location: index.php');
        exit();
      }
    }
  }
}
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
    <div class="container mt-5">
      <h1 class="mb-5 text-center fw-bold">Login or Register</h1>
      <form class="container-md container-lg" method="post">
        <div class="form-floating mb-3">
          <input type="text" name="username" class="form-control rounded-3" id="floatingInput" placeholder="Username" maxlength="15" required>
          <label for="floatingInput">Username</label>
        </div>
        <div class="form-floating mb-3">
          <input type="password" name="password" class="form-control rounded-3" id="floatingPassword" placeholder="Password" maxlength="20" required>
          <label for="floatingPassword">Password</label>
        </div>
        <center>
          <button class="btn btn-primary fw-bold" type="submit" name="login">Login</button>
          <button class="btn btn-primary fw-bold" type="submit" name="register">Register</button>
        </center>
      </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-mQ93GR66B00ZXjt0YO5KlohRA5SY2XofN4zfuZxLkoj1gXtW8ANNCe9d5Y3eG5eD" crossorigin="anonymous"></script>
  </body>
</html>