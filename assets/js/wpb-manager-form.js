jQuery(document).ready(function($) {
    'use strict';

    // Initialize Select2 for product selection
    $('#ix-wpb-selected-products').select2({
        ajax: {
            url: ix_wpb_manager_form.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    action: 'ix_wpb_search_products',
                    nonce: ix_wpb_manager_form.nonce
                };
            },
            processResults: function(data) {
                if (!data.success) {
                    console.error('AJAX error:', data.data);
                    return { results: [] };
                }
                
                return {
                    results: data.data.map(function(product) {
                        return {
                            id: product.id,
                            text: product.text
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: ix_wpb_manager_form.i18n.select_products,
        minimumInputLength: 2,
        width: '100%'
    });

    // Handle form submission
   $('#ix-wpb-manager-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('.ix-wpb-form-message');
        var $submit = $form.find('button[type="submit"]');
        
        $message.removeClass('success error').html('');
        $submit.prop('disabled', true).text(ix_wpb_manager_form.i18n.saving);
        
        $.ajax({
            url: ix_wpb_manager_form.ajaxurl,
            type: 'POST',
            data: {
                action: 'ix_wpb_save_manager_settings',
                nonce: ix_wpb_manager_form.nonce,
                image_source: $form.find('#ix-wpb-image-source').val(),
                image_size: $form.find('#ix-wpb-image-size').val(),
                selected_products: $form.find('#ix-wpb-selected-products').val() || []
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.data.message);
                } else {
                    $message.addClass('error').text(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $message.addClass('error').text(ix_wpb_manager_form.i18n.error);
                console.error(error);
            },
            complete: function() {
                $submit.prop('disabled', false).text(ix_wpb_manager_form.i18n.save);
            }
        });
    });
});