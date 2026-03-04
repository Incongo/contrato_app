<?php
// Core/AuthMiddleware.php
require_once __DIR__ . '/Auth.php';

class AuthMiddleware {
    
    /**
     * Verifica que el usuario está logueado.
     * Si no lo está, redirige al login.
     */
    public static function protegerPagina() {
        $auth = new Auth();
        
        if (!$auth->check()) {
            // Guardar la URL a la que intentaba acceder
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            
            // Redirigir al login
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Verifica que el usuario está logueado.
     * Si no lo está, devuelve false (para APIs).
     */
    public static function verificarSesion() {
        $auth = new Auth();
        return $auth->check();
    }
}