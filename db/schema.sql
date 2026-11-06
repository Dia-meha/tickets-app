-- Schéma de la base « tickets » (MariaDB / MySQL)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100) NOT NULL,
    email        VARCHAR(190) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,            -- hash password_hash()
    role         ENUM('utilisateur','admin') NOT NULL DEFAULT 'utilisateur',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre       VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    categorie   ENUM('materiel','logiciel','reseau','autre') NOT NULL,
    priorite    ENUM('faible','moyenne','elevee') NOT NULL DEFAULT 'moyenne',
    statut      ENUM('ouvert','en_cours','resolu') NOT NULL DEFAULT 'ouvert',
    user_id     INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tickets_user (user_id),
    KEY idx_tickets_statut (statut),
    KEY idx_tickets_priorite (priorite),
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reponses (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id  INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    contenu    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reponses_ticket (ticket_id),
    CONSTRAINT fk_reponses_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_reponses_user   FOREIGN KEY (user_id)   REFERENCES users (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
