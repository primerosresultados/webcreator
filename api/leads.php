<?php
/**
 * ============================================
 * LEADS API - CRM Lead Management
 * ============================================
 * GET    /api/leads.php              - List all leads (admin)
 * GET    /api/leads.php?id=X         - Get single lead (admin)
 * POST   /api/leads.php              - Create lead (public form)
 * PUT    /api/leads.php?id=X         - Update lead status (admin)
 * DELETE /api/leads.php?id=X         - Delete lead (admin)
 * GET    /api/leads.php?action=stats - Get lead statistics (admin)
 */

require_once __DIR__ . '/init.php';

$method = getMethod();
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ============================================
    // GET: List leads or get single lead
    // ============================================
    case 'GET':
        $user = requireAuth();
        $db = getDB();

        // Stats endpoint
        if ($action === 'stats' || $action === 'stats_v2') {
            $stats = [];
            
            // Total leads
            $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            
            // By status
            $stmt = $db->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
            $stats['by_status'] = [];
            while ($row = $stmt->fetch()) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }
            
            // Today's leads
            $stats['today'] = (int)$db->query("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            
            // This week
            $stats['this_week'] = (int)$db->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            
            // This month
            $stats['this_month'] = (int)$db->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // Last 7 days trend
            $stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
            $stats['daily_trend'] = $stmt->fetchAll();
            
            jsonSuccess(['stats' => $stats]);
        }

        // Single lead
        if ($id) {
            $stmt = $db->prepare("SELECT l.*, u.full_name as assigned_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.id = ?");
            $stmt->execute([$id]);
            $lead = $stmt->fetch();
            
            if (!$lead) jsonError('Lead no encontrado.', 404);
            
            // Get activity log (Bitácora)
            $logStmt = $db->prepare("SELECT a.*, u.full_name as user_name FROM activity_log a LEFT JOIN users u ON a.user_id = u.id WHERE a.entity_type = 'lead' AND a.entity_id = ? ORDER BY a.created_at DESC");
            $logStmt->execute([$id]);
            $lead['log'] = $logStmt->fetchAll();
            
            jsonSuccess(['lead' => $lead]);
        }

        // List with pagination, search, and filters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $search = sanitize($_GET['search'] ?? '');
        $status = sanitize($_GET['status'] ?? '');
        $sortBy = in_array($_GET['sort'] ?? '', ['name', 'email', 'status', 'created_at']) ? $_GET['sort'] : 'created_at';
        $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.message LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if (!empty($status) && in_array($status, ['new', 'contacted', 'qualified', 'converted', 'lost'])) {
            $where[] = "l.status = ?";
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM leads l {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch leads
        $sql = "SELECT l.*, u.full_name as assigned_name 
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                {$whereClause} 
                ORDER BY l.{$sortBy} {$sortDir} 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $leads = $stmt->fetchAll();

        jsonSuccess([
            'leads' => $leads,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        break;

    // ============================================
    // POST: Create new lead (PUBLIC - from contact form)
    // ============================================
    case 'POST':
        // Rate limit for public form submissions
        if (!rateLimit('lead_submit', 3, 300)) {
            jsonError('Ha enviado demasiados formularios. Intente más tarde.', 429);
        }

        $data = getJSONBody();
        
        // Validate required fields
        $name = sanitize($data['name'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $phone = sanitize($data['phone'] ?? '');
        $message = sanitize($data['message'] ?? '');
        $source = sanitize($data['source'] ?? 'website');

        // Anti-spam: honeypot field check
        if (!empty($data['website_url'])) {
            // Bots fill the hidden honeypot field
            jsonSuccess(['message' => 'Formulario enviado exitosamente.']); // Fake success
        }

        // Anti-spam: time check (form filled too quickly)
        $formTime = (int)($data['_form_time'] ?? 0);
        if ($formTime > 0 && (time() - $formTime) < 3) {
            jsonSuccess(['message' => 'Formulario enviado exitosamente.']); // Fake success
        }

        if (empty($name) || strlen($name) < 2) {
            jsonError('El nombre es requerido (mínimo 2 caracteres).');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Email inválido.');
        }

        if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone)) {
            jsonError('Formato de teléfono inválido.');
        }

        if (strlen($message) > 2000) {
            jsonError('El mensaje no puede exceder los 2000 caracteres.');
        }

        // Check for duplicate submission (same email in last 5 minutes)
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute([$email]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonError('Ya se recibió un formulario con este email. Intente más tarde.');
        }

        // Insert lead
        $stmt = $db->prepare("INSERT INTO leads (name, email, phone, message, source, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $email,
            $phone ?: null,
            $message ?: null,
            $source,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);

        $leadId = (int)$db->lastInsertId();
        logActivity('create', 'lead', $leadId, ['source' => $source]);

        jsonSuccess(['message' => '¡Gracias! Tu mensaje fue enviado exitosamente. Te contactaremos pronto.'], 201);
        break;

    // ============================================
    // PUT: Update lead (admin only)
    // ============================================
    case 'PUT':
        $user = requireAuth();
        requireCSRF();
        
        if (!$id) jsonError('ID de lead requerido.');

        $db = getDB();
        
        // Check lead exists
        $stmt = $db->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch();
        if (!$lead) jsonError('Lead no encontrado.', 404);

        $data = getJSONBody();
        
        // Build update fields dynamically
        $allowedFields = ['status', 'notes', 'assigned_to', 'name', 'email', 'phone'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "`{$field}` = ?";
                $value = $data[$field];
                
                // Validate specific fields
                if ($field === 'status' && !in_array($value, ['new', 'contacted', 'qualified', 'converted', 'lost'])) {
                    jsonError('Estado inválido.');
                }
                if ($field === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    jsonError('Email inválido.');
                }
                
                $params[] = is_string($value) ? sanitize($value) : $value;
            }
        }

        if (empty($updates)) jsonError('No hay campos para actualizar.');

        $params[] = $id;
        $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ?";
        $db->prepare($sql)->execute($params);

        logActivity('update', 'lead', $id, ['fields' => array_keys(array_intersect_key($data, array_flip($allowedFields)))]);

        // Return updated lead
        $stmt = $db->prepare("SELECT l.*, u.full_name as assigned_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.id = ?");
        $stmt->execute([$id]);
        
        jsonSuccess(['lead' => $stmt->fetch(), 'message' => 'Lead actualizado exitosamente.']);
        break;

    // ============================================
    // DELETE: Delete lead (admin only)
    // ============================================
    case 'DELETE':
        $user = requireAuth();
        requireCSRF();
        
        if (!$id) jsonError('ID de lead requerido.');

        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) jsonError('Lead no encontrado.', 404);

        $db->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
        
        logActivity('delete', 'lead', $id);

        jsonSuccess(['message' => 'Lead eliminado exitosamente.']);
        break;

    default:
        jsonError('Método no permitido.', 405);
}
