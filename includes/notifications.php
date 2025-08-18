<?php
/**
 * Notification Helper Functions
 * Provides comprehensive notification management for the Tailor Management System
 */

/**
 * Create a new notification
 * @param int $user_id User ID to receive the notification
 * @param string $type Notification type (info, success, warning, error)
 * @param string $message Notification message
 * @param string $title Optional notification title
 * @param array $data Additional notification data
 * @return bool Success status
 */
function create_notification($user_id, $type, $message, $title = '', $data = []) {
    global $pdo;
    
    try {
        // Check if notifications are enabled
        if (!is_setting_enabled('enable_notifications')) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        if ($stmt->execute([$user_id, $title, $message, $type])) {
            // Send real-time notification if enabled
            send_realtime_notification($user_id, $type, $message, $title);
            
            // Send email notification if enabled
            if (is_setting_enabled('email_notifications')) {
                send_email_notification($user_id, $type, $message, $title);
            }
            
            // Send SMS notification if enabled
            if (is_setting_enabled('sms_notifications')) {
                send_sms_notification($user_id, $type, $message, $title);
            }
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send low stock alert notification
 * @param int $user_id User ID to receive the notification
 * @param string $product_name Product name
 * @param int $current_stock Current stock level
 * @param int $threshold Stock threshold
 * @return bool Success status
 */
function send_low_stock_alert($user_id, $product_name, $current_stock, $threshold) {
    if (!is_setting_enabled('low_stock_alerts')) {
        return false;
    }
    
    $message = "Low stock alert: {$product_name} stock is {$current_stock} (threshold: {$threshold})";
    $title = "Low Stock Alert";
    
    return create_notification($user_id, 'warning', $message, $title, [
        'product_name' => $product_name,
        'current_stock' => $current_stock,
        'threshold' => $threshold,
        'type' => 'low_stock'
    ]);
}

/**
 * Send payment reminder notification
 * @param int $user_id User ID to receive the notification
 * @param string $customer_name Customer name
 * @param float $amount Outstanding amount
 * @param string $due_date Due date
 * @return bool Success status
 */
function send_payment_reminder($user_id, $customer_name, $amount, $due_date) {
    if (!is_setting_enabled('payment_reminders')) {
        return false;
    }
    
    $message = "Payment reminder: {$customer_name} owes {$amount} due on {$due_date}";
    $title = "Payment Reminder";
    
    return create_notification($user_id, 'info', $message, $title, [
        'customer_name' => $customer_name,
        'amount' => $amount,
        'due_date' => $due_date,
        'type' => 'payment_reminder'
    ]);
}

/**
 * Send order status update notification
 * @param int $user_id User ID to receive the notification
 * @param string $order_number Order number
 * @param string $old_status Old status
 * @param string $new_status New status
 * @return bool Success status
 */
function send_order_status_update($user_id, $order_number, $old_status, $new_status) {
    if (!is_setting_enabled('order_status_updates')) {
        return false;
    }
    
    $message = "Order {$order_number} status changed from {$old_status} to {$new_status}";
    $title = "Order Status Update";
    
    return create_notification($user_id, 'info', $message, $title, [
        'order_number' => $order_number,
        'old_status' => $old_status,
        'new_status' => $new_status,
        'type' => 'order_status'
    ]);
}

/**
 * Send delivery reminder notification
 * @param int $user_id User ID to receive the notification
 * @param string $order_number Order number
 * @param string $delivery_date Delivery date
 * @param string $customer_name Customer name
 * @return bool Success status
 */
function send_delivery_reminder($user_id, $order_number, $delivery_date, $customer_name) {
    if (!is_setting_enabled('delivery_reminders')) {
        return false;
    }
    
    $message = "Delivery reminder: Order {$order_number} for {$customer_name} is due on {$delivery_date}";
    $title = "Delivery Reminder";
    
    return create_notification($user_id, 'warning', $message, $title, [
        'order_number' => $order_number,
        'delivery_date' => $delivery_date,
        'customer_name' => $customer_name,
        'type' => 'delivery_reminder'
    ]);
}

/**
 * Send email notification
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $message Message content
 * @param string $title Notification title
 * @return bool Success status
 */
function send_email_notification($user_id, $type, $message, $title) {
    global $pdo;
    
    try {
        // Get user email
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['email']) {
            return false;
        }
        
        // Get SMTP settings
        $smtp_server = get_setting('smtp_server', 'smtp.gmail.com');
        $smtp_port = get_setting('smtp_port', '587');
        $smtp_username = get_setting('smtp_username');
        $smtp_password = get_setting('smtp_password');
        $from_email = get_setting('from_email', 'noreply@tailorshop.com');
        
        if (!$smtp_username || !$smtp_password) {
            return false;
        }
        
        // For now, we'll use PHP's mail() function
        // In a production environment, you'd want to use PHPMailer or similar
        $headers = [
            'From: ' . $from_email,
            'Reply-To: ' . $from_email,
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $subject = "[Tailor Shop] {$title}";
        $html_message = "
            <html>
            <body>
                <h2>{$title}</h2>
                <p><strong>Type:</strong> " . ucfirst($type) . "</p>
                <p><strong>Message:</strong> {$message}</p>
                <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                <hr>
                <p><small>This is an automated notification from your Tailor Management System.</small></p>
            </body>
            </html>
        ";
        
        return mail($user['email'], $subject, $html_message, implode("\r\n", $headers));
        
    } catch (Exception $e) {
        error_log("Error sending email notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send SMS notification
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $message Message content
 * @param string $title Notification title
 * @return bool Success status
 */
function send_sms_notification($user_id, $type, $message, $title) {
    global $pdo;
    
    try {
        // Get user phone number
        $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['phone']) {
            return false;
        }
        
        // Get SMS settings
        $provider = get_setting('sms_provider', 'twilio');
        $api_key = get_setting('sms_api_key');
        $api_secret = get_setting('sms_api_secret');
        
        if (!$api_key || !$api_secret) {
            return false;
        }
        
        // For now, we'll just log the SMS
        // In a production environment, you'd integrate with Twilio, Nexmo, etc.
        error_log("SMS Notification to {$user['phone']}: [{$type}] {$title} - {$message}");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error sending SMS notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send real-time notification (for future WebSocket implementation)
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $message Message content
 * @param string $title Notification title
 * @return bool Success status
 */
function send_realtime_notification($user_id, $type, $message, $title) {
    // This function is a placeholder for future WebSocket implementation
    // For now, it just returns true
    return true;
}

/**
 * Mark notification as read
 * @param int $notification_id Notification ID
 * @return bool Success status
 */
function mark_notification_read($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_all_notifications_read($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete notification
 * @param int $notification_id Notification ID
 * @return bool Success status
 */
function delete_notification($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (Exception $e) {
        error_log("Error deleting notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for a user
 * @param int $user_id User ID
 * @return int Unread count
 */
function get_unread_notification_count($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get notifications for a user
 * @param int $user_id User ID
 * @param int $limit Limit of notifications to return
 * @param int $offset Offset for pagination
 * @param bool $unread_only Whether to return only unread notifications
 * @return array Notifications
 */
function get_user_notifications($user_id, $limit = 50, $offset = 0, $unread_only = false) {
    global $pdo;
    
    try {
        $where_clause = "WHERE user_id = ?";
        $params = [$user_id];
        
        if ($unread_only) {
            $where_clause .= " AND is_read = 0";
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            {$where_clause} 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting user notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Clean up old notifications
 * @param int $days_to_keep Days to keep notifications
 * @return bool Success status
 */
function cleanup_old_notifications($days_to_keep = 30) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
            AND is_read = 1
        ");
        
        return $stmt->execute([$days_to_keep]);
        
    } catch (Exception $e) {
        error_log("Error cleaning up old notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notification statistics
 * @return array Statistics
 */
function get_notification_stats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total notifications
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
        $stats['total'] = $stmt->fetchColumn();
        
        // Unread notifications
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        $stats['unread'] = $stmt->fetchColumn();
        
        // Notifications by type
        $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM notifications GROUP BY type");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Notifications by date (last 7 days)
        $stmt = $pdo->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM notifications 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY date DESC
        ");
        $stats['by_date'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting notification stats: " . $e->getMessage());
        return [];
    }
}
?>
