// ============================================================
// menu.js — LOGIC RIÊNG CỦA TRANG THỰC ĐƠN (chỉ nạp trong Menu.html)
// Chỉ lo phần giao diện của form Thêm / Sửa món (ẩn / xóa món là form POST xử lý bằng PHP).
// Đã bỏ "Định mức nguyên liệu": kho giờ chỉ quản lý số ly / phần ở trang Kho.
// ============================================================

// ---- Tab trên cùng (Sửa món / Thêm món) ----
const tabs = document.querySelectorAll('.topnav-item');
const panels = document.querySelectorAll('.tab-panel');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        const targetPanel = document.getElementById('panel-' + tab.dataset.action);
        if (targetPanel) targetPanel.classList.add('active');

        // Mỗi lần sang tab "Thêm món" đều về chế độ thêm mới;
        // openEditItemForm() sẽ đổ lại dữ liệu sửa ngay sau lệnh click này.
        if (tab.dataset.action === 'add' && typeof resetAddFormToAddMode === 'function') {
            resetAddFormToAddMode();
        }
    });
});

// ============================================================
// ============================================================
// DỮ LIỆU PHP CHO CÁC THAO TÁC (tab Sửa món)
// ============================================================
const menuDataEl = document.getElementById('adminMenuData');
let currentMenuData = menuDataEl ? JSON.parse(menuDataEl.textContent) : [];

// Chỉ nút "Sửa" cần JavaScript (đổ dữ liệu vào form). Ẩn/hiện và xóa món là form POST xử lý bằng PHP.
function attachMenuItemEvents() {
    document.querySelectorAll('#menuCategoryList button[data-action="edit"]').forEach(btn => {
        btn.addEventListener('click', () => openEditItemForm(btn.dataset.id));
    });
}

// ============================================================
// FORM "THÊM / SỬA MÓN"
// ============================================================
// Ảnh món: chọn file -> gửi nguyên file lên server, server lưu vào assets/images/menu/
const imgInputEl = document.getElementById('itemImage');
const imgPrevEl = document.getElementById('itemImagePreview');
function showImgPreview(src) {
    if (!imgPrevEl) return;
    // Ảnh lưu dạng đường dẫn assets/... (trang admin nằm trong thư mục admin/ nên thêm ../)
    if (src && src.indexOf('assets/') === 0) src = '../' + src;
    imgPrevEl.style.display = src ? 'block' : 'none';
    imgPrevEl.src = src || '';
}
if (imgInputEl) imgInputEl.addEventListener('change', () => {
    const f = imgInputEl.files[0];
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) { alert('Ảnh quá lớn (tối đa 5MB).'); imgInputEl.value = ''; return; }
    showImgPreview(URL.createObjectURL(f));
});
const addItemFormEl = document.querySelector('.add-item-form');
const itemCategorySelectEl = document.getElementById('itemCategory');

function resetAddFormToAddMode() {
    if (!addItemFormEl) return;
    addItemFormEl.reset();
    showImgPreview('');
    const editIdInput = document.getElementById('editItemId');
    if (editIdInput) editIdInput.value = '';
    const headerH2 = document.querySelector('#panel-add .menu-page-header h2');
    if (headerH2) headerH2.textContent = 'Thêm món mới';
    const hintP = document.getElementById('addPanelHint');
    if (hintP) hintP.textContent = 'Chọn danh mục có sẵn hoặc tạo danh mục mới, rồi nhập tên món và giá. Mô tả và ảnh không bắt buộc. Món mới có tồn kho là 0 và chưa bán được, hãy nhập hàng ở mục Kho.';
    const submitBtn = addItemFormEl.querySelector('.btn-submit');
    if (submitBtn) submitBtn.textContent = 'Thêm món + danh mục (nếu có)';
    const cancelBtn = document.getElementById('btnCancelEdit');
    if (cancelBtn) cancelBtn.style.display = 'none';
}

function getFlatMenuItems() {
    const flat = [];
    currentMenuData.forEach(category => {
        category.items.forEach(item => {
            flat.push({ ...item, category: category.category });
        });
    });
    return flat;
}

function openEditItemForm(id) {
    if (!addItemFormEl) return;
    const item = getFlatMenuItems().find(it => String(it.id) === String(id));
    if (!item) return;

    document.querySelector('.topnav-item[data-action="add"]').click(); // tự gọi resetAddFormToAddMode()

    const editIdInput = document.getElementById('editItemId');
    if (editIdInput) editIdInput.value = item.id;
    if (itemCategorySelectEl) itemCategorySelectEl.value = item.category;
    document.getElementById('itemPrice').value = item.price;
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemDescription').value = item.desc || '';
    showImgPreview(item.img || '');

    const headerH2 = document.querySelector('#panel-add .menu-page-header h2');
    if (headerH2) headerH2.textContent = 'Sửa món';
    const hintP = document.getElementById('addPanelHint');
    if (hintP) hintP.textContent = 'Chỉnh thông tin món rồi bấm "Lưu thay đổi". Số lượng tồn kho không đổi ở đây, hãy chỉnh ở mục Kho.';
    const submitBtn = addItemFormEl.querySelector('.btn-submit');
    if (submitBtn) submitBtn.textContent = 'Lưu thay đổi';
    const cancelBtn = document.getElementById('btnCancelEdit');
    if (cancelBtn) cancelBtn.style.display = 'block';
}

function validateItemForm({ category, newCategory, price, name, editId }) {
    const cleanName = name.trim();
    const cleanNewCategory = newCategory.trim();
    const cleanPrice = Number(price);

    if (!cleanName) return { valid: false, message: 'Vui lòng nhập tên món.' };
    if (!category && !cleanNewCategory) return { valid: false, message: 'Vui lòng chọn hoặc tạo một danh mục.' };
    if (price === '' || Number.isNaN(cleanPrice)) return { valid: false, message: 'Giá món phải là một số.' };
    if (cleanPrice <= 0) return { valid: false, message: 'Giá món phải lớn hơn 0.' };

    let finalCategory = category;
    let finalNewCategory = '';
    if (cleanNewCategory) {
        const existing = currentMenuData.find(
            c => c.category.trim().toLowerCase() === cleanNewCategory.toLowerCase()
        );
        finalNewCategory = existing ? '' : cleanNewCategory;
        finalCategory = existing ? existing.category : '';
    }

    const targetCategoryName = finalNewCategory || finalCategory;
    const categoryObj = currentMenuData.find(c => c.category === targetCategoryName);
    if (categoryObj) {
        const duplicate = categoryObj.items.find(
            it => it.name.trim().toLowerCase() === cleanName.toLowerCase() && String(it.id) !== String(editId)
        );
        if (duplicate) {
            return { valid: false, message: `Món "${cleanName}" đã tồn tại trong danh mục "${targetCategoryName}".` };
        }
    }

    return {
        valid: true,
        message: '',
        cleanCategory: finalCategory,
        cleanNewCategory: finalNewCategory,
        cleanPrice,
        cleanName,
    };
}

if (addItemFormEl) {
    addItemFormEl.addEventListener('submit', async (e) => {
        e.preventDefault();

        const editId = document.getElementById('editItemId') ? document.getElementById('editItemId').value : '';
        const category = itemCategorySelectEl.value;
        const newCategory = document.getElementById('newCategory').value;
        const price = document.getElementById('itemPrice').value;
        const name = document.getElementById('itemName').value;
        const description = document.getElementById('itemDescription').value;

        const result = validateItemForm({ category, newCategory, price, name, editId });
        if (!result.valid) {
            alert(result.message);
            return;
        }

        itemCategorySelectEl.value = result.cleanCategory;
        document.getElementById('newCategory').value = result.cleanNewCategory;
        addItemFormEl.submit();
    });
}

const btnCancelEditEl = document.getElementById('btnCancelEdit');
if (btnCancelEditEl) {
    btnCancelEditEl.addEventListener('click', () => {
        resetAddFormToAddMode();
        document.querySelector('.topnav-item[data-action="edit"]').click();
    });
}

// Tìm kiếm + phân trang do server xử lý qua URL (?page=menu&q=...&p=...).

// Giữ nguyên HTML PHP ban đầu, không gọi API để dựng lại danh sách.
attachMenuItemEvents();
