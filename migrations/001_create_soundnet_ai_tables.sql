-- SoundNet AI Table Migration
-- Table: oc_product_vector_status

CREATE TABLE IF NOT EXISTS `oc_product_vector_status` (
  `product_id` INT(11) NOT NULL,
  `is_indexed` TINYINT(1) DEFAULT 0,
  `has_audio` TINYINT(1) DEFAULT 0,
  `audio_path` VARCHAR(255) NULL,
  `content_hash` VARCHAR(32) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `idx_is_indexed` (`is_indexed`),
  KEY `idx_has_audio` (`has_audio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

