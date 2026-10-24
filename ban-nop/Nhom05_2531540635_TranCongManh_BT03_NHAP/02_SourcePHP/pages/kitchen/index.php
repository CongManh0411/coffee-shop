<!-- pages/kitchen/index.php — giao diện trang "Bếp" (header/footer do index.php gốc in) -->
    <main class="main">
        <div class="main-content">
            <div class="menu-page-header">
                <h2>Bếp</h2>
                <p id="kSummary">Chưa có món nào.</p>
            </div>

            <div class="k-board">
                <section class="k-col k-waiting" aria-labelledby="kTitleWaiting">
                    <h3 id="kTitleWaiting">Chờ làm <span class="k-count" id="kCountWaiting">0</span></h3>
                    <div class="k-list" id="kListWaiting"></div>
                </section>
                <section class="k-col k-making" aria-labelledby="kTitleMaking">
                    <h3 id="kTitleMaking">Đang làm <span class="k-count" id="kCountMaking">0</span></h3>
                    <div class="k-list" id="kListMaking"></div>
                </section>
                <section class="k-col k-done" aria-labelledby="kTitleDone">
                    <h3 id="kTitleDone">Đã xong <span class="k-count" id="kCountDone">0</span></h3>
                    <div class="k-list" id="kListDone"></div>
                </section>
            </div>

            <p class="sync-hint">Các món gửi cùng lúc của một bàn được gom thành một phiếu. Phiếu chuyển sang vàng sau 3 phút và đỏ sau 5 phút chưa xong. Bấm "Xong" là Phục vụ nhận thông báo ngay. Cột Đã xong chỉ giữ 30 phút gần nhất.</p>
        </div>
    </main>
