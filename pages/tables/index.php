<!-- pages/tables/index.php — giao diện trang "Bàn" (header/footer do index.php gốc in) -->
 
    <main class="main">
<div id="order-app">
<!-- VIEW 1: SƠ ĐỒ BÀN -->
        <div id="view-tables" class="view-panel active">
            <div class="topnav">
                <button class="topnav-item active" data-status="all" onclick="filterTables('all')">Tất cả bàn</button>
                <button class="topnav-item" data-status="empty" onclick="filterTables('empty')">Bàn trống</button>
                <button class="topnav-item" data-status="occupied" onclick="filterTables('occupied')">Đang phục vụ</button>
            </div>
 
            <div class="main-content">
                <h2 class="tables-title">Sơ đồ bàn</h2>
                <div id="tablesGrid" class="tables-grid"></div>
            </div>
        </div>
 
        <!-- VIEW 2: MENU GỌI MÓN & HÓA ĐƠN TẠM TÍNH -->
        <div id="view-menu" class="view-panel">
            <div class="topnav">
                <button type="button" class="topnav-back" id="btnBackToTables" onclick="backToTables()">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại sơ đồ bàn
                </button>
                <span id="categoryTabs" style="display:contents"></span>
            </div>
 
            <div class="main-content">
                <!-- Cảnh báo khi chưa chọn bàn -->
                <div id="no-table-warning" class="menu-empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-chair"></i></div>
                    <div>Vui lòng chọn bàn từ <b>Sơ đồ bàn</b> để bắt đầu gọi món!</div>
                </div>
 
                <!-- Màn hình Order 2 cột (Menu dòng bên trái + Bill bên phải) -->
                <div id="order-screen" class="order-screen">
                    <div class="order-layout-container">
                        
                        <!-- CỘT TRÁI: DÒNG MENU DẠNG ADMIN -->
                        <div class="menu-section">
                            <div class="menu-search-bar"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="orderSearchInput" placeholder="Tìm món theo tên, danh mục..." autocomplete="off"></div>
                            <div id="menuGrid" class="menu-grid"></div>
                        </div>
 
                        <!-- CỘT PHẢI: HÓA ĐƠN TẠM TÍNH -->
                        <div class="bill-section">
                            <div class="bill-header">
                                <h3 id="currentTableTitle" class="current-table-title">Chưa chọn bàn</h3>
                                <span id="currentInvoiceId" class="current-invoice-id">Mã HD: --</span>
                            </div>
 
                            <hr class="bill-divider-dashed">
 
                            <div class="selected-items-title">Món đã chọn:</div>
                            <div id="billItems" class="bill-items-container">
                                <div class="bill-empty">Chưa chọn món nào</div>
                            </div>
 
                            <hr class="bill-divider">
 
                            <div class="bill-total">
                                <span>Tạm tính:</span>
                                <span id="totalAmount" class="total-amount">0 đ</span>
                            </div>
 
                            <!-- NÚT XÁC NHẬN GỬI THU NGÂN -->
                            <div id="billActions" class="bill-actions">
                                <button class="btn-submit" disabled>Xác nhận</button>
                            </div>
                        </div>
 
                    </div>
                </div>
            </div>
        </div>
 
    
</div>
</main>
