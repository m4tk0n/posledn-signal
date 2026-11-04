-- POSLEDNÍ SIGNÁL – initial schema
-- All table names must use the {DB_PREFIX} placeholder.
-- Keep this file compatible with MySQL 8.0.

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}schema_migrations` (
    `version` VARCHAR(64) NOT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}players` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `display_name` VARCHAR(40) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}game_saves` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id` BIGINT UNSIGNED NOT NULL,
    `save_name` VARCHAR(80) NOT NULL DEFAULT 'Nová hra',
    `chapter` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `game_state` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_game_saves_player_updated` (`player_id`, `updated_at`),
    CONSTRAINT `fk_{DB_PREFIX}game_saves_player` FOREIGN KEY (`player_id`) REFERENCES `{DB_PREFIX}players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}game_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `save_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `event_data` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_game_events_save_created` (`save_id`, `created_at`),
    CONSTRAINT `fk_{DB_PREFIX}game_events_save` FOREIGN KEY (`save_id`) REFERENCES `{DB_PREFIX}game_saves` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
