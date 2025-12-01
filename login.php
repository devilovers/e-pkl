<?php
session_start();
require_once "config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT id, username, nama, nis_nip, katasandi, role FROM users WHERE username = ? LIMIT 1";
        if ($stmt = $koneksi->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['katasandi'])) {
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['nis_nip'] = $user['nis_nip'];
                    $_SESSION['role'] = $user['role'];

                    switch ($user['role']) {
                        case 'Siswa':
                            header("Location: dashboard_siswa.php");
                            break;
                        case 'Pembimbing':
                            header("Location: dashboard_pembimbing.php");
                            break;
                        case 'Penilai':
                            header("Location: dashboard_penilai.php");
                            break;
                        case 'Admin':
                            header("Location: dashboard_admin.php");
                            break;
                        default:
                            $error = "Role tidak dikenali!";
                    }
                    exit;
                } else {
                    $error = "Password salah!";
                }
            } else {
                $error = "Username tidak ditemukan!";
            }

            $stmt->close();
        } else {
            $error = "Query gagal dipersiapkan: " . $koneksi->error;
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Jurnal PKL</title>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #ffffff 50%, #d2b48c 50%);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      width: 350px;
      text-align: center;
    }
    h2 {
      font-size: 20px;
      color: #4a3c31;
      margin-bottom: 10px;
    }
    h3 {
      color: #6e5b48;
      margin-bottom: 20px;
      font-size: 12px;
    }
    .form-group {
      margin-bottom: 15px;
      text-align: left;
    }
    label {
      display: block;
      font-weight: bold;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper input {
      width: 100%;
      padding: 10px;
      padding-right: 40px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 14px;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      height: 100%;
      display: flex;
      align-items: center;
      cursor: pointer;
      font-size: 18px;
      color: #888;
    }
    .toggle-password:hover {
      color: #333;
    }
    button {
      width: 100%;
      padding: 12px;
      background: #d2b48c;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      cursor: pointer;
      margin-top: 10px;
    }
    button:hover {
      background: #b4936f;
    }
    .error {
      color: red;
      margin-bottom: 15px;
      font-size: 14px;
    }
  </style>
</head>
<body>

<div class="login-container">
  <h2>JURNAL KEGIATAN SISWA PKL</h2>
  <h3>SMK NEGERI 4 BANJARMASIN</h3>
  <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
  <form method="POST" autocomplete="off">
    <div class="form-group">
      <label for="username">Username</label>
      <div class="input-wrapper">
        <input type="text" name="username" id="username" required>
        <!-- Kosongkan elemen posisi kanan agar padding & tinggi sama -->
        <div style="width: 24px; height: 100%; position: absolute; right: 12px;"></div>
      </div>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <div class="input-wrapper">
        <input type="password" name="password" id="password" required>
        <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
      </div>
    </div>
    <button type="submit">Login</button>
  </form>
</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordField = document.getElementById('password');

  togglePassword.addEventListener('click', function () {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
  });
</script>

</body>
</html>