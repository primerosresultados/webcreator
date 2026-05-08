<?php
/**
 * ============================================
 * USERS API — gestión de administradores
 * ============================================
 * GET    /api/users.php                  - Lista de usuarios
 * POST   /api/users.php?action=create    - Crear nuevo admin
 * POST   /api/users.php?action=update    - Actualizar email/full_name/role
 * POST   /api/users.php?action=password  - Cambiar contraseña (propia o de otro)
 * POST   /api/users.php?action=toggle    - Activar/desactivar usuario
 * POST   /api/users.php?action=delete    - Eliminar usuario
 *
 * Roles:
 *  - superadmin: puede todo (crear, editar, borrar, cambiar password de cualquiera)
 *  - admin: puede cambiar su propia password y email
 */

require_once __DIR__ . '/init.php';

$method = getMethod();
$action = $_GET['action'] ?? '';

// Auth siempre requerida
$currentUser = requireAuth();
$db = getDB();

// Helper: ¿el usuario actual es super?
$isSuper = ($currentUser['role'] ?? '') === 'superadmin';

switch (true) {

    // ============================================
    // LIST USERS
    // ============================================
    case $method === 'GET' && $action === '':
        if (!$isSuper) jsonError('Solo superadmin puede ver la lista de usuarios.', 403);

        $stmt = $db->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll();
        jsonSuccess(['users' => $users]);
        break;

    // ============================================
    // CREATE USER (solo superadmin)
    // ============================================
    case $method === 'POST' && $action === 'create':
        if (!$isSuper) jsonError('Solo superadmin puede crear usuarios.', 403);
        requireCSRF();

        $data = getJSONBody();
        $username = sanitize($data['username'] ?? '');
        $email    = sanitize($data['email'] ?? '');
        $fullName = sanitize($data['full_name'] ?? '');
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? 'admin';

        if (empty($username) || empty($email) || empty($password)) {
            jsonError('Usuario, email y contraseña son obligatorios.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Email inválido.');
        }
        if (strlen($password) < 8) {
            jsonError('La contraseña debe tener al menos 8 caracteres.');
        }
        if (!in_array($role, ['superadmin', 'admin', 'editor'], true)) {
            $role = 'admin';
        }

        // Chequear duplicados
        $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $check->execute([$email, $username]);
        if ($check->fetch()) jsonError('Ya existe un usuario con ese email o nombre de usuario.');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $db->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $ins->execute([$username, $email, $hash, $fullName ?: $username, $role]);
        $newId = $db->lastInsertId();

        logActivity('create_user', 'users', $newId, ['email' => $email, 'role' => $role]);
        jsonSuccess(['message' => 'Usuario creado.', 'id' => intval($newId)]);
        break;

    // ============================================
    // UPDATE USER (email, full_name, role)
    // - Superadmin: cualquier usuario
    // - Admin: solo su propio email/full_name (no role)
    // ============================================
    case $method === 'POST' && $action === 'update':
        requireCSRF();
        $data = getJSONBody();
        $userId = intval($data['id'] ?? 0);
        if (!$userId) jsonError('ID de usuario requerido.');

        $isSelf = $userId === intval($currentUser['id']);
        if (!$isSuper && !$isSelf) jsonError('No tenés permiso para editar este usuario.', 403);

        $fields = [];
        $values = [];

        if (isset($data['email'])) {
            $email = sanitize($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Email inválido.');
            // Chequear duplicado
            $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $check->execute([$email, $userId]);
            if ($check->fetch()) jsonError('Ya hay otro usuario con ese email.');
            $fields[] = 'email = ?'; $values[] = $email;
        }
        if (isset($data['full_name'])) {
            $fields[] = 'full_name = ?'; $values[] = sanitize($data['full_name']);
        }
        // Solo superadmin puede cambiar role
        if (isset($data['role']) && $isSuper) {
            $role = $data['role'];
            if (!in_array($role, ['superadmin', 'admin', 'editor'], true)) jsonError('Rol inválido.');
            // No permitir que superadmin se quite el rol a sí mismo si es el último
            if ($isSelf && $role !== 'superadmin') {
                $cnt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1")->fetchColumn();
                if ((int)$cnt <= 1) jsonError('No podés quitarte el rol de superadmin: sos el último.');
            }
            $fields[] = 'role = ?'; $values[] = $role;
        }

        if (empty($fields)) jsonError('Nada para actualizar.');

        $values[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->prepare($sql)->execute($values);

        // Si actualizó su propio email, refrescar sesión
        if ($isSelf && isset($data['email'])) {
            $_SESSION['user_email'] = sanitize($data['email']);
        }

        logActivity('update_user', 'users', $userId, ['fields' => array_keys($data)]);
        jsonSuccess(['message' => 'Usuario actualizado.']);
        break;

    // ============================================
    // CHANGE PASSWORD
    // - Si target = self: requiere current_password
    // - Si target ≠ self: solo superadmin (sin current_password)
    // ============================================
    case $method === 'POST' && $action === 'password':
        requireCSRF();
        $data = getJSONBody();
        $userId = intval($data['id'] ?? $currentUser['id']);
        $newPassword = $data['new_password'] ?? '';
        $currentPassword = $data['current_password'] ?? '';

        if (strlen($newPassword) < 8) jsonError('La nueva contraseña debe tener al menos 8 caracteres.');

        $isSelf = $userId === intval($currentUser['id']);
        if (!$isSelf && !$isSuper) jsonError('No tenés permiso para cambiar la contraseña de otro usuario.', 403);

        if ($isSelf) {
            // Verificar contraseña actual
            $u = $db->prepare("SELECT password FROM users WHERE id = ?");
            $u->execute([$userId]);
            $row = $u->fetch();
            if (!$row || !password_verify($currentPassword, $row['password'])) {
                jsonError('La contraseña actual es incorrecta.', 401);
            }
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);

        logActivity('change_password', 'users', $userId, ['by_self' => $isSelf]);
        jsonSuccess(['message' => 'Contraseña actualizada.']);
        break;

    // ============================================
    // TOGGLE ACTIVE (activar/desactivar)
    // ============================================
    case $method === 'POST' && $action === 'toggle':
        if (!$isSuper) jsonError('Solo superadmin.', 403);
        requireCSRF();
        $data = getJSONBody();
        $userId = intval($data['id'] ?? 0);
        $active = !empty($data['is_active']) ? 1 : 0;
        if (!$userId) jsonError('ID requerido.');
        if ($userId === intval($currentUser['id']) && !$active) {
            jsonError('No podés desactivarte a vos mismo.');
        }
        $db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$active, $userId]);
        logActivity('toggle_user', 'users', $userId, ['is_active' => $active]);
        jsonSuccess(['message' => $active ? 'Usuario activado.' : 'Usuario desactivado.']);
        break;

    // ============================================
    // DELETE
    // ============================================
    case $method === 'POST' && $action === 'delete':
        if (!$isSuper) jsonError('Solo superadmin.', 403);
        requireCSRF();
        $data = getJSONBody();
        $userId = intval($data['id'] ?? 0);
        if (!$userId) jsonError('ID requerido.');
        if ($userId === intval($currentUser['id'])) jsonError('No podés borrarte a vos mismo.');
        // No borrar el último superadmin
        $u = $db->prepare("SELECT role FROM users WHERE id = ?");
        $u->execute([$userId]);
        $row = $u->fetch();
        if ($row && $row['role'] === 'superadmin') {
            $cnt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1")->fetchColumn();
            if ((int)$cnt <= 1) jsonError('No podés borrar el último superadmin.');
        }
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        logActivity('delete_user', 'users', $userId);
        jsonSuccess(['message' => 'Usuario eliminado.']);
        break;

    default:
        jsonError('Acción no reconocida.', 400);
}
