-- Create the feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    lab_number VARCHAR(10) NOT NULL,
    rating INT NOT NULL,
    message TEXT,
    had_issues TINYINT(1) DEFAULT 0,
    issues_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

-- Add an index for faster lookups
CREATE INDEX idx_feedback_user ON feedback(user_id);
CREATE INDEX idx_feedback_reservation ON feedback(reservation_id);

-- Add has_feedback column to reservations table to track feedback status
ALTER TABLE reservations ADD COLUMN has_feedback TINYINT(1) DEFAULT 0;
