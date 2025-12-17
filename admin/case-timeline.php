<?php
/**
 * Case Timeline Management
 */

require_once 'auth_check.php';

$page_title = 'Case Timeline';

$db = Database::getInstance();

$case_id = (int)($_GET['case_id'] ?? 0);

if (!$case_id) {
    $_SESSION['error'] = 'Invalid case ID';
    header('Location: cases.php');
    exit;
}

// Get case
$case = $db->fetchOne("SELECT * FROM case_threads WHERE id = ?", [$case_id]);

if (!$case) {
    $_SESSION['error'] = 'Case not found';
    header('Location: cases.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM case_timeline_events WHERE id = ? AND case_id = ?", [$id, $case_id]);
    $_SESSION['success'] = 'Event deleted successfully';
    header('Location: case-timeline.php?case_id=' . $case_id);
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $event_title = trim($_POST['event_title'] ?? '');
    $event_description = trim($_POST['event_description'] ?? '');
    $event_date = $_POST['event_date'] ?? null;
    $event_time = $_POST['event_time'] ?? null;
    $event_type = $_POST['event_type'] ?? 'general';
    $is_major_event = isset($_POST['is_major_event']) ? 1 : 0;
    
    if ($event_id > 0) {
        // Update
        $db->query("
            UPDATE case_timeline_events SET
                event_title = ?, event_description = ?, event_date = ?, event_time = ?,
                event_type = ?, is_major_event = ?
            WHERE id = ? AND case_id = ?
        ", [$event_title, $event_description, $event_date, $event_time, $event_type, $is_major_event, $event_id, $case_id]);
        $_SESSION['success'] = 'Event updated successfully';
    } else {
        // Insert
        $db->insert('case_timeline_events', [
            'case_id' => $case_id,
            'event_title' => $event_title,
            'event_description' => $event_description,
            'event_date' => $event_date,
            'event_time' => $event_time,
            'event_type' => $event_type,
            'is_major_event' => $is_major_event
        ]);
        $_SESSION['success'] = 'Event added successfully';
    }
    
    header('Location: case-timeline.php?case_id=' . $case_id);
    exit;
}

// Get events
$events = $db->fetchAll("
    SELECT * FROM case_timeline_events 
    WHERE case_id = ? 
    ORDER BY event_date DESC, event_time DESC
", [$case_id]);

// Edit mode
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_event = $db->fetchOne("SELECT * FROM case_timeline_events WHERE id = ? AND case_id = ?", [$edit_id, $case_id]);
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Timeline: <?php echo htmlspecialchars($case['title']); ?></h1>
        <a href="case-edit.php?id=<?php echo $case_id; ?>" class="btn btn-secondary">← Back to Case</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_event): ?>
                            <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Event Title *</label>
                            <input type="text" name="event_title" class="form-control" required
                                   value="<?php echo htmlspecialchars($edit_event['event_title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="event_description" class="form-control" rows="4"><?php echo htmlspecialchars($edit_event['event_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label>Date *</label>
                                    <input type="date" name="event_date" class="form-control" required
                                           value="<?php echo htmlspecialchars($edit_event['event_date'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="time" name="event_time" class="form-control"
                                           value="<?php echo htmlspecialchars($edit_event['event_time'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Event Type</label>
                            <select name="event_type" class="form-control">
                                <option value="general" <?php echo ($edit_event['event_type'] ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
                                <option value="incident" <?php echo ($edit_event['event_type'] ?? '') === 'incident' ? 'selected' : ''; ?>>Incident</option>
                                <option value="investigation" <?php echo ($edit_event['event_type'] ?? '') === 'investigation' ? 'selected' : ''; ?>>Investigation</option>
                                <option value="arrest" <?php echo ($edit_event['event_type'] ?? '') === 'arrest' ? 'selected' : ''; ?>>Arrest</option>
                                <option value="trial" <?php echo ($edit_event['event_type'] ?? '') === 'trial' ? 'selected' : ''; ?>>Trial</option>
                                <option value="verdict" <?php echo ($edit_event['event_type'] ?? '') === 'verdict' ? 'selected' : ''; ?>>Verdict</option>
                                <option value="appeal" <?php echo ($edit_event['event_type'] ?? '') === 'appeal' ? 'selected' : ''; ?>>Appeal</option>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_major_event" class="form-check-input" id="majorEvent"
                                   <?php echo ($edit_event['is_major_event'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="majorEvent">
                                Major Event (highlighted in timeline)
                            </label>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php echo $edit_event ? 'Update Event' : 'Add Event'; ?>
                            </button>
                            <?php if ($edit_event): ?>
                                <a href="case-timeline.php?case_id=<?php echo $case_id; ?>" class="btn btn-secondary btn-block">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Timeline Events (<?php echo count($events); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <p class="text-center text-muted">No events yet. Add your first event.</p>
                    <?php else: ?>
                        <div class="timeline-admin">
                            <?php foreach ($events as $event): ?>
                                <div class="timeline-item-admin <?php echo $event['is_major_event'] ? 'major' : ''; ?>">
                                    <div class="timeline-date">
                                        <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                        <?php if ($event['event_time']): ?>
                                            <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h5>
                                            <?php echo htmlspecialchars($event['event_title']); ?>
                                            <?php if ($event['is_major_event']): ?>
                                                <span class="badge badge-danger">Major</span>
                                            <?php endif; ?>
                                            <span class="badge badge-info"><?php echo ucfirst($event['event_type']); ?></span>
                                        </h5>
                                        <?php if ($event['event_description']): ?>
                                            <p><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="?case_id=<?php echo $case_id; ?>&edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="?case_id=<?php echo $case_id; ?>&delete=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this event?');">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-admin {
    position: relative;
    padding-left: 30px;
}
.timeline-admin::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}
.timeline-item-admin {
    position: relative;
    padding-bottom: 30px;
}
.timeline-item-admin::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid #fff;
}
.timeline-item-admin.major::before {
    width: 16px;
    height: 16px;
    background: #dc3545;
    left: -26px;
}
.timeline-date {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}
</style>

<?php include 'includes/footer.php'; ?>
