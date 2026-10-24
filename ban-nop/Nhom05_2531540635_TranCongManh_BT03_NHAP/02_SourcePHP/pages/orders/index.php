<!-- pages/orders/index.php — giao diện trang "Đơn hàng" (header/footer do index.php gốc in) -->
    <main class="main">
        <nav class="topnav" id="ordTabs">
            <button class="topnav-item active" data-tab="processing">Đang xử lý</button>
            <button class="topnav-item" data-tab="done">Đã hoàn thành</button>
            <button class="topnav-item" data-tab="cancelled">Đã hủy / hoàn tiền</button>
        </nav>

        <div class="main-content cs-wrap">
            <div class="menu-page-header">
                <h2 id="ordTitle">Đơn đang xử lý</h2>
                <p id="ordSub">—</p>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead id="ordHead"></thead>
                    <tbody id="ordBody"></tbody>
                </table>
            </div>
            <p class="cs-note" id="ordNote"></p>
        </div>
    </main>
