-- Chạy MỘT LẦN trên database cs403 hiện có (KHÔNG import lại csdl.sql đầy đủ).
USE `cs403`;
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT NOT NULL,
    change_amount INT NOT NULL,
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    note VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_user_date (user_id, created_at),
    CONSTRAINT fk_wallet_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallet_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_wallet_change CHECK(change_amount <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS barter_confirmations (
    barter_request_id INT PRIMARY KEY,
    sender_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    receiver_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_barter_confirmation_request FOREIGN KEY (barter_request_id)
    REFERENCES barter_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
