// ============================================================
// MAIN JAVASCRIPT FILE
// ============================================================

(function($) {
    'use strict';

    // Document Ready
    $(document).ready(function() {
        
        // Back to Top Button
        initBackToTop();
        
        // Initialize Tooltips
        initTooltips();
        
        // Initialize Popovers
        initPopovers();
        
        // Lazy Load Images
        initLazyLoad();
        
        // Search Functionality
        initSearch();
        
        // Live Update Features
        initLiveUpdates();
        
        // Comment Form Submission
        initCommentForm();
        
    });

    // Back to Top Button
    function initBackToTop() {
        const backToTop = $('#backToTop');
        
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                backToTop.addClass('show');
            } else {
                backToTop.removeClass('show');
            }
        });
        
        backToTop.click(function() {
            $('html, body').animate({scrollTop: 0}, 600);
            return false;
        });
    }

    // Initialize Bootstrap Tooltips
    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Initialize Bootstrap Popovers
    function initPopovers() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Lazy Load Images
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const image = entry.target;
                        image.src = image.dataset.src;
                        image.classList.remove('lazy');
                        imageObserver.unobserve(image);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    // Search Functionality
    function initSearch() {
        const searchForm = $('#searchForm');
        const searchInput = $('#searchInput');
        const searchResults = $('#searchResults');
        
        let searchTimeout;
        
        searchInput.on('input', function() {
            const query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(function() {
                    performSearch(query);
                }, 500);
            } else {
                searchResults.hide();
            }
        });
        
        function performSearch(query) {
            $.ajax({
                url: BASE_URL + '/api/search.php',
                type: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.results.length > 0) {
                        displaySearchResults(response.results);
                    } else {
                        searchResults.html('<div class="p-3">No results found</div>').show();
                    }
                },
                error: function() {
                    console.error('Search request failed');
                }
            });
        }
        
        function displaySearchResults(results) {
            let html = '<div class="list-group">';
            
            results.forEach(function(result) {
                html += `
                    <a href="${BASE_URL}/article/${result.slug}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100">
                            <img src="${UPLOADS_URL}/articles/${result.thumbnail}" class="me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="">
                            <div>
                                <h6 class="mb-1">${result.title}</h6>
                                <small class="text-muted">${result.category_name}</small>
                            </div>
                        </div>
                    </a>
                `;
            });
            
            html += '</div>';
            searchResults.html(html).show();
        }
        
        // Hide search results when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#searchForm').length) {
                searchResults.hide();
            }
        });
    }

    // Live Updates for Live Articles, Election, Cricket, Market
    function initLiveUpdates() {
        // Check if we're on a page that needs live updates
        if (typeof ENABLE_LIVE_UPDATES !== 'undefined' && ENABLE_LIVE_UPDATES) {
            setInterval(updateLiveContent, LIVE_UPDATE_INTERVAL || 10000);
        }
    }

    function updateLiveContent() {
        // Update based on page type
        if (typeof PAGE_TYPE !== 'undefined') {
            switch(PAGE_TYPE) {
                case 'election':
                    updateElectionResults();
                    break;
                case 'cricket':
                    updateCricketScores();
                    break;
                case 'market':
                    updateMarketData();
                    break;
                case 'live_article':
                    updateLiveArticle();
                    break;
            }
        }
    }

    // Update Election Results
    function updateElectionResults() {
        $.ajax({
            url: BASE_URL + '/api/election/get_results.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    refreshElectionData(response.data);
                }
            }
        });
    }

    function refreshElectionData(data) {
        // Update summary
        if (data.summary) {
            $('#totalSeats').text(data.summary.total_seats);
            $('#seatsCounted').text(data.summary.seats_counted);
            $('#voterTurnout').text(data.summary.voter_turnout + '%');
        }
        
        // Update party-wise results
        if (data.party_results) {
            updateElectionChart(data.party_results);
        }
        
        // Update constituency table
        if (data.constituencies) {
            updateConstituencyTable(data.constituencies);
        }
        
        // Update live news
        if (data.live_news) {
            updateElectionNews(data.live_news);
        }
    }

    // Update Cricket Scores
    function updateCricketScores() {
        $.ajax({
            url: BASE_URL + '/api/cricket/live_matches.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    refreshCricketScores(response.data);
                }
            }
        });
    }

    function refreshCricketScores(data) {
        data.forEach(function(match) {
            const matchCard = $(`#match-${match.id}`);
            if (matchCard.length) {
                matchCard.find('.team1-score').text(`${match.team1_runs}/${match.team1_wickets}`);
                matchCard.find('.team1-overs').text(`(${match.team1_overs})`);
                matchCard.find('.team2-score').text(`${match.team2_runs}/${match.team2_wickets}`);
                matchCard.find('.team2-overs').text(`(${match.team2_overs})`);
                matchCard.find('.match-status').text(match.status_text);
            }
        });
    }

    // Update Market Data
    function updateMarketData() {
        $.ajax({
            url: BASE_URL + '/api/market/get_indices.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    refreshMarketData(response.data);
                }
            }
        });
    }

    function refreshMarketData(data) {
        data.indices.forEach(function(index) {
            const indexCard = $(`#index-${index.symbol}`);
            if (indexCard.length) {
                indexCard.find('.index-value').text(index.current_value.toFixed(2));
                indexCard.find('.index-change').text(index.change_value.toFixed(2));
                indexCard.find('.index-percent').text(`(${index.change_percentage.toFixed(2)}%)`);
                
                // Update color based on positive/negative
                const changeClass = index.change_value >= 0 ? 'text-success' : 'text-danger';
                indexCard.find('.index-change, .index-percent').removeClass('text-success text-danger').addClass(changeClass);
            }
        });
        
        // Update stocks
        if (data.stocks) {
            updateStockTable(data.stocks);
        }
    }

    // Update Live Article
    function updateLiveArticle() {
        const articleId = $('#article-id').val();
        
        $.ajax({
            url: BASE_URL + '/api/articles/get_live_updates.php',
            type: 'GET',
            data: { article_id: articleId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.updates.length > 0) {
                    prependLiveUpdates(response.updates);
                }
            }
        });
    }

    function prependLiveUpdates(updates) {
        const container = $('#liveUpdatesContainer');
        
        updates.forEach(function(update) {
            const existingUpdate = container.find(`[data-update-id="${update.id}"]`);
            
            if (existingUpdate.length === 0) {
                const html = `
                    <div class="alert alert-info" data-update-id="${update.id}">
                        <small class="text-muted">${update.created_at}</small>
                        <p class="mb-0 mt-1">${update.update_text}</p>
                    </div>
                `;
                container.prepend(html);
            }
        });
    }

    // Article Actions (Like, Save, Share, Download)
    window.likeArticle = function(articleId) {
        $.ajax({
            url: BASE_URL + '/api/articles/like.php',
            type: 'POST',
            data: { article_id: articleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const likeBtn = $('#likeBtn');
                    const icon = likeBtn.find('i');
                    
                    // Update count
                    $('#likesCount').text(response.likes_count);
                    
                    // Update button style and icon
                    if (response.liked) {
                        likeBtn.removeClass('btn-outline-primary').addClass('btn-primary');
                        icon.removeClass('bi-heart').addClass('bi-heart-fill');
                    } else {
                        likeBtn.removeClass('btn-primary').addClass('btn-outline-primary');
                        icon.removeClass('bi-heart-fill').addClass('bi-heart');
                    }
                    
                    showToast(response.message, 'success');
                } else {
                    // Check if authentication is required
                    if (response.message && response.message.includes('login')) {
                        showToast('Please login to like articles', 'warning');
                        setTimeout(function() {
                            window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                    } else {
                        showToast(response.message, 'danger');
                    }
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    showToast('Please login to like articles', 'warning');
                    setTimeout(function() {
                        window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                } else {
                    showToast('Failed to update like status', 'danger');
                }
            }
        });
    };

    window.saveArticle = function(articleId) {
        $.ajax({
            url: BASE_URL + '/api/articles/save.php',
            type: 'POST',
            data: { article_id: articleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const saveBtn = $('#saveBtn');
                    const icon = saveBtn.find('i');
                    const text = saveBtn.contents().filter(function() {
                        return this.nodeType === 3; // Text node
                    });
                    
                    // Update button style, icon and text
                    if (response.saved) {
                        saveBtn.removeClass('btn-outline-warning').addClass('btn-warning');
                        icon.removeClass('bi-bookmark').addClass('bi-bookmark-fill');
                        text.last().replaceWith(' Saved');
                    } else {
                        saveBtn.removeClass('btn-warning').addClass('btn-outline-warning');
                        icon.removeClass('bi-bookmark-fill').addClass('bi-bookmark');
                        text.last().replaceWith(' Save');
                    }
                    
                    showToast(response.message, 'success');
                } else {
                    // Check if authentication is required
                    if (response.message && response.message.includes('login')) {
                        showToast('Please login to save articles', 'warning');
                        setTimeout(function() {
                            window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                    } else {
                        showToast(response.message, 'danger');
                    }
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    showToast('Please login to save articles', 'warning');
                    setTimeout(function() {
                        window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                } else {
                    showToast('Failed to update save status', 'danger');
                }
            }
        });
    };

    window.downloadArticle = function(articleId) {
        window.location.href = BASE_URL + '/api/articles/download.php?id=' + articleId;
    };

    window.shareArticle = function(platform, url, title) {
        // Remove .php extension from URL if present
        url = url.replace(/\.php(\?|$)/, '$1');
        
        let shareUrl = '';
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    };

    // Show Toast Notification
    function showToast(message, type = 'info') {
        const toast = $(`
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);
        
        $('#toastContainer').append(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.on('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Comment Form Submission
    function initCommentForm() {
        const form = $('#commentForm');
        if (form.length === 0) {
            return; // Form doesn't exist on this page
        }
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Posting...');
            
            $.ajax({
                url: BASE_URL + '/api/comments/add.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        form[0].reset();
                        
                        if (response.comment) {
                            prependComment(response.comment);
                        }
                    } else {
                        showToast(response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Comment submission error:', error);
                    showToast('Failed to post comment. Please try again.', 'danger');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    function prependComment(comment) {
        const html = `
            <div class="comment-item">
                <div class="comment-author">${comment.user_name}</div>
                <div class="comment-time text-muted small">${comment.created_at}</div>
                <div class="comment-text mt-2">${comment.comment_text}</div>
            </div>
        `;
        $('#commentsContainer').prepend(html);
    }

    // Poll Voting
    window.submitPollVote = function(pollId, option) {
        $.ajax({
            url: BASE_URL + '/api/polls/vote.php',
            type: 'POST',
            data: { 
                poll_id: pollId, 
                option: option 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    updatePollResults(pollId, response.results);
                } else {
                    showToast(response.message, 'danger');
                }
            }
        });
    };

    function updatePollResults(pollId, results) {
        const pollCard = $(`#poll-${pollId}`);
        const total = results.total_votes;
        
        Object.keys(results.options).forEach(function(option) {
            const votes = results.options[option];
            const percentage = total > 0 ? ((votes / total) * 100).toFixed(1) : 0;
            
            pollCard.find(`.option-${option} .progress-bar`).css('width', percentage + '%').text(percentage + '%');
            pollCard.find(`.option-${option} .vote-count`).text(`${votes} votes`);
        });
        
        pollCard.find('.total-votes').text(`Total Votes: ${total}`);
    }

    // Toast Container initialization
    if ($('#toastContainer').length === 0) {
        $('body').append('<div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
    }

})(jQuery);
