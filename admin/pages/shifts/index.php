<!-- pages/shifts/index.php â€” giao diá»‡n trang "Lá»‹ch ca" (header/footer do index.php gá»‘c in) -->
    <main class="main">
        <div id="schedule-app">
            <h2 class="sch-title">Xáº¿p lá»‹ch ca</h2>
            <p class="sch-note" style="margin:-8px 0 14px">Báº¥m "+ ThÃªm ca" trong Ã´ Ä‘á»ƒ xáº¿p ca, báº¥m vÃ o ca cÃ³ sáºµn Ä‘á»ƒ sá»­a hoáº·c xÃ³a. Giá» má»Ÿ cá»­a 6h30 â€“ 22h.</p>

            <div class="sch-weekbar">
                <button type="button" id="shPrev">Tuáº§n trÆ°á»›c</button>
                <div class="sch-week-label"><b id="shMonth"></b><span id="shRange"></span></div>
                <button type="button" id="shThis">Tuáº§n nÃ y</button>
                <button type="button" id="shNext">Tuáº§n sau</button>
                <button type="button" id="shCopy" class="shf-outline">Sao chÃ©p tá»« tuáº§n trÆ°á»›c</button>
                <button type="button" id="shClear" class="shf-danger">XÃ³a lá»‹ch tuáº§n nÃ y</button>
            </div>

            <div class="sch-table-wrap"><table class="sch-table" id="shTable"></table></div>
            <section class="shf-mobile-schedule" id="shMobileSchedule" aria-label="Lá»‹ch ca theo ngÃ y" hidden>
                <div class="shf-day-tabs" id="shDayTabs" role="group" aria-label="Chá»n ngÃ y trong tuáº§n"></div>
                <div class="shf-day-list" id="shDayList" aria-live="polite"></div>
            </section>
            <p class="sch-note">Lá»‹ch lÆ°u ngay khi báº¥m LÆ°u. NhÃ¢n viÃªn (Phá»¥c vá»¥) xem á»Ÿ má»¥c "Lá»‹ch ca lÃ m". LÆ°Æ¡ng chá»‰ tÃ­nh cho giá» lÃ m náº±m trong ca Ä‘Ã£ xáº¿p.</p>
            <details class="shift-server-data" open>
                <summary>Danh sÃ¡ch ca lÃ m tá»« MySQL (PHP)</summary>
                <div class="data-table-wrapper"><table class="data-table">
                    <thead><tr><th>NhÃ¢n viÃªn</th><th>Vai trÃ²</th><th>NgÃ y</th><th>Giá» lÃ m</th></tr></thead>
                    <tbody>
                    <?php foreach ($adminShifts as $shift): ?>
                        <tr><td><?= e($shift['full_name']) ?></td><td><?= e($shift['role']) ?></td>
                            <td><?= e($shift['shift_date']) ?></td><td><?= e(substr($shift['start_time'], 0, 5)) ?> â€“ <?= e(substr($shift['end_time'], 0, 5)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$adminShifts): ?><tr><td colspan="4">ChÆ°a xáº¿p ca lÃ m nÃ o.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </details>
        </div>
    </main>

    <div id="shModal" class="modal-overlay">
        <form class="modal-box" id="shForm" novalidate>
            <h3 class="modal-title" id="shTitle">ThÃªm ca</h3>
            <p class="modal-sub" id="shSub"></p>
            <div class="form-row">
                <div class="form-group"><label for="shStart">Giá» báº¯t Ä‘áº§u</label><input type="time" id="shStart" step="900"></div>
                <div class="form-group"><label for="shEnd">Giá» káº¿t thÃºc</label><input type="time" id="shEnd" step="900"></div>
            </div>
            <p class="shf-error" id="shError" role="alert" hidden></p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="shCancel">ÄÃ³ng</button>
                <button type="button" class="btn-danger" id="shDelete" hidden>XÃ³a ca</button>
                <button type="submit" class="btn-submit">LÆ°u</button>
            </div>
        </form>
    </div>

