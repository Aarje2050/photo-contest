/**
 * Voxel Photo Contests
 * Admin JavaScript
 */

(function($) {
    'use strict';
    
    // VPC Admin namespace
    const VPCAdmin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initDatepickers();
        },
        
        bindEvents: function() {
            // Handle tab clicks
            $('.vpc-tab-link').on('click', this.handleTabClick);
            
            // Confirmation dialogs
            $('.vpc-confirm-action').on('click', this.confirmAction);
        },
        
        /**
         * Initialize tabs on admin pages
         */
        initTabs: function() {
            // Check if tabs exist
            if ($('.vpc-tab-navigation').length) {
                // Show the active tab
                const activeTab = $('.vpc-tab-navigation a.active').data('tab');
                if (activeTab) {
                    $('.vpc-tab-content').hide();
                    $('#' + activeTab).show();
                } else {
                    // Show first tab if none is active
                    $('.vpc-tab-navigation a:first').addClass('active');
                    $('.vpc-tab-content:first').show();
                }
            }
        },
        
        /**
         * Initialize datepickers for date fields
         */
        initDatepickers: function() {
            // Check if jQuery UI datepicker is available
            if ($.fn.datepicker) {
                $('.vpc-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },
        
        /**
         * Handle tab click
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const tab = $this.data('tab');
            
            // Update active tab
            $('.vpc-tab-navigation a').removeClass('active');
            $this.addClass('active');
            
            // Show tab content
            $('.vpc-tab-content').hide();
            $('#' + tab).show();
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + tab);
            } else {
                location.hash = '#' + tab;
            }
        },
        
        /**
         * Show confirmation dialog before action
         */
        confirmAction: function(e) {
            const message = $(this).data('confirm');
            if (message && !confirm(message)) {
                e.preventDefault();
            }
        },
        
        /**
         * Initialize contest results chart
         */
        initResultsChart: function(chartData) {
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                return;
            }
            
            const ctx = document.getElementById('vpc-results-chart');
            if (!ctx) {
                return;
            }
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Votes',
                        data: chartData.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }
                }
            });
        }
    };
    
    // Initialize admin on document ready
    $(document).ready(function() {
        VPCAdmin.init();
        
        // Check for hash in URL
        if (window.location.hash) {
            const tab = window.location.hash.substring(1);
            $('.vpc-tab-link[data-tab="' + tab + '"]').trigger('click');
        }
        
        // Initialize results chart if data is available
        if (typeof vpcChartData !== 'undefined') {
            VPCAdmin.initResultsChart(vpcChartData);
        }
    });
    
})(jQuery);