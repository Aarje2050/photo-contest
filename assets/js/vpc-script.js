/**
 * Voxel Photo Contests
 * Main JavaScript
 */

(function($) {
    'use strict';
    
    // VPC namespace
    const VPC = {
        init: function() {
            this.bindEvents();
            this.initLightbox();
        },
        
        bindEvents: function() {
            // Vote button click
            $(document).on('click', '.vpc-vote-button', this.handleVote);
            
            // Image submission preview
            $(document).on('change', '.vpc-submission-file-input', this.handleImagePreview);
        },
        
        /**
         * Handle vote button click
         */
        handleVote: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const container = button.closest('.vpc-vote-container');
            const postId = container.data('post-id');
            const contestId = container.data('contest-id');
            
            // Disable button to prevent multiple votes
            button.prop('disabled', true).css('opacity', 0.7);
            
            // Send AJAX request
            $.ajax({
                url: vpcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'vpc_vote',
                    post_id: postId,
                    contest_id: contestId,
                    nonce: vpcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update vote count
                        container.find('.vpc-vote-count').text(
                            response.data.count + ' ' + 
                            (response.data.count === 1 ? 'vote' : 'votes')
                        );
                        
                        // Replace button with "Voted" message
                        button.replaceWith(
                            '<div class="vpc-voted"><span class="vpc-heart vpc-voted-heart">❤</span> ' + 
                            vpcData.messages.vote_success + '</div>'
                        );
                        
                        // Update votes left count if present
                        if (container.find('.vpc-votes-left').length) {
                            const votesLeftElem = container.find('.vpc-votes-left');
                            const currentVotesLeft = parseInt(votesLeftElem.text().match(/\d+/)[0], 10);
                            
                            if (currentVotesLeft <= 1) {
                                votesLeftElem.replaceWith(
                                    '<div class="vpc-votes-limit-reached">' +
                                    vpcData.messages.no_votes_left + '</div>'
                                );
                            } else {
                                const newVotesLeft = currentVotesLeft - 1;
                                votesLeftElem.text(
                                    vpcData.messages.votes_left.replace('%d', newVotesLeft) + 
                                    (newVotesLeft === 1 ? '' : 's')
                                );
                            }
                        }
                    } else {
                        // Show error message
                        alert(response.data.message);
                        button.prop('disabled', false).css('opacity', 1);
                    }
                },
                error: function() {
                    // Show error message
                    alert(vpcData.messages.vote_error);
                    button.prop('disabled', false).css('opacity', 1);
                }
            });
        },
        
        /**
         * Handle image preview for submission form
         */
        handleImagePreview: function(e) {
            const input = e.target;
            const preview = $('#vpc-image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.html('<img src="' + e.target.result + '" alt="Preview">');
                    preview.show();
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.hide();
            }
        },
        
        /**
         * Initialize lightbox for submission gallery
         */
        initLightbox: function() {
            // Check if Voxel's lightbox is available
            if (typeof window.VX !== 'undefined' && typeof window.VX.lightbox !== 'undefined') {
                // Use Voxel's lightbox if available
                $('.vpc-submission-image a').on('click', function(e) {
                    e.preventDefault();
                    
                    const imageUrl = $(this).attr('href');
                    const title = $(this).data('title') || '';
                    const author = $(this).data('author') || '';
                    
                    window.VX.lightbox.open({
                        images: [imageUrl],
                        titles: [title],
                        captions: [author]
                    });
                });
            } else {
                // Simple lightbox fallback
                $('.vpc-submission-image a').on('click', function(e) {
                    e.preventDefault();
                    
                    const imageUrl = $(this).attr('href');
                    const overlay = $('<div class="vpc-lightbox-overlay"></div>');
                    const container = $('<div class="vpc-lightbox-container"></div>');
                    const img = $('<img src="' + imageUrl + '" alt="">');
                    const close = $('<button class="vpc-lightbox-close">&times;</button>');
                    
                    container.append(img);
                    container.append(close);
                    overlay.append(container);
                    $('body').append(overlay);
                    
                    overlay.fadeIn(200);
                    
                    close.on('click', function() {
                        overlay.fadeOut(200, function() {
                            overlay.remove();
                        });
                    });
                    
                    overlay.on('click', function(e) {
                        if (e.target === overlay[0]) {
                            overlay.fadeOut(200, function() {
                                overlay.remove();
                            });
                        }
                    });
                });
                
                // Add lightbox styles if needed
                if ($('#vpc-lightbox-styles').length === 0) {
                    $('head').append(`
                        <style id="vpc-lightbox-styles">
                            .vpc-lightbox-overlay {
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background-color: rgba(0, 0, 0, 0.9);
                                z-index: 9999;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                padding: 20px;
                                box-sizing: border-box;
                            }
                            
                            .vpc-lightbox-container {
                                position: relative;
                                max-width: 90%;
                                max-height: 90%;
                            }
                            
                            .vpc-lightbox-container img {
                                max-width: 100%;
                                max-height: 80vh;
                                display: block;
                                margin: 0 auto;
                            }
                            
                            .vpc-lightbox-close {
                                position: absolute;
                                top: -40px;
                                right: 0;
                                background: none;
                                border: none;
                                color: white;
                                font-size: 30px;
                                cursor: pointer;
                            }
                        </style>
                    `);
                }
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VPC.init();
    });
    
})(jQuery);