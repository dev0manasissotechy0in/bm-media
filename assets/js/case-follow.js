/**
 * CASE THREADS FOLLOW SYSTEM
 * Handles follow/unfollow functionality for case threads
 */

(function() {
    'use strict';
    
    // Follow/Unfollow case
    window.toggleCaseFollow = async function(caseId, currentlyFollowing) {
        try {
            const userId = getUserId();
            
            if (!userId) {
                alert('Please login to follow cases');
                window.location.href = BASE_URL + '/login';
                return;
            }
            
            let response, data;
            
            if (currentlyFollowing) {
                // Unfollow
                response = await fetch(`${API_URL}/cases/unfollow.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        case_id: parseInt(caseId),
                        user_id: userId
                    })
                });
                data = await response.json();
                
                if (data.success) {
                    updateFollowButton(false);
                    alert('Successfully unfollowed this case');
                }
            } else {
                // Follow - show notification preferences modal
                showNotificationPreferences(caseId);
            }
        } catch (error) {
            console.error('Error toggling follow:', error);
            alert('An error occurred. Please try again.');
        }
    };
    
    // Show notification preferences modal
    function showNotificationPreferences(caseId) {
        const modalHTML = `
            <div class="modal fade" id="notificationPreferencesModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Follow Case - Notification Preferences</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Choose which updates you want to receive:</p>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notifyArticles" checked>
                                <label class="form-check-label" for="notifyArticles">
                                    <i class="bi bi-newspaper me-1"></i> New Articles
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notifyTimeline" checked>
                                <label class="form-check-label" for="notifyTimeline">
                                    <i class="bi bi-clock-history me-1"></i> Timeline Events
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notifyDocuments" checked>
                                <label class="form-check-label" for="notifyDocuments">
                                    <i class="bi bi-file-earmark-text me-1"></i> Documents Added
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notifyVerdicts" checked>
                                <label class="form-check-label" for="notifyVerdicts">
                                    <i class="bi bi-gavel me-1"></i> Verdicts & Major Updates
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="confirmFollow(${caseId})">
                                <i class="bi bi-bell me-1"></i> Follow Case
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('notificationPreferencesModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('notificationPreferencesModal'));
        modal.show();
    }
    
    // Confirm follow with preferences
    window.confirmFollow = async function(caseId) {
        try {
            const userId = getUserId();
            const preferences = {
                notify_new_articles: document.getElementById('notifyArticles').checked,
                notify_timeline_events: document.getElementById('notifyTimeline').checked,
                notify_documents: document.getElementById('notifyDocuments').checked,
                notify_verdicts: document.getElementById('notifyVerdicts').checked
            };
            
            const response = await fetch(`${API_URL}/cases/follow.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: parseInt(caseId),
                    user_id: userId,
                    ...preferences
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('notificationPreferencesModal'));
                modal.hide();
                
                // Update button
                updateFollowButton(true);
                
                alert('Successfully followed this case! You will receive notifications based on your preferences.');
            } else {
                alert(data.message || 'Failed to follow case');
            }
        } catch (error) {
            console.error('Error following case:', error);
            alert('An error occurred. Please try again.');
        }
    };
    
    // Update follow button appearance
    function updateFollowButton(isFollowing) {
        const followBtn = document.getElementById('followCaseBtn');
        if (!followBtn) return;
        
        const icon = followBtn.querySelector('i');
        const text = followBtn.querySelector('span');
        
        if (isFollowing) {
            followBtn.classList.remove('btn-primary');
            followBtn.classList.add('btn-success');
            icon.className = 'bi bi-check-circle me-1';
            text.textContent = 'Following';
            followBtn.onclick = function() {
                const caseId = this.dataset.caseId;
                toggleCaseFollow(caseId, true);
            };
        } else {
            followBtn.classList.remove('btn-success');
            followBtn.classList.add('btn-primary');
            icon.className = 'bi bi-bell me-1';
            text.textContent = 'Follow Case';
            followBtn.onclick = function() {
                const caseId = this.dataset.caseId;
                toggleCaseFollow(caseId, false);
            };
        }
        
        // Reload page to update follower count
        setTimeout(() => {
            location.reload();
        }, 1500);
    }
    
    // Get user ID (placeholder)
    function getUserId() {
        // TODO: Get from session or JWT token
        return 1;
    }
})();
