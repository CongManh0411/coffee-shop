<?php
// helpers/pos.php — nghiệp vụ bán hàng bằng PHP cho trang Bàn / Gọi món / Thanh toán / Đơn hàng.
// Mọi thao tác là form POST có CSRF; hàm pos_handle_post() gọi lại các hàm orders_*(), warehouse_*() có sẵn rồi trả về kết quả để trang redirect.
require_once __DIR__ . '/../functions.php';

const POS_LARGE_DISCOUNT_PCT = 20;
const POS_LARGE_DISCOUNT_VND = 50000;
const POS_METHODS = array('cash' => 'Tiền mặt', 'qr' => 'Mã QR', 'card' => 'Thẻ', 'ewallet' => 'Ví điện tử');
const POS_ORDER_TYPES = array('dinein' => 'Tại quán', 'takeaway' => 'Mang đi', 'delivery' => 'Giao hàng');
const POS_LINE_TEXT = array('draft' => 'Nháp', 'waiting' => 'Chờ pha chế', 'making' => 'Đang làm', 'done' => 'Đã xong');
const POS_SIZES = array('S' => 0, 'M' => 5000, 'L' => 10000);
const POS_TOPPINGS = array('Trân châu' => 7000, 'Thạch' => 5000, 'Kem cheese' => 10000);
const POS_SUGAR = array('0%', '30%', '50%', '100%');
const POS_ICE = array('Không đá', 'Ít đá', 'Bình thường');

function pos_log($me, $type, $text, $amount = 0, $flag = false)
{
    try { auditlog_create(array('type' => $type, 'text' => $text, 'amount' => $amount, 'flag' => $flag), $me); } catch (Throwable $e) { error_log($e); }
}

function pos_is_cashier($me)
{
    return in_array($me['roleKey'] ?? '', array('cashier', 'manager'), true);
}

// Đơn đang mở, kèm tổng tạm tính / tổng đã gửi bếp
function pos_open_orders()
{
    $orders = orders_list('open');
    foreach ($orders as &$o) {
        $o['sent_total'] = 0; $o['all_total'] = 0; $o['sent_count'] = 0; $o['not_ready'] = 0; $o['count'] = 0;
        foreach ($o['items'] as $i) {
            $line = $i['price'] * $i['quantity'];
            $o['all_total'] += $line;
            $o['count'] += $i['quantity'];
            if ($i['status'] !== 'draft') {
                $o['sent_total'] += $line;
                $o['sent_count'] += $i['quantity'];
                if ($i['status'] !== 'done') $o['not_ready'] += 1;
            }
        }
    }
    unset($o);
    return $orders;
}

function pos_find_order($orders, $orderId)
{
    foreach ($orders as $o) if ((int) $o['id'] === (int) $orderId) return $o;
    return null;
}

// Mã khuyến mãi: trả về array(code, label, amount) hoặc null nếu không hợp lệ / hết hạn (cùng luật với orders_pay)
function pos_voucher($code, $subtotal)
{
    $code = strtoupper(trim((string) $code));
    if ($code === '') return null;
    foreach (ops_promotion_list() as $p) {
        if ($p['code'] !== $code || !$p['active']) continue;
        $today = date('Y-m-d'); $hour = (int) date('G');
        if (($p['from'] && $today < $p['from']) || ($p['to'] && $today > $p['to'])) return null;
        if ($p['maxUses'] > 0 && $p['used'] >= $p['maxUses']) return null;
        if ($p['hourFrom'] !== '' && ($hour < (int) $p['hourFrom'] || $hour >= (int) $p['hourTo'])) return null;
        $amt = $p['type'] === 'pct' ? (int) round($subtotal * $p['value'] / 100) : (int) $p['value'];
        return array('code' => $code, 'label' => $p['label'], 'amount' => min($subtotal, $amt));
    }
    return null;
}

function pos_manual_discount($type, $value, $subtotal)
{
    $value = max(0, (float) $value);
    $amt = $type === 'pct' ? (int) round($subtotal * min($value, 100) / 100) : (int) round($value);
    return min($subtotal, $amt);
}

function pos_large_discount($amount, $subtotal)
{
    return $amount > 0 && ($amount >= POS_LARGE_DISCOUNT_VND || ($subtotal > 0 && $amount / $subtotal * 100 >= POS_LARGE_DISCOUNT_PCT));
}

// Số "còn đặt" từng món (kho trừ món nằm trong đơn mở), theo id món
function pos_availability()
{
    $out = array();
    $w = warehouse_list();
    foreach (($w['items'] ?? array()) as $it) $out[(int) $it['id']] = array('available' => (int) $it['available'], 'unit' => $it['unit']);
    return $out;
}

// Danh mục có tùy chọn (size, đường, đá, topping)
function pos_category_options()
{
    $out = array();
    foreach (db()->fetchAll("SELECT category_name, has_options FROM categories") as $c) $out[$c['category_name']] = (int) $c['has_options'] === 1;
    return $out;
}

// Xử lý toàn bộ form POST của trang Bàn / Đơn hàng. Trả về array('error' => '', 'ok' => '', 'to' => url|null)
function pos_handle_post($me)
{
    $r = array('error' => '', 'ok' => '', 'to' => null);
    if (!post_csrf_ok()) { $r['error'] = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.'; return $r; }
    $act = (string) ($_POST['pos'] ?? '');
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $back = 'index.php?page=tables&order=' . $orderId;
    $isCashier = pos_is_cashier($me);

    try {
        switch ($act) {
            case 'add_item': {
                $menuId = (int) ($_POST['menu_id'] ?? 0);
                $qty = (int) ($_POST['quantity'] ?? 1);
                $tableId = (int) ($_POST['table_id'] ?? 0);
                $av = pos_availability();
                $m = db()->fetchOne("SELECT m.item_name, c.category_name, c.has_options FROM menu_items m JOIN categories c ON c.category_id = m.category_id WHERE m.item_id = ?", array($menuId));
                if (!$m) { $r['error'] = 'Món này vừa bị Quản lý ẩn hoặc xóa khỏi menu.'; break; }
                $have = $av[$menuId]['available'] ?? 0;
                if ($have < $qty) { $r['error'] = $have < 1 ? 'Món này đã hết hàng.' : 'Chỉ còn đặt được ' . $have . ' ' . ($av[$menuId]['unit'] ?? 'ly') . ' món này.'; break; }
                $options = array(); $noteParts = array();
                if ((int) $m['has_options'] === 1 && isset($_POST['size'])) {
                    $size = (string) $_POST['size']; $sugar = (string) ($_POST['sugar'] ?? '50%'); $ice = (string) ($_POST['ice'] ?? 'Ít đá');
                    $tops = array_values(array_filter((array) ($_POST['toppings'] ?? array()), 'is_string'));
                    $options = array('size' => $size, 'sugar' => $sugar, 'ice' => $ice, 'toppings' => $tops);
                    $noteParts = array_merge(array('Size ' . $size, $sugar . ' đường', $ice), array_map(function ($t) { return '+' . $t; }, $tops));
                }
                $free = trim((string) ($_POST['note'] ?? ''));
                $note = implode(', ', $noteParts) . ($free !== '' ? ($noteParts ? ' · ' : '') . $free : '');
                if ($orderId <= 0) {
                    list($orderId, $err) = orders_create(array('type' => 'dinein', 'tableId' => $tableId), $me);
                    if ($err) { $r['error'] = $err; break; }
                }
                list($lineId, $err) = orders_add_item(array('orderId' => $orderId, 'menuId' => $menuId, 'quantity' => $qty, 'note' => $note, 'options' => $options));
                if ($err) { $r['error'] = $err; break; }
                $r['to'] = 'index.php?page=tables&order=' . $orderId . '&cat=' . rawurlencode((string) ($_POST['cat'] ?? '')) . '&q=' . rawurlencode((string) ($_POST['q'] ?? ''));
                $r['ok'] = 'Đã thêm ' . $m['item_name'] . '.';
                break;
            }
            case 'update_item':
                $err = orders_update_item((int) ($_POST['item_id'] ?? 0), array('quantity' => (int) ($_POST['quantity'] ?? 0), 'note' => (string) ($_POST['note'] ?? '')));
                if ($err) $r['error'] = $err; else $r['ok'] = 'Đã cập nhật món.';
                break;
            case 'delete_item':
                $err = orders_delete_item((int) ($_POST['item_id'] ?? 0));
                if ($err) $r['error'] = $err; else $r['ok'] = 'Đã xóa món.';
                break;
            case 'cancel_item': {
                $itemId = (int) ($_POST['item_id'] ?? 0);
                $reason = trim((string) ($_POST['reason'] ?? ''));
                $line = db()->fetchOne("SELECT oi.item_name, oi.unit_price, oi.status, o.table_name FROM order_items oi JOIN orders o ON o.order_id = oi.order_id WHERE oi.order_item_id = ?", array($itemId));
                $err = orders_cancel_item($itemId, $reason, $me);
                if ($err) { $r['error'] = $err; break; }
                if ($line) {
                    if ($line['status'] === 'done') pos_log($me, 'void_done', 'Hủy món ĐÃ LÀM: 1 × ' . $line['item_name'] . ' (' . $line['table_name'] . '). Lý do: ' . $reason, (int) $line['unit_price'], true);
                    else pos_log($me, 'void_item', 'Hủy món chưa xong: 1 × ' . $line['item_name'] . ' (' . $line['table_name'] . ')', (int) $line['unit_price'], false);
                }
                $r['ok'] = 'Đã hủy 1 phần món.';
                break;
            }
            case 'confirm':
                $err = orders_confirm($orderId);
                if ($err) $r['error'] = $err; else { $r['ok'] = 'Đã gửi bếp.'; $r['to'] = 'index.php?page=tables'; }
                break;
            case 'cancel_order':
                $err = orders_cancel_empty($orderId);
                if ($err) $r['error'] = $err; else { $r['ok'] = 'Đã hủy đơn.'; $r['to'] = 'index.php?page=tables'; }
                break;
            case 'create_togo': {
                if (!$isCashier) { $r['error'] = 'Chỉ Thu ngân tạo đơn mang đi / giao hàng.'; break; }
                $type = (string) ($_POST['type'] ?? 'takeaway');
                $name = $type === 'delivery' ? 'Giao hàng' : 'Mang đi';
                list($id, $err) = orders_create(array('type' => $type, 'tableName' => $name, 'customer' => (string) ($_POST['customer'] ?? ''), 'address' => (string) ($_POST['address'] ?? '')), $me);
                if ($err) $r['error'] = $err; else $r['to'] = 'index.php?page=tables&order=' . $id;
                break;
            }
            case 'move': {
                $dst = (int) ($_POST['target_table'] ?? 0);
                $o = db()->fetchOne("SELECT table_name FROM orders WHERE order_id = ?", array($orderId));
                $err = orders_move($orderId, $dst);
                if ($err) { $r['error'] = $err; break; }
                $nt = db()->fetchOne("SELECT table_name FROM dining_tables WHERE table_id = ?", array($dst));
                pos_log($me, 'table', 'Chuyển ' . ($o['table_name'] ?? '') . ' → ' . ($nt['table_name'] ?? ''));
                $r['ok'] = 'Đã chuyển bàn.';
                break;
            }
            case 'merge': {
                $src = (int) ($_POST['source_order'] ?? 0);
                $s = db()->fetchOne("SELECT table_name FROM orders WHERE order_id = ?", array($src));
                $t = db()->fetchOne("SELECT table_name FROM orders WHERE order_id = ?", array($orderId));
                $err = orders_merge($src, $orderId);
                if ($err) { $r['error'] = $err; break; }
                pos_log($me, 'table', 'Gộp ' . ($s['table_name'] ?? '') . ' vào ' . ($t['table_name'] ?? ''));
                $r['ok'] = 'Đã gộp bàn.';
                break;
            }
            case 'split': {
                $ids = array_map('intval', (array) ($_POST['split_items'] ?? array()));
                $dst = (int) ($_POST['target_table'] ?? 0);
                if (!$ids) { $r['error'] = 'Chọn ít nhất một dòng món để tách.'; break; }
                $o = pos_find_order(pos_open_orders(), $orderId);
                if ($o && count($ids) >= count($o['items'])) { $r['error'] = 'Chọn ít nhất 1 dòng và không chọn hết.'; break; }
                list($newOrder, $err) = orders_split($orderId, $dst, $ids, $me);
                if ($err) { $r['error'] = $err; break; }
                $nt = db()->fetchOne("SELECT table_name FROM dining_tables WHERE table_id = ?", array($dst));
                pos_log($me, 'table', 'Tách bill ' . ($o['tableName'] ?? '') . ' → ' . ($nt['table_name'] ?? ''));
                $r['ok'] = 'Đã tách bill sang ' . ($nt['table_name'] ?? 'bàn mới') . '.';
                break;
            }
            case 'soldout': {
                if (!$isCashier) { $r['error'] = 'Chỉ Thu ngân báo hết món.'; break; }
                $menuId = (int) ($_POST['menu_id'] ?? 0);
                $flag = !empty($_POST['sold_out']);
                $err = menu_set_sold_out($menuId, $flag);
                if ($err) { $r['error'] = $err; break; }
                $m = db()->fetchOne("SELECT item_name FROM menu_items WHERE item_id = ?", array($menuId));
                pos_log($me, 'soldout', ($flag ? 'Đánh dấu HẾT' : 'Mở bán lại') . ': ' . ($m['item_name'] ?? ''), 0);
                $r['ok'] = $flag ? 'Đã báo hết món.' : 'Đã mở bán lại.';
                break;
            }
            case 'pay': {
                $o = pos_find_order(pos_open_orders(), $orderId);
                if (!$o || $o['sent_total'] <= 0) { $r['error'] = 'Đơn này chưa có món nào đã gửi bếp để thanh toán.'; break; }
                $sub = (int) $o['sent_total'];
                $method = (string) ($_POST['method'] ?? '');
                if (!isset(POS_METHODS[$method])) { $r['error'] = 'Chọn cách thanh toán.'; break; }
                $disc = 0; $code = '';
                if ($isCashier) {
                    $voucherIn = trim((string) ($_POST['voucher'] ?? ''));
                    if ($voucherIn !== '') {
                        $v = pos_voucher($voucherIn, $sub);
                        if (!$v) { $r['error'] = 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn.'; break; }
                        $disc = $v['amount']; $code = $v['code'];
                    } else {
                        $disc = pos_manual_discount((string) ($_POST['disc_type'] ?? 'vnd'), $_POST['disc_val'] ?? 0, $sub);
                    }
                }
                $total = $sub - $disc;
                $given = (float) ($_POST['given'] ?? 0);
                if ($method === 'cash' && $given < $total) { $r['error'] = 'Khách đưa chưa đủ tiền.'; break; }
                if ($o['not_ready'] > 0 && empty($_POST['confirm_notready'])) { $r['error'] = 'Còn ' . $o['not_ready'] . ' món chưa xong. Tick "Vẫn thanh toán" nếu khách không đợi.'; break; }
                $large = pos_large_discount($disc, $sub);
                if ($large && empty($_POST['confirm_large'])) { $r['error'] = 'Giảm giá lớn (' . money($disc) . '): tick xác nhận — thao tác sẽ ghi Nhật ký cho Quản lý.'; break; }
                list($invoice, $err) = orders_pay(array(
                    'orderId' => $orderId, 'paymentMethod' => $method === 'qr' || $method === 'ewallet' ? $method : $method,
                    'discount' => $disc, 'expectedSubtotal' => $sub, 'voucher' => $code, 'paymentRef' => trim((string) ($_POST['ref'] ?? '')),
                ), $me);
                if ($err) { $r['error'] = $err; break; }
                if ($disc > 0) {
                    pos_log($me, $large ? 'discount_big' : 'discount', 'Giảm ' . money($disc) . ' / ' . money($sub) . ' cho ' . $o['tableName'] . ' (' . ($code ? 'mã ' . $code : 'thủ công') . ') — ' . ($invoice['id'] ?? ''), $disc, $large);
                }
                $_SESSION['pos_paid'] = array('method' => $method, 'given' => $given, 'total' => $total);
                $r['to'] = 'index.php?page=tables&receipt=' . $orderId;
                break;
            }
            case 'refund': {
                if (!$isCashier) { $r['error'] = 'Chỉ Thu ngân hoặc Quản lý được hoàn tiền.'; break; }
                $reason = trim((string) ($_POST['reason'] ?? ''));
                if ($reason === '') { $r['error'] = 'Chưa nhập lý do — chưa hoàn tiền.'; break; }
                $inv = db()->fetchOne("SELECT order_code, table_name, total, payment_method FROM orders WHERE order_id = ? AND status = 'paid'", array($orderId));
                $err = orders_refund_paid($orderId, $reason, $me);
                if ($err) { $r['error'] = $err; break; }
                if ($inv) pos_log($me, 'refund', 'Hoàn tiền ' . $inv['order_code'] . ' (' . $inv['table_name'] . ', ' . ($inv['payment_method'] === 'cash' ? 'Tiền mặt' : 'Không dùng tiền mặt') . '). Lý do: ' . $reason, (int) $inv['total'], true);
                $r['ok'] = 'Đã hoàn tiền hóa đơn.';
                $r['to'] = 'index.php?page=orders&tab=cancelled';
                break;
            }
            default:
                $r['error'] = 'Thao tác không hợp lệ.';
        }
    } catch (ApiException $e) {
        $r['error'] = $e->getMessage();
    }
    if ($r['error'] === '' && $r['to'] === null) $r['to'] = $back;
    return $r;
}

// Chạy form POST của trang Bàn / Đơn hàng: ghi thông báo vào session rồi redirect (Post/Redirect/Get)
function pos_run_post($me)
{
    $res = pos_handle_post($me);
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $tableId = (int) ($_POST['table_id'] ?? 0);
    if ((string) ($_POST['back'] ?? '') === 'orders') {
        $tab = in_array((string) ($_POST['tab'] ?? ''), array('processing', 'done', 'cancelled'), true) ? (string) $_POST['tab'] : 'done';
        $errTo = 'index.php?page=orders&tab=' . $tab;
    } else {
        $errTo = 'index.php?page=tables' . ($orderId > 0 ? '&order=' . $orderId : ($tableId > 0 ? '&table=' . $tableId : ''))
            . ((string) ($_POST['pos'] ?? '') === 'pay' ? '&pay=1' : '');
    }
    if ($res['error'] !== '') {
        $_SESSION['pos_flash'] = array('error' => $res['error'], 'ok' => '');
        redirect($errTo);
    }
    $_SESSION['pos_flash'] = array('error' => '', 'ok' => $res['ok']);
    redirect($res['to'] ?: $errTo);
}

function pos_take_flash()
{
    $f = $_SESSION['pos_flash'] ?? array('error' => '', 'ok' => '');
    unset($_SESSION['pos_flash']);
    return $f;
}

function pos_url($params = array())
{
    return 'index.php?' . http_build_query(array('page' => 'tables') + $params);
}

function pos_fmt_time($iso)
{
    return $iso ? date('H:i', strtotime($iso)) : '—';
}
