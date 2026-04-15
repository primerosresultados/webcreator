<?php
/**
 * ============================================
 * AUTH API - Login / Logout / Session
 * ============================================
 * POST /api/auth.php?action=login   - Login
 * POST /api/auth.php?action=logout  - Logout
 * GET  /api/auth.php?action=me      - Get current user
 * GET  /api/auth.php?action=csrf    - Get CSRF token
 */

require_once __DIR__ . '/init.php';

$action = $_GET['action'] ?? '';
$method = getMethod();

switch ($action) {

    // ============================================
    // GET CSRF TOKEN
    // ============================================
    case 'csrf':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        jsonSuccess(['csrf_token' => generateCSRFToken()]);
        break;

    // ============================================
    // LOGIN
    // ============================================
    case 'login':
        if ($method !== 'POST') jsonError('Método no permitido', 405);
        
        // Rate limit: 5 attempts per minute
        if (!rateLimit('login', 5, 60)) {
            jsonError('Demasiados intentos. Espere un momento.', 429);
        }

        $data = getJSONBody();
        $email = sanitize($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            jsonError('Email y contraseña son requeridos.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Formato de email inválido.');
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, password, full_name, role, avatar, is_active FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            logActivity('login_failed', 'auth', null, ['email' => $email]);
            jsonError('Credenciales incorrectas.', 401);
        }

        if (!$user['is_active']) {
            jsonError('Cuenta desactivada. Contacte al administrador.', 403);
        }

        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['last_activity'] = time();

        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        logActivity('login', 'auth', $user['id']);

        jsonSuccess([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'avatar' => $user['avatar']
            ],
            'csrf_token' => $_SESSION['csrf_token']
        ]);
        break;

    // ============================================
    // LOGOUT
    // ============================================
    case 'logout':
        if ($method !== 'POST') jsonError('Método no permitido', 405);
        
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            logActivity('logout', 'auth', $userId);
        }
        
        // Destroy session completely
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        jsonSuccess(['message' => 'Sesión cerrada exitosamente.']);
        break;

    // ============================================
    // GET CURRENT USER
    // ============================================
    case 'me':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        
        $currentUser = requireAuth();
        
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, full_name, role, avatar, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();
        
        if (!$user) jsonError('Usuario no encontrado.', 404);
        
        jsonSuccess(['user' => $user, 'csrf_token' => generateCSRFToken()]);
        break;

    default:
        jsonError('Acción no válida.', 400);
}
