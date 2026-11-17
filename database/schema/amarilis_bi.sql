-- Amarilis Ambiental: estructura real de la primera implementación.
-- Solo esquema, sin datos ni credenciales. Para una base nueva.
-- La base actual ya tiene migraciones; no necesita ejecutar este archivo.
CREATE DATABASE IF NOT EXISTS `amarilis_bi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `amarilis_bi`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consultor',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `private_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preview',
  `summary` json NOT NULL,
  `payload` json NOT NULL,
  `failure` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_batches_sha256_unique` (`sha256`),
  KEY `import_batches_created_by_foreign` (`created_by`),
  CONSTRAINT `import_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint unsigned NOT NULL,
  `locator` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `consulted_on` date NOT NULL,
  `import_batch_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sources_code_unique` (`code`),
  KEY `sources_import_batch_id_foreign` (`import_batch_id`),
  CONSTRAINT `sources_import_batch_id_foreign` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documentary_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `import_batch_id` bigint unsigned NOT NULL,
  `source_row` int unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `label` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(24,10) DEFAULT NULL,
  `unit` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operator` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `process` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `waste_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `territory` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Amarilis',
  `period` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `temporal_precision` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `locator` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `restrictions` json NOT NULL,
  `qualifier` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documentary_records_code_unique` (`code`),
  KEY `documentary_records_source_id_foreign` (`source_id`),
  KEY `documentary_records_import_batch_id_foreign` (`import_batch_id`),
  KEY `documentary_records_year_index` (`year`),
  KEY `documentary_records_process_index` (`process`),
  CONSTRAINT `documentary_records_import_batch_id_foreign` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `documentary_records_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documentary_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_id` bigint unsigned NOT NULL,
  `import_batch_id` bigint unsigned NOT NULL,
  `source_row` int unsigned NOT NULL,
  `number` tinyint unsigned NOT NULL,
  `place` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `participants` int unsigned NOT NULL,
  `day` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `schedule` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `waste_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documentary_routes_source_id_number_unique` (`source_id`,`number`),
  KEY `documentary_routes_import_batch_id_foreign` (`import_batch_id`),
  CONSTRAINT `documentary_routes_import_batch_id_foreign` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `documentary_routes_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quality_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `finding` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `treatment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_evidence` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_codes` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quality_issues_code_unique` (`code`),
  KEY `quality_issues_source_id_foreign` (`source_id`),
  CONSTRAINT `quality_issues_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `evidence_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `private_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bytes` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evidence_files_sha256_unique` (`sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` json DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_events_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `operational_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operational_zones_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_statuses` (
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` tinyint unsigned NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_status_transitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_status_transitions_from_status_to_status_unique` (`from_status`,`to_status`),
  KEY `incident_status_transitions_to_status_foreign` (`to_status`),
  CONSTRAINT `incident_status_transitions_from_status_foreign` FOREIGN KEY (`from_status`) REFERENCES `incident_statuses` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `incident_status_transitions_to_status_foreign` FOREIGN KEY (`to_status`) REFERENCES `incident_statuses` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `operational_settings` (
  `key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `critical_sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `verification_criterion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `verified_by` bigint unsigned NOT NULL,
  `verified_at` timestamp NOT NULL,
  `lock_version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `critical_sites_code_unique` (`code`),
  KEY `critical_sites_zone_id_foreign` (`zone_id`),
  KEY `critical_sites_verified_by_foreign` (`verified_by`),
  KEY `critical_sites_status_index` (`status`),
  CONSTRAINT `critical_sites_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `critical_sites_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `operational_zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_type_id` bigint unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `detected_at` datetime NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_source` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_verified_at` timestamp NULL DEFAULT NULL,
  `location_verified_by` bigint unsigned DEFAULT NULL,
  `priority` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_reason` text COLLATE utf8mb4_unicode_ci,
  `reported_by_label` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `status_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reportada',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `attended_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `attention_summary` text COLLATE utf8mb4_unicode_ci,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `duplicate_of_id` bigint unsigned DEFAULT NULL,
  `critical_site_id` bigint unsigned DEFAULT NULL,
  `lock_version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incidents_code_unique` (`code`),
  KEY `incidents_incident_type_id_foreign` (`incident_type_id`),
  KEY `incidents_location_verified_by_foreign` (`location_verified_by`),
  KEY `incidents_reported_by_foreign` (`reported_by`),
  KEY `incidents_assigned_to_foreign` (`assigned_to`),
  KEY `incidents_verified_by_foreign` (`verified_by`),
  KEY `incidents_duplicate_of_id_foreign` (`duplicate_of_id`),
  KEY `incidents_critical_site_id_foreign` (`critical_site_id`),
  KEY `incidents_status_code_detected_at_index` (`status_code`,`detected_at`),
  KEY `incidents_zone_id_detected_at_index` (`zone_id`,`detected_at`),
  KEY `incidents_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `incidents_detected_at_index` (`detected_at`),
  KEY `incidents_priority_index` (`priority`),
  KEY `incidents_status_code_index` (`status_code`),
  CONSTRAINT `incidents_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_critical_site_id_foreign` FOREIGN KEY (`critical_site_id`) REFERENCES `critical_sites` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_duplicate_of_id_foreign` FOREIGN KEY (`duplicate_of_id`) REFERENCES `incidents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_incident_type_id_foreign` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_location_verified_by_foreign` FOREIGN KEY (`location_verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_status_code_foreign` FOREIGN KEY (`status_code`) REFERENCES `incident_statuses` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incidents_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `operational_zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_evidence` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned NOT NULL,
  `phase` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `private_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_evidence_incident_id_foreign` (`incident_id`),
  KEY `incident_evidence_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `incident_evidence_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incident_evidence_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned NOT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changes` json DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_history_incident_id_foreign` (`incident_id`),
  KEY `incident_history_user_id_foreign` (`user_id`),
  CONSTRAINT `incident_history_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `incident_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `critical_site_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `critical_site_id` bigint unsigned NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `critical_site_history_critical_site_id_foreign` (`critical_site_id`),
  KEY `critical_site_history_user_id_foreign` (`user_id`),
  CONSTRAINT `critical_site_history_critical_site_id_foreign` FOREIGN KEY (`critical_site_id`) REFERENCES `critical_sites` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `critical_site_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

