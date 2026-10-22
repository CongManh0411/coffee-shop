<!-- pages/tablemanage/index.php â€” giao diá»‡n trang "BÃ n" (header/footer do index.php gá»‘c in) -->
    <main class="main">
        <div class="main-content">
            <div class="menu-page-header">
                <h2>Quáº£n lÃ½ bÃ n</h2>
                <p>ThÃªm, sá»­a tÃªn hoáº·c xÃ³a bÃ n. Tráº¡ng thÃ¡i bÃ n cáº­p nháº­t theo thá»i gian thá»±c tá»« trang Phá»¥c vá»¥.</p>
            </div>

            <div class="tm-toolbar">
                <p class="tm-summary" id="tableSummary">Tá»•ng cá»™ng <?= count($adminTables) ?> bÃ n</p>
                <button type="button" class="btn-submit" id="btnAddTable">+ ThÃªm bÃ n</button>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>TÃªn bÃ n</th>
                            <th>Tráº¡ng thÃ¡i</th>
                            <th>VÃ o lÃºc</th>
                            <th>HÃ nh Ä‘á»™ng</th>
                        </tr>
                    </thead>
                    <tbody id="tableManageBody">
                        <?php foreach ($adminTables as $table): ?>
                            <tr><td><b><?= e($table['table_name']) ?></b></td><td><?= (int) $table['is_active'] ? 'Äang hoáº¡t Ä‘á»™ng' : 'Ngá»«ng hoáº¡t Ä‘á»™ng' ?></td><td>â€”</td><td>â€”</td></tr>
                        <?php endforeach; ?>
                        <?php if (!$adminTables): ?>
                        <tr><td colspan="4"><div class="empty-table-state">ChÆ°a cÃ³ bÃ n nÃ o. Báº¥m "+ ThÃªm bÃ n" Ä‘á»ƒ báº¯t Ä‘áº§u.</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <details class="table-server-data" open>
                <summary>Danh sÃ¡ch bÃ n tá»« MySQL (PHP)</summary>
                <div class="data-table-wrapper"><table class="data-table">
                    <thead><tr><th>MÃ£ bÃ n</th><th>TÃªn bÃ n</th><th>Tráº¡ng thÃ¡i</th></tr></thead>
                    <tbody>
                    <?php foreach ($adminTables as $table): ?>
                        <tr><td><?= (int) $table['table_id'] ?></td><td><?= e($table['table_name']) ?></td>
                            <td><?= (int) $table['is_active'] ? 'Äang hoáº¡t Ä‘á»™ng' : 'Ngá»«ng hoáº¡t Ä‘á»™ng' ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$adminTables): ?><tr><td colspan="3">ChÆ°a cÃ³ bÃ n nÃ o.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </details>

            <p class="sync-hint">Chá»‰ xÃ³a Ä‘Æ°á»£c bÃ n Ä‘ang trá»‘ng. BÃ n Ä‘ang phá»¥c vá»¥ hoáº·c chá» thanh toÃ¡n sáº½ xÃ³a Ä‘Æ°á»£c sau khi Phá»¥c vá»¥ thanh toÃ¡n xong. Äá»•i tÃªn bÃ n khÃ´ng áº£nh hÆ°á»Ÿng hÃ³a Ä‘Æ¡n Ä‘Ã£ láº­p.</p>
        </div>
    </main>

    <!-- MODAL: ThÃªm / sá»­a bÃ n -->
    <div id="tableModal" class="modal-overlay">
        <form class="modal-box" id="tableForm" novalidate>
            <h3 class="modal-title" id="tableModalTitle">ThÃªm bÃ n</h3>
            <p class="modal-sub" id="tableModalSub">Nháº­p tÃªn bÃ n má»›i.</p>
            <div class="form-group">
                <label for="tableNameInput">TÃªn bÃ n</label>
                <input type="text" id="tableNameInput" maxlength="30" autocomplete="off" placeholder="VÃ­ dá»¥: BÃ n 09">
            </div>
            <p class="tm-error" id="tableNameError" role="alert" hidden></p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="btnTableCancel">ÄÃ³ng</button>
                <button type="submit" class="btn-submit" id="btnTableSave">ThÃªm bÃ n</button>
            </div>
        </form>
    </div>

    <!-- MODAL: XÃ¡c nháº­n xÃ³a bÃ n -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <h3 class="modal-title">XÃ³a bÃ n</h3>
            <p class="modal-sub">Báº¡n cÃ³ cháº¯c muá»‘n xÃ³a <b id="deleteModalName">--</b>? BÃ n sáº½ biáº¿n máº¥t khá»i sÆ¡ Ä‘á»“ bÃ n cá»§a Phá»¥c vá»¥.</p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="btnDeleteCancel">ÄÃ³ng</button>
                <button type="button" class="tm-danger-solid" id="btnDeleteConfirm">XÃ¡c nháº­n xÃ³a</button>
            </div>
        </div>
    </div>

