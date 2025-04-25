<?php 
include 'db_conn.php'; 

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $roleMap = [
        "pregnant_woman" => 1,
        "healthcare_provider" => 2,
        "admin" => 3
    ];
    $role_key = $_POST['role'];
    $role_id = $roleMap[$role_key] ?? 1;

    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $password, $role_id);
        if ($stmt->execute()) {
            $message = "Registration successful. You can now <a href='login.php'>login</a>.";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Maternal Health</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap and Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Custom CSS -->
    <link href="CSS/styles.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column justify-content-center align-items-center min-vh-100">

    <!-- Big Top Heading -->
    <div class="top-heading text-center mb-4">
        <h1>Welcome to Mama Well Solutions</h1>
    </div>

    <!-- Registration Form -->
    <div class="container" style="max-width: 450px;">

        <div class="heading text-center mb-3">Register</div>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="form">
            <input required class="input" type="text" name="fullname" placeholder="Full Name">
            <input required class="input" type="email" name="email" placeholder="E-mail">
            <input required class="input" type="password" name="password" placeholder="Password">

            <select required class="input" name="role">
                <option value="" disabled selected>Select Role</option>
                <option value="pregnant_woman">Pregnant Woman</option>
                <option value="healthcare_provider">Health Care Provider</option>
                <option value="admin">Admin</option>
            </select>

            <input class="login-button" type="submit" value="Register">
        </form>

        <div class="social-account-container text-center mt-3">
            <span class="title">Or Sign up with</span>
            <div class="social-accounts d-flex justify-content-center gap-2 mt-2">
                <button class="social-button google"><i class="fab fa-google"></i></button>
                <button class="social-button facebook"><i class="fab fa-facebook-f"></i></button>
                <button class="social-button twitter"><i class="fab fa-x-twitter"></i></button>
            </div>
        </div>
    </div>

</body>
</html>
