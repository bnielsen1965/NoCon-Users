CREATE TABLE nocon_user (
    username varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    created timestamp NULL DEFAULT NULL,
    lastLogin timestamp NULL DEFAULT NULL,
    flags int NOT NULL DEFAULT 0,
    PRIMARY KEY (username)
);