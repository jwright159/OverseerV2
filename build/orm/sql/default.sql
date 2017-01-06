
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `modlevel` INTEGER NOT NULL,
    `confirmed` TINYINT(1) NOT NULL,
    `confirmation_key` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- characters
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `characters`;

CREATE TABLE `characters`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `species` INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `chumhandle` VARCHAR(255) NOT NULL,
    `owner_id` INTEGER NOT NULL,
    `session_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `characters_fi_973e78` (`owner_id`),
    INDEX `characters_fi_a06fc0` (`session_id`),
    CONSTRAINT `characters_fk_973e78`
        FOREIGN KEY (`owner_id`)
        REFERENCES `users` (`id`),
    CONSTRAINT `characters_fk_a06fc0`
        FOREIGN KEY (`session_id`)
        REFERENCES `sessions` (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sessions
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `owner_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `sessions_fi_973e78` (`owner_id`),
    CONSTRAINT `sessions_fk_973e78`
        FOREIGN KEY (`owner_id`)
        REFERENCES `users` (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- system_parameters
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `system_parameters`;

CREATE TABLE `system_parameters`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `maintenance_level` INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
