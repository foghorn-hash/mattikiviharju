<div class="wrap">
    <h1>Word to Blog AI - Luo blogiartikkeleita</h1>
    
    <div class="wtbai-container">
        <!-- Settings Section -->
        <div class="wtbai-card">
            <h2>Asetukset</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('wtbai_settings');
                do_settings_sections('word-to-blog-ai');
                submit_button('Tallenna API-avain');
                ?>
            </form>
        </div>
        
        <!-- Upload Section -->
        <div class="wtbai-card">
            <h2>Tuo Word-tiedosto</h2>
            <p>Lataa suomenkielinen Word-tiedosto (.docx tai .doc), josta luodaan blogiartikkeli OpenAI:n avulla.</p>
            
            <div class="wtbai-upload-area">
                <input type="file" id="wtbai-word-file" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
                <button type="button" class="button button-primary" id="wtbai-upload-btn">
                    <span class="dashicons dashicons-upload"></span> Lataa Word-tiedosto
                </button>
            </div>
            
            <div id="wtbai-status" class="wtbai-status" style="display: none;"></div>
        </div>
        
        <!-- Content Preview -->
        <div class="wtbai-card" id="wtbai-preview-section" style="display: none;">
            <h2>Esikatselu sisällöstä</h2>
            <div id="wtbai-content-preview" class="wtbai-preview"></div>
            
            <div id="wtbai-images-preview" class="wtbai-images"></div>
            
            <h3>AI-asetukset</h3>
            <p>
                <label for="wtbai-tone">Tyyli:</label>
                <select id="wtbai-tone" class="regular-text">
                    <option value="professional">Ammattimainen</option>
                    <option value="casual">Rento</option>
                    <option value="informative">Informatiivinen</option>
                    <option value="engaging">Kiinnostava</option>
                </select>
            </p>
            
            <button type="button" class="button button-primary button-hero" id="wtbai-generate-btn">
                <span class="dashicons dashicons-admin-generic"></span> Luo blogiartikkeli AI:lla
            </button>
        </div>
        
        <!-- Result Section -->
        <div class="wtbai-card" id="wtbai-result-section" style="display: none;">
            <h2>✓ Blogiartikkeli luotu!</h2>
            <div id="wtbai-result-content"></div>
        </div>
    </div>
</div>
