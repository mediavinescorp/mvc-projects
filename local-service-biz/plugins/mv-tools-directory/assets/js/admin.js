/**
 * MV Tools Directory — Admin JS
 * Icon picker, feature rows, AJAX save, drag-to-reorder
 */
jQuery(function ($) {

    /* ════════════════════════════════════════
       ICON PICKER
    ════════════════════════════════════════ */
    $(document).on('click', '.mvtd-icon-option', function () {
        var icon  = $(this).data('icon');
        var label = $(this).data('label');
        $('.mvtd-icon-option').removeClass('selected');
        $(this).addClass('selected');
        $('#f-icon').val(icon);
        $('#mvtd-selected-icon-name').text(label);
    });

    // Icon search
    $('#mvtd-icon-search').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        if (!q) {
            $('.mvtd-icon-option').removeClass('hidden');
            $('.mvtd-icon-group').removeClass('all-hidden');
            return;
        }
        $('.mvtd-icon-group').each(function () {
            var anyVisible = false;
            $(this).find('.mvtd-icon-option').each(function () {
                var label = $(this).data('label').toLowerCase();
                var icon  = $(this).data('icon').toLowerCase();
                var match = label.includes(q) || icon.includes(q);
                $(this).toggleClass('hidden', !match);
                if (match) anyVisible = true;
            });
            $(this).toggleClass('all-hidden', !anyVisible);
        });
    });

    /* ════════════════════════════════════════
       CATEGORY RADIO HIGHLIGHT
    ════════════════════════════════════════ */
    $(document).on('change', '.mvtd-cat-option input', function () {
        $('.mvtd-cat-option').removeClass('selected');
        $(this).closest('.mvtd-cat-option').addClass('selected');
    });

    /* ════════════════════════════════════════
       BADGE COLOR RADIO HIGHLIGHT
    ════════════════════════════════════════ */
    $(document).on('change', '.mvtd-color-option input', function () {
        $('.mvtd-color-option').removeClass('selected');
        $(this).closest('.mvtd-color-option').addClass('selected');
    });

    /* ════════════════════════════════════════
       FEATURE ROWS
    ════════════════════════════════════════ */
    $('#mvtd-add-feature').on('click', function () {
        var row = $('<div class="mvtd-feature-row">' +
            '<input type="text" name="features[]" placeholder="e.g. Generates a unique QR code for every business">' +
            '<button type="button" class="mvtd-remove-feature" title="Remove">✕</button>' +
            '</div>');
        $('#mvtd-features-list').append(row);
        row.find('input').focus();
    });

    $(document).on('click', '.mvtd-remove-feature', function () {
        $(this).closest('.mvtd-feature-row').remove();
    });

    /* ════════════════════════════════════════
       AJAX FORM SAVE
    ════════════════════════════════════════ */
    $('#mvtd-tool-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#mvtd-save-btn');
        $btn.text('Saving...').prop('disabled', true);
        $('#mvtd-save-notice, #mvtd-error-notice').hide();

        var formData = new FormData(this);

        $.ajax({
            url: MVTD.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#mvtd-save-notice').show();
                    $('html, body').animate({ scrollTop: 0 }, 300);
                    // Update hidden original_id to new id (for new tools)
                    $('input[name="original_id"]').val(res.data.id);
                    $btn.text('Update Tool');
                } else {
                    $('#mvtd-error-msg').text(res.data.message || 'Something went wrong.');
                    $('#mvtd-error-notice').show();
                }
            },
            error: function () {
                $('#mvtd-error-msg').text('Connection error. Please try again.');
                $('#mvtd-error-notice').show();
            },
            complete: function () {
                $btn.prop('disabled', false);
                if ($btn.text() === 'Saving...') $btn.text('Update Tool');
            }
        });
    });

    /* ════════════════════════════════════════
       DRAG-TO-REORDER (tools list table)
    ════════════════════════════════════════ */
    var $sortable = $('#mvtd-sortable');
    if ($sortable.length && typeof $.fn.sortable !== 'undefined') {
        $sortable.sortable({
            handle: '.mvtd-drag-handle',
            axis: 'y',
            cursor: 'grabbing',
            placeholder: 'mvtd-sort-placeholder',
            update: function () {
                var order = [];
                $sortable.find('tr').each(function () {
                    order.push($(this).data('id'));
                });
                $.post(MVTD.ajax_url, {
                    action: 'mvtd_reorder_tools',
                    nonce:  MVTD.nonce,
                    order:  order,
                }, function (res) {
                    if (res.success) {
                        // Update order numbers in the UI
                        $sortable.find('tr').each(function (i) {
                            $(this).find('.mvtd-order-num').text(i + 1);
                        });
                    }
                });
            }
        });
    }

});
