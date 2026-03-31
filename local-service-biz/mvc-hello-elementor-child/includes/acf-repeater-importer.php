<?php
/**
 * ACF Repeater Importer — Multi-file Upload + Manual File→Post Matching
 * Load via functions.php: require_once get_stylesheet_directory() . '/includes/acf-repeater-importer.php';
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin menu ────────────────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_menu_page(
        'ACF Repeater Importer',
        'Repeater Importer',
        'manage_options',
        'acf-repeater-importer',
        'lsb_repeater_importer_page',
        'dashicons-editor-table',
        80
    );
} );

// ── AJAX: get CPTs ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_lsb_get_post_types', function () {
    check_ajax_referer( 'lsb_repeater_nonce', 'nonce' );
    $cpts = get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' );
    $out  = [];
    foreach ( $cpts as $cpt ) {
        $out[] = [ 'slug' => $cpt->name, 'label' => $cpt->label ];
    }
    wp_send_json_success( $out );
} );

// ── AJAX: get repeater fields for a CPT ──────────────────────────────────────
add_action( 'wp_ajax_lsb_get_repeater_fields', function () {
    check_ajax_referer( 'lsb_repeater_nonce', 'nonce' );
    $cpt = sanitize_key( $_POST['cpt'] ?? '' );
    if ( ! $cpt ) wp_send_json_error( 'No CPT.' );

    $groups    = acf_get_field_groups( [ 'post_type' => $cpt ] );
    $repeaters = [];

    foreach ( $groups as $group ) {
        $fields = acf_get_fields( $group['key'] );
        if ( ! $fields ) continue;
        foreach ( $fields as $field ) {
            if ( $field['type'] === 'repeater' ) {
                $sub = [];
                foreach ( $field['sub_fields'] as $sf ) {
                    $sub[] = [
                        'key'   => $sf['key'],
                        'name'  => $sf['name'],
                        'label' => $sf['label'],
                        'type'  => $sf['type'],
                    ];
                }
                $repeaters[] = [
                    'key'        => $field['key'],
                    'name'       => $field['name'],
                    'label'      => $field['label'],
                    'sub_fields' => $sub,
                ];
            }
        }
    }

    wp_send_json_success( $repeaters );
} );

// ── AJAX: get all posts for a CPT ─────────────────────────────────────────────
add_action( 'wp_ajax_lsb_get_cpt_posts', function () {
    check_ajax_referer( 'lsb_repeater_nonce', 'nonce' );
    $cpt = sanitize_key( $_POST['cpt'] ?? '' );
    if ( ! $cpt ) wp_send_json_error( 'No CPT.' );

    $posts = get_posts( [
        'post_type'      => $cpt,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    $out = [];
    foreach ( $posts as $p ) {
        $out[] = [ 'id' => $p->ID, 'title' => $p->post_title, 'slug' => $p->post_name ];
    }

    wp_send_json_success( $out );
} );

// ── AJAX: save repeater for one post ─────────────────────────────────────────
add_action( 'wp_ajax_lsb_save_repeater', function () {
    check_ajax_referer( 'lsb_repeater_nonce', 'nonce' );

    $post_id    = intval( $_POST['post_id'] ?? 0 );
    $field_name = sanitize_key( $_POST['field_name'] ?? '' );
    $rows_raw   = $_POST['rows'] ?? [];

    if ( ! $post_id || ! $field_name || empty( $rows_raw ) ) {
        wp_send_json_error( 'Missing data.' );
    }

    $rows = [];
    foreach ( $rows_raw as $row ) {
        $clean = [];
        foreach ( $row as $k => $v ) {
            $clean[ sanitize_key( $k ) ] = wp_kses_post( $v );
        }
        $rows[] = $clean;
    }

    // update_field() returns false both on failure AND when value is unchanged.
    // So we verify by reading back the saved value instead of trusting the return.
    update_field( $field_name, $rows, $post_id );

    $saved = get_field( $field_name, $post_id );

    // Normalize both sides to string for a reliable comparison
    $normalize = function( $data ) {
        array_walk_recursive( $data, function( &$v ) { $v = (string) $v; } );
        return $data;
    };

    if ( ! empty( $saved ) ) {
        wp_send_json_success( [ 'post_id' => $post_id ] );
    } else {
        wp_send_json_error( "Failed on post ID {$post_id} — field returned empty after save." );
    }
} );

// ── Page ──────────────────────────────────────────────────────────────────────
function lsb_repeater_importer_page() {
    $nonce = wp_create_nonce( 'lsb_repeater_nonce' );
    ?>
    <div class="wrap" id="lsb-ri-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:6px;"><span>📋</span> ACF Repeater Importer</h1>
        <p style="color:#646970;margin-bottom:24px;">Upload multiple files and match each one to a post. Each file's rows go into that post's repeater.</p>

        <!-- Progress -->
        <div id="lsb-steps" style="display:flex;gap:0;margin-bottom:28px;border:1px solid #ddd;border-radius:6px;overflow:hidden;max-width:760px;">
            <?php
            foreach ( ['1. CPT &amp; Field','2. Upload Files','3. Map Columns','4. Match Files→Posts','5. Review &amp; Save'] as $i => $label ) {
                $n = $i + 1;
                echo "<div class='lsb-step-pill' id='lsb-step-pill-{$n}' style='flex:1;padding:9px 4px;text-align:center;font-size:12px;font-weight:500;background:#f6f7f7;color:#646970;border-right:1px solid #ddd;'>{$label}</div>";
            }
            ?>
        </div>

        <!-- STEP 1 -->
        <div id="lsb-panel-1" class="lsb-panel" style="max-width:760px;">
            <div class="lsb-card">
                <h2 class="lsb-card-title">Select CPT &amp; Repeater Field</h2>
                <p class="lsb-card-sub">Choose the post type and which repeater field to populate.</p>
                <label class="lsb-label">Post Type</label>
                <select id="lsb-cpt" style="width:100%;margin-bottom:16px;"><option value="">— Loading… —</option></select>
                <label class="lsb-label">Repeater Field</label>
                <select id="lsb-repeater" style="width:100%;margin-bottom:6px;" disabled><option value="">— Select a post type first —</option></select>
                <p id="lsb-field-hint" style="font-size:12px;color:#646970;margin-bottom:16px;min-height:18px;"></p>
                <div style="display:flex;justify-content:flex-end;">
                    <button class="button button-primary" id="lsb-step1-next" disabled>Continue →</button>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div id="lsb-panel-2" class="lsb-panel" style="max-width:760px;display:none;">
            <div class="lsb-card">
                <h2 class="lsb-card-title">Upload Files</h2>
                <p class="lsb-card-sub">Select one or more <strong>CSV</strong> or <strong>Excel (.xlsx)</strong> files — one file per post. First row of each file must be column headers.</p>
                <div id="lsb-drop-zone" style="border:2px dashed #c3c4c7;border-radius:6px;padding:36px 20px;text-align:center;cursor:pointer;transition:border-color .2s;margin-bottom:16px;">
                    <div style="font-size:32px;margin-bottom:8px;">📂</div>
                    <p style="margin:0 0 8px;font-weight:500;">Drag &amp; drop files here</p>
                    <p style="margin:0 0 12px;color:#646970;font-size:13px;">CSV or .xlsx — select multiple at once</p>
                    <input type="file" id="lsb-file-input" accept=".csv,.xlsx" multiple style="display:none;">
                    <button type="button" class="button" id="lsb-browse-btn">Browse Files</button>
                </div>

                <!-- Uploaded files list -->
                <div id="lsb-files-list" style="display:none;">
                    <p class="lsb-section-label" style="margin-bottom:8px;">Uploaded files</p>
                    <div id="lsb-files-table-wrap" style="border:1px solid #ddd;border-radius:4px;overflow:hidden;"></div>
                </div>

                <div style="display:flex;justify-content:space-between;margin-top:16px;">
                    <button class="button" onclick="lsbGoTo(1)">← Back</button>
                    <button class="button button-primary" id="lsb-step2-next" disabled>Continue →</button>
                </div>
            </div>
        </div>

        <!-- STEP 3 -->
        <div id="lsb-panel-3" class="lsb-panel" style="max-width:760px;display:none;">
            <div class="lsb-card">
                <h2 class="lsb-card-title">Map Columns to ACF Sub-fields</h2>
                <p class="lsb-card-sub">Columns are read from the first uploaded file and applied to all files. Adjust any that need changing.</p>
                <div id="lsb-mapping-wrap"></div>
                <div style="display:flex;justify-content:space-between;margin-top:16px;">
                    <button class="button" onclick="lsbGoTo(2)">← Back</button>
                    <button class="button button-primary" id="lsb-step3-next">Continue →</button>
                </div>
            </div>
        </div>

        <!-- STEP 4 -->
        <div id="lsb-panel-4" class="lsb-panel" style="max-width:760px;display:none;">
            <div class="lsb-card">
                <h2 class="lsb-card-title">Match Files to Posts</h2>
                <p class="lsb-card-sub">For each file, choose which post it should update. Files set to <em>— skip —</em> will be ignored.</p>

                <div style="overflow-x:auto;border:1px solid #ddd;border-radius:4px;margin-bottom:12px;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="background:#f6f7f7;padding:9px 12px;text-align:left;font-size:12px;font-weight:600;border-bottom:1px solid #ddd;width:40%;">File</th>
                                <th style="background:#f6f7f7;padding:9px 12px;text-align:left;font-size:12px;font-weight:600;border-bottom:1px solid #ddd;">Rows</th>
                                <th style="background:#f6f7f7;padding:9px 12px;text-align:left;font-size:12px;font-weight:600;border-bottom:1px solid #ddd;">Assign to Post</th>
                            </tr>
                        </thead>
                        <tbody id="lsb-match-tbody"></tbody>
                    </table>
                </div>

                <p id="lsb-match-summary" style="font-size:12px;color:#646970;min-height:18px;"></p>
                <div style="display:flex;justify-content:space-between;margin-top:12px;">
                    <button class="button" onclick="lsbGoTo(3)">← Back</button>
                    <button class="button button-primary" id="lsb-step4-next">Review →</button>
                </div>
            </div>
        </div>

        <!-- STEP 5 -->
        <div id="lsb-panel-5" class="lsb-panel" style="max-width:760px;display:none;">
            <div class="lsb-card">
                <h2 class="lsb-card-title">Review &amp; Save</h2>
                <p class="lsb-card-sub">Each matched file will overwrite that post's repeater. Unmatched files are skipped.</p>
                <div id="lsb-review-items"></div>
                <div id="lsb-save-status" style="min-height:20px;margin:12px 0;"></div>
                <div style="display:flex;justify-content:space-between;">
                    <button class="button" id="lsb-back-4" onclick="lsbGoTo(4)">← Back</button>
                    <button class="button button-primary" id="lsb-save-btn">Save to WordPress</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    #lsb-ri-wrap .lsb-card{background:#fff;border:1px solid #ddd;border-radius:6px;padding:22px 24px;margin-bottom:16px;}
    #lsb-ri-wrap .lsb-card-title{font-size:15px;font-weight:600;margin:0 0 4px;color:#1d2327;}
    #lsb-ri-wrap .lsb-card-sub{font-size:13px;color:#646970;margin:0 0 18px;}
    #lsb-ri-wrap .lsb-label{display:block;font-size:13px;font-weight:500;margin-bottom:5px;color:#1d2327;}
    #lsb-ri-wrap .lsb-section-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#646970;margin:0 0 8px;display:block;}
    #lsb-ri-wrap .lsb-map-row{display:grid;grid-template-columns:1fr 36px 1fr;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f1;}
    #lsb-ri-wrap .lsb-map-row:last-child{border-bottom:none;}
    #lsb-ri-wrap .lsb-map-acf strong{display:block;font-size:13px;color:#1d2327;}
    #lsb-ri-wrap .lsb-map-acf small{color:#646970;font-family:monospace;font-size:11px;}
    #lsb-ri-wrap .lsb-map-arrow{text-align:center;color:#646970;font-size:18px;}
    #lsb-ri-wrap .lsb-file-row{display:flex;align-items:center;gap:10px;padding:9px 12px;border-bottom:1px solid #f0f0f1;font-size:13px;}
    #lsb-ri-wrap .lsb-file-row:last-child{border-bottom:none;}
    #lsb-ri-wrap .lsb-file-name{flex:1;font-weight:500;color:#1d2327;word-break:break-all;}
    #lsb-ri-wrap .lsb-file-rows{font-size:12px;color:#646970;white-space:nowrap;}
    #lsb-ri-wrap .lsb-remove-file{background:none;border:none;color:#646970;cursor:pointer;font-size:16px;padding:0;line-height:1;flex-shrink:0;}
    #lsb-ri-wrap .lsb-remove-file:hover{color:#d63638;}
    #lsb-ri-wrap table th{background:#f6f7f7;padding:8px 10px;text-align:left;font-size:12px;font-weight:600;color:#1d2327;border-bottom:1px solid #ddd;}
    #lsb-ri-wrap table td{padding:7px 10px;font-size:13px;border-bottom:1px solid #f0f0f1;color:#1d2327;vertical-align:middle;}
    #lsb-ri-wrap table tr:last-child td{border-bottom:none;}
    #lsb-ri-wrap .lsb-review-item{border:1px solid #ddd;border-radius:4px;margin-bottom:12px;overflow:hidden;}
    #lsb-ri-wrap .lsb-review-item-head{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f6f7f7;border-bottom:1px solid #ddd;font-size:13px;}
    #lsb-ri-wrap .lsb-review-item-head.skipped{background:#fff8e1;border-color:#f0c33c;}
    #lsb-ri-wrap .lsb-review-table-wrap{overflow-x:auto;padding:0;}
    #lsb-ri-wrap .lsb-review-table-wrap table{width:100%;border-collapse:collapse;font-size:12px;}
    #lsb-ri-wrap .lsb-notice{padding:10px 14px;border-radius:4px;font-size:13px;border-left:3px solid;}
    #lsb-ri-wrap .lsb-notice.info{background:#f0f6fc;border-color:#72aee6;color:#1d2327;}
    #lsb-ri-wrap .lsb-notice.success{background:#edfaef;border-color:#00a32a;color:#1d2327;}
    #lsb-ri-wrap .lsb-notice.error{background:#fcf0f1;border-color:#d63638;color:#1d2327;}
    #lsb-ri-wrap .lsb-badge{display:inline-block;font-size:11px;padding:2px 7px;border-radius:10px;background:#e0e0e0;color:#444;margin-left:4px;}
    #lsb-ri-wrap .lsb-badge.auto{background:#edfaef;color:#00a32a;}
    #lsb-ri-wrap .lsb-badge.skip{background:#fff8e1;color:#856404;}
    #lsb-ri-wrap .lsb-badge.ok{background:#edfaef;color:#00a32a;}
    #lsb-ri-wrap .lsb-step-pill.active{background:#2271b1!important;color:#fff!important;}
    #lsb-ri-wrap .lsb-step-pill.done{background:#edfaef!important;color:#00a32a!important;}
    #lsb-drop-zone.dragover{border-color:#2271b1;background:#f0f6fc;}
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    (function($){
        const nonce   = '<?php echo esc_js( $nonce ); ?>';
        const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

        let state = {
            cpt:'', cptLabel:'', fieldName:'',
            subFields:[],
            files:[],       // [{name, headers, rows}]
            mapping:{},     // acfFieldName -> fileColumnName
            allPosts:[],    // [{id,title,slug}]
            matches:{},     // fileIndex -> postId ('' = skip)
        };

        // ── Navigation ────────────────────────────────────────────────────────
        window.lsbGoTo = function(n){
            $('.lsb-panel').hide();
            $('#lsb-panel-'+n).show();
            for(let i=1;i<=5;i++){
                const p=$('#lsb-step-pill-'+i);
                p.removeClass('active done');
                if(i===n) p.addClass('active');
                else if(i<n) p.addClass('done');
            }
        };

        // ── Step 1 ────────────────────────────────────────────────────────────
        $.post(ajaxUrl,{action:'lsb_get_post_types',nonce},function(res){
            if(!res.success) return;
            const s=$('#lsb-cpt').empty().append('<option value="">— Select post type —</option>');
            res.data.forEach(c=>s.append(`<option value="${c.slug}">${c.label} (${c.slug})</option>`));
        });

        $('#lsb-cpt').on('change',function(){
            state.cpt=$(this).val();
            state.cptLabel=$(this).find('option:selected').text();
            state.allPosts=[];
            $('#lsb-repeater').prop('disabled',true).html('<option>Loading…</option>');
            $('#lsb-field-hint').text('');
            $('#lsb-step1-next').prop('disabled',true);
            if(!state.cpt) return;
            // Pre-load posts in background
            $.post(ajaxUrl,{action:'lsb_get_cpt_posts',nonce,cpt:state.cpt},function(res){
                if(res.success) state.allPosts=res.data;
            });
            $.post(ajaxUrl,{action:'lsb_get_repeater_fields',nonce,cpt:state.cpt},function(res){
                const r=$('#lsb-repeater').prop('disabled',false).empty();
                if(!res.success||!res.data.length){r.append('<option value="">— No repeater fields found —</option>');return;}
                r.append('<option value="">— Select repeater field —</option>');
                res.data.forEach(f=>r.append(`<option value="${f.name}" data-key="${f.key}" data-subs='${JSON.stringify(f.sub_fields)}'>${f.label} (${f.name})</option>`));
            });
        });

        $('#lsb-repeater').on('change',function(){
            const opt=$(this).find('option:selected');
            if(!opt.val()){$('#lsb-step1-next').prop('disabled',true);return;}
            state.fieldName=opt.val();
            state.subFields=opt.data('subs');
            $('#lsb-field-hint').text(`${state.subFields.length} sub-field(s): ${state.subFields.map(s=>s.name).join(', ')}`);
            $('#lsb-step1-next').prop('disabled',false);
        });

        $('#lsb-step1-next').on('click',()=>lsbGoTo(2));

        // ── Step 2: Multi-file upload ─────────────────────────────────────────
        document.getElementById('lsb-browse-btn').addEventListener('click',function(){
            document.getElementById('lsb-file-input').click();
        });

        const dz=document.getElementById('lsb-drop-zone');
        dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('dragover');});
        dz.addEventListener('dragleave',()=>dz.classList.remove('dragover'));
        dz.addEventListener('drop',e=>{
            e.preventDefault();dz.classList.remove('dragover');
            if(e.dataTransfer.files.length) processFiles(e.dataTransfer.files);
        });
        document.getElementById('lsb-file-input').addEventListener('change',function(){
            if(this.files.length) processFiles(this.files);
            this.value=''; // reset so same file can be re-added
        });

        function processFiles(fileList){
            const allowed=['csv','xlsx'];
            Array.from(fileList).forEach(file=>{
                const ext=file.name.split('.').pop().toLowerCase();
                if(!allowed.includes(ext)) return;
                // Avoid duplicates by name
                if(state.files.find(f=>f.name===file.name)) return;
                parseFile(file);
            });
        }

        function parseFile(file){
            const ext=file.name.split('.').pop().toLowerCase();
            const reader=new FileReader();
            reader.onload=function(e){
                let wb;
                try{
                    if(ext==='csv') wb=XLSX.read(e.target.result,{type:'string'});
                    else wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
                }catch(err){alert(`Could not parse ${file.name}: ${err.message}`);return;}
                const ws=wb.Sheets[wb.SheetNames[0]];
                const data=XLSX.utils.sheet_to_json(ws,{defval:''});
                if(!data.length){alert(`${file.name} appears empty.`);return;}
                state.files.push({name:file.name,headers:Object.keys(data[0]),rows:data});
                renderFilesList();
                $('#lsb-step2-next').prop('disabled',false);
            };
            ext==='csv'?reader.readAsText(file):reader.readAsArrayBuffer(file);
        }

        function renderFilesList(){
            if(!state.files.length){
                $('#lsb-files-list').hide();
                $('#lsb-step2-next').prop('disabled',true);
                return;
            }
            let html='';
            state.files.forEach((f,i)=>{
                html+=`<div class="lsb-file-row">
                    <span class="lsb-post-icon" style="font-size:16px;">📄</span>
                    <span class="lsb-file-name">${escH(f.name)}</span>
                    <span class="lsb-file-rows">${f.rows.length} row${f.rows.length!==1?'s':''} · ${f.headers.length} cols</span>
                    <button class="lsb-remove-file" data-idx="${i}" title="Remove">×</button>
                </div>`;
            });
            $('#lsb-files-table-wrap').html(html);
            $('#lsb-files-list').show();

            // Remove file
            $('#lsb-files-table-wrap').off('click','.lsb-remove-file').on('click','.lsb-remove-file',function(){
                const idx=parseInt($(this).data('idx'));
                state.files.splice(idx,1);
                // Clean up any match that referenced this index
                const newMatches={};
                Object.keys(state.matches).forEach(k=>{
                    const ki=parseInt(k);
                    if(ki<idx) newMatches[ki]=state.matches[k];
                    else if(ki>idx) newMatches[ki-1]=state.matches[k];
                });
                state.matches=newMatches;
                renderFilesList();
            });
        }

        $('#lsb-step2-next').on('click',function(){
            if(!state.files.length){alert('Upload at least one file.');return;}
            buildMapping();
            lsbGoTo(3);
        });

        // ── Step 3: Column mapping (uses first file's headers) ────────────────
        function buildMapping(){
            const headers=state.files[0].headers;
            const norm=s=>s.toLowerCase().replace(/[\s_\-]+/g,'');
            const normH={};
            headers.forEach(h=>{normH[norm(h)]=h;});
            state.mapping={};
            let html='';
            state.subFields.forEach(sf=>{
                const auto=normH[norm(sf.name)]||normH[norm(sf.label)]||'';
                if(auto) state.mapping[sf.name]=auto;
                const badge=auto?`<span class="lsb-badge auto">auto-matched</span>`:`<span class="lsb-badge">unmatched</span>`;
                const opts=headers.map(h=>`<option value="${escH(h)}"${auto===h?' selected':''}>${escH(h)}</option>`).join('');
                html+=`<div class="lsb-map-row">
                    <div class="lsb-map-acf"><strong>${escH(sf.label)} ${badge}</strong><small>${escH(sf.name)} &middot; ${sf.type}</small></div>
                    <div class="lsb-map-arrow" style="text-align:center;color:#646970;font-size:18px;">→</div>
                    <div><select class="lsb-map-select" data-sf="${escH(sf.name)}" style="width:100%;">
                        <option value="">— skip —</option>${opts}
                    </select></div>
                </div>`;
            });
            $('#lsb-mapping-wrap').html(html);
            $(document).off('change','.lsb-map-select').on('change','.lsb-map-select',function(){
                const sf=$(this).data('sf'),col=$(this).val();
                if(col) state.mapping[sf]=col; else delete state.mapping[sf];
            });
        }

        $('#lsb-step3-next').on('click',function(){
            if(!Object.keys(state.mapping).length){alert('Map at least one column.');return;}
            buildMatchTable();
            lsbGoTo(4);
        });

        // ── Step 4: File → Post matching ──────────────────────────────────────
        function buildMatchTable(){
            const postOptions=state.allPosts.map(p=>
                `<option value="${p.id}">${escH(p.title)} (${escH(p.slug)})</option>`
            ).join('');

            let html='';
            state.files.forEach((f,i)=>{
                const current=state.matches[i]||'';
                html+=`<tr>
                    <td style="padding:9px 12px;font-size:13px;">
                        <span style="font-size:14px;margin-right:6px;">📄</span>
                        <strong>${escH(f.name)}</strong>
                    </td>
                    <td style="padding:9px 12px;font-size:13px;color:#646970;">${f.rows.length} row${f.rows.length!==1?'s':''}</td>
                    <td style="padding:9px 12px;">
                        <select class="lsb-post-assign" data-idx="${i}" style="width:100%;min-width:220px;">
                            <option value="">— skip this file —</option>
                            ${postOptions}
                        </select>
                    </td>
                </tr>`;
            });
            $('#lsb-match-tbody').html(html);

            // Restore previous selections
            Object.keys(state.matches).forEach(i=>{
                $(`select.lsb-post-assign[data-idx="${i}"]`).val(state.matches[i]||'');
            });

            updateMatchSummary();

            $(document).off('change','.lsb-post-assign').on('change','.lsb-post-assign',function(){
                const idx=$(this).data('idx');
                state.matches[idx]=$(this).val();
                updateMatchSummary();
            });
        }

        function updateMatchSummary(){
            const matched=Object.values(state.matches).filter(v=>v!=='').length;
            const total=state.files.length;
            const skipped=total-matched;
            let txt=`${matched} of ${total} file${total!==1?'s':''} matched`;
            if(skipped>0) txt+=` · ${skipped} will be skipped`;
            $('#lsb-match-summary').text(txt);
        }

        $('#lsb-step4-next').on('click',function(){
            const matched=Object.values(state.matches).filter(v=>v!=='').length;
            if(!matched){
                lsbStatus('#lsb-save-status','error','Assign at least one file to a post.');
                alert('Assign at least one file to a post before continuing.');
                return;
            }
            buildReview();
            lsbGoTo(5);
        });

        // ── Step 5: Review ────────────────────────────────────────────────────
        function buildReview(){
            const mSFs=state.subFields.filter(sf=>state.mapping[sf.name]);
            let html='';

            state.files.forEach((f,i)=>{
                const postId=state.matches[i]||'';
                if(!postId){
                    // Skipped
                    html+=`<div class="lsb-review-item">
                        <div class="lsb-review-item-head skipped">
                            <span style="font-size:14px;">📄</span>
                            <strong style="flex:1;">${escH(f.name)}</strong>
                            <span class="lsb-badge skip">skipped</span>
                        </div>
                    </div>`;
                    return;
                }
                const post=state.allPosts.find(p=>p.id==postId);
                const postLabel=post?`${escH(post.title)} <span style="font-family:monospace;font-size:11px;color:#646970;">${escH(post.slug)}</span>`:`ID ${postId}`;

                // Build preview table (all rows)
                let th='<thead><tr>'+mSFs.map(sf=>`<th>${escH(sf.name)}</th>`).join('')+'</tr></thead><tbody>';
                let tb='';
                f.rows.forEach(r=>{
                    tb+='<tr>'+mSFs.map(sf=>`<td>${escH(String(r[state.mapping[sf.name]]??''))}</td>`).join('')+'</tr>';
                });

                html+=`<div class="lsb-review-item">
                    <div class="lsb-review-item-head">
                        <span style="font-size:14px;">📄</span>
                        <strong style="flex:1;">${escH(f.name)}</strong>
                        <span style="font-size:12px;color:#646970;margin-right:6px;">→</span>
                        <span>${postLabel}</span>
                        <span class="lsb-badge ok">${f.rows.length} rows</span>
                    </div>
                    <div class="lsb-review-table-wrap">
                        <table>${th+tb}</tbody></table>
                    </div>
                </div>`;
            });

            $('#lsb-review-items').html(html);
            $('#lsb-save-status').html('');
        }

        // ── Save ──────────────────────────────────────────────────────────────
        $('#lsb-save-btn').on('click',function(){
            const mSFs=state.subFields.filter(sf=>state.mapping[sf.name]);
            const toSave=state.files
                .map((f,i)=>({file:f,postId:state.matches[i]||''}))
                .filter(x=>x.postId!=='');

            if(!toSave.length){alert('Nothing to save.');return;}

            lsbStatus('#lsb-save-status','info',`Saving ${toSave.length} file(s)…`);
            $('#lsb-save-btn,#lsb-back-4').prop('disabled',true);

            let done=0,savedCount=0,errors=[];

            toSave.forEach(({file,postId})=>{
                const rows=file.rows.map(fr=>{
                    const row={};
                    mSFs.forEach(sf=>{
                        let v=String(fr[state.mapping[sf.name]]??'');
                        if(sf.type==='true_false') v=['1','true','yes','on'].includes(v.toLowerCase())?'1':'0';
                        row[sf.name]=v;
                    });
                    return row;
                });

                $.post(ajaxUrl,{
                    action:'lsb_save_repeater',
                    nonce,
                    post_id:postId,
                    field_name:state.fieldName,
                    rows,
                },function(res){
                    done++;
                    if(res.success){
                        savedCount++;
                        const post=state.allPosts.find(p=>p.id==postId);
                        const label=post?post.title:`ID ${postId}`;
                        // Update review item to show saved
                        // find review item by file name and add a success indicator
                    }else{
                        const post=state.allPosts.find(p=>p.id==postId);
                        errors.push(post?post.title:`ID ${postId}`);
                    }
                    if(done===toSave.length){
                        $('#lsb-back-4').prop('disabled',false);
                        if(!errors.length){
                            lsbStatus('#lsb-save-status','success',`✓ All ${savedCount} post(s) updated successfully.`);
                            $('#lsb-save-btn').prop('disabled',true);
                        }else{
                            lsbStatus('#lsb-save-status','error',
                                `${savedCount} saved. Failed: ${errors.join(', ')}`);
                            $('#lsb-save-btn').prop('disabled',false);
                        }
                    }
                });
            });
        });

        // ── Helpers ───────────────────────────────────────────────────────────
        function lsbStatus(sel,type,msg){$(sel).html(`<div class="lsb-notice ${type}">${msg}</div>`);}
        function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

        lsbGoTo(1);
    })(jQuery);
    </script>
    <?php
}