-- Drop tables if they exist (in the correct dependency order)
DROP TABLE IF EXISTS user_book_progress CASCADE;
DROP TABLE IF EXISTS group_members CASCADE;
DROP TABLE IF EXISTS groups CASCADE;
DROP TABLE IF EXISTS books CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Create users table
CREATE TABLE users (
    users_id SERIAL PRIMARY KEY,
    users_uid TEXT NOT NULL,
    users_pwd TEXT NOT NULL,
    users_email TEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Add default admin user (already hashed password)
INSERT INTO users (users_uid, users_email, is_admin, users_pwd) VALUES 
('admin', 'admin@admin.com', TRUE, '$2y$10$INvQoLFmST6ewSBItlkXVOR8Q1sTzGRxkOU7TPiNvLOiLZ01nYhvG');

-- Create groups table
CREATE TABLE groups (
    group_id SERIAL PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    group_description TEXT,
    creator_user_id INTEGER NOT NULL,
    secret_code VARCHAR(64) NOT NULL UNIQUE,
    requires_approval BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_user_id) REFERENCES users (users_id) ON DELETE CASCADE
);

-- Create group_members table
CREATE TABLE group_members (
    group_member_id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    member_status TEXT NOT NULL DEFAULT 'pending' CHECK (member_status IN ('approved', 'pending', 'denied', 'invited')),
    UNIQUE (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups (group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (users_id) ON DELETE CASCADE
);



CREATE TABLE books (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    genre VARCHAR(255),
    cover_image VARCHAR(255),
    total_pages INTEGER CHECK (total_pages > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_book_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(users_id) ON DELETE CASCADE,
    book_id INTEGER NOT NULL REFERENCES books(id) ON DELETE CASCADE,
    pages_read INTEGER DEFAULT 0,
    review TEXT,
    rating DECIMAL(2,1) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, book_id)
);

CREATE TABLE group_books (
    group_book_id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL REFERENCES groups(group_id) ON DELETE CASCADE,
    book_id INTEGER NOT NULL REFERENCES books(id) ON DELETE CASCADE,
    added_by_user_id INTEGER NOT NULL REFERENCES users(users_id) ON DELETE SET NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (group_id, book_id)
);

INSERT INTO books (title, author, genre, cover_image, total_pages) VALUES
('The Midnight Library', 'Matt Haig', 'Contemporary Fiction, Magical Realism', 'covers/midnight-library.jpg', 304),
('1984', 'George Orwell', 'Dystopian, Political Fiction, Science Fiction', 'covers/1984.jpg', 328),
('The Name of the Wind', 'Patrick Rothfuss', 'Epic Fantasy, Adventure', 'covers/name-of-the-wind.jpg', 662),

('Book Title 1',  'Author Horror',   'Horror',                          'covers/1984.jpg', 220),
('Book Title 2',  'Author Horror',   'Horror, Thriller',               'covers/1984.jpg', 245),
('Book Title 3',  'Author Horror',   'Supernatural Horror',            'covers/1984.jpg', 260),
('Book Title 4',  'Author Horror',   'Horror',                         'covers/1984.jpg', 230),
('Book Title 5',  'Author Horror',   'Psychological Horror',           'covers/1984.jpg', 250),

('Book Title 6',  'Author Comedy',   'Comedy, Satire',                 'covers/1984.jpg', 210),
('Book Title 7',  'Author Comedy',   'Comedy',                         'covers/1984.jpg', 190),
('Book Title 8',  'Author Comedy',   'Dark Comedy',                    'covers/1984.jpg', 200),
('Book Title 9',  'Author Comedy',   'Comedy',                         'covers/1984.jpg', 195),
('Book Title 10', 'Author Comedy',   'Romantic Comedy',                'covers/1984.jpg', 225),

('Book Title 11', 'Author SciFi',    'Science Fiction, Space Opera',   'covers/1984.jpg', 310),
('Book Title 12', 'Author SciFi',    'Science Fiction',                'covers/1984.jpg', 295),
('Book Title 13', 'Author SciFi',    'Cyberpunk, Science Fiction',     'covers/1984.jpg', 275),
('Book Title 14', 'Author SciFi',    'Science Fiction',                'covers/1984.jpg', 285),
('Book Title 15', 'Author SciFi',    'Hard Science Fiction',           'covers/1984.jpg', 320),

('Book Title 16', 'Author Thriller', 'Thriller, Mystery',              'covers/1984.jpg', 340),
('Book Title 17', 'Author Thriller', 'Thriller',                       'covers/1984.jpg', 300),
('Book Title 18', 'Author Thriller', 'Psychological Thriller',         'covers/1984.jpg', 310),
('Book Title 19', 'Author Thriller', 'Thriller',                       'covers/1984.jpg', 295),
('Book Title 20', 'Author Thriller', 'Crime Thriller',                 'covers/1984.jpg', 305);


DROP TRIGGER IF EXISTS trg_validate_group_name_length ON groups;

CREATE OR REPLACE FUNCTION validate_group_name_length()
RETURNS TRIGGER AS $$
BEGIN
    IF LENGTH(TRIM(NEW.group_name)) < 3 THEN
        RAISE EXCEPTION 'Numele grupului este prea scurt: "%"', NEW.group_name;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validate_group_name_length
BEFORE INSERT OR UPDATE ON groups
FOR EACH ROW
EXECUTE FUNCTION validate_group_name_length();


DROP TRIGGER IF EXISTS trg_validate_username_length ON users;

CREATE OR REPLACE FUNCTION validate_username_length()
RETURNS TRIGGER AS $$
BEGIN
    IF LENGTH(TRIM(NEW.users_uid)) < 3 THEN
        RAISE EXCEPTION 'Numele de utilizator este prea scurt: "%"', NEW.users_uid;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validate_username_length
BEFORE INSERT OR UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION validate_username_length();


DROP TRIGGER IF EXISTS trg_lowercase_email ON users;

CREATE OR REPLACE FUNCTION lowercase_email()
RETURNS TRIGGER AS $$
BEGIN
    NEW.users_email := LOWER(NEW.users_email);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_lowercase_email
BEFORE INSERT OR UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION lowercase_email();


CREATE OR REPLACE FUNCTION generate_unique_secret_code() RETURNS VARCHAR AS $$
DECLARE
    candidate VARCHAR(10);
    exists_count INTEGER;
BEGIN
    LOOP
        candidate := substring(md5(random()::text), 1, 10);
        SELECT COUNT(*) INTO exists_count FROM groups WHERE secret_code = candidate;
        IF exists_count = 0 THEN
            RETURN candidate;
        END IF;
    END LOOP;
END;
$$ LANGUAGE plpgsql;
