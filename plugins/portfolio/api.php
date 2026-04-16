<?php
/**
 * ============================================
 * PLUGIN: Portfolio — API
 * ============================================
 * CRUD for portfolio projects and images.
 * This file is included via /api/plugins.php?action=api&plugin=portfolio
 * so init.php is already loaded and user is authenticated for write ops.
 *
 * Endpoints:
 *   GET  ?p_action=list           — List projects (admin, with drafts)
 *   GET  ?p_action=get&id=X       — Get single project with images
 *   GET  ?p_action=public_list    — List published projects (public)
 *   GET  ?p_action=public_get&slug=X — Get published project by slug (public)
 *   GET  ?p_action=public_featured — Featured projects for homepage component
 *   POST ?p_action=create         — Create project
 *   POST ?p_action=update&id=X    — Update project
 *   POST ?p_action=delete&id=X    — Delete project
 *   POST ?p_action=upload_image   — Upload image to project
 *   POST ?p_action=delete_image&id=X — Delete image
 *   POST ?p_action=reorder_images — Reorder images
 *   GET  ?p_action=categories     — List categories
 */

$pAction = $_GET['p_action'] ?? 'list';

switch ($pAction) {

    // ============================================
    // LIST PROJECTS (Admin — includes drafts)
    // ============================================
    case 'list':
        requireAuth();
        $db = getDB();
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $where = [];
        $params = [];

        if ($category) {
            $where[] = 'p.category = ?';
            $params[] = $category;
        }
        if ($status) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($search) {
            $where[] = '(p.title LIKE ? OR p.client_name LIKE ? OR p.location LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM portfolio_projects p {$whereSQL}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch
        $stmt = $db->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM portfolio_images pi WHERE pi.project_id = p.id) as image_count
            FROM portfolio_projects p 
            {$whereSQL} 
            ORDER BY p.sort_order ASC, p.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $projects = $stmt->fetchAll();

        jsonSuccess([
            'projects' => $projects,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        break;

    // ============================================
    // GET SINGLE PROJECT (Admin)
    // ============================================
    case 'get':
        requireAuth();
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonError('ID requerido.', 400);

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM portfolio_projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) jsonError('Proyecto no encontrado.', 404);

        // Get images
        $imgStmt = $db->prepare("SELECT * FROM portfolio_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute([$id]);
        $project['images'] = $imgStmt->fetchAll();

        jsonSuccess(['project' => $project]);
        break;

    // ============================================
    // PUBLIC LIST (only published)
    // ============================================
    case 'public_list':
        $db = getDB();
        $category = $_GET['category'] ?? '';

        $where = "WHERE p.status = 'published'";
        $params = [];

        if ($category) {
            $where .= ' AND p.category = ?';
            $params[] = $category;
        }

        $stmt = $db->prepare("
            SELECT p.id, p.title, p.slug, p.category, p.description, p.client_name, 
                   p.location, p.year, p.area_m2, p.featured_image, p.tags
            FROM portfolio_projects p 
            {$where} 
            ORDER BY p.sort_order ASC, p.created_at DESC
        ");
        $stmt->execute($params);
        $projects = $stmt->fetchAll();

        jsonSuccess(['projects' => $projects]);
        break;

    // ============================================
    // PUBLIC GET by slug
    // ============================================
    case 'public_get':
        $slug = $_GET['slug'] ?? '';
        if (empty($slug)) jsonError('Slug requerido.', 400);

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM portfolio_projects WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $project = $stmt->fetch();
        if (!$project) jsonError('Proyecto no encontrado.', 404);

        // Get images
        $imgStmt = $db->prepare("SELECT * FROM portfolio_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute([$project['id']]);
        $project['images'] = $imgStmt->fetchAll();

        // Get prev/next for navigation
        $prevStmt = $db->prepare("SELECT slug, title FROM portfolio_projects WHERE status = 'published' AND (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1");
        $prevStmt->execute([$project['sort_order'], $project['sort_order'], $project['id']]);
        $project['prev'] = $prevStmt->fetch() ?: null;

        $nextStmt = $db->prepare("SELECT slug, title FROM portfolio_projects WHERE status = 'published' AND (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1");
        $nextStmt->execute([$project['sort_order'], $project['sort_order'], $project['id']]);
        $project['next'] = $nextStmt->fetch() ?: null;

        jsonSuccess(['project' => $project]);
        break;

    // ============================================
    // PUBLIC FEATURED (for homepage component)
    // ============================================
    case 'public_featured':
        $db = getDB();
        $limit = min(12, max(1, intval($_GET['limit'] ?? 6)));

        $stmt = $db->prepare("
            SELECT p.id, p.title, p.slug, p.category, p.featured_image, p.location, p.year
            FROM portfolio_projects p 
            WHERE p.status = 'published'
            ORDER BY p.sort_order ASC, p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $projects = $stmt->fetchAll();

        jsonSuccess(['projects' => $projects]);
        break;

    // ============================================
    // CREATE PROJECT
    // ============================================
    case 'create':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido.', 405);

        $body = getJSONBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) jsonError('El título es obligatorio.', 400);

        $slug = createSlug($title);
        
        $db = getDB();

        // Ensure unique slug
        $slugBase = $slug;
        $counter = 1;
        while (true) {
            $check = $db->prepare("SELECT id FROM portfolio_projects WHERE slug = ?");
            $check->execute([$slug]);
            if (!$check->fetch()) break;
            $slug = $slugBase . '-' . $counter++;
        }

        $stmt = $db->prepare("
            INSERT INTO portfolio_projects (title, slug, category, description, client_name, location, year, area_m2, status, featured_image, tags, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $slug,
            $body['category'] ?? 'residencial',
            $body['description'] ?? null,
            $body['client_name'] ?? null,
            $body['location'] ?? null,
            !empty($body['year']) ? intval($body['year']) : null,
            !empty($body['area_m2']) ? floatval($body['area_m2']) : null,
            $body['status'] ?? 'draft',
            $body['featured_image'] ?? null,
            $body['tags'] ?? null,
            intval($body['sort_order'] ?? 0)
        ]);

        $projectId = $db->lastInsertId();
        logActivity('portfolio_create', 'portfolio_project', $projectId, ['title' => $title]);

        jsonSuccess(['message' => 'Proyecto creado.', 'id' => intval($projectId), 'slug' => $slug], 201);
        break;

    // ============================================
    // UPDATE PROJECT
    // ============================================
    case 'update':
        requireAuth();
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonError('ID requerido.', 400);

        $body = getJSONBody();
        $db = getDB();

        // Check exists
        $check = $db->prepare("SELECT id FROM portfolio_projects WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) jsonError('Proyecto no encontrado.', 404);

        $fields = [];
        $params = [];

        $updatable = ['title', 'category', 'description', 'client_name', 'location', 'year', 'area_m2', 'status', 'featured_image', 'tags', 'sort_order'];
        foreach ($updatable as $field) {
            if (array_key_exists($field, $body)) {
                $fields[] = "`{$field}` = ?";
                $val = $body[$field];
                if ($field === 'year') $val = !empty($val) ? intval($val) : null;
                if ($field === 'area_m2') $val = !empty($val) ? floatval($val) : null;
                if ($field === 'sort_order') $val = intval($val);
                $params[] = $val;
            }
        }

        // Handle slug update if title changed
        if (isset($body['title']) && !empty($body['title'])) {
            $newSlug = createSlug($body['title']);
            $slugBase = $newSlug;
            $counter = 1;
            while (true) {
                $slugCheck = $db->prepare("SELECT id FROM portfolio_projects WHERE slug = ? AND id != ?");
                $slugCheck->execute([$newSlug, $id]);
                if (!$slugCheck->fetch()) break;
                $newSlug = $slugBase . '-' . $counter++;
            }
            $fields[] = '`slug` = ?';
            $params[] = $newSlug;
        }

        if (empty($fields)) jsonError('Nada que actualizar.', 400);

        $params[] = $id;
        $db->prepare("UPDATE portfolio_projects SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        logActivity('portfolio_update', 'portfolio_project', $id);
        jsonSuccess(['message' => 'Proyecto actualizado.']);
        break;

    // ============================================
    // DELETE PROJECT
    // ============================================
    case 'delete':
        requireAuth();
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jsonError('ID requerido.', 400);

        $db = getDB();
        // Images cascade-deleted by FK
        $stmt = $db->prepare("DELETE FROM portfolio_projects WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) jsonError('Proyecto no encontrado.', 404);

        logActivity('portfolio_delete', 'portfolio_project', $id);
        jsonSuccess(['message' => 'Proyecto eliminado.']);
        break;

    // ============================================
    // UPLOAD IMAGE
    // ============================================
    case 'upload_image':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido.', 405);

        $projectId = intval($_POST['project_id'] ?? $_GET['project_id'] ?? 0);
        if (!$projectId) jsonError('project_id requerido.', 400);

        if (empty($_FILES['image'])) jsonError('No se recibió imagen.', 400);

        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) jsonError('Error al subir archivo.', 400);

        // Validate it's an image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) jsonError('Solo se permiten imágenes (JPG, PNG, WebP, GIF).', 400);
        if ($file['size'] > 10 * 1024 * 1024) jsonError('La imagen no puede pesar más de 10MB.', 400);

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'portfolio_' . $projectId . '_' . uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/portfolio/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonError('Error al guardar la imagen.', 500);
        }

        $imageUrl = '/uploads/portfolio/' . $filename;

        // Get next sort order
        $db = getDB();
        $sortStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM portfolio_images WHERE project_id = ?");
        $sortStmt->execute([$projectId]);
        $sortOrder = $sortStmt->fetchColumn();

        $caption = $_POST['caption'] ?? '';
        $stmt = $db->prepare("INSERT INTO portfolio_images (project_id, image_url, caption, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, $imageUrl, $caption, $sortOrder]);
        $imageId = $db->lastInsertId();

        // If this is the first image, set it as featured
        $countStmt = $db->prepare("SELECT COUNT(*) FROM portfolio_images WHERE project_id = ?");
        $countStmt->execute([$projectId]);
        if ($countStmt->fetchColumn() == 1) {
            $db->prepare("UPDATE portfolio_projects SET featured_image = ? WHERE id = ? AND (featured_image IS NULL OR featured_image = '')")
               ->execute([$imageUrl, $projectId]);
        }

        jsonSuccess([
            'message' => 'Imagen subida.',
            'image' => [
                'id' => intval($imageId),
                'project_id' => $projectId,
                'image_url' => $imageUrl,
                'caption' => $caption,
                'sort_order' => intval($sortOrder)
            ]
        ]);
        break;

    // ============================================
    // DELETE IMAGE
    // ============================================
    case 'delete_image':
        requireAuth();
        $imageId = intval($_GET['id'] ?? 0);
        if (!$imageId) jsonError('ID de imagen requerido.', 400);

        $db = getDB();
        
        // Get image info before deleting
        $imgStmt = $db->prepare("SELECT * FROM portfolio_images WHERE id = ?");
        $imgStmt->execute([$imageId]);
        $image = $imgStmt->fetch();
        if (!$image) jsonError('Imagen no encontrada.', 404);

        // Delete from database
        $db->prepare("DELETE FROM portfolio_images WHERE id = ?")->execute([$imageId]);

        // Delete file from disk
        $filePath = __DIR__ . '/..' . $image['image_url'];
        if (file_exists($filePath)) @unlink($filePath);

        // If this was the featured image, clear it
        $db->prepare("UPDATE portfolio_projects SET featured_image = NULL WHERE id = ? AND featured_image = ?")
           ->execute([$image['project_id'], $image['image_url']]);

        jsonSuccess(['message' => 'Imagen eliminada.']);
        break;

    // ============================================
    // REORDER IMAGES
    // ============================================
    case 'reorder_images':
        requireAuth();
        $body = getJSONBody();
        $order = $body['order'] ?? []; // Array of image IDs in new order

        if (empty($order)) jsonError('Orden vacío.', 400);

        $db = getDB();
        $stmt = $db->prepare("UPDATE portfolio_images SET sort_order = ? WHERE id = ?");
        foreach ($order as $index => $imageId) {
            $stmt->execute([$index, intval($imageId)]);
        }

        jsonSuccess(['message' => 'Orden actualizado.']);
        break;

    // ============================================
    // SET FEATURED IMAGE
    // ============================================
    case 'set_featured':
        requireAuth();
        $body = getJSONBody();
        $projectId = intval($body['project_id'] ?? 0);
        $imageUrl = $body['image_url'] ?? '';

        if (!$projectId || empty($imageUrl)) jsonError('project_id e image_url requeridos.', 400);

        $db = getDB();
        $db->prepare("UPDATE portfolio_projects SET featured_image = ? WHERE id = ?")->execute([$imageUrl, $projectId]);

        jsonSuccess(['message' => 'Imagen destacada actualizada.']);
        break;

    // ============================================
    // CATEGORIES LIST
    // ============================================
    case 'categories':
        $categories = [
            ['id' => 'residencial', 'label' => 'Residencial'],
            ['id' => 'comercial', 'label' => 'Comercial'],
            ['id' => 'institucional', 'label' => 'Institucional'],
            ['id' => 'interiorismo', 'label' => 'Interiorismo'],
            ['id' => 'paisajismo', 'label' => 'Paisajismo'],
            ['id' => 'restauracion', 'label' => 'Restauración'],
            ['id' => 'industrial', 'label' => 'Industrial'],
            ['id' => 'otro', 'label' => 'Otro']
        ];
        jsonSuccess(['categories' => $categories]);
        break;

    default:
        jsonError('Acción de plugin no válida.', 400);
}

// ============================================
// HELPER: Create URL-safe slug
// ============================================
function createSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    // Transliterate common spanish characters
    $replacements = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'proyecto';
}
