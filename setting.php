<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: session.php');
  exit();
}

// Establish database connection
try {
  $db = new PDO('sqlite:message');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get input data
  $current_password = htmlspecialchars($_POST['current_password']);
  $new_password = htmlspecialchars($_POST['new_password']);
  $confirm_password = htmlspecialchars($_POST['confirm_password']);

  // Validate input data
  $errors = [];
  if (empty($current_password)) {
    $errors[] = "Please enter your current password.";
  }
  if (empty($new_password)) {
    $errors[] = "Please enter a new password.";
  }
  if ($new_password !== $confirm_password) {
    $errors[] = "New password and confirm password do not match.";
  }

  // Check if current password is correct
  $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id AND password = :password");
  $stmt->bindParam(":user_id", $user_id);
  $stmt->bindParam(":password", $current_password);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) {
    $errors[] = "Current password is incorrect.";
  }

  // If no errors, update password in database
  if (empty($errors)) {
    $stmt = $db->prepare("UPDATE users SET password = :new_password WHERE id = :user_id");
    $stmt->bindParam(":new_password", $new_password);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $success_message = "Password updated successfully.";
  }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Change Password</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container mt-3">

      <h1 class="text-center fw-semibold mb-3">Change Password</h1>
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endforeach; ?>
      <?php endif; ?>

      <form method="post">
        <div class="form-floating mb-2">
          <input type="password" class="form-control fw-bold" name="current_password" placeholder="Enter current password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
          <label for="floatingPassword" class="fw-bold"><small>Enter current password</small></label>
        </div>
        <div class="form-floating mb-2">
          <input type="password" class="form-control fw-bold" name="new_password" max placeholder="Type new password"length="40" pattern="^[a-zA-Z0-9_@.-]+$">
          <label for="floatingPassword" class="fw-bold"><small>Type new password</small></label>
        </div>
        <div class="form-floating mb-2">
          <input type="password" class="form-control fw-bold" name="confirm_password" placeholder="Confirm new password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
          <label for="floatingPassword" class="fw-bold"><small>Confirm new password</small></label>
        </div>
        <div class="container">
          <header class="d-flex justify-content-center py-3">
            <ul class="nav nav-pills">
              <li class="nav-item"><button type="submit" class="btn btn-secondary me-1 fw-bold" name="submit">Save</button></li>
              <li class="nav-item"><a href="index.php" class="btn btn-secondary ms-1 fw-bold">Back</a></li>
            </ul>
          </header>
        </div> 
      </form>
    </div>
  </body>
</html>
