<?php
// app/Core/Auth.php
require_once __DIR__ . '/Database.php';

class Auth
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($nombre, $email, $password)
    {
        // Verificar si email existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['error' => 'El email ya está registrado'];
        }

        // Hash de contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)"
        );

        if ($stmt->execute([$nombre, $email, $hash])) {
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        }

        return ['error' => 'Error al registrar usuario'];
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, email, password FROM usuarios WHERE email = ? AND activo = 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];

            // Actualizar último login
            $stmt = $this->pdo->prepare(
                "UPDATE usuarios SET last_login = NOW() WHERE id = ?"
            );
            $stmt->execute([$user['id']]);

            return ['success' => true, 'user' => $user];
        }

        return ['error' => 'Email o contraseña incorrectos'];
    }

    public function logout()
    {
        session_destroy();
        return true;
    }

    public function check()
    {
        return isset($_SESSION['user_id']);
    }

    public function user()
    {
        if (!$this->check()) return null;

        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, email, created_at, last_login FROM usuarios WHERE id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
}
