/**
 * Voxel Photo Contest - Voting functionality
 */
(function($) {
    'use strict';
    
    // Handle vote button click
    $(document).on('click', '.voxel-vote-button', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const postId = button.data('post-id');
        const contestId = button.data('contest-id');
        
        // Don't allow multiple clicks
        if (button.hasClass('voting') || button.hasClass('voted')) {
            return;
        }
        
        // Add loading state
        button.addClass('voting').prop('disabled', true);
        
        // Send ajax request
        $.ajax({
            url: voxelPhotoContests.ajaxUrl,
            type: 'POST',
            data: {
                action: 'voxel_photo_contest_vote',
                post_id: postId,
                contest_id: contestId,
                vote_value: 1,
                nonce: voxelPhotoContests.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update button state
                    button.removeClass('voting').addClass('voted');
                    button.find('.voxel-vote-icon').replaceWith('<span class="voxel-vote-icon-voted">❤</span>');
                    button.find('.voxel-vote-text').text(voxelPhotoContests.i18n.vote_success);
                    
                    // Update vote count if present
                    if (response.data.vote_count) {
                        const countSpan = button.find('.voxel-vote-count');
                        if (countSpan.length) {
                            countSpan.text(response.data.vote_count);
                        } else {
                            button.append('<span class="voxel-vote-count">' + response.data.vote_count + '</span>');
                        }
                    }
                } else {
                    button.removeClass('voting').prop('disabled', false);
                    
                    // Show error message
                    alert(response.data.message);
                    
                    // If login required, redirect to login page
                    if (response.data.require_login) {
                        const currentUrl = window.location.href;
                        window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(currentUrl);
                    }
                }
            },
            error: function() {
                button.removeClass('voting').prop('disabled', false);
                alert(voxelPhotoContests.i18n.vote_error);
            }
        });
    });
    
    // Handle star rating
    $(document).on('click', '.voxel-star', function() {
        const star = $(this);
        const value = star.data('value');
        const wrapper = star.closest('.voxel-vote-wrapper');
        const postId = wrapper.data('post-id');
        const contestId = wrapper.data('contest-id');
        
        // Don't allow voting twice
        if (star.closest('.voxel-star-rating').hasClass('voted')) {
            return;
        }
        
        // Add loading state
        star.closest('.voxel-star-rating').addClass('voting');
        
        // Send ajax request
        $.ajax({
            url: voxelPhotoContests.ajaxUrl,
            type: 'POST',
            data: {
                action: 'voxel_photo_contest_vote',
                post_id: postId,
                contest_id: contestId,
                vote_value: value,
                nonce: voxelPhotoContests.nonce
            },
            success: function(response) {
                star.closest('.voxel-star-rating').removeClass('voting');
                
                if (response.success) {
                    // Update stars
                    star.closest('.voxel-star-rating').addClass('voted');
                    star.closest('.voxel-star-rating').find('.voxel-star').each(function() {
                        if ($(this).data('value') <= value) {
                            $(this).addClass('voted');
                        }
                    });
                } else {
                    // Show error message
                    alert(response.data.message);
                    
                    // If login required, redirect to login page
                    if (response.data.require_login) {
                        const currentUrl = window.location.href;
                        window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(currentUrl);
                    }
                }
            },
            error: function() {
                star.closest('.voxel-star-rating').removeClass('voting');
                alert(voxelPhotoContests.i18n.vote_error);
            }
        });
    });
})(jQuery);