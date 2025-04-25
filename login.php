<?php 
include 'db_conn.php'; 
session_start(); 

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password, $role_id);

    if ($stmt->num_rows == 1) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['role_id'] = $role_id;

            switch ($role_id) {
                case 1:
                    header("Location: pregnant_dashboard.php");
                    break;
                case 2:
                    header("Location: provider_dashboard.php");
                    break;
                case 3:
                    header("Location: admin_dashboard.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Maternal Health</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap and Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Custom Styles -->
    <link href="CSS/styles.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column justify-content-center align-items-center min-vh-100">

    <!-- Big Top Heading -->
    <div class="top-heading text-center mb-4">
        <h1>Welcome to Mama Well Solutions</h1>
    </div>

    <!-- Login Form Container -->
    <div class="container" style="max-width: 400px;">

        <div class="heading text-center mb-3">Sign In</div>

        <!-- Show Error -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <input required class="input" type="email" name="email" id="email" placeholder="E-mail">
            <input required class="input" type="password" name="password" id="password" placeholder="Password">
            <span class="forgot-password"><a href="#">Forgot Password?</a></span>
            <input class="login-button" type="submit" value="Sign In">
            <p>Not a member? <a href="register.php">Register</a></p>
        </form>

        <div class="social-account-container text-center mt-3">
            <span class="title">Or Sign in with</span>
            <div class="social-accounts d-flex justify-content-center gap-2 mt-2">
                <button class="social-button google"><i class="fab fa-google"></i></button>
                <button class="social-button facebook"><i class="fab fa-facebook-f"></i></button>
                <button class="social-button twitter"><i class="fab fa-x-twitter"></i></button>
            </div>
        </div>
    </div>

</body>
</html>
