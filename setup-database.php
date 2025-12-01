<?php
/**
 * Скрипт установки базы данных
 * Запуск: php setup-database.php
 */

class DatabaseSetup
{
    private $connection;
    private $config;

    public function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }

    private function loadConfig()
    {
        // Читаем конфигурацию из .env
        if (file_exists('.env')) {
            $envContent = file_get_contents('.env');
            preg_match_all('/database\.default\.(\w+)\s*=\s*(.+)/', $envContent, $matches);
            
            $this->config = [
                'hostname' => '',
                'database' => '',
                'username' => '',
                'password' => '',
                'port' => 5432
            ];
				
            foreach ($matches[1] as $index => $key) {
                if (isset($this->config[$key])) {
                    $this->config[$key] = trim($matches[2][$index]);
                }
            }
        } else {
            echo "Не удается найти .env файл";
        }
    }

    private function connect()
    {
        try {
            $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s", $this->config['hostname'], $this->config['port'], 'postgres');

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "Подключение к PostgreSQL успешно\n";
        } catch (PDOException $e) {
            die("Ошибка подключения: " . $e->getMessage() . "\n");
        }
    }

    public function run()
    {
        echo "Запуск установки базы данных\n";
        echo "===============================\n";

        $this->createDatabase();
        $this->createTables();

        echo "\nУстановка базы данных завершена успешно!\n";
    }

    private function createDatabase()
    {
        try {
            // Проверяем существование базы данных
            $stmt = $this->connection->query(
                "SELECT 1 FROM pg_database WHERE datname = '{$this->config['database']}'"
            );
            
            if ($stmt->rowCount() === 0) {
                echo "Создание базы данных '{$this->config['database']}'... ";
                $this->connection->exec("CREATE DATABASE {$this->config['database']}");
                echo "\n";
            } else {
                echo "База данных '{$this->config['database']}' уже существует\n";
            }

            // Переподключаемся к созданной базе данных
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s",
                $this->config['hostname'],
                $this->config['port'],
                $this->config['database']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo "Ошибка при создании базы данных: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function createTables()
    {
        $tables = [
            'files' => "
                CREATE TABLE IF NOT EXISTS files (
                    id SERIAL PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    file_path TEXT NOT NULL,
                    file_size INTEGER NOT NULL,
                    row_count INTEGER NOT NULL DEFAULT 0,
                    column_count INTEGER NOT NULL DEFAULT 0,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",

            'action_logs' => "
                CREATE TABLE IF NOT EXISTS action_logs (
                    id SERIAL PRIMARY KEY,
                    file_id INTEGER,
                    filename VARCHAR(255) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    user_ip VARCHAR(45),
                    user_agent TEXT,
                    details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_file_id FOREIGN KEY (file_id) 
                    REFERENCES files(id) ON DELETE SET NULL
                )
            "
        ];

        foreach ($tables as $tableName => $sql) {
            echo "Создание таблицы '$tableName'... ";
            try {
                $this->connection->exec($sql);
                echo "\n";
            } catch (PDOException $e) {
                echo "Ошибка: " . $e->getMessage() . "\n";
            }
        }
    }

}

// Запуск скрипта
if (php_sapi_name() === 'cli') {
    echo "===========================================\n";
    echo "  Установщик базы данных\n";
    echo "===========================================\n\n";

    $setup = new DatabaseSetup();
    $setup->run();
    
    "\n Установка завершена! Теперь вы можете запустить приложение.\n";
    echo "   Команда для запуска: php spark serve\n";
} else {
    echo "Запустите этот скрипт из командной строки: php setup-database.php\n";
}