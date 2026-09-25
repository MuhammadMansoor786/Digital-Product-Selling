<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Support Management';

$db = getDB();

// Handle status update
if (isset($_POST['action']) && $_POST['action'] == 'update_status' && isset($_POST['ticket_id'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $status = sanitizeInput($_POST['status']);
    
    $db->update('support_tickets', ['status' => $status], 'id = ?', [$ticketId]);
    
    setFlashMessage('success', 'Ticket status updated successfully');
    redirect('support.php');
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id']) && isset($_POST['message'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $message = sanitizeInput($_POST['message']);
    
    if (empty($message)) {
        setFlashMessage('danger', 'Message is required');
    } else {
        $replyId = $db->insert('support_replies', [
            'ticket_id' => $ticketId,
            'admin_id' => $_SESSION['admin_id'],
            'message' => $message,
            'is_admin' => 1
        ]);
        
        if ($replyId) {
            // Update ticket status to in_progress if it was open
            $ticket = $db->fetchOne("SELECT status FROM support_tickets WHERE id = ?", [$ticketId]);
            if ($ticket['status'] == 'open') {
                $db->update('support_tickets', ['status' => 'in_progress'], 'id = ?', [$ticketId]);
            }
            
            setFlashMessage('success', 'Reply sent successfully!');
        } else {
            setFlashMessage('danger', 'Failed to send reply');
        }
    }
    
    redirect('support.php');
}

// Get search term
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$priorityFilter = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (st.subject LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND st.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $where .= " AND st.priority = ?";
    $params[] = $priorityFilter;
}

// Get tickets with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$tickets = $db->fetchAll("
    SELECT st.*, u.full_name, u.email,
           (SELECT COUNT(*) FROM support_replies WHERE ticket_id = st.id) as reply_count
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE $where 
    ORDER BY st.created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

// Get total count for pagination
$totalTickets = $db->fetchOne("SELECT COUNT(*) as count FROM support_tickets st LEFT JOIN users u ON st.user_id = u.id WHERE $where", $params);
$pagination = getPagination($totalTickets['count'], $perPage, $page);

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Support Management</h2>
    </div>
</div>

<!-- Search and Filter -->
<div class="row mb-4">
    <div class="col-md-8">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search tickets by subject, customer name, or email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-select" name="status" style="max-width: 120px;">
                    <option value="">All Status</option>
                    <option value="open" <?php echo $statusFilter == 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $statusFilter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $statusFilter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $statusFilter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <select class="form-select" name="priority" style="max-width: 120px;">
                    <option value="">All Priority</option>
                    <option value="low" <?php echo $priorityFilter == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $priorityFilter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $priorityFilter == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="urgent" <?php echo $priorityFilter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tickets -->
<div class="row">
    <?php if (empty($tickets)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No support tickets found.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($ticket['full_name'] ?? 'Unknown'); ?>
                                    (<a href="mailto:<?php echo htmlspecialchars($ticket['email'] ?? ''); ?>"><?php echo htmlspecialchars($ticket['email'] ?? ''); ?></a>)
                                </small>
                            </div>
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
                                <form method="POST" action="" class="mt-3">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="message" 
                                               placeholder="Type your reply..." required>
                                        <button type="submit" class="btn btn-primary">Send Reply</button>
                                    </div>
                                </form>
                                
                                <form method="POST" action="" class="mt-2">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    
                                    <div class="d-flex gap-2 align-items-center">
                                        <label class="small mb-0">Update Status:</label>
                                        <select class="form-select form-select-sm" name="status" style="width: 150px;">
                                            <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?>">
                    Previous
                </a>
            </li>
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?>">
                    Next
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once '../includes/admin-footer.php'; ?>
