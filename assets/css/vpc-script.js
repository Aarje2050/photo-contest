(function($) {
    'use strict';
    
    // Handle vote button click
    $(document).on('click', '.vpc-vote-button', function() {
        const button = $(this);
        const container = button.closest('.vpc-vote-container');
        const postId = container.data('post-id');
        
        // Disable button to prevent multiple votes
        button.prop('disabled', true).css('opacity', 0.7);
        
        // Send AJAX request
        $.ajax({
            url: vpcData.ajax_url,
            type: 'POST',
            data: {
                action: 'vpc_vote',
                post_id: postId,
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
                        'Voted</div>'
                    );
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
    });
    
})(jQuery);