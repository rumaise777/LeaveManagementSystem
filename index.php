
<?php
require('db.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leave Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
    background-image: url("ag.jpg"); 
    background-size: cover;   
    background-repeat: no-repeat;
    background-attachment: fixed; 
    }

    .hero {
      padding: 215px 258px;
      text-align: center;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: bold;
    }
    .features {
      padding: 40px 20px;
    }
    .footer {
      background: #343a40;
      color: white;
      text-align: center;
      padding: 20px;
      margin-top: 50px;
    }
  </style>
</head>
<body>

 <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="#">Leave Management System</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero">
    <div class="container">
      <h1>Welcome to the Leave Management System</h1>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <p>© 2025 Leave Management System. All rights reserved.</p>
  </footer>

</body>
</html>
