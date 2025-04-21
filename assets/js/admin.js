/**
 * Voxel Photo Contest - Admin functionality
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize datepickers for contest start/end dates
        if ($.fn.datepicker) {
            $('#contest_start_date, #contest_end_date').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
        
        // Contest statistics
        $('.voxel-contest-stats-refresh').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const contestId = button.data('contest-id');
            const statsContainer = button.closest('.voxel-contest-stats').find('.voxel-stats-container');
            
            button.prop('disabled', true).text('Refreshing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'voxel_refresh_contest_stats',
                    contest_id: contestId,
                    nonce: voxelPhotoContests.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).text('Refresh Stats');
                    
                    if (response.success) {
                        statsContainer.html(response.data.html);
                    } else {
                        alert(response.data.message || 'Error refreshing stats');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Refresh Stats');
                    alert('Error refreshing stats. Please try again.');
                }
            });
        });
    });
})(jQuery);