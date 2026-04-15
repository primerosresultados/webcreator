<?php
/**
 * Thank You Page - Loads config from settings API
 */
$v = time();

// Load thank you config from DB
$thankYouConfig = [];
$siteInfo = [];
try {
    $cfgPath = __DIR__ . '/config/database.php';
    if (file_exists($cfgPath)) {
        require_once $cfgPath;
        $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('thank_you_config','site_info')");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            if ($row['setting_key'] === 'thank_you_config') {
                $thankYouConfig = json_decode($row['setting_value'], true) ?: [];
            } elseif ($row['setting_key'] === 'site_info') {
                $siteInfo = json_decode($row['setting_value'], true) ?: [];
            }
        }
    }
} catch (Exception $e) {}

$title = !empty($thankYouConfig['title']) ? htmlspecialchars($thankYouConfig['title']) : '¡Gracias por contactarnos!';
$message = !empty($thankYouConfig['message']) ? htmlspecialchars($thankYouConfig['message']) : 'Hemos recibido tu mensaje y nos pondremos en contacto contigo a la brevedad.';
$youtubeUrl = !empty($thankYouConfig['youtubeUrl']) ? $thankYouConfig['youtubeUrl'] : '';
$ctaText = !empty($thankYouConfig['ctaText']) ? htmlspecialchars($thankYouConfig['ctaText']) : 'Volver al inicio';
$ctaUrl = !empty($thankYouConfig['ctaUrl']) ? htmlspecialchars($thankYouConfig['ctaUrl']) : '/';
$showSocial = !empty($thankYouConfig['showSocial']);

$youtubeEmbed = '';
if ($youtubeUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
    $youtubeEmbed = $matches[1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$title?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/variables.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/base.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?=$v?>">
    <link rel="stylesheet" href="/api/theme.css.php">
    <style>
        .thanks-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #0a0a0a 100%);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .thanks-page::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(99, 102, 241, 0.06) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(201, 169, 110, 0.04) 0%, transparent 50%);
            animation: bgShift 15s ease-in-out infinite alternate;
        }
        @keyframes bgShift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-5%, 5%); }
        }
        .thanks-card {
            position: relative;
            max-width: 680px;
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .thanks-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--color-primary, #6366f1), var(--color-secondary, #c9a96e));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: popIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes popIn {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
        .thanks-icon svg {
            width: 36px;
            height: 36px;
            color: #fff;
        }
        .thanks-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.75rem;
            font-family: var(--font-headings, 'Montserrat'), sans-serif;
        }
        .thanks-message {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .thanks-video {
            margin: 2rem 0;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            padding-bottom: 56.25%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .thanks-video iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .thanks-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--radius-lg, 12px);
            text-decoration: none;
            transition: all 0.3s;
        }
        .thanks-social {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .thanks-social a {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.5);
            transition: all 0.3s;
            text-decoration: none;
        }
        .thanks-social a:hover {
            background: rgba(255,255,255,0.12);
            color: #fff;
            transform: translateY(-2px);
        }
        .thanks-social a svg {
            width: 20px;
            height: 20px;
        }
        @media (max-width: 480px) {
            .thanks-card { padding: 2rem 1.5rem; }
            .thanks-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="thanks-page">
        <div class="thanks-card">
            <div class="thanks-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>

            <h1 class="thanks-title"><?=$title?></h1>
            <p class="thanks-message"><?=$message?></p>

            <?php if ($youtubeEmbed): ?>
            <div class="thanks-video">
                <iframe src="https://www.youtube.com/embed/<?=$youtubeEmbed?>?rel=0" allowfullscreen loading="lazy"></iframe>
            </div>
            <?php endif; ?>

            <a href="<?=$ctaUrl?>" class="thanks-cta btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <?=$ctaText?>
            </a>

            <?php if ($showSocial && !empty($siteInfo)): ?>
            <div class="thanks-social">
                <?php if (!empty($siteInfo['instagram'])): ?>
                <a href="<?=htmlspecialchars($siteInfo['instagram'])?>" target="_blank" rel="noopener" title="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($siteInfo['facebook'])): ?>
                <a href="<?=htmlspecialchars($siteInfo['facebook'])?>" target="_blank" rel="noopener" title="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($siteInfo['youtube'])): ?>
                <a href="<?=htmlspecialchars($siteInfo['youtube'])?>" target="_blank" rel="noopener" title="YouTube">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.5a3.07 3.07 0 0 0-2.15-2.17C19.58 4 12 4 12 4s-7.58 0-9.35.33A3.07 3.07 0 0 0 .5 6.5 32.1 32.1 0 0 0 0 12a32.1 32.1 0 0 0 .5 5.5 3.07 3.07 0 0 0 2.15 2.17C4.42 20 12 20 12 20s7.58 0 9.35-.33a3.07 3.07 0 0 0 2.15-2.17A32.1 32.1 0 0 0 24 12a32.1 32.1 0 0 0-.5-5.5zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($siteInfo['linkedin'])): ?>
                <a href="<?=htmlspecialchars($siteInfo['linkedin'])?>" target="_blank" rel="noopener" title="LinkedIn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
