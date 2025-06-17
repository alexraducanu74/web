-- Drop tables if they exist (in the correct dependency order)
DROP TABLE IF EXISTS user_book_progress CASCADE;
DROP TABLE IF EXISTS group_members CASCADE;
DROP TABLE IF EXISTS group_books CASCADE;
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
('Amintiri din Copilarie', 'Ion Creangă', 'Memoir, Romanian Literature, Classics', 'covers/amintiri-din-copilarie.jpg', 130),
('Circe', 'Madeline Miller', 'Fantasy, Mythology, Literary Fiction', 'covers/circe.jpg', 393),
('Crime and Punishment', 'Fyodor Dostoevsky', 'Literary fiction, Philosophical fiction', 'covers/crime-and-prejudice.jpg', 527),
('Dune', 'Frank Herbert', 'Science Fiction, Philosophical fiction', 'covers/dune.jpg', 412),
('Enders Game', 'Orson Scott Card', 'Science Fiction', 'covers/enders-game.jpg', 324),
('A Game of Thrones', 'George R. R. Martin', 'Political novel, epic fantasy', 'covers/game-of-thrones.jpg', 694),
('Harry Potter and The Deathly Hallows', 'J.K. Rowling', 'Young Adult, Fiction, Magic', 'covers/harry-potter.jpg', 759),
('I am Malala', 'Malala Yousafzai', 'Biography, Autobiography, Personal Memoirs', 'covers/i-am-malala.jpg', 464),
('In Search of Lost Time', 'Marcel Proust', 'Fiction, France, Literature', 'covers/in-the-search-of-lost-time.jpg', 4211),
('Lost Illusions', 'Honoré de Balzac', 'Fiction', 'covers/lost-illusions.jpg', 458),
('One Hundred Years of Solitude', 'Gabriel García Márquez', 'Magic realism', 'covers/one-hundred-years-of-solitude.jpg', 422),
('The Catcher in the Rye', 'J. D. Salinger', 'Realistic fiction, Coming-of-age fiction', 'covers/the-cathcer-in-the-rye.jpg', 234),
('The Fault in Our Stars', 'John Green', 'contemporary romance; realistic fiction;', 'covers/the-fault-in-our-stars.jpg', 336),
('The Hobbit', 'J. R. R. Tolkien', 'High fantasy, Children''s fantasy', 'covers/the-hobbit.jpg', 310),
('The Silent Patient', 'Alex Michaelides', 'Thriller, Mystery, Fiction', 'covers/the-silent-patient.jpg', 336),
('The Time Machine', 'H. G. Wells', 'Science fiction', 'covers/the-time-machine.jpg', 84);

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
