-- ============================================
-- Migration 002 — Portfolio plugin + featured projects seed
-- ============================================
-- Runs automatically on first admin login after deploy.
-- Creates portfolio tables, activates the plugin, and seeds
-- the 4 featured projects shown on the homepage.
--
-- Convention: the FIRST item in `tags` (comma-separated) is used
-- as the visible "project status" chip on the home/detail pages
-- (e.g. "Proyecto Ejecutivo", "En Construcción", "Ante Proyecto").
-- ============================================

-- ── 1. Tables (copied from plugins/portfolio/install.sql + migrate_v1_1.sql) ──

CREATE TABLE IF NOT EXISTS `portfolio_projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `category` ENUM('residencial','comercial','institucional','interiorismo','paisajismo','restauracion','industrial','otro') NOT NULL DEFAULT 'residencial',
    `description` TEXT DEFAULT NULL,
    `client_name` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `year` SMALLINT UNSIGNED DEFAULT NULL,
    `project_date` DATE DEFAULT NULL,
    `area_m2` DECIMAL(10,2) DEFAULT NULL,
    `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `video_url` VARCHAR(500) DEFAULT NULL,
    `tags` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_status` (`status`),
    INDEX `idx_portfolio_category` (`category`),
    INDEX `idx_portfolio_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portfolio_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_img_project` (`project_id`),
    CONSTRAINT `fk_portfolio_images_project` FOREIGN KEY (`project_id`)
        REFERENCES `portfolio_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portfolio_videos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `video_url` VARCHAR(500) NOT NULL,
    `video_type` ENUM('youtube','vimeo','upload','other') NOT NULL DEFAULT 'youtube',
    `title` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_vid_project` (`project_id`),
    CONSTRAINT `fk_portfolio_videos_project` FOREIGN KEY (`project_id`)
        REFERENCES `portfolio_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Activate the portfolio plugin ──
-- Reads current active_plugins, merges with 'portfolio', writes back.
-- Idempotent: safe to re-run.

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES ('active_plugins', JSON_ARRAY('portfolio'), 'json')
ON DUPLICATE KEY UPDATE
    `setting_value` = CASE
        WHEN JSON_CONTAINS(`setting_value`, '"portfolio"') THEN `setting_value`
        ELSE JSON_ARRAY_APPEND(`setting_value`, '$', 'portfolio')
    END;

-- ── 3. Seed the 4 featured projects ──
-- Idempotent via INSERT IGNORE on unique slug.

INSERT IGNORE INTO `portfolio_projects`
    (`title`, `slug`, `category`, `description`, `location`, `year`, `area_m2`, `status`, `featured_image`, `tags`, `sort_order`)
VALUES
    (
        'Casa Alma del Bosque',
        'alma-bosque',
        'residencial',
        'Casa Alma del Bosque nace de un encargo íntimo: una residencia que respete la vegetación existente y permita habitar el bosque sin perturbarlo. Cada volumen se posó entre los árboles, esquivando raíces y copas, con una huella baja que prioriza la coexistencia con el entorno.\n\nLa planta libre del primer piso conecta cocina, comedor y living en un solo gesto continuo, orientado a la vista noroeste para capturar la luz de tarde. Grandes ventanales de piso a techo desdibujan el límite entre el interior y la terraza, que se extiende como una prolongación del living sobre el bosque.\n\nEl segundo nivel alberga los dormitorios privados, con el master suite volcado hacia el cielo nativo y un pequeño mirador de lectura que corona la escalera. El revestimiento exterior combina madera de coigüe cepillada y piedra local, buscando que la casa envejezca con el paisaje.',
        'Pucón, Chile',
        2024,
        185.00,
        'published',
        '/assets/img/proyecto-alma-bosque.png',
        'Proyecto Ejecutivo, Madera nativa, Piedra local, Hormigón visto, 3 dormitorios',
        10
    ),
    (
        'Casa Stark',
        'stark',
        'residencial',
        'Casa Stark se proyecta sobre una ladera con orientación norte, aprovechando el desnivel natural para escalonar sus dos niveles. El resultado es una vivienda que se lee como dos volúmenes macizos en diálogo: uno público, abierto al paisaje, y otro privado, recogido hacia el bosque interior del terreno.\n\nEl partido arquitectónico apuesta por una geometría nítida, con cubiertas planas y fachadas limpias que contrastan con la textura del entorno. Las perforaciones del volumen se estudiaron milimétricamente para enmarcar vistas específicas: el volcán Villarrica al norte, un grupo de arrayanes al poniente, y el patio de acceso con maitenes al oriente.\n\nActualmente la obra se encuentra en fase de terminaciones, con cierres de fachada, revestimientos interiores de madera nativa y la instalación de ventanas de perfil mínimo. La entrega está proyectada para fines del año en curso.',
        'Pucón, Chile',
        2025,
        207.00,
        'published',
        '/assets/img/proyecto-stark.png',
        'En Construcción, Hormigón, Madera nativa, Acero, 4 dormitorios',
        20
    ),
    (
        'Casa El Mirador',
        'mirador',
        'residencial',
        'Casa El Mirador se sitúa en un terreno elevado con vista privilegiada al lago Villarrica y al volcán. El partido parte de una pregunta simple: cómo hacer que la vista no sea un adorno, sino el eje estructural de toda la casa.\n\nLa respuesta es una vivienda alargada, casi como una terraza habitable, donde cada espacio principal —living, comedor, cocina y el dormitorio principal— cuenta con fachada continua hacia el paisaje. Un alero profundo protege del sol estival y del agua de lluvia, y permite que la fachada vidriada permanezca abierta durante la mayor parte del año.\n\nLos espacios de servicio se ubican en la cara posterior, liberando por completo la fachada norte. El interior utiliza una paleta sobria —maderas claras, muros blancos, pisos oscuros— para no competir con el protagonismo del exterior. La casa es, finalmente, un marco silencioso para el paisaje.',
        'Pucón, Chile',
        2024,
        188.00,
        'published',
        '/assets/img/proyecto-mirador.png',
        'Proyecto Ejecutivo, Madera clara, Ventanales piso a techo, Cubierta metálica, 3 dormitorios',
        30
    ),
    (
        'Casa Dawulco',
        'dawulco',
        'residencial',
        'Casa Dawulco está pensada como una reinterpretación contemporánea de la casa patagónica: techo a dos aguas, tablazón vertical de madera y un volumen compacto que dialoga con las ruca ancestrales y las construcciones rurales de la zona. La propuesta busca que la casa parezca siempre haber estado ahí.\n\nEn planta, los espacios se organizan en torno a una estufa a leña central, verdadero corazón térmico y simbólico de la vivienda. El programa se divide en dos alas: una pública, de cocina-comedor-living con doble altura, y una privada con dormitorios compactos y un altillo para invitados.\n\nActualmente en fase de anteproyecto, el diseño contempla una envolvente térmica de alto desempeño —propia del clima lluvioso del sur— con aislamiento continuo y ventanas termopanel de madera nativa. La casa apunta a un consumo energético bajo incluso en los inviernos más fríos.',
        'Pucón, Chile',
        2025,
        180.00,
        'published',
        '/assets/img/proyecto-dawulco.png',
        'Ante Proyecto, Madera nativa, Piedra, Tejuela de alerce, 3 dormitorios + altillo',
        40
    );
