<?php
/**
 * ============================================
 * UPLOAD API - Secure Image/File Upload
 * ============================================
 * POST /api/upload.php  - Upload file (admin only)
 * GET  /api/upload.php  - List uploads (admin only)
 * DELETE /api/upload.php?id=X - Delete upload (admin only)
 */

require_once __DIR__ . '/init.php';

$method = getMethod();

// Upload configuration
$uploadDir = __DIR__ . '/../uploads/';
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    'application/pdf'
];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];

switch ($method) {

    // ============================================
    // POST: Upload file
    // ============================================
    case 'POST':
        $user = requireAuth();
        // CSRF via header for multipart forms
        if (!validateCSRFToken()) {
            // Check in POST data for multipart
            if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                jsonError('Token CSRF inválido.', 403);
            }
        }

        if (empty($_FILES['file'])) {
            jsonError('No se recibió ningún archivo.');
        }

        $file = $_FILES['file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo del servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: directorio temporal no encontrado.',
                UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se puede escribir en disco.',
            ];
            jsonError($uploadErrors[$file['error']] ?? 'Error desconocido al subir archivo.', 400);
        }

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            jsonError('El archivo excede el tamaño máximo de 5MB.', 400);
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            jsonError('Tipo de archivo no permitido. Solo se aceptan: JPG, PNG, GIF, WebP, SVG, PDF.', 400);
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            jsonError('Extensión de archivo no permitida.', 400);
        }

        // Generate safe filename
        $uniqueName = date('Y/m'); // Organize by year/month
        $subDir = $uploadDir . $uniqueName;
        if (!is_dir($subDir)) {
            mkdir($subDir, 0755, true);
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $subDir . '/' . $safeName;
        $relativePath = 'uploads/' . $uniqueName . '/' . $safeName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            jsonError('Error al guardar el archivo.', 500);
        }

        // Save to database
        $db = getDB();
        $altText = sanitize($_POST['alt_text'] ?? '');
        
        $stmt = $db->prepare("INSERT INTO media (filename, original_name, mime_type, file_size, path, alt_text, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $safeName,
            $file['name'],
            $mimeType,
            $file['size'],
            $relativePath,
            $altText ?: null,
            $user['id']
        ]);

        $mediaId = (int)$db->lastInsertId();
        logActivity('upload', 'media', $mediaId, ['filename' => $file['name'], 'size' => $file['size']]);

        jsonSuccess([
            'media' => [
                'id' => $mediaId,
                'filename' => $safeName,
                'original_name' => $file['name'],
                'mime_type' => $mimeType,
                'file_size' => $file['size'],
                'path' => $relativePath,
                'url' => (defined('SITE_URL') ? SITE_URL : '') . '/' . $relativePath,
                'alt_text' => $altText
            ],
            'message' => 'Archivo subido exitosamente.'
        ], 201);
        break;

    // ============================================
    // GET: List uploaded files
    // ============================================
    case 'GET':
        $user = requireAuth();
        $db = getDB();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $typeFilter = sanitize($_GET['type'] ?? '');

        $where = '';
        $params = [];
        
        if (!empty($typeFilter)) {
            $where = "WHERE mime_type LIKE ?";
            $params[] = "{$typeFilter}%";
        }

        $total = (int)$db->prepare("SELECT COUNT(*) FROM media {$where}")->execute($params) 
            ? $db->prepare("SELECT COUNT(*) FROM media {$where}") : null;
        
        $countStmt = $db->prepare("SELECT COUNT(*) FROM media {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT m.*, u.full_name as uploaded_by_name FROM media m LEFT JOIN users u ON m.uploaded_by = u.id {$where} ORDER BY m.created_at DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);

        jsonSuccess([
            'media' => $stmt->fetchAll(),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        break;

    // ============================================
    // DELETE: Remove uploaded file
    // ============================================
    case 'DELETE':
        $user = requireAuth();
        requireCSRF();
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) jsonError('ID de archivo requerido.');

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();

        if (!$media) jsonError('Archivo no encontrado.', 404);

        // Delete physical file
        $filePath = __DIR__ . '/../' . $media['path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $db->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
        
        logActivity('delete', 'media', $id, ['filename' => $media['original_name']]);

        jsonSuccess(['message' => 'Archivo eliminado exitosamente.']);
        break;

    default:
        jsonError('Método no permitido.', 405);
}
