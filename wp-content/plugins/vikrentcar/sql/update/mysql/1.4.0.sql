ALTER TABLE `#__vikrentcar_wpshortcodes` ADD COLUMN `parent_id` int(10) unsigned DEFAULT 0;

ALTER TABLE `#__vikrentcar_seasons` CHANGE `idcars` `idcars` varchar(1024) DEFAULT NULL;

ALTER TABLE `#__vikrentcar_optionals` ADD COLUMN `maxdays` int(10) NOT NULL DEFAULT 0 AFTER `forceifdays`;

ALTER TABLE `#__vikrentcar_gpayments` CHANGE `params` `params` varchar(1024) DEFAULT NULL;

ALTER TABLE `#__vikrentcar_places` ADD COLUMN `combomap` varchar(1024) DEFAULT NULL AFTER `wopening`;

ALTER TABLE `#__vikrentcar_condtexts` CHANGE `msg` `msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

ALTER TABLE `#__vikrentcar_coupons` ADD COLUMN `maxtotord` decimal(12,2) DEFAULT NULL;

ALTER TABLE `#__vikrentcar_coupons` ADD COLUMN `excludetaxes` tinyint(1) NOT NULL DEFAULT 1;