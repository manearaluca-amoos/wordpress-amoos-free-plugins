/* Restrict Access to Environment - JavaScript */
jQuery(document).ready(function($) {
    
    console.log('RAE: Script loaded, jQuery version:', $.fn.jquery);
    console.log('RAE: AJAX settings:', rae_ajax);
    
    // Ensure form doesn't submit normally
    $('#rae-login-form').attr('onsubmit', 'return false;');
    
    // Handle login form submission
    $('#rae-login-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('RAE: Form submission intercepted');
        
        var form = $(this);
        var button = form.find('button[type="submit"]');
        var password = form.find('#rae-password').val();
        
        if (!password) {
            alert('Please enter a password');
            return false;
        }
        
        // Show loading state
        form.addClass('loading');
        button.prop('disabled', true).text('Authenticating...');
        
        // Remove any previous error messages
        $('.rae-error, .rae-success').remove();
        
        console.log('RAE: Sending AJAX request to:', rae_ajax.ajax_url);
        
        // Send AJAX request
        $.ajax({
            url: rae_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rae_login',
                password: password,
                nonce: rae_ajax.nonce
            },
            timeout: 15000, // 15 second timeout
            beforeSend: function() {
                console.log('RAE: AJAX request starting...');
            },
            success: function(response, textStatus, xhr) {
                console.log('RAE: AJAX response received:', response);
                console.log('RAE: Response status:', textStatus);
                console.log('RAE: XHR object:', xhr);
                
                if (response && response.success) {
                    // Show success message
                    form.before('<div class="rae-success">✓ Access granted! Redirecting to homepage...</div>');
                    
                    // Extended delay to ensure session is properly set
                    setTimeout(function() {
                        console.log('RAE: Redirecting to:', rae_ajax.home_url);
                        // Force a full page reload to the home URL
                        window.location.href = rae_ajax.home_url + '?authenticated=' + Date.now();
                    }, 2500); // Increased delay
                } else {
                    // Show error message
                    var errorMsg = 'Login failed. ';
                    if (response && response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response && response.data) {
                        errorMsg += 'Server response: ' + JSON.stringify(response.data);
                    } else {
                        errorMsg += 'No error message received.';
                    }
                    
                    form.before('<div class="rae-error">✗ ' + errorMsg + '</div>');
                    console.log('RAE: Login failed:', errorMsg);
                    
                    // Reset form
                    resetForm();
                }
            },
            error: function(xhr, status, error) {
                console.log('RAE: AJAX error occurred');
                console.log('RAE: XHR:', xhr);
                console.log('RAE: Status:', status);
                console.log('RAE: Error:', error);
                console.log('RAE: Response Text:', xhr.responseText);
                
                var errorMsg = 'Connection error: ';
                if (status === 'timeout') {
                    errorMsg += 'Request timed out. ';
                } else if (status === 'error') {
                    errorMsg += 'Network error. ';
                } else if (status === 'parsererror') {
                    errorMsg += 'Response parsing error. ';
                } else {
                    errorMsg += status + '. ';
                }
                
                if (xhr.responseText) {
                    errorMsg += 'Server response: ' + xhr.responseText.substring(0, 200);
                }
                
                // Show error message
                form.before('<div class="rae-error">✗ ' + errorMsg + '</div>');
                
                // Reset form
                resetForm();
            },
            complete: function() {
                console.log('RAE: AJAX request completed');
            }
        });
        
        function resetForm() {
            form.removeClass('loading');
            button.prop('disabled', false).text('Access Site');
            form.find('#rae-password').val('').focus();
        }
        
        return false; // Ensure form doesn't submit normally
    });
    
    // Prevent any normal form submission
    $('#rae-login-form').on('submit.prevent', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Auto-focus password field
    $('#rae-password').focus();
    
    // Handle Enter key press
    $('#rae-password').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#rae-login-form').trigger('submit');
            return false;
        }
    });
    
    // Prevent form from submitting normally on button click
    $('#rae-submit-btn').on('click', function(e) {
        e.preventDefault();
        $('#rae-login-form').trigger('submit');
        return false;
    });
    
    // Auto-remove error/success messages after 10 seconds
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('rae-error') || $(e.target).hasClass('rae-success')) {
            setTimeout(function() {
                $(e.target).fadeOut(500, function() {
                    $(this).remove();
                });
            }, 10000);
        }
    });
    
    // Add debug information
    console.log('RAE: Debug Mode - Current URL:', window.location.href);
    console.log('RAE: Debug Mode - AJAX settings available:', typeof rae_ajax !== 'undefined');
    
    if (typeof rae_ajax !== 'undefined') {
        console.log('RAE: AJAX URL:', rae_ajax.ajax_url);
        console.log('RAE: Home URL:', rae_ajax.home_url);
        console.log('RAE: Nonce:', rae_ajax.nonce);
    }
    
    // Test AJAX connectivity
    setTimeout(function() {
        console.log('RAE: Testing AJAX connectivity...');
        $.ajax({
            url: rae_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'heartbeat',
                _nonce: rae_ajax.nonce
            },
            success: function() {
                console.log('RAE: AJAX connectivity test successful');
            },
            error: function() {
                console.log('RAE: AJAX connectivity test failed');
            }
        });
    }, 1000);
    
}); 