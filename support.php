<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

$pageTitle = 'Support Tickets';

$db = getDB();

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    $priority = sanitizeInput($_POST['priority']);
    
    if (empty($subject) || empty($message)) {
        setFlashMessage('danger', 'Subject and message are required');
    } else {
        $ticketId = $db->insert('support_tickets', [
            'user_id' => $_SESSION['user_id'],
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority
        ]);
        
        if ($ticketId) {
            setFlashMessage('success', 'Support ticket created successfully!');
            redirect('support.php');
        } else {
            setFlashMessage('danger', 'Failed to create support ticket');
        }
    }
}

// Get user's support tickets
$tickets = $db->fetchAll("
    SELECT st.*, 
           (SELECT COUNT(*) FROM support_replies WHERE ticket_id = st.id) as reply_count
    FROM support_tickets st 
    WHERE st.user_id = ? 
    ORDER BY st.created_at DESC
", [$_SESSION['user_id']]);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Support</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Create New Ticket</h5>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Submit Ticket</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <h5 class="mb-4">My Support Tickets</h5>
            
            <?php if (empty($tickets)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> You haven't created any support tickets yet.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                        <div>
                                            <span class="badge bg-<?php 
                                                echo match($ticket['status']) {
                                                    'open' => 'primary',
                                                    'in_progress' => 'warning',
                                                    'resolved' => 'success',
                                                    'closed' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                            <span class="badge bg-<?php 
                                                echo match($ticket['priority']) {
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?> ms-1">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text text-muted small mb-2">
                                        <?php echo substr(htmlspecialchars($ticket['message']), 0, 150) . '...'; ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo formatDate($ticket['created_at']); ?>
                                            <?php if ($ticket['reply_count'] > 0): ?>
                                                <span class="ms-2">
                                                    <i class="bi bi-chat"></i> <?php echo $ticket['reply_count']; ?> reply(ies)
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#ticket-<?php echo $ticket['id']; ?>">
                                            View Details
                                        </button>
                                    </div>
                                    
                                    <div class="collapse mt-3" id="ticket-<?php echo $ticket['id']; ?>">
                                        <hr>
                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                                        
                                        <?php
                                        // Get ticket replies
                                        $replies = $db->fetchAll("
                                            SELECT sr.*, 
                                                   CASE 
                                                       WHEN sr.admin_id IS NOT NULL THEN a.full_name
                                                       ELSE u.full_name
                                                   END as sender_name
                                            FROM support_replies sr
                                            LEFT JOIN admins a ON sr.admin_id = a.id
                                            LEFT JOIN users u ON sr.user_id = u.id
                                            WHERE sr.ticket_id = ?
                                            ORDER BY sr.created_at ASC
                                        ", [$ticket['id']]);
                                        ?>
                                        
                                        <?php if (!empty($replies)): ?>
                                            <h6 class="mb-3">Replies</h6>
                                            <?php foreach ($replies as $reply): ?>
                                                <div class="card mb-2 <?php echo $reply['is_admin'] ? 'border-primary' : ''; ?>">
                                                    <div class="card-body py-2">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong><?php echo htmlspecialchars($reply['sender_name']); ?></strong>
                                                            <small class="text-muted"><?php echo formatDate($reply['created_at'], 'M d, Y H:i'); ?></small>
                                                        </div>
                                                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($ticket['status'] != 'closed'): ?>
                                            <form method="POST" action="support-reply.php" class="mt-3">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="message" 
                                                           placeholder="Type your reply..." required>
                                                    <button type="submit" class="btn btn-primary">Send</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
