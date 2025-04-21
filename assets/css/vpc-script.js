(function($) {
    'use strict';
    
    // Handle vote button click
    $(document).on('click', '.vpc-vote-button', function() {
        const button = $(this);
        const container = button.closest('.vpc-vote-container');
        const postId = container.data('post-id');
        const contestId = container.data('contest-id');
        
        console.log('Vote clicked:', postId, contestId);
        
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
                console.log('Vote response:', response);
                
                if (response.success) {
                    // Update vote count
                    container.find('.vpc-vote-count').text(
                        response.data.count + ' ' + 
                        (response.data.count === 1 ? 'vote' : 'votes')
                    );
                    
                    // Replace button with "Voted" message
                    button.replaceWith(
                        '<div class="vpc-voted"><span class="vpc-heart vpc-voted-heart">❤</span> Voted</div>'
                    );
                    
                    // Update votes left count if present
                    if (container.find('.vpc-votes-left').length) {
                        const votesLeftElem = container.find('.vpc-votes-left');
                        const currentVotesLeft = parseInt(votesLeftElem.text().match(/\d+/)[0], 10);
                        
                        if (currentVotesLeft <= 1) {
                            votesLeftElem.replaceWith(
                                '<div class="vpc-votes-limit-reached">You have used all your votes</div>'
                            );
                        } else {
                            const newVotesLeft = currentVotesLeft - 1;
                            votesLeftElem.text(
                                'You have ' + newVotesLeft + ' ' + 
                                (newVotesLeft === 1 ? 'vote' : 'votes') + ' left'
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
    });
    
})(jQuery);