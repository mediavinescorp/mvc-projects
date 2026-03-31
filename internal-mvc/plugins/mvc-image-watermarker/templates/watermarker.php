<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$d = get_option( 'mvc_iw_defaults', array() );
function mvc_iw_d( $key, $fallback, $d ) {
    return isset($d[$key]) && $d[$key] !== '' ? $d[$key] : $fallback;
}
?>
<div id="mvc-watermarker-wrap">

<?php
// Inline CSS only if not enqueued (e.g. block editor previews)
if ( ! wp_style_is( 'mvc-image-watermarker', 'done' ) ) {
    echo '<link rel="stylesheet" href="' . esc_url( MVC_IW_URL . 'assets/watermarker.css' ) . '">';
}
?>

<!-- 1. IMAGES -->
<div class="mvc-section">
    <div class="mvc-section-title">1 — Images</div>
    <div class="mvc-drop-zone" id="mvcImgDrop" onclick="document.getElementById('mvcImgInput').click()">
        <div class="mvc-big">Drop images here or click to select</div>
        <p>JPEG · PNG · WEBP · GIF · AVIF · BMP · SVG — any quantity</p>
    </div>
    <input type="file" id="mvcImgInput" multiple accept="image/*" style="display:none">
    <div class="mvc-file-list" id="mvcFileList"></div>
</div>

<!-- 2. OUTPUT -->
<div class="mvc-section">
    <div class="mvc-section-title">2 — Output dimensions</div>
    <div class="mvc-row">
        <div class="mvc-field"><label>Width (px)</label><input type="number" id="mvcOutW" value="<?php echo esc_attr(mvc_iw_d('out_w',1200,$d)); ?>" min="100" max="8000"></div>
        <div class="mvc-field"><label>Height (px)</label><input type="number" id="mvcOutH" value="<?php echo esc_attr(mvc_iw_d('out_h',800,$d)); ?>" min="100" max="8000"></div>
        <div class="mvc-field"><label>Fit mode</label>
            <select id="mvcFitMode">
                <option value="cover">Cover (crop to fill)</option>
                <option value="contain">Contain (letterbox)</option>
                <option value="stretch">Stretch</option>
            </select>
        </div>
        <div class="mvc-field"><label>Quality (%)</label><input type="number" id="mvcQuality" value="<?php echo esc_attr(mvc_iw_d('quality',92,$d)); ?>" min="1" max="100"></div>
    </div>
    <p class="mvc-note">Output is always <strong>JPEG</strong>.</p>
    <div id="mvcUpscaleWarn" style="display:none; margin-top:10px; padding:8px 12px; background:#fff8e1; border:1px solid #ffe082; border-radius:6px; font-size:12px; color:#7a5800;">
        ⚠️ Your output dimensions are larger than the source image — this will cause pixelation. Lower the output size or use a higher-resolution source image for best results.
    </div>
</div>

<!-- 3. FILE NAMING -->
<div class="mvc-section">
    <div class="mvc-section-title">3 — File naming</div>

    <!-- Naming mode tabs -->
    <div class="mvc-row" style="margin-bottom:14px; align-items:center; gap:16px; flex-wrap:wrap;">
        <label class="mvc-toggle"><input type="radio" name="mvcNameMode" value="structured" id="mvcNameStructured" checked onchange="mvcToggleNameMode()"> Structured name</label>
        <label class="mvc-toggle"><input type="radio" name="mvcNameMode" value="prefix" id="mvcNamePrefix" onchange="mvcToggleNameMode()"> Add prefix</label>
        <label class="mvc-toggle"><input type="radio" name="mvcNameMode" value="manual" id="mvcNameManual" onchange="mvcToggleNameMode()"> Rename each manually</label>
        <label class="mvc-toggle"><input type="radio" name="mvcNameMode" value="auto" id="mvcNameAuto" onchange="mvcToggleNameMode()"> Keep original</label>
    </div>

    <!-- STRUCTURED -->
    <div id="mvcStructuredWrap">
        <p class="mvc-note" style="margin-bottom:12px;">Builds: <strong>Industry - Keyword - Location - Client.jpg</strong> &nbsp;e.g. <em>HVAC-heatpump-northridge-ca-socal-climate-control.jpg</em></p>
        <div class="mvc-segment-row">
            <div class="mvc-field">
                <label>Industry</label>
                <input type="text" id="mvcSegIndustry" placeholder="HVAC" oninput="mvcUpdateFilenamePreview()">
                <span class="mvc-slug-hint">e.g. HVAC, Roofing, Plumbing, Restoration</span>
            </div>
            <div class="mvc-field">
                <label>Keyword</label>
                <input type="text" id="mvcSegKeyword" placeholder="heatpump" oninput="mvcUpdateFilenamePreview()">
                <span class="mvc-slug-hint">e.g. heatpump, leak-repair, emergency</span>
            </div>
            <div class="mvc-field">
                <label>City</label>
                <input type="text" id="mvcSegCity" placeholder="northridge" oninput="mvcUpdateFilenamePreview()">
            </div>
            <div class="mvc-field">
                <label>State</label>
                <input type="text" id="mvcSegState" placeholder="ca" maxlength="4" oninput="mvcUpdateFilenamePreview()">
            </div>
            <div class="mvc-field">
                <label>Client name</label>
                <input type="text" id="mvcSegClient" placeholder="socal climate control" oninput="mvcUpdateFilenamePreview()">
                <span class="mvc-slug-hint">Spaces → hyphens automatically</span>
            </div>
        </div>
        <p style="font-size:12px;color:#666;margin-bottom:4px;">Preview:</p>
        <div class="mvc-filename-preview" id="mvcFilenamePreview">Fill fields above to see filename preview</div>
        <p class="mvc-note" style="margin-top:6px;">For multiple images, a number is appended automatically: <em>filename-1.jpg, filename-2.jpg…</em></p>
    </div>

    <!-- PREFIX -->
    <div id="mvcPrefixWrap" style="display:none; max-width:320px;">
        <div class="mvc-field"><label>Prefix</label><input type="text" id="mvcPrefixVal" placeholder="e.g. mediavines_"></div>
        <p class="mvc-note" style="margin-top:6px;">Result: <em>mediavines_originalname.jpg</em></p>
    </div>

    <!-- MANUAL -->
    <div id="mvcManualRenameWrap" style="display:none;">
        <p class="mvc-note" style="margin-bottom:8px;">Add images first, then name each one below. Leave blank to use original name.</p>
        <div style="overflow-x:auto;">
            <table class="mvc-rename-table">
                <thead><tr><th>Original file</th><th>Save as (no extension needed)</th></tr></thead>
                <tbody id="mvcRenameBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. LOGO -->
<div class="mvc-section">
    <div class="mvc-section-title">4 — Logo</div>
    <div class="mvc-row" style="align-items:flex-start; gap:16px;">
        <div>
            <div class="mvc-logo-drop" id="mvcLogoDrop" onclick="document.getElementById('mvcLogoInput').click()">
                <p id="mvcLogoLabel" style="font-size:12px;color:#888;">Click to upload logo</p>
                <img id="mvcLogoPreview" style="display:none" alt="logo">
            </div>
            <input type="file" id="mvcLogoInput" accept="image/*" style="display:none">
        </div>
        <div style="flex:1; display:flex; flex-direction:column; gap:10px;">
            <div class="mvc-row">
                <div class="mvc-field"><label>Size (% of image width)</label><input type="number" id="mvcLogoSize" value="<?php echo esc_attr(mvc_iw_d('logo_size',12,$d)); ?>" min="2" max="40"></div>
                <div class="mvc-field"><label>Margin (px)</label><input type="number" id="mvcLogoMargin" value="<?php echo esc_attr(mvc_iw_d('logo_margin',20,$d)); ?>" min="0" max="200"></div>
                <div class="mvc-field"><label>Opacity (%)</label><input type="number" id="mvcLogoOpacity" value="<?php echo esc_attr(mvc_iw_d('logo_opacity',85,$d)); ?>" min="10" max="100"></div>
            </div>
            <div>
                <div style="font-size:12px;color:#666;margin-bottom:6px;font-weight:500;">Position</div>
                <div class="mvc-pos-grid" id="mvcPosGrid">
                    <div class="mvc-pos-btn" data-pos="tl">↖</div><div class="mvc-pos-btn" data-pos="tc">↑</div><div class="mvc-pos-btn" data-pos="tr">↗</div>
                    <div class="mvc-pos-btn" data-pos="ml">←</div><div class="mvc-pos-btn" data-pos="mc">·</div><div class="mvc-pos-btn" data-pos="mr">→</div>
                    <div class="mvc-pos-btn" data-pos="bl">↙</div><div class="mvc-pos-btn" data-pos="bc">↓</div><div class="mvc-pos-btn active" data-pos="<?php echo esc_attr(mvc_iw_d('logo_pos','br',$d)); ?>">↘</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. TEXT OVERLAYS -->
<div class="mvc-section">
    <div class="mvc-section-title">5 — Text overlays</div>

    <div class="mvc-text-block">
        <div class="mvc-text-block-title">Text overlay 1</div>
        <div class="mvc-row" style="margin-bottom:10px;">
            <div class="mvc-field" style="flex:3;min-width:180px;"><label>Text</label><input type="text" id="mvcTxt1" value="<?php echo esc_attr(mvc_iw_d('txt1','',$d)); ?>" placeholder="© YourBrand 2025"></div>
            <div class="mvc-field"><label>Size (px)</label><input type="number" id="mvcFs1" value="<?php echo esc_attr(mvc_iw_d('fs1',24,$d)); ?>" min="8" max="300"></div>
            <div class="mvc-field"><label>Color</label><input type="color" id="mvcFc1" value="<?php echo esc_attr(mvc_iw_d('fc1','#ffffff',$d)); ?>"></div>
            <div class="mvc-field"><label>Opacity (%)</label><input type="number" id="mvcTo1" value="<?php echo esc_attr(mvc_iw_d('to1',85,$d)); ?>" min="10" max="100"></div>
        </div>
        <div class="mvc-row">
            <div class="mvc-field"><label>Font</label>
                <select id="mvcFf1">
                    <?php foreach(['Arial','Georgia','Verdana','Times New Roman','Courier New','Trebuchet MS'] as $f): ?>
                    <option <?php selected(mvc_iw_d('ff1','Arial',$d),$f); ?>><?php echo esc_html($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mvc-field"><label>Weight</label>
                <select id="mvcFw1">
                    <option value="normal" <?php selected(mvc_iw_d('fw1','bold',$d),'normal'); ?>>Normal</option>
                    <option value="bold" <?php selected(mvc_iw_d('fw1','bold',$d),'bold'); ?>>Bold</option>
                </select>
            </div>
            <div class="mvc-field"><label>Position</label>
                <select id="mvcTp1">
                    <?php
                    $tpos_opts = ['br'=>'Bottom right','bl'=>'Bottom left','tr'=>'Top right','tl'=>'Top left','bc'=>'Bottom center','tc'=>'Top center'];
                    foreach($tpos_opts as $v=>$l): ?>
                    <option value="<?php echo $v; ?>" <?php selected(mvc_iw_d('tp1','br',$d),$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mvc-field"><label>Shadow</label>
                <select id="mvcTs1">
                    <option value="dark" <?php selected(mvc_iw_d('ts1','dark',$d),'dark'); ?>>Dark</option>
                    <option value="light" <?php selected(mvc_iw_d('ts1','dark',$d),'light'); ?>>Light</option>
                    <option value="none" <?php selected(mvc_iw_d('ts1','dark',$d),'none'); ?>>None</option>
                </select>
            </div>
        </div>
    </div>

    <div class="mvc-text-block">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <div class="mvc-text-block-title" style="margin-bottom:0;">Text overlay 2</div>
            <span class="mvc-pill">optional</span>
            <label class="mvc-toggle" style="margin-bottom:0;margin-left:auto;"><input type="checkbox" id="mvcTxt2enable" onchange="mvcToggleTxt2()"> Enable</label>
        </div>
        <div id="mvcTxt2fields" style="display:none;">
            <div class="mvc-row" style="margin-bottom:10px;">
                <div class="mvc-field" style="flex:3;min-width:180px;"><label>Text</label><input type="text" id="mvcTxt2" value="<?php echo esc_attr(mvc_iw_d('txt2','',$d)); ?>" placeholder="yourwebsite.com"></div>
                <div class="mvc-field"><label>Size (px)</label><input type="number" id="mvcFs2" value="<?php echo esc_attr(mvc_iw_d('fs2',18,$d)); ?>" min="8" max="300"></div>
                <div class="mvc-field"><label>Color</label><input type="color" id="mvcFc2" value="<?php echo esc_attr(mvc_iw_d('fc2','#ffffff',$d)); ?>"></div>
                <div class="mvc-field"><label>Opacity (%)</label><input type="number" id="mvcTo2" value="<?php echo esc_attr(mvc_iw_d('to2',70,$d)); ?>" min="10" max="100"></div>
            </div>
            <div class="mvc-row">
                <div class="mvc-field"><label>Font</label>
                    <select id="mvcFf2">
                        <?php foreach(['Arial','Georgia','Verdana','Times New Roman','Courier New','Trebuchet MS'] as $f): ?>
                        <option <?php selected(mvc_iw_d('ff2','Arial',$d),$f); ?>><?php echo esc_html($f); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mvc-field"><label>Weight</label>
                    <select id="mvcFw2">
                        <option value="normal" <?php selected(mvc_iw_d('fw2','normal',$d),'normal'); ?>>Normal</option>
                        <option value="bold" <?php selected(mvc_iw_d('fw2','normal',$d),'bold'); ?>>Bold</option>
                    </select>
                </div>
                <div class="mvc-field"><label>Position</label>
                    <select id="mvcTp2">
                        <?php foreach($tpos_opts as $v=>$l): ?>
                        <option value="<?php echo $v; ?>" <?php selected(mvc_iw_d('tp2','bl',$d),$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mvc-field"><label>Shadow</label>
                    <select id="mvcTs2">
                        <option value="dark" <?php selected(mvc_iw_d('ts2','dark',$d),'dark'); ?>>Dark</option>
                        <option value="light" <?php selected(mvc_iw_d('ts2','dark',$d),'light'); ?>>Light</option>
                        <option value="none" <?php selected(mvc_iw_d('ts2','dark',$d),'none'); ?>>None</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 6. PREVIEW & EXPORT -->
<div class="mvc-section">
    <div class="mvc-section-title">6 — Preview & export</div>
    <div class="mvc-row" style="margin-bottom:12px; align-items:center;">
        <button class="mvc-btn-sec" onclick="mvcRenderPreview()">Preview first image</button>
        <span style="font-size:13px;color:#888;" id="mvcPreviewInfo"></span>
    </div>
    <div class="mvc-preview-wrap" id="mvcPreviewWrap" style="display:none;">
        <canvas id="mvcPreviewCanvas"></canvas>
    </div>
    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <button class="mvc-btn-primary" id="mvcProcessBtn" onclick="mvcProcessAll()" disabled>Process & download all</button>
        <span id="mvcStatusMsg" class="mvc-status"></span>
    </div>
    <div class="mvc-progress" id="mvcProgressWrap"><div class="mvc-progress-bar" id="mvcProgressBar" style="width:0%"></div></div>
</div>

</div><!-- #mvc-watermarker-wrap -->
<canvas id="mvcWorkCanvas" style="display:none"></canvas>

<script>
(function(){
var mvcImages = [];
var mvcLogoImg = null;
var mvcLogoPos = '<?php echo esc_js(mvc_iw_d('logo_pos','br',$d)); ?>';

// pos grid
document.getElementById('mvcPosGrid').querySelectorAll('.mvc-pos-btn').forEach(function(btn){
    if(btn.dataset.pos === mvcLogoPos) { btn.classList.add('active'); } else { btn.classList.remove('active'); }
    btn.addEventListener('click', function(){
        document.getElementById('mvcPosGrid').querySelectorAll('.mvc-pos-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        mvcLogoPos = btn.dataset.pos;
    });
});

// drag/drop images
var imgDrop = document.getElementById('mvcImgDrop');
imgDrop.addEventListener('dragover', function(e){ e.preventDefault(); imgDrop.classList.add('drag-over'); });
imgDrop.addEventListener('dragleave', function(){ imgDrop.classList.remove('drag-over'); });
imgDrop.addEventListener('drop', function(e){ e.preventDefault(); imgDrop.classList.remove('drag-over'); mvcAddImages(e.dataTransfer.files); });
document.getElementById('mvcImgInput').addEventListener('change', function(e){ mvcAddImages(e.target.files); });

function mvcAddImages(files){
    Array.from(files).forEach(function(f){
        if(!f.type.startsWith('image/')) return;
        mvcImages.push({ file: f, url: URL.createObjectURL(f), customName: '' });
    });
    mvcRenderThumbs();
    mvcRenderRenameTable();
    mvcUpdateBtn();
    mvcUpdateFilenamePreview();
}

function mvcRenderThumbs(){
    var fl = document.getElementById('mvcFileList');
    fl.innerHTML = '';
    mvcImages.forEach(function(img, i){
        var d = document.createElement('div');
        d.className = 'mvc-thumb';
        d.innerHTML = '<img src="'+img.url+'" alt=""><button class="mvc-rm" onclick="mvcRemoveImg('+i+')">×</button>';
        fl.appendChild(d);
    });
}

window.mvcRemoveImg = function(i){
    URL.revokeObjectURL(mvcImages[i].url);
    mvcImages.splice(i,1);
    mvcRenderThumbs();
    mvcRenderRenameTable();
    mvcUpdateBtn();
    mvcUpdateFilenamePreview();
};

function mvcUpdateBtn(){ document.getElementById('mvcProcessBtn').disabled = mvcImages.length === 0; }

// naming modes
window.mvcToggleNameMode = function(){
    var mode = document.querySelector('input[name=mvcNameMode]:checked').value;
    document.getElementById('mvcStructuredWrap').style.display = mode==='structured' ? 'block' : 'none';
    document.getElementById('mvcPrefixWrap').style.display = mode==='prefix' ? 'block' : 'none';
    document.getElementById('mvcManualRenameWrap').style.display = mode==='manual' ? 'block' : 'none';
};

function mvcSlugify(str){
    return str.trim().toLowerCase()
        .replace(/[^a-z0-9\s-]/g,'')
        .replace(/\s+/g,'-')
        .replace(/-+/g,'-')
        .replace(/^-|-$/g,'');
}

window.mvcUpdateFilenamePreview = function(){
    var industry = mvcSlugify(document.getElementById('mvcSegIndustry').value);
    var keyword  = mvcSlugify(document.getElementById('mvcSegKeyword').value);
    var city     = mvcSlugify(document.getElementById('mvcSegCity').value);
    var state    = mvcSlugify(document.getElementById('mvcSegState').value);
    var client   = mvcSlugify(document.getElementById('mvcSegClient').value);
    var parts = [industry,keyword,city,state,client].filter(Boolean);
    var base = parts.length ? parts.join('-') : 'fill-fields-above';
    var multi = mvcImages.length > 1 ? ' (multiple images → '+base+'-1.jpg, '+base+'-2.jpg…)' : '';
    document.getElementById('mvcFilenamePreview').textContent = base + '.jpg' + multi;
};

// listen for changes on structured fields
['mvcSegIndustry','mvcSegKeyword','mvcSegCity','mvcSegState','mvcSegClient'].forEach(function(id){
    document.getElementById(id).addEventListener('input', mvcUpdateFilenamePreview);
});

function mvcRenderRenameTable(){
    var tbody = document.getElementById('mvcRenameBody');
    tbody.innerHTML = '';
    mvcImages.forEach(function(img,i){
        var tr = document.createElement('tr');
        tr.innerHTML = '<td class="mvc-orig" title="'+img.file.name+'">'+img.file.name+'</td>'
            +'<td><input type="text" placeholder="'+img.file.name.replace(/\.[^.]+$/,'')+'" value="'+img.customName+'" oninput="mvcImages['+i+'].customName=this.value"></td>';
        tbody.appendChild(tr);
    });
}

// logo upload
document.getElementById('mvcLogoInput').addEventListener('change', function(e){
    var f = e.target.files[0]; if(!f) return;
    var url = URL.createObjectURL(f);
    mvcLogoImg = new Image();
    mvcLogoImg.onload = function(){
        document.getElementById('mvcLogoPreview').src = url;
        document.getElementById('mvcLogoPreview').style.display = 'block';
        document.getElementById('mvcLogoLabel').style.display = 'none';
    };
    mvcLogoImg.src = url;
});

window.mvcToggleTxt2 = function(){
    document.getElementById('mvcTxt2fields').style.display = document.getElementById('mvcTxt2enable').checked ? 'block' : 'none';
};

function mvcGetFilename(img, i){
    var mode = document.querySelector('input[name=mvcNameMode]:checked').value;
    var orig = img.file.name.replace(/\.[^.]+$/,'');
    if(mode === 'auto') return orig + '.jpg';
    if(mode === 'prefix'){
        var pfx = (document.getElementById('mvcPrefixVal').value||'').trim();
        return pfx + orig + '.jpg';
    }
    if(mode === 'manual'){
        var n = (img.customName||'').trim();
        return (n ? n : orig) + '.jpg';
    }
    // structured
    var industry = mvcSlugify(document.getElementById('mvcSegIndustry').value);
    var keyword  = mvcSlugify(document.getElementById('mvcSegKeyword').value);
    var city     = mvcSlugify(document.getElementById('mvcSegCity').value);
    var state    = mvcSlugify(document.getElementById('mvcSegState').value);
    var client   = mvcSlugify(document.getElementById('mvcSegClient').value);
    var parts = [industry,keyword,city,state,client].filter(Boolean);
    var base = parts.length ? parts.join('-') : orig;
    return mvcImages.length > 1 ? base+'-'+(i+1)+'.jpg' : base+'.jpg';
}

// Step-down scaling: draws large-to-small in halving steps for much sharper results
function mvcStepDown(ctx, src, sw, sh, sx, sy, dw, dh, dx, dy){
    // if downscaling by more than 2x, step down in halves
    var scaleX = dw / sw, scaleY = dh / sh;
    if(scaleX >= 0.5 && scaleY >= 0.5){
        ctx.drawImage(src, sx, sy, sw, sh, dx, dy, dw, dh);
        return;
    }
    var tmpCanvas = document.createElement('canvas');
    var curW = sw, curH = sh;
    var tmpCtx = tmpCanvas.getContext('2d');
    tmpCtx.imageSmoothingEnabled = true;
    tmpCtx.imageSmoothingQuality = 'high';
    // first step: draw source into temp at source size
    tmpCanvas.width = curW; tmpCanvas.height = curH;
    tmpCtx.drawImage(src, sx, sy, sw, sh, 0, 0, curW, curH);
    while(curW * 0.5 > dw || curH * 0.5 > dh){
        var nextW = Math.max(Math.floor(curW * 0.5), dw);
        var nextH = Math.max(Math.floor(curH * 0.5), dh);
        var tmp2 = document.createElement('canvas');
        tmp2.width = nextW; tmp2.height = nextH;
        var tmp2Ctx = tmp2.getContext('2d');
        tmp2Ctx.imageSmoothingEnabled = true;
        tmp2Ctx.imageSmoothingQuality = 'high';
        tmp2Ctx.drawImage(tmpCanvas, 0, 0, curW, curH, 0, 0, nextW, nextH);
        tmpCanvas = tmp2; tmpCtx = tmp2Ctx;
        curW = nextW; curH = nextH;
    }
    ctx.drawImage(tmpCanvas, 0, 0, curW, curH, dx, dy, dw, dh);
}

function mvcApplyOverlay(srcUrl, forExport){
    return new Promise(function(resolve){
        var outW = parseInt(document.getElementById('mvcOutW').value)||1200;
        var outH = parseInt(document.getElementById('mvcOutH').value)||800;
        var fit  = document.getElementById('mvcFitMode').value;
        var src  = new Image();
        src.onload = function(){

            // warn if upscaling significantly
            var warnEl = document.getElementById('mvcUpscaleWarn');
            if(warnEl){
                var upscaling = (outW > src.width * 1.2) || (outH > src.height * 1.2);
                warnEl.style.display = upscaling ? 'block' : 'none';
            }

            var canvas = document.getElementById('mvcWorkCanvas');
            // For export: use exact output px. For preview: also exact (canvas toDataURL ignores CSS size)
            canvas.width  = outW;
            canvas.height = outH;

            var ctx = canvas.getContext('2d');
            // HIGH QUALITY smoothing — key fix
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.clearRect(0,0,outW,outH);

            if(fit==='stretch'){
                mvcStepDown(ctx, src, src.width, src.height, 0, 0, outW, outH, 0, 0);
            } else if(fit==='cover'){
                var sr=src.width/src.height, dr=outW/outH, sw,sh,sx,sy;
                if(sr>dr){ sh=src.height; sw=sh*dr; sx=(src.width-sw)/2; sy=0; }
                else { sw=src.width; sh=sw/dr; sx=0; sy=(src.height-sh)/2; }
                mvcStepDown(ctx, src, sw, sh, sx, sy, outW, outH, 0, 0);
            } else {
                ctx.fillStyle='#000'; ctx.fillRect(0,0,outW,outH);
                var sr2=src.width/src.height, dr2=outW/outH, dw,dh,dx,dy;
                if(sr2>dr2){ dw=outW; dh=outW/sr2; dx=0; dy=(outH-dh)/2; }
                else { dh=outH; dw=outH*sr2; dx=(outW-dw)/2; dy=0; }
                mvcStepDown(ctx, src, src.width, src.height, 0, 0, dw, dh, dx, dy);
            }

            var margin = parseInt(document.getElementById('mvcLogoMargin').value)||20;
            if(mvcLogoImg){
                var lsp=(parseInt(document.getElementById('mvcLogoSize').value)||12)/100;
                var lw=Math.round(outW*lsp);
                var lh=Math.round(lw*(mvcLogoImg.height/mvcLogoImg.width));
                var lop=(parseInt(document.getElementById('mvcLogoOpacity').value)||85)/100;
                var pos=mvcLogoPos;
                var lx=pos.indexOf('l')>-1?margin:pos.indexOf('r')>-1?outW-lw-margin:(outW-lw)/2;
                var ly=pos.indexOf('t')>-1?margin:pos.indexOf('b')>-1?outH-lh-margin:(outH-lh)/2;
                ctx.save();
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.globalAlpha=lop;
                mvcStepDown(ctx, mvcLogoImg, mvcLogoImg.width, mvcLogoImg.height, 0, 0, lw, lh, lx, ly);
                ctx.restore();
            }

            function drawText(textId,fsId,fcId,toId,ffId,fwId,tpId,tsId){
                var txt=document.getElementById(textId).value.trim();
                if(!txt) return;
                var fs=parseInt(document.getElementById(fsId).value)||24;
                var fc=document.getElementById(fcId).value;
                var top2=(parseInt(document.getElementById(toId).value)||80)/100;
                var ff=document.getElementById(ffId).value;
                var fw=document.getElementById(fwId).value;
                var tpos=document.getElementById(tpId).value;
                var shadow=document.getElementById(tsId).value;
                ctx.save();
                ctx.globalAlpha=top2;
                ctx.font=fw+' '+fs+'px '+ff;
                ctx.fillStyle=fc;
                if(shadow==='dark'){ ctx.shadowColor='rgba(0,0,0,0.85)'; ctx.shadowBlur=6; ctx.shadowOffsetX=1; ctx.shadowOffsetY=1; }
                else if(shadow==='light'){ ctx.shadowColor='rgba(255,255,255,0.85)'; ctx.shadowBlur=6; ctx.shadowOffsetX=1; ctx.shadowOffsetY=1; }
                if(tpos.indexOf('l')>-1){ ctx.textAlign='left'; }
                else if(tpos.indexOf('r')>-1){ ctx.textAlign='right'; }
                else { ctx.textAlign='center'; }
                var tx=tpos.indexOf('l')>-1?margin:tpos.indexOf('r')>-1?outW-margin:outW/2;
                var ty=tpos.indexOf('t')>-1?margin+fs:outH-margin;
                ctx.fillText(txt,tx,ty);
                ctx.restore();
            }
            drawText('mvcTxt1','mvcFs1','mvcFc1','mvcTo1','mvcFf1','mvcFw1','mvcTp1','mvcTs1');
            if(document.getElementById('mvcTxt2enable').checked){
                drawText('mvcTxt2','mvcFs2','mvcFc2','mvcTo2','mvcFf2','mvcFw2','mvcTp2','mvcTs2');
            }
            resolve(canvas);
        };
        src.src = srcUrl;
    });
}

window.mvcRenderPreview = function(){
    if(!mvcImages.length){ document.getElementById('mvcPreviewInfo').textContent='Add images first'; return; }
    mvcApplyOverlay(mvcImages[0].url).then(function(canvas){
        var pc=document.getElementById('mvcPreviewCanvas');
        var pw=document.getElementById('mvcPreviewWrap');
        var dpr = window.devicePixelRatio || 1;
        // display size: constrain to wrap width
        var maxW = pw.offsetWidth || 800;
        var aspect = canvas.height / canvas.width;
        var dispW = Math.min(canvas.width, maxW);
        var dispH = Math.round(dispW * aspect);
        // physical pixels = display size * dpr for crispness on retina
        pc.width  = dispW * dpr;
        pc.height = dispH * dpr;
        pc.style.width  = dispW + 'px';
        pc.style.height = dispH + 'px';
        var pCtx = pc.getContext('2d');
        pCtx.scale(dpr, dpr);
        pCtx.imageSmoothingEnabled = true;
        pCtx.imageSmoothingQuality = 'high';
        pCtx.drawImage(canvas, 0, 0, dispW, dispH);
        pw.style.display='flex';
        document.getElementById('mvcPreviewInfo').textContent=canvas.width+' × '+canvas.height+'px · previewing at '+dispW+'×'+dispH;
    });
};

window.mvcProcessAll = function(){
    if(!mvcImages.length) return;
    var btn=document.getElementById('mvcProcessBtn');
    btn.disabled=true;
    var status=document.getElementById('mvcStatusMsg');
    var pw=document.getElementById('mvcProgressWrap');
    var pb=document.getElementById('mvcProgressBar');
    pw.style.display='block'; pb.style.width='0%';
    var q=Math.min(100,Math.max(1,parseInt(document.getElementById('mvcQuality').value)||92))/100;
    var i=0;
    function next(){
        if(i>=mvcImages.length){
            pb.style.width='100%';
            status.textContent='Done! '+mvcImages.length+' image'+(mvcImages.length>1?'s':'')+' saved as JPEG.';
            btn.disabled=false;
            return;
        }
        status.textContent='Processing '+(i+1)+' of '+mvcImages.length+'…';
        pb.style.width=Math.round((i/mvcImages.length)*100)+'%';
        mvcApplyOverlay(mvcImages[i].url).then(function(canvas){
            var dataUrl=canvas.toDataURL('image/jpeg',q);
            var a=document.createElement('a');
            a.download=mvcGetFilename(mvcImages[i],i);
            a.href=dataUrl;
            a.click();
            i++;
            setTimeout(next,250);
        });
    }
    next();
};

// init preview
mvcUpdateFilenamePreview();
})();
</script>
