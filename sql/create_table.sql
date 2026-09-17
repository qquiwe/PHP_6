CREATE DATABASE IF NOT EXISTS practicum4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practicum4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(12, 2) NOT NULL,           -- сума операції (додатна - дохід, від'ємна - витрата)
    category VARCHAR(100) NOT NULL,           -- категорія: "Продукти", "Транспорт", "Зарплата" тощо
    transaction_date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- тестові дані 
INSERT INTO transactions (amount, category, transaction_date) VALUES
    (15000.00, 'Зарплата', '2026-09-01'),
    (-850.50,  'Продукти', '2026-09-02'),
    (-320.00,  'Транспорт', '2026-09-03'),
    (-1200.00, 'Комунальні послуги', '2026-09-05'),
    (500.00,   'Подарунок', '2026-09-08');
