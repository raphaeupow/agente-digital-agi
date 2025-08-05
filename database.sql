-- Tabela de scripts (configurações dos robôs)
CREATE TABLE scripts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    language VARCHAR(50) DEFAULT 'pt-BR',
    voice VARCHAR(100) DEFAULT NULL,
    provider VARCHAR(100) DEFAULT NULL,
    production JSON DEFAULT NULL,
    test JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de atendimentos (registro de cada chamada)
CREATE TABLE attendances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end TIMESTAMP NULL,
    timer INT NULL COMMENT 'Tempo em segundos',
    script_id INT NOT NULL,
    phone VARCHAR(20) ,
    unique_id VARCHAR(50) ,
    key_id INT,
    is_test BOOLEAN DEFAULT FALSE,
    hangout_direction VARCHAR(20) NULL COMMENT 'bot, client, etc',
    status_id INT NULL,
    variables TEXT NULL COMMENT 'Variáveis do sistema em JSON',
    INDEX idx_script_id (script_id),
    INDEX idx_phone (phone),
    INDEX idx_unique_id (unique_id),
    INDEX idx_start (start)
);

-- Tabela de passos (cada ação executada no script)
CREATE TABLE steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attendance_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL COMMENT 'Nome do componente (play, decision, etc)',
    component_id INT NOT NULL COMMENT 'ID do componente no script',
    script_id INT NOT NULL,
    start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end TIMESTAMP NULL,
    timer INT NULL COMMENT 'Tempo em segundos',
    INDEX idx_attendance_id (attendance_id),
    INDEX idx_script_id (script_id),
    INDEX idx_start (start),
    FOREIGN KEY (attendance_id) REFERENCES attendances(id) ON DELETE CASCADE
);
CREATE TABLE debug (
    id INT PRIMARY KEY AUTO_INCREMENT,
    step_id INT NOT NULL,
    script_id INT NOT NULL,
    type VARCHAR(20) NOT NULL COMMENT 'info, error, warning, etc',
    datetime VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_step_id (step_id),
    INDEX idx_script_id (script_id),
    INDEX idx_type (type),
    INDEX idx_datetime (datetime),
    FOREIGN KEY (step_id) REFERENCES steps(id) ON DELETE CASCADE
);