<!-- pages/cashshift/index.php — giao diện trang "Ca & Quỹ" (header/footer do index.php gốc in) -->
    <main class="main">
        <div class="main-content cs-wrap">
            <div class="menu-page-header">
                <h2>Ca & Quỹ tiền mặt</h2>
                <p id="shSub">—</p>
            </div>
            <div id="shRoot"></div>
        </div>
    </main>

    <!-- Báo cáo kết ca -->
    <div id="shModal" class="modal-overlay">
        <div class="modal-box" style="max-width:460px">
            <div id="shReport" class="cs-report"></div>
            <div class="modal-actions" style="margin-top:16px">
                <button class="btn-outline" style="flex:1" id="shClose">Đóng</button>
                <button class="btn-outline" style="flex:1" id="shPrint"><i class="fa-solid fa-print"></i> In báo cáo</button>
            </div>
        </div>
    </div>
