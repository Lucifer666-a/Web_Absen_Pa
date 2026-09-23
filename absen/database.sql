CREATE DATABASE IF NOT EXISTS `db_absen`;
USE `db_absen`;

CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama_acara` VARCHAR(150) NOT NULL,
  `access_token` VARCHAR(64) NOT NULL,
  `pin_acara` VARCHAR(10) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `presensi` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `tanda_tangan` MEDIUMTEXT NOT NULL,
  `waktu_absen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal Pengaturan
INSERT INTO `pengaturan` (`nama_acara`, `access_token`, `pin_acara`) 
VALUES ('Rapat Tahunan Perusahaan', 'TOKENRAHASIA123', '123456');

-- Data Awal Admin (username: admin, password: admin123)
INSERT INTO `admin` (`username`, `password`, `nama_lengkap`) 
VALUES ('admin', '$2y$10$O9Jb84PS8ct56HafnNo2o.ccL9/FCC2LFkJgQi7oJFjoA/OOfv.DO', 'Administrator Utama');
