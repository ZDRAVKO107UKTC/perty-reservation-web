CREATE DATABASE IF NOT EXISTS reservation_software
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE reservation_software;

DROP TABLE IF EXISTS reservation;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS parties;
DROP TABLE IF EXISTS tables;
DROP TABLE IF EXISTS registered_person;

CREATE TABLE registered_person (
  id_person INT AUTO_INCREMENT PRIMARY KEY,
  first_name_person VARCHAR(100) NOT NULL,
  last_name_person VARCHAR(100) NOT NULL,
  email_person VARCHAR(255) NOT NULL UNIQUE,
  pass_person VARCHAR(255) NOT NULL,
  phone_number_person VARCHAR(20) NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tables (
  id_tables INT AUTO_INCREMENT PRIMARY KEY,
  table_number INT NOT NULL UNIQUE,
  seats_number INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parties (
  id_party INT AUTO_INCREMENT PRIMARY KEY,
  name_party VARCHAR(150) NOT NULL,
  event_date DATETIME NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contact_messages (
  id_contact_message INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservation (
  id_reservation INT AUTO_INCREMENT PRIMARY KEY,
  id_person INT NOT NULL,
  id_table INT NOT NULL,
  id_party INT NOT NULL,
  date_for_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservation_person FOREIGN KEY (id_person) REFERENCES registered_person(id_person) ON DELETE CASCADE,
  CONSTRAINT fk_reservation_table FOREIGN KEY (id_table) REFERENCES tables(id_tables) ON DELETE CASCADE,
  CONSTRAINT fk_reservation_party FOREIGN KEY (id_party) REFERENCES parties(id_party) ON DELETE CASCADE,
  CONSTRAINT uniq_party_table UNIQUE (id_party, id_table),
  CONSTRAINT uniq_person_party UNIQUE (id_person, id_party)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO registered_person (
  first_name_person,
  last_name_person,
  email_person,
  pass_person,
  phone_number_person,
  role
) VALUES
('Admin', 'User', 'admin@myclub.local', '$2y$10$gL5jgQ51BZXY/mrjfqR3z.HiBpLe02LCDuHVD78yzP8SlKsTxl0PC', '0888000001', 'admin'),
('Ivan', 'Hristov', 'user@myclub.local', '$2y$10$SWX76Gaph56XaYb/4iuv6eiUrUGK6mbOYfIri.WGLSt.3om5vodJi', '0888000002', 'user');

INSERT INTO tables (table_number, seats_number) VALUES
(1, 8), (2, 8), (3, 8), (4, 8), (5, 8),
(6, 6), (7, 6), (8, 6), (9, 6),
(10, 4), (11, 4), (12, 4);

INSERT INTO parties (name_party, event_date, description) VALUES
('Opening Night', '2026-05-15 22:00:00', 'Grand opening with resident DJs and welcome cocktails.'),
('Retro Fever', '2026-05-22 21:30:00', 'A full night of retro dance hits and themed drinks.'),
('Summer Kickoff', '2026-06-05 22:30:00', 'Season-opening party with special guests and live sets.');

INSERT INTO reservation (id_person, id_table, id_party, date_for_reservation) VALUES
(2, 3, 1, '2026-04-18 13:00:00');

INSERT INTO contact_messages (name, phone, email, subject, message, created_at) VALUES
('Maria Petrova', '0888123456', 'maria@example.com', 'Private event inquiry', 'Hello, I would like to ask about booking a private event for 20 people.', '2026-04-17 09:45:00');

-- Demo logins:
-- admin@myclub.local / admin123
-- user@myclub.local / user12345
