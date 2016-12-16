CREATE TABLE `download_content` (
  `download_id` bigint(20) NOT NULL,
  `language` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `seo_url_overwrite` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description_overwrite` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_title_overwrite` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_downloads` int(11) DEFAULT '0',
  `extra_id` int(11) DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_extensions` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` float DEFAULT NULL,
  `download_widget_extra_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'download_images'
CREATE TABLE `download_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sequence` int(11) NOT NULL,
  `created_on` datetime NOT NULL,
  `edited_on` datetime NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci,
  `hidden` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'download_images_content'
CREATE TABLE `download_images_content` (
  `image_id` bigint(20) NOT NULL,
  `language` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'downloads'
CREATE TABLE `downloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hidden` enum('N','Y') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT NULL,
  `edited_on` timestamp NULL DEFAULT NULL,
  `sequence` int(11) DEFAULT NULL,
  `status` enum('active','draft') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_on` timestamp NULL DEFAULT NULL,
  `size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `require_details` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_widget` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'downloads_categories'
CREATE TABLE `downloads_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_on` timestamp NULL DEFAULT NULL,
  `edited_on` timestamp NULL DEFAULT NULL,
  `sequence` int(11) DEFAULT NULL,
  `hidden` enum('N','Y') COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `parent_id` int(11) DEFAULT NULL,
  `path` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'downloads_category_content'
CREATE TABLE `downloads_category_content` (
  `category_id` bigint(20) NOT NULL,
  `language` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `extra_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'downloads_entries'
CREATE TABLE `downloads_entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `download_id` int(11) unsigned NOT NULL,
  `language` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `edited_on` date DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'downloads_linked_catgories'
CREATE TABLE `downloads_linked_catgories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `download_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `download_id` (`download_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
