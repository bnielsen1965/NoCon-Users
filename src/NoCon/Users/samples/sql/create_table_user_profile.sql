CREATE TABLE nocon_user_profile (
    username varchar(255) NOT NULL,
    firstName varchar(255) NOT NULL,
    lastName varchar(255) NOT NULL,
    json text NOT NULL DEFAULT '',
    FOREIGN KEY (username) REFERENCES nocon_user(username)
);