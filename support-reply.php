<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Invalid request');
    redirect('support.php');
}

$ticketId = (int)$_POST['ticket_id'];
$message = sanitizeInput($_POST['message']);

if (empty($message)) {
    setFlashMessage('danger', 'Message is required');
    redirect('support.php');
}

$db = getDB();

// Verify ticket belongs to user
$ticket = $db->fetchOne("
    SELECT * FROM support_tickets 
    WHERE id = ? AND user_id = ?
", [$ticketId, $_SESSION['user_id']]);

if (!$ticket) {
    setFlashMessage('danger', 'Ticket not found');
    redirect('support.php');
}

// Add reply
$replyId = $db->insert('support_replies', [
    'ticket_id' => $ticketId,
    'user_id' => $_SESSION['user_id'],
    'message' => $message,
    'is_admin' => 0
]);

if ($replyId) {
    // Update ticket status to in_progress if it was open
    if ($ticket['status'] == 'open') {
        $db->update('support_tickets',
            ['status' => 'in_progress'],
            'id = ?',
            [$ticketId]
        );
    }
    
    setFlashMessage('success', 'Reply sent successfully!');
} else {
    setFlashMessage('danger', 'Failed to send reply');
}

redirect('support.php');
