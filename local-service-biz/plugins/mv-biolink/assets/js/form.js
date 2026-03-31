/**
 * MV BioLink Generator — Form JS
 * Fixed: explainer toggle, social handle preview, website auto-prefix, 3 link default
 */
(function ($) {
    'use strict';

    var MAX_LINKS = 10;
    var RECOMMENDED_LINKS = 3;

    $(document).ready(function () {

        /* ── STEP NAVIGATION ── */
        function goToStep(n) {
            $('#mvbl-wrap .mvbl-step-panel').removeClass('active');
            $('#mvbl-panel-' + n).addClass('active');
            $('#mvbl-wrap .mvbl-step').removeClass('active');
            $('#mvbl-wrap .mvbl-step[data-step="' + n + '"]').addClass('active');
            $('#mvbl-wrap .mvbl-step').each(function () {
                var s = parseInt($(this).data('step'));
                if (s < n) $(this).addClass('done');
                else $(this).removeClass('done');
            });
            var top = $('#mvbl-wrap').offset().top - 40;
            $('html, body').animate({ scrollTop: top }, 300);
        }

        $(document).on('click', '#mvbl-wrap .mvbl-next-btn', function (e) {
            e.preventDefault(); e.stopPropagation();
            goToStep(parseInt($(this).data('next')));
        });
        $(document).on('click', '#mvbl-wrap .mvbl-back-btn', function (e) {
            e.preventDefault(); e.stopPropagation();
            goToStep(parseInt($(this).data('back')));
        });
        $(document).on('click', '#mvbl-wrap .mvbl-step', function (e) {
            e.preventDefault(); e.stopPropagation();
            goToStep(parseInt($(this).data('step')));
        });

        /* ── AVATAR UPLOAD ── */
        $(document).on('change', '#mvbl-avatar-file', function () {
            var file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('Image too large. Please use a file under 5MB.');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#mvbl-avatar-preview').html('<img src="' + e.target.result + '" alt="Preview">');
                $('#mvbl-avatar-area').addClass('has-photo');
                $('#mvbl-avatar-area .mvbl-avatar-label').text('Photo selected ✓');
                $('#mvbl-remove-avatar').show();
            };
            reader.readAsDataURL(file);
        });

        $(document).on('click', '#mvbl-remove-avatar', function (e) {
            e.preventDefault(); e.stopPropagation();
            $('#mvbl-avatar-file').val('');
            $('#mvbl-avatar-preview').html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>');
            $('#mvbl-avatar-area').removeClass('has-photo');
            $('#mvbl-avatar-area .mvbl-avatar-label').text('Click to upload');
            $(this).hide();
        });

        /* ── SOCIAL HANDLE LIVE PREVIEW ── */
        $(document).on('input', '.mvbl-handle-input', function () {
            var val      = $(this).val().trim();
            var network  = $(this).data('network');
            var prefix   = $(this).data('prefix');
            var $preview = $('#mvbl-preview-' + network);
            if (!val) { $preview.text(''); return; }
            if (val.startsWith('http')) {
                $preview.text(val);
            } else {
                var handle = val.replace(/^@/, '');
                if (network === 'youtube' && handle.match(/^UC[a-zA-Z0-9_-]{22}$/)) {
                    $preview.text('https://www.youtube.com/channel/' + handle);
                } else {
                    $preview.text(prefix + handle);
                }
            }
        });

        /* ── WEBSITE AUTO-PREFIX ── */
        $(document).on('blur', '#mvbl-website', function () {
            var val = $(this).val().trim();
            if (val && !val.startsWith('http')) {
                $(this).val('https://' + val);
            }
        });

        /* ── STEP 2 EXPLAINER TOGGLE ── */
        $(document).on('click', '#mvbl-explainer-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $body    = $('#mvbl-explainer-body');
            var $chevron = $(this).find('.mvbl-explainer-chevron');
            var isOpen   = $body.is(':visible');
            if (isOpen) {
                $body.slideUp(200);
                $chevron.css('transform', 'rotate(0deg)');
            } else {
                $body.slideDown(200);
                $chevron.css('transform', 'rotate(180deg)');
            }
        });

        /* ── LAYOUT PICKER ── */
        $(document).on('click', '#mvbl-wrap .mvbl-layout-option', function (e) {
            e.stopPropagation();
            $('#mvbl-wrap .mvbl-layout-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
        });

        /* ── COLOR PRESETS ── */
        $(document).on('click', '#mvbl-wrap .mvbl-color-presets span', function (e) {
            e.stopPropagation();
            var color  = $(this).data('color');
            var target = $(this).closest('.mvbl-color-wrap').find('input[type="color"]');
            target.val(color).trigger('change');
        });

        /* ── LINKS ── */
        function getLinkIconOptions() {
            return $('#mvbl-links-list .mvbl-link-row:first select').html() || '';
        }

        function updateLinkCount() {
            var n = $('#mvbl-links-list .mvbl-link-row').length;
            $('#mvbl-links-count').text(n + ' / ' + MAX_LINKS + ' link' + (n !== 1 ? 's' : ''));
            $('#mvbl-add-link').toggle(n < MAX_LINKS);
        }

        // Start with 3 empty link rows (recommended)
        var existingRows = $('#mvbl-links-list .mvbl-link-row').length;
        for (var i = existingRows; i < RECOMMENDED_LINKS; i++) {
            addLinkRow();
        }
        updateLinkCount();

        function addLinkRow() {
            var opts = getLinkIconOptions();
            var row = $('<div class="mvbl-link-row"><div class="mvbl-link-row-inner">' +
                '<select name="link_icon[]" class="mvbl-link-icon-select">' + opts + '</select>' +
                '<input type="text" name="link_label[]" placeholder="Label  e.g. Book Appointment" class="mvbl-link-label">' +
                '<input type="url"  name="link_url[]"   placeholder="URL    e.g. https://..." class="mvbl-link-url">' +
                '<button type="button" class="mvbl-remove-link" title="Remove">✕</button>' +
                '</div></div>');
            $('#mvbl-links-list').append(row);
            updateLinkCount();
        }

        $(document).on('click', '#mvbl-add-link', function (e) {
            e.preventDefault(); e.stopPropagation();
            if ($('#mvbl-links-list .mvbl-link-row').length >= MAX_LINKS) return;
            addLinkRow();
            $('#mvbl-links-list .mvbl-link-row:last .mvbl-link-label').focus();
        });

        $(document).on('click', '#mvbl-wrap .mvbl-remove-link', function (e) {
            e.preventDefault(); e.stopPropagation();
            if ($('#mvbl-links-list .mvbl-link-row').length <= 1) return;
            $(this).closest('.mvbl-link-row').remove();
            updateLinkCount();
        });

        /* ── SUBMIT ── */
        $(document).on('click', '#mvbl-submit-btn', function (e) {
            e.preventDefault(); e.stopPropagation();
            $('#mvbl-error').hide();

            var name = $('#mvbl-full-name').val().trim();
            if (!name) {
                alert('Please enter your full name on Step 1.');
                goToStep(1);
                return;
            }

            // Build socials
            var socials = [];
            $('#mvbl-wrap .mvbl-social-input').each(function () {
                var val     = $(this).val().trim();
                var network = $(this).data('network');
                var type    = $(this).data('type');
                if (!val) return;
                var url = '';
                if (type === 'handle') {
                    var prefix = $(this).data('prefix');
                    if (val.startsWith('http')) {
                        url = val;
                    } else {
                        var handle = val.replace(/^@/, '');
                        if (network === 'youtube' && handle.match(/^UC[a-zA-Z0-9_-]{22}$/)) {
                            url = 'https://www.youtube.com/channel/' + handle;
                        } else {
                            url = prefix + handle;
                        }
                    }
                } else {
                    url = val.startsWith('http') ? val : 'https://' + val;
                }
                socials.push({ network: network, url: url, label: network });
            });

            // Build links — only non-empty rows
            var links = [];
            $('#mvbl-links-list .mvbl-link-row').each(function () {
                var label = $(this).find('[name="link_label[]"]').val().trim();
                var url   = $(this).find('[name="link_url[]"]').val().trim();
                var icon  = $(this).find('[name="link_icon[]"]').val();
                if (label && url) {
                    if (!url.startsWith('http')) url = 'https://' + url;
                    links.push({ label: label, url: url, icon: icon });
                }
            });

            var $btn = $(this);
            $btn.prop('disabled', true).html(
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:mvbl-spin .8s linear infinite">' +
                '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Building…'
            );

            $('#mvbl-form-state').hide();
            $('#mvbl-loading-state').show();

            var fd = new FormData();
            fd.append('action',       'mvbl_submit');
            fd.append('nonce',        MVBL.nonce);
            fd.append('full_name',    name);
            fd.append('job_title',    $('#mvbl-job-title').val().trim());
            fd.append('company',      $('#mvbl-company').val().trim());
            fd.append('bio',          $('#mvbl-bio').val().trim());
            fd.append('phone',        $('#mvbl-phone').val().trim());
            fd.append('email',        $('#mvbl-email').val().trim());
            fd.append('website',      $('#mvbl-website').val().trim());
            fd.append('address',      $('#mvbl-address').val().trim());
            fd.append('layout',       $('input[name="layout"]:checked').val() || 'layout-1');
            fd.append('bg_color',     $('#mvbl-bg-color').val());
            fd.append('accent_color', $('#mvbl-accent-color').val());
            fd.append('text_color',   $('#mvbl-text-color').val());
            fd.append('socials',      JSON.stringify(socials));
            fd.append('links',        JSON.stringify(links));

            var avatarFile = $('#mvbl-avatar-file')[0].files[0];
            if (avatarFile) fd.append('avatar', avatarFile);

            $.ajax({
                url:         MVBL.ajax_url,
                type:        'POST',
                data:        fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        window.location.href = res.data.page_url;
                    } else {
                        $('#mvbl-loading-state').hide();
                        $('#mvbl-form-state').show();
                        $('#mvbl-error').text(res.data.message || 'Something went wrong.').show();
                        goToStep(3);
                        $btn.prop('disabled', false).html(
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg> Generate My Bio Page'
                        );
                    }
                },
                error: function () {
                    $('#mvbl-loading-state').hide();
                    $('#mvbl-form-state').show();
                    $('#mvbl-error').text('Connection error. Please try again.').show();
                    goToStep(3);
                    $btn.prop('disabled', false).html(
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg> Generate My Bio Page'
                    );
                }
            });
        });

    });

}(jQuery));
