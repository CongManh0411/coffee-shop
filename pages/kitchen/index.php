<?php
// pages/kitchen/index.php — giao diện "Bếp" (header/footer do index.php gốc in). Dữ liệu từ action.php, in bằng foreach.
$columns = array(
    'waiting' => array('class' => 'k-waiting', 'title' => 'Chờ làm', 'empty' => 'Không có món nào đang chờ.'),
    'making' => array('class' => 'k-making', 'title' => 'Đang làm', 'empty' => 'Chưa nhận làm món nào.'),
    'done' => array('class' => 'k-done', 'title' => 'Đã xong', 'empty' => 'Chưa có món nào xong.'),
);

// Một nút = một form POST nhỏ gửi danh sách món + trạng thái mới.
$kButton = function ($ids, $to, $text, $class) {
    return '<form method="post" action="' . e(url('kitchen')) . '" class="k-form">'
        . '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="item_ids" value="' . e(implode(',', $ids)) . '">'
        . '<input type="hidden" name="to" value="' . e($to) . '">'
        . '<button type="submit" name="kitchen_action" value="1" class="k-btn ' . e($class) . '">' . e($text) . '</button></form>';
};
?>
    <main class="main">
        <div class="main-content">
            <div class="menu-page-header">
                <h2>Bếp</h2>
                <p><?= ($counts['waiting'] + $counts['making'])
                    ? $counts['waiting'] . ' món chờ · ' . $counts['making'] . ' món đang làm · ' . $activeTickets . ' phiếu'
                    : 'Hiện không còn món nào cần làm.' ?></p>
<?php if ($kitchenError !== ''): ?>
                <p class="cancel-reason-note" role="alert"><?= e($kitchenError) ?></p>
<?php endif; ?>
            </div>

            <div class="k-board">
<?php foreach ($columns as $status => $col): ?>
                <section class="k-col <?= e($col['class']) ?>">
                    <h3><?= e($col['title']) ?> <span class="k-count"><?= (int) $counts[$status] ?></span></h3>
                    <div class="k-list">
<?php if (!$tickets[$status]): ?>
                        <div class="k-empty"><?= e($col['empty']) ?></div>
<?php endif; ?>
<?php foreach ($tickets[$status] as $t):
    $isDone = $status === 'done';
    $ref = $isDone ? $t['done_sec'] : $t['since_sec'];
    $urg = $isDone ? '' : kitchen_urgency($t['since_sec']);
    $isNew = $status === 'waiting' && $t['since_sec'] < 60;
    $multi = count($t['items']) > 1;
    $allIds = array_map(function ($x) { return (int) $x['order_item_id']; }, $t['items']);
?>
                        <article class="k-order <?= e($urg) ?>">
                            <div class="k-order-top">
                                <span class="k-table"><?= e($t['table_name']) ?><?= $isNew ? '<span class="k-new">Mới</span>' : '' ?></span>
                                <span class="k-elapsed"><?= $isDone ? 'xong ' : '' ?><?= e(kitchen_format_elapsed($ref)) ?><?= $isDone ? ' trước' : '' ?></span>
                            </div>
                            <ul class="k-lines">
<?php foreach ($t['items'] as $x): ?>
                                <li class="k-line">
                                    <span class="k-qty"><?= (int) $x['quantity'] ?></span>
                                    <span class="k-name"><?= e($x['item_name']) ?><?= $x['note'] !== null && $x['note'] !== '' ? '<span class="k-note">' . e($x['note']) . '</span>' : '' ?></span>
<?php if ($status === 'making'): ?>
                                    <span class="k-timer"><?= e(kitchen_format_mmss($x['making_sec'])) ?></span>
<?php endif; ?>
<?php if ($multi && $status === 'waiting'): ?>
                                    <span class="k-line-btns"><?= $kButton(array($x['order_item_id']), 'making', 'Nhận', 'mini') ?></span>
<?php elseif ($multi && $status === 'making'): ?>
                                    <span class="k-line-btns"><?= $kButton(array($x['order_item_id']), 'done', 'Xong', 'mini go') ?></span>
<?php endif; ?>
                                </li>
<?php endforeach; ?>
                            </ul>
                            <div class="k-actions">
<?php if ($status === 'waiting'): ?>
                                <?= $kButton($allIds, 'making', $multi ? 'Nhận làm tất cả (' . count($allIds) . ')' : 'Nhận làm', 'primary') ?>
<?php elseif ($status === 'making'): ?>
                                <?= $kButton($allIds, 'done', $multi ? 'Xong tất cả (' . count($allIds) . ')' : 'Xong', 'go') ?>
                                <?= $kButton($allIds, 'waiting', 'Trả về chờ', 'ghost') ?>
<?php else: ?>
                                <?= $kButton($allIds, 'making', 'Hoàn tác', 'ghost') ?>
<?php endif; ?>
                            </div>
                        </article>
<?php endforeach; ?>
                    </div>
                </section>
<?php endforeach; ?>
            </div>

            <p class="sync-hint">Trang tự tải lại mỗi 10 giây. Các món gửi cùng lúc của một bàn được gom thành một phiếu. Phiếu chuyển sang vàng sau 3 phút và đỏ sau 5 phút chưa xong. Bấm "Xong" là Phục vụ nhận thông báo. Cột Đã xong chỉ giữ 30 phút gần nhất.</p>
        </div>
    </main>
