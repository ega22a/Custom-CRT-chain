CREATE TABLE `auth`(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` TEXT NOT NULL,
    `user_id` INT NOT NULL,
    `tokens` JSON NULL COMMENT '[
  {
    \"token\": \"\",
    \"ip\": \"\",
    \"expire_at\": \"\",
    \"sign\": \"\"
  }
]'
);
ALTER TABLE
    `auth` ADD UNIQUE `auth_email_unique`(`email`);
ALTER TABLE
    `auth` ADD INDEX `auth_user_id_index`(`user_id`);
CREATE TABLE `users`(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `fullname` JSON NOT NULL COMMENT '{
  \"lastname\": \"\",
  \"firstname\": \"\",
  \"patronymic\": \"\"
}',
    `location` JSON NOT NULL COMMENT '{
  \"countryName\": \"\",
  \"stateOrProvinceName\": \"\",
  \"localityName\": \"\"
}',
    `organization` JSON NOT NULL COMMENT '{
  \"organizationName\": \"\",
  \"organizationalUnitName\": \"\"
}',
    `role` INT NOT NULL COMMENT '1 - Администратор системы;
2 - Администратор зоны (назначаемый);
3 - Обычный пользователь с подписями.'
);
CREATE TABLE `signatures`(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `dn` JSON NOT NULL,
    `path` TEXT NOT NULL,
    `create_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `creater_id` INT NOT NULL,
    `owner_id` INT NOT NULL,
    `zone_id` INT NULL,
    `passphrase` TEXT NOT NULL,
    `valid_to` VARCHAR(255) NOT NULL
);
ALTER TABLE
    `signatures` ADD INDEX `signatures_creater_id_index`(`creater_id`);
ALTER TABLE
    `signatures` ADD INDEX `signatures_owner_id_index`(`owner_id`);
ALTER TABLE
    `signatures` ADD INDEX `signatures_zone_id_index`(`zone_id`);
CREATE TABLE `log_of_signs`(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sign_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `signer_id` INT NOT NULL,
    `sign_id` INT NOT NULL,
    `filename` TEXT NOT NULL
);
ALTER TABLE
    `log_of_signs` ADD INDEX `log_of_signs_signer_id_index`(`signer_id`);
ALTER TABLE
    `log_of_signs` ADD INDEX `log_of_signs_sign_id_index`(`sign_id`);
CREATE TABLE `zones`(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `dn` JSON NOT NULL,
    `creater_id` INT NOT NULL,
    `admin_id` INT NOT NULL,
    `signature_id` INT NOT NULL
);
ALTER TABLE
    `zones` ADD UNIQUE `zones_name_unique`(`name`);
ALTER TABLE
    `zones` ADD INDEX `zones_creater_id_index`(`creater_id`);
ALTER TABLE
    `zones` ADD INDEX `zones_admin_id_index`(`admin_id`);
ALTER TABLE
    `zones` ADD INDEX `zones_signature_id_index`(`signature_id`);
ALTER TABLE
    `signatures` ADD CONSTRAINT `signatures_creater_id_foreign` FOREIGN KEY(`creater_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `auth` ADD CONSTRAINT `auth_user_id_foreign` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `signatures` ADD CONSTRAINT `signatures_owner_id_foreign` FOREIGN KEY(`owner_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `log_of_signs` ADD CONSTRAINT `log_of_signs_signer_id_foreign` FOREIGN KEY(`signer_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `zones` ADD CONSTRAINT `zones_creater_id_foreign` FOREIGN KEY(`creater_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `zones` ADD CONSTRAINT `zones_admin_id_foreign` FOREIGN KEY(`admin_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `log_of_signs` ADD CONSTRAINT `log_of_signs_sign_id_foreign` FOREIGN KEY(`sign_id`) REFERENCES `signatures`(`id`);
ALTER TABLE
    `zones` ADD CONSTRAINT `zones_signature_id_foreign` FOREIGN KEY(`signature_id`) REFERENCES `signatures`(`id`);
ALTER TABLE
    `signatures` ADD CONSTRAINT `signatures_zone_id_foreign` FOREIGN KEY(`zone_id`) REFERENCES `zones`(`id`);