<?php
require_once 'config\config.php';
session_start();

if(isset($_SESSION['user_id'])) {
    header("Location: public\dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    :root {
        --color-primary: #3d2b1e;
        --color-secondary: #6d4c41;
        --color-accent: #8d6e63;
        --color-light: #efebe9;
        --color-dark: #1e1611;
    }

    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
            url('../src/img/Crub.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Cormorant Garamond', serif;
        position: relative;
        overflow: hidden;
        color: var(--color-dark);
    }

    .login-container {
        max-width: 480px;
        width: 90%;
        padding: 2.5rem;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        position: relative;
        z-index: 1;
        border: 1px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(8px);
        overflow: hidden;
    }

    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 8px;
        background: linear-gradient(90deg, var(--color-primary), var(--color-accent));
    }

    .logo-container {
        text-align: center;
        margin-bottom: 0.5rem;
        position: relative;
    }

    .book-icon-container {
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 2.5rem;
        color: var(--color-accent);
        opacity: 0.8;
    }

    .book-image {
        height: 100px;
        width: 100px;
        margin: 0 auto;
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.15));
        transition: transform 0.3s ease;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--color-primary);
    }

    .book-image:hover {
        transform: scale(1.05) rotate(-2deg);
    }

    .library-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.5rem;
        color: var(--color-accent);
        opacity: 0.8;
    }

    h2 {
        color: var(--color-primary);
        font-weight: 700;
        margin: 1.5rem 0;
        text-align: center;
        position: relative;
        font-size: 2.4rem;
        letter-spacing: 1px;
    }

    h2::after {
        content: '';
        position: absolute;
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--color-accent), transparent);
    }

    .form-label {
        color: var(--color-primary);
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 1.05rem;
    }

    .form-control {
        border-radius: 6px;
        padding: 12px 16px;
        border: 1px solid #d7ccc8;
        transition: all 0.3s;
        background-color: rgba(255, 255, 255, 0.8);
    }

    .form-control:focus {
        border-color: var(--color-accent);
        box-shadow: 0 0 0 0.25rem rgba(109, 76, 65, 0.2);
        background-color: white;
    }

    .input-group-text {
        background-color: var(--color-light);
        border-color: #d7ccc8;
        color: var(--color-primary);
    }

    .btn-login {
        background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
        color: white;
        border: none;
        padding: 12px;
        border-radius: 6px;
        transition: all 0.3s;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-top: 1.5rem;
        box-shadow: 0 4px 8px rgba(61, 43, 30, 0.2);
    }

    .btn-login:hover {
        background: linear-gradient(135deg, var(--color-accent), var(--color-primary));
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(61, 43, 30, 0.25);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .alert {
        border-radius: 6px;
        padding: 0.75rem 1.25rem;
    }

    .book-pages-decoration {
        position: absolute;
        opacity: 0.05;
        z-index: -1;
        font-size: 15rem;
        color: var(--color-primary);
    }

    .book-pages-left {
        left: -3rem;
        top: 30%;
        transform: rotate(15deg);
    }

    .book-pages-right {
        right: -3rem;
        bottom: 20%;
        transform: rotate(-15deg);
    }

    @media (max-width: 576px) {
        .login-container {
            padding: 2rem 1.5rem;
        }

        h2 {
            font-size: 2rem;
        }

        .library-icon {
            font-size: 2rem;
            right: 10px;
        }
    }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <!-- Decoración de páginas de libros -->
        <i class="bi bi-journal-bookmark-fill book-pages-decoration book-pages-left"></i>
        <i class="bi bi-journal-bookmark-fill book-pages-decoration book-pages-right"></i>

        <!-- Logo -->
        <div class="logo-container">
            <img src="src\img\logoUnachi.jpg" alt="Logo Biblioteca" class="book-image">
        </div>

        <!-- Icono del libro debajo del logo -->
        <div class="book-icon-container">
            <i class="bi bi-book-half"></i>
        </div>
        <h2>Biblioteca CRUBA</h2>

        <?php
            if (isset($_GET['success']) && $_GET['success']) {
                echo '<div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> Usuario registrado</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle"></i> '.htmlspecialchars($_GET['error']).'</div>';
            }
        ?>

        <form action="public\login.php" method="post" class="form-group">
            <div class="mb-4">
                <label for="cedula" class="form-label"><i class="bi bi-person-badge"></i> Cédula</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control" id="cedula" name="cedula" placeholder="Ingrese su cédula"
                        required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label"><i class="bi bi-lock"></i> Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Ingrese su contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>