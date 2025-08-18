<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

$activePage = 'backup';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_type = $_POST['backup_type'] ?? 'full';
    $backup_location = get_setting('backup_location', 'backups/');
    
    try {
        $backup_dir = rtrim($backup_location, '/') . '/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_filename = "tailor_backup_{$backup_type}_{$timestamp}";
        
        $success = false;
        $message = '';
        
        switch ($backup_type) {
            case 'database':
                $success = create_database_backup($backup_dir, $backup_filename);
                $message = $success ? 'Database backup created successfully!' : 'Failed to create database backup.';
                break;
                
            case 'files':
                $success = create_files_backup($backup_dir, $backup_filename);
                $message = $success ? 'Files backup created successfully!' : 'Failed to create files backup.';
                break;
                
            case 'full':
            default:
                $success = create_full_backup($backup_dir, $backup_filename);
                $message = $success ? 'Full backup created successfully!' : 'Failed to create full backup.';
                break;
        }
        
        if ($success) {
            // Update last backup date
            set_setting('last_backup_date', date('Y-m-d H:i:s'), 'Last Backup Date');
            
            header("Location: backup.php?success=created&type=" . $backup_type);
            exit;
        } else {
            $error = $message;
        }
        
    } catch (Exception $e) {
        $error = "Error creating backup: " . $e->getMessage();
    }
}

// Get backup history
$backup_files = get_backup_files();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-cloud-arrow-up me-2"></i>System Backup</h2>
                <a href="settings.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Settings
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['success'] === 'created') {
                        $type = $_GET['type'] ?? 'backup';
                        echo ucfirst($type) . " created successfully!";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Create Backup -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Backup</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Backup Type</label>
                                    <select name="backup_type" class="form-control" required>
                                        <option value="full">Full Backup (Database + Files)</option>
                                        <option value="database">Database Only</option>
                                        <option value="files">Files Only</option>
                                    </select>
                                    <div class="form-text">Choose what to backup</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Backup Location</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars(get_setting('backup_location', 'backups/')) ?>" readonly>
                                    <div class="form-text">Backup storage directory</div>
                                </div>
                                
                                <button type="submit" name="create_backup" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>Create Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Backup Status -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Backup Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Auto Backup:</strong></p>
                                    <p class="mb-1"><strong>Frequency:</strong></p>
                                    <p class="mb-1"><strong>Last Backup:</strong></p>
                                    <p class="mb-1"><strong>Next Backup:</strong></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><?= is_setting_enabled('auto_backup') ? 'Enabled' : 'Disabled' ?></p>
                                    <p class="mb-1"><?= ucfirst(get_setting('backup_frequency', 'weekly')) ?></p>
                                    <p class="mb-1">
                                        <?php 
                                        $last_backup = get_setting('last_backup_date', 'Never');
                                        echo $last_backup !== 'Never' ? date('d/m/Y H:i', strtotime($last_backup)) : 'Never';
                                        ?>
                                    </p>
                                    <p class="mb-1">
                                        <?php 
                                        if (is_setting_enabled('auto_backup')) {
                                            $frequency = get_setting('backup_frequency', 'weekly');
                                            $last = get_setting('last_backup_date');
                                            if ($last && $last !== 'Never') {
                                                $next = '';
                                                switch($frequency) {
                                                    case 'daily':
                                                        $next = date('d/m/Y H:i', strtotime($last . ' +1 day'));
                                                        break;
                                                    case 'weekly':
                                                        $next = date('d/m/Y H:i', strtotime($last . ' +1 week'));
                                                        break;
                                                    case 'monthly':
                                                        $next = date('d/m/Y H:i', strtotime($last . ' +1 month'));
                                                        break;
                                                }
                                                echo $next;
                                            } else {
                                                echo 'Not scheduled';
                                            }
                                        } else {
                                            echo 'Auto backup disabled';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                         <!-- Backup History -->
             <div class="card mt-4">
                 <div class="card-header bg-secondary text-white">
                     <div class="d-flex justify-content-between align-items-center">
                         <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Backup History</h5>
                         <?php if (!empty($backup_files)): ?>
                             <div class="d-flex gap-2">
                                 <button type="button" class="btn btn-sm btn-outline-light" onclick="refreshBackupHistory()" title="Refresh Backup History">
                                     <i class="bi bi-arrow-clockwise"></i>
                                 </button>
                                 <button type="button" class="btn btn-sm btn-outline-light" onclick="selectAllBackups()">
                                     <i class="bi bi-check-all me-1"></i>Select All
                                 </button>
                                 <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteBackups()" id="bulkDeleteBtn" disabled>
                                     <i class="bi bi-trash me-1"></i>Delete Selected
                                 </button>
                             </div>
                         <?php endif; ?>
                     </div>
                 </div>
                 <div class="card-body">
                     <?php if (empty($backup_files)): ?>
                         <div class="text-center py-4">
                             <i class="bi bi-inbox display-1 text-muted"></i>
                             <p class="text-muted mt-3">No backup files found</p>
                         </div>
                     <?php else: ?>
                         <div class="table-responsive">
                             <table class="table table-striped">
                                 <thead>
                                     <tr>
                                         <th width="50">
                                             <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                         </th>
                                         <th>Backup File</th>
                                         <th>Type</th>
                                         <th>Size</th>
                                         <th>Created</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                <tbody>
                                                                         <?php foreach ($backup_files as $file): ?>
                                         <tr>
                                             <td>
                                                 <input type="checkbox" class="form-check-input backup-checkbox" value="<?= htmlspecialchars($file['name']) ?>" onchange="updateBulkDeleteButton()">
                                             </td>
                                             <td><?= htmlspecialchars($file['name']) ?></td>
                                            <td><span class="badge bg-<?= $file['type'] === 'full' ? 'primary' : ($file['type'] === 'database' ? 'success' : 'info') ?>"><?= ucfirst($file['type']) ?></span></td>
                                            <td><?= format_file_size($file['size']) ?></td>
                                            <td><?= date('d/m/Y H:i', $file['mtime']) ?></td>
                                                                                         <td>
                                                 <div class="btn-group" role="group">
                                                     <a href="download_backup.php?file=<?= urlencode($file['name']) ?>" class="btn btn-sm btn-success" title="Download Backup">
                                                         <i class="bi bi-download"></i>
                                                     </a>
                                                     <button type="button" class="btn btn-sm btn-info" onclick="viewBackupInfo('<?= htmlspecialchars($file['name']) ?>', '<?= format_file_size($file['size']) ?>', '<?= date('d/m/Y H:i', $file['mtime']) ?>')" title="View Details">
                                                         <i class="bi bi-info-circle"></i>
                                                     </button>
                                                     <button type="button" class="btn btn-sm btn-danger" onclick="deleteBackup('<?= htmlspecialchars($file['name']) ?>')" title="Delete Backup">
                                                         <i class="bi bi-trash"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function deleteBackup(filename) {
    if (confirm('Are you sure you want to delete this backup file? This action cannot be undone.')) {
        // Show loading state
        const deleteBtn = event.target.closest('button');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Deleting...';
        deleteBtn.disabled = true;
        
        // Create form data
        const formData = new FormData();
        formData.append('filename', filename);
        
        // Send delete request
        fetch('delete_backup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            // Try to parse JSON response
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', data.message);
                // Remove the row from the table
                const row = deleteBtn.closest('tr');
                row.style.animation = 'fadeOut 0.5s';
                setTimeout(() => {
                    row.remove();
                    // If no more files, refresh the page to show empty state
                    if (document.querySelectorAll('tbody tr').length === 0) {
                        location.reload();
                    }
                }, 500);
            } else {
                showAlert('danger', data.message || 'Unknown error occurred');
                // Reset button
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while deleting the backup file: ' + error.message);
            // Reset button
            deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
        });
    }
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert alert at the top of the main content
    const mainContent = document.querySelector('main');
    mainContent.insertBefore(alertDiv, mainContent.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function viewBackupInfo(filename, size, created) {
    // Create modal content
    const modalContent = `
        <div class="modal fade" id="backupInfoModal" tabindex="-1" aria-labelledby="backupInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="backupInfoModalLabel">
                            <i class="bi bi-info-circle me-2"></i>Backup File Information
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Filename:</strong></p>
                                <p><strong>Size:</strong></p>
                                <p><strong>Created:</strong></p>
                                <p><strong>Type:</strong></p>
                            </div>
                            <div class="col-6">
                                <p class="text-break">${filename}</p>
                                <p>${size}</p>
                                <p>${created}</p>
                                <p><span class="badge bg-primary">${getBackupType(filename)}</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="download_backup.php?file=${encodeURIComponent(filename)}" class="btn btn-success">
                            <i class="bi bi-download me-1"></i>Download
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('backupInfoModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalContent);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('backupInfoModal'));
    modal.show();
    
    // Remove modal from DOM after it's hidden
    document.getElementById('backupInfoModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function getBackupType(filename) {
    if (filename.includes('database')) return 'Database';
    if (filename.includes('files')) return 'Files';
    if (filename.includes('full')) return 'Full';
    return 'Unknown';
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const backupCheckboxes = document.querySelectorAll('.backup-checkbox');
    
    backupCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkedCheckboxes = document.querySelectorAll('.backup-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.disabled = checkedCheckboxes.length === 0;
        bulkDeleteBtn.innerHTML = `<i class="bi bi-trash me-1"></i>Delete Selected (${checkedCheckboxes.length})`;
    }
}

function selectAllBackups() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    selectAllCheckbox.checked = true;
    toggleSelectAll();
}

function bulkDeleteBackups() {
    const checkedCheckboxes = document.querySelectorAll('.backup-checkbox:checked');
    const filenames = Array.from(checkedCheckboxes).map(cb => cb.value);
    
    if (filenames.length === 0) {
        showAlert('warning', 'Please select backup files to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${filenames.length} backup file(s)? This action cannot be undone.`)) {
        // Show loading state
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const originalText = bulkDeleteBtn.innerHTML;
        bulkDeleteBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Deleting...';
        bulkDeleteBtn.disabled = true;
        
        // Delete files one by one
        let deletedCount = 0;
        let failedCount = 0;
        
        const deletePromises = filenames.map(filename => {
            const formData = new FormData();
            formData.append('filename', filename);
            
            return fetch('delete_backup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Server returned invalid response format');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    deletedCount++;
                    // Remove the row
                    const checkbox = document.querySelector(`input[value="${filename}"]`);
                    if (checkbox) {
                        const row = checkbox.closest('tr');
                        row.style.animation = 'fadeOut 0.5s';
                        setTimeout(() => row.remove(), 500);
                    }
                } else {
                    failedCount++;
                    console.error('Delete failed for', filename, ':', data.message);
                }
                return data;
            })
            .catch(error => {
                failedCount++;
                console.error('Network error for', filename, ':', error.message);
                return { success: false, message: 'Network error: ' + error.message };
            });
        });
        
        // Wait for all deletions to complete
        Promise.all(deletePromises).then(() => {
            // Show results
            if (failedCount === 0) {
                showAlert('success', `Successfully deleted ${deletedCount} backup file(s).`);
            } else if (deletedCount === 0) {
                showAlert('danger', `Failed to delete ${failedCount} backup file(s).`);
            } else {
                showAlert('warning', `Deleted ${deletedCount} backup file(s), failed to delete ${failedCount} file(s).`);
            }
            
            // Reset bulk delete button
            bulkDeleteBtn.innerHTML = originalText;
            bulkDeleteBtn.disabled = true;
            
            // Update select all checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            selectAllCheckbox.checked = false;
            
                         // If no more files, refresh the page
             if (document.querySelectorAll('tbody tr').length === 0) {
                 location.reload();
             }
         });
     }
 }

function refreshBackupHistory() {
    const refreshBtn = event.target.closest('button');
    const originalHTML = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    refreshBtn.disabled = true;
    
    // Refresh the page
    setTimeout(() => {
        location.reload();
    }, 500);
}

// Add CSS for fade out animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(-100%); }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>


