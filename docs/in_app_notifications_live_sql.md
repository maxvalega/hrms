# In-App Notifications — Live SQL (Spectal)

Run on database `u658412463_hrm_soft`:

```sql
CREATE TABLE IF NOT EXISTS `in_app_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `module` varchar(64) NOT NULL,
  `action` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text,
  `link` varchar(255) DEFAULT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `in_app_notifications_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `in_app_notifications_created_by_index` (`created_by`),
  KEY `in_app_notifications_module_action_index` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Also ensure earlier document/sync columns exist if not already applied:

```sql
ALTER TABLE ducument_uploads
  ADD COLUMN IF NOT EXISTS uploaded_by BIGINT UNSIGNED NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS assigned_user_id BIGINT UNSIGNED NULL AFTER uploaded_by,
  ADD COLUMN IF NOT EXISTS assigned_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER assigned_user_id;

ALTER TABLE gr_sync_ups
  ADD COLUMN IF NOT EXISTS employee_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
```

Note: MySQL versions before 8.0.12 may not support `ADD COLUMN IF NOT EXISTS` — check columns first or ignore duplicate-column errors.
