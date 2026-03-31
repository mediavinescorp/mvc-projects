/**
 * MV Review Generator — Frontend Form JS
 * Handles: logo preview, how-to toggle, validation, AJAX submit, share actions
 */
(function ($) {
    'use strict';

    var currentPageUrl = '';
    var currentBizName = '';

    $(document).ready(function () {

        /* ── Logo upload preview ── */
        $(document).on('change', '#mvrg-logo-file', function () {
            var file = this.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                showError('Logo file is too large. Please use an image under 5MB.');
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                $('#mvrg-preview-img').attr('src', e.target.result);
                $('#mvrg-preview-row').addClass('show');
                $('#mvrg-upload-area').addClass('has-logo');
                $('#mvrg-upload-area .mvrg-upload-text').text('Logo selected ✓');
            };
            reader.readAsDataURL(file);
        });

        /* ── Remove logo ── */
        $(document).on('click', '#mvrg-remove-logo', function () {
            $('#mvrg-logo-file').val('');
            $('#mvrg-preview-row').removeClass('show');
            $('#mvrg-upload-area').removeClass('has-logo');
            $('#mvrg-upload-area .mvrg-upload-text').text('Click to upload your logo');
        });

        /* ── How-to toggle ── */
        $(document).on('click', '#mvrg-help-toggle', function () {
            var $howto = $('#mvrg-howto');
            var isVisible = $howto.is(':visible');
            $howto.slideToggle(200);
            $(this).text(isVisible ? 'How to get it?' : 'Hide instructions');
        });

        /* ── Submit ── */
        $(document).on('click', '#mvrg-submit-btn', function () {
            hideError();

            var bizName    = $('#mvrg-biz-name').val().trim();
            var reviewLink = $('#mvrg-review-link').val().trim();

            // Client-side validation
            var errors = [];
            if (!bizName) {
                errors.push('Please enter your business name.');
            }
            if (!reviewLink) {
                errors.push('Please enter your Google Review link.');
            } else if (!reviewLink.startsWith('http')) {
                errors.push('Please enter a valid URL starting with https://');
            }

            if (errors.length) {
                showError(errors.join(' '));
                return;
            }

            currentBizName = bizName;

            // Build FormData (supports file upload)
            var formData = new FormData();
            formData.append('action',      'mvrg_submit');
            formData.append('nonce',       MVRG.nonce);
            formData.append('biz_name',    bizName);
            formData.append('review_link', reviewLink);
            formData.append('tagline',     $('#mvrg-tagline').val().trim());
            formData.append('industry',    $('#mvrg-industry').val());

            var logoFile = $('#mvrg-logo-file')[0].files[0];
            if (logoFile) {
                formData.append('logo', logoFile);
            }

            // Show loading state
            showState('loading');

            $.ajax({
                url:         MVRG.ajax_url,
                type:        'POST',
                data:        formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        window.location.href = response.data.page_url;
                    } else {
                        showState('form');
                        showError(response.data.message || 'Something went wrong. Please try again.');
                    }
                },
                error: function () {
                    showState('form');
                    showError('Connection error. Please check your internet and try again.');
                }
            });
        });

        /* ── Copy URL ── */
        $(document).on('click', '#mvrg-copy-btn', function () {
            if (!currentPageUrl) return;
            var $btn = $(this);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(currentPageUrl).then(function () {
                    $btn.text('Copied!');
                    setTimeout(function () { $btn.text('Copy'); }, 2000);
                });
            } else {
                // Fallback
                var $tmp = $('<input>').val(currentPageUrl).appendTo('body').select();
                document.execCommand('copy');
                $tmp.remove();
                $btn.text('Copied!');
                setTimeout(function () { $btn.text('Copy'); }, 2000);
            }
        });

        /* ── Share via Email ── */
        $(document).on('click', '#mvrg-share-email-btn', function () {
            if (!currentPageUrl || !currentBizName) return;
            var subject = encodeURIComponent("We'd love your review – " + currentBizName);
            var body    = encodeURIComponent(
                "Hi there,\n\nThank you so much for choosing " + currentBizName + "!\n\n" +
                "We'd be grateful if you could leave us a quick Google review:\n" +
                currentPageUrl + "\n\nThank you!\n" + currentBizName
            );
            window.open('mailto:?subject=' + subject + '&body=' + body);
        });

        /* ── Share via Text ── */
        $(document).on('click', '#mvrg-share-sms-btn', function () {
            if (!currentPageUrl || !currentBizName) return;
            var msg = encodeURIComponent(
                "Hi! Thank you for choosing " + currentBizName +
                ". We'd love a quick Google review: " + currentPageUrl
            );
            window.open('sms:?body=' + msg);
        });

        /* ── Create another page ── */
        $(document).on('click', '#mvrg-new-btn', function () {
            // Reset form
            $('#mvrg-biz-name').val('');
            $('#mvrg-review-link').val('');
            $('#mvrg-tagline').val('');
            $('#mvrg-industry').val('');
            $('#mvrg-logo-file').val('');
            $('#mvrg-preview-row').removeClass('show');
            $('#mvrg-upload-area').removeClass('has-logo');
            $('#mvrg-upload-area .mvrg-upload-text').text('Click to upload your logo');
            $('#mvrg-howto').hide();
            $('#mvrg-help-toggle').text('How to get it?');
            hideError();
            currentPageUrl = '';
            currentBizName = '';
            showState('form');
        });

        /* ── Helpers ── */
        function showState(state) {
            $('#mvrg-form-state').toggle(state === 'form');
            $('#mvrg-loading-state').toggle(state === 'loading');
            $('#mvrg-success-state').toggle(state === 'success');
        }

        function showError(msg) {
            $('#mvrg-error').text(msg).show();
            // Scroll to error
            var $error = $('#mvrg-error');
            if ($error.length) {
                $('html, body').animate({ scrollTop: $error.offset().top - 80 }, 300);
            }
        }

        function hideError() {
            $('#mvrg-error').hide().text('');
        }
    });

}(jQuery));
