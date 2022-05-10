CREATE TABLE `files`
(
    `id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `path` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX (`path`)
) ENGINE = InnoDB;


CREATE TABLE `backups`
(
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('full','inc') NOT NULL,
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX (`date`)
) ENGINE = InnoDB;

CREATE TABLE `backup_files`
(
    `id`               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `file_id`          BIGINT(20) UNSIGNED NOT NULL,
    `hash`             VARCHAR(32)  NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `backup_files`
    ADD FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `archives`
(
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `backup_id`        INT UNSIGNED NOT NULL,
    `archive_rel_path` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `backup_files` ADD `archive_id` BIGINT(20) UNSIGNED NOT NULL AFTER `file_id`;
ALTER TABLE `backup_files` ADD FOREIGN KEY (`archive_id`) REFERENCES `archives`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `backups` ADD `finish_date` DATETIME NULL AFTER `date`;
ALTER TABLE `backups` ADD INDEX(`finish_date`);
