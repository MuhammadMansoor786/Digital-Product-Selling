<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'User Management';

$db = getDB();

// Handle block/unblock actions
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $action = $_GET['action'];
    
    if ($action == 'block') {
        $db->update('users', ['is_blocked' => 1], 'id = ?', [$userId]);
        setFlashMessage('success', 'User blocked successfully');
    } elseif ($action == 'unblock') {
        $db->update('users', ['is_blocked' => 0], 'id = ?', [$userId]);
        setFlashMessage('success', 'User unblocked successfully');
    }
    
    redirect('users.php');
}

// Get search term
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$users = $db->fetchAll("
    SELECT * FROM users 
    WHERE $where 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

// Get total count for pagination
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE $where", $params);
$pagination = getPagination($totalUsers['count'], $perPage, $page);

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">User Management</h2>
    </div>
</div>

<!-- Search -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search users by name or email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No users found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($user['is_blocked'] == 1): ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <?php if ($user['is_blocked'] == 1): ?>
                                        <a href="users.php?action=unblock&user_id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-success btn-block" 
                                           data-action="unblock">
                                            <i class="bi bi-unlock"></i> Unblock
                                        </a>
                                    <?php else: ?>
                                        <a href="users.php?action=block&user_id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger btn-block" 
                                           data-action="block">
                                            <i class="bi bi-lock"></i> Block
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>
