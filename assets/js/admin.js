jQuery(document).ready(function($) {
    if ($('#ix_wpb_selected_products').length) {
        $('#ix_wpb_selected_products').select2({
            ajax: {
                url: ix_wpb_admin.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        term: params.term,
                        action: 'ix_wpb_search_products',
                        security: ix_wpb_admin.search_nonce
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: ix_wpb_admin.placeholder_text,
            width: '50%',
            allowClear: true
        });

        // Pre-populate selected products
        if (ix_wpb_admin.selected_products && ix_wpb_admin.selected_products.length) {
            var data = {
                results: ix_wpb_admin.selected_products
            };
            $('#ix_wpb_selected_products').select2('data', data.results);
        }
    }
});