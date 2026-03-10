<?php
require_once __DIR__ . '/../INCLUDE/env_loader.php';
loadEnv(__DIR__ . '/../.env');

$dbURL = getenv('DATABASES_URL');
if (!$dbURL) {
    die('DATABASES_URL is not set in the environment variables.');
}

//parse the url
$parts =$parts['host'];
$port = $parts['port']?? 5432;
$dbname = ltrim($parts['path'], '/');
$user = $parts['user'];
$password = $parts['pass'];

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
try {
    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDo::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
        ]

    );

    initializeDatabase($pdo);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
function initializeDatabase($pdo) {
   
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
    
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
color VARCHAR(7) DEFAULT '#667eea',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// insert default categories if the table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$catCount = $stmt->fetchColumn();

if ($catCount ==0 )
    {
        $pdo->exec("INSERT INTO categories (name, color) VALUES 
        ( 'Personal','#e91e63'),
        ('Work','#2196f3'),
        (Ideas','#ff9800'),
        ('Shopping','#4caf50'),
        (Ímportant','#f44336'),
        ");
    }

    //create notes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE,
        
        title VARCHAR(255) NOT NULL,
        content TEXT ,
        COLOR VARCHAR(7) DEFAULT '#ffffff',
        is_pinned INTEGER DEFAULT 0,
        is_archuved INTEGER DEFAULT 0,
        category_id INTEGER NULL REFERENCES categories(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
           )");

           //create indexex (postgreSQL automatically creates an index for primary keys and unique constraints, but we can create additional indexes for better performance)
              $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_is_archived ON notes(is_archived)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_is_pinned ON notes(is_pinned)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_is_updated_at ON notes(updated_at)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_user_id ON notes(user_id)");
                