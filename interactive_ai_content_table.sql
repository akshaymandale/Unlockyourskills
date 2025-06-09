-- Interactive & AI Powered Content Package Table
CREATE TABLE IF NOT EXISTS `interactive_ai_content_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content_type` enum('adaptive_learning', 'ai_tutoring', 'ar_vr') NOT NULL,
  `description` text,
  `tags` text,
  `version` varchar(50) NOT NULL,
  `language` varchar(100) DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes',
  `mobile_support` enum('Yes', 'No') DEFAULT 'No',
  
  -- Content-specific fields
  `content_url` varchar(500) DEFAULT NULL COMMENT 'URL for external interactive content',
  `embed_code` text DEFAULT NULL COMMENT 'HTML embed code for interactive content',
  `ai_model` varchar(100) DEFAULT NULL COMMENT 'AI model used (GPT-4, Claude, etc.)',
  `interaction_type` varchar(100) DEFAULT NULL COMMENT 'Type of interaction (chat, simulation, etc.)',
  `difficulty_level` enum('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
  `learning_objectives` text DEFAULT NULL COMMENT 'Learning objectives for the content',
  `prerequisites` text DEFAULT NULL COMMENT 'Prerequisites for the content',
  
  -- File uploads
  `content_file` varchar(255) DEFAULT NULL COMMENT 'Uploaded content file (HTML5, Unity, etc.)',
  `thumbnail_image` varchar(255) DEFAULT NULL COMMENT 'Thumbnail image for the content',
  `metadata_file` varchar(255) DEFAULT NULL COMMENT 'Metadata or configuration file',
  
  -- AR/VR specific fields
  `vr_platform` varchar(100) DEFAULT NULL COMMENT 'VR platform (Oculus, HTC Vive, etc.)',
  `ar_platform` varchar(100) DEFAULT NULL COMMENT 'AR platform (ARCore, ARKit, etc.)',
  `device_requirements` text DEFAULT NULL COMMENT 'Device requirements for AR/VR',
  
  -- AI Tutoring specific fields
  `tutor_personality` varchar(100) DEFAULT NULL COMMENT 'AI tutor personality type',
  `response_style` varchar(100) DEFAULT NULL COMMENT 'AI response style (formal, casual, etc.)',
  `knowledge_domain` varchar(200) DEFAULT NULL COMMENT 'Domain of knowledge for AI tutor',
  
  -- Adaptive Learning specific fields
  `adaptation_algorithm` varchar(100) DEFAULT NULL COMMENT 'Algorithm used for adaptation',
  `assessment_integration` enum('Yes', 'No') DEFAULT 'No' COMMENT 'Whether it integrates with assessments',
  `progress_tracking` enum('Yes', 'No') DEFAULT 'Yes' COMMENT 'Whether it tracks learner progress',
  
  -- Common fields
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  
  PRIMARY KEY (`id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Interactive and AI-powered content packages';
