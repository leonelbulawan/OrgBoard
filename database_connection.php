<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'orgboard_db');
define('DB_USER', 'orgboard_user');
define('DB_PASS', 'secure_password_2024');
define('DB_CHARSET', 'utf8mb4');

    
error_reporting(E_ALL);
ini_set('display_errors', 1);


class Database {
    private static $instance = null;
    private $connection;
    private $error;
    

    private function __construct() {
        $this->connect();
    }
    

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    

    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = "Connection failed: " . $e->getMessage();
            error_log($this->error);
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    

    public function getConnection() {
        return $this->connection;
    }
    

    public function getError() {
        return $this->error;
    }
    

    public function close() {
        $this->connection = null;
    }
    

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    

    public function commit() {
        return $this->connection->commit();
    }
    

    public function rollback() {
        return $this->connection->rollback();
    }
}


function authenticateUser($email, $password) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT user_id, email, password_hash, full_name, first_name, role, officer_role, avatar_path, is_active FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Remove password hash from returned data
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}


function createUser($userData) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name, first_name, role, officer_role) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $userData['email'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['full_name'],
            $userData['first_name'],
            $userData['role'],
            $userData['officer_role'] ?? null
        ]);
        
        return $result ? $conn->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        return false;
    }
}


function getUserById($userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT user_id, email, full_name, first_name, role, officer_role, avatar_path, is_active, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}


function updateUserAvatar($userId, $avatarPath) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE users SET avatar_path = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        return $stmt->execute([$avatarPath, $userId]);
    } catch (Exception $e) {
        error_log("Avatar update error: " . $e->getMessage());
        return false;
    }
}


function getUserEvents($userId, $status = null) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT e.*, o.org_name, o.org_code 
                FROM events e 
                JOIN organizations o ON e.org_id = o.org_id 
                JOIN user_organizations uo ON o.org_id = uo.org_id 
                WHERE uo.user_id = ?";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY e.event_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get events error: " . $e->getMessage());
        return [];
    }
}


function createEvent($eventData) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO events (org_id, event_name, event_description, event_date, event_time, location, budget, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $eventData['org_id'],
            $eventData['event_name'],
            $eventData['event_description'],
            $eventData['event_date'],
            $eventData['event_time'],
            $eventData['location'],
            $eventData['budget'] ?? 0,
            $eventData['status'] ?? 'Planning',
            $eventData['created_by']
        ]);
        
        return $result ? $conn->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("Event creation error: " . $e->getMessage());
        return false;
    }
}


function getUserFinancialReports($userId, $status = null) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT fr.*, o.org_name, o.org_code, e.event_name 
                FROM financial_reports fr 
                JOIN organizations o ON fr.org_id = o.org_id 
                JOIN user_organizations uo ON o.org_id = uo.org_id 
                LEFT JOIN events e ON fr.event_id = e.event_id 
                WHERE uo.user_id = ?";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND fr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY fr.report_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get financial reports error: " . $e->getMessage());
        return [];
    }
}


function createFinancialReport($reportData) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO financial_reports (org_id, event_id, report_title, report_type, description, amount, report_date, file_path, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $reportData['org_id'],
            $reportData['event_id'] ?? null,
            $reportData['report_title'],
            $reportData['report_type'],
            $reportData['description'],
            $reportData['amount'] ?? 0,
            $reportData['report_date'],
            $reportData['file_path'] ?? null,
            $reportData['uploaded_by'],
            $reportData['status'] ?? 'Pending'
        ]);
        
        return $result ? $conn->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("Financial report creation error: " . $e->getMessage());
        return false;
    }
}


function getUserAnnouncements($userId, $activeOnly = true) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT a.*, o.org_name, o.org_code, u.full_name as creator_name 
                FROM announcements a 
                JOIN organizations o ON a.org_id = o.org_id 
                JOIN user_organizations uo ON o.org_id = uo.org_id 
                JOIN users u ON a.created_by = u.user_id 
                WHERE uo.user_id = ?";
        $params = [$userId];
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1 AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP)";
        }
        
        $sql .= " ORDER BY a.priority DESC, a.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get announcements error: " . $e->getMessage());
        return [];
    }
}


function createAnnouncement($announcementData) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO announcements (org_id, title, content, priority, created_by, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $announcementData['org_id'],
            $announcementData['title'],
            $announcementData['content'],
            $announcementData['priority'] ?? 'Medium',
            $announcementData['created_by'],
            $announcementData['expires_at'] ?? null
        ]);
        
        return $result ? $conn->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("Announcement creation error: " . $e->getMessage());
        return false;
    }
}


function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}


function markNotificationRead($notificationId, $userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}


function createNotification($notificationData) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $notificationData['user_id'],
            $notificationData['title'],
            $notificationData['message'],
            $notificationData['type'],
            $notificationData['related_id'] ?? null
        ]);
        
        return $result ? $conn->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}


function getUserOrganizations($userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT o.*, uo.position FROM organizations o JOIN user_organizations uo ON o.org_id = uo.org_id WHERE uo.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get user organizations error: " . $e->getMessage());
        return [];
    }
}


function getDashboardStats($userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stats = [];
        
        // Get events count by status
        $stmt = $conn->prepare("SELECT e.status, COUNT(*) as count 
                                FROM events e 
                                JOIN user_organizations uo ON e.org_id = uo.org_id 
                                WHERE uo.user_id = ? 
                                GROUP BY e.status");
        $stmt->execute([$userId]);
        $stats['events'] = $stmt->fetchAll();
        
        // Get financial reports count by status
        $stmt = $conn->prepare("SELECT fr.status, COUNT(*) as count 
                                FROM financial_reports fr 
                                JOIN user_organizations uo ON fr.org_id = uo.org_id 
                                WHERE uo.user_id = ? 
                                GROUP BY fr.status");
        $stmt->execute([$userId]);
        $stats['reports'] = $stmt->fetchAll();
        
        // Get unread notifications count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $stats['unread_notifications'] = $stmt->fetch()['count'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return [];
    }
}
function createUserSession($userId, $sessionId, $ipAddress, $userAgent) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        $stmt = $conn->prepare("INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent, $expiresAt]);
    } catch (Exception $e) {
        error_log("Create session error: " . $e->getMessage());
        return false;
    }
}

function validateSession($sessionId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT s.user_id, s.expires_at, u.full_name, u.role, u.officer_role 
                                FROM sessions s 
                                JOIN users u ON s.user_id = u.user_id 
                                WHERE s.session_id = ? AND s.expires_at > CURRENT_TIMESTAMP AND u.is_active = 1");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if ($session) {
            // Update last activity
            $updateStmt = $conn->prepare("UPDATE sessions SET last_activity = CURRENT_TIMESTAMP WHERE session_id = ?");
            $updateStmt->execute([$sessionId]);
            return $session;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Validate session error: " . $e->getMessage());
        return false;
    }
}


function destroySession($sessionId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id = ?");
        return $stmt->execute([$sessionId]);
    } catch (Exception $e) {
        error_log("Destroy session error: " . $e->getMessage());
        return false;
    }
}


function cleanupExpiredSessions() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM sessions WHERE expires_at < CURRENT_TIMESTAMP");
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Cleanup sessions error: " . $e->getMessage());
        return false;
    }
}

// Initialize database connection on include
Database::getInstance();

?>
