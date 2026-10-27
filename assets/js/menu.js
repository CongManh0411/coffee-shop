// ============================================================
// menu.js — LOGIC RIÊNG CỦA TRANG THỰC ĐƠN (chỉ nạp trong Menu.html)
// Cần nạp SAU auth.js, common.js, menu-data.js, inventory.js.
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
// MenuAPI — gọi backend (api/modules/menu.php)
// ============================================================
const MENU_STORAGE_KEY = MENU_KEY; // khai báo trong menu-data.js

// Bản sao cho các trang chưa có API (Bán hàng, Kho) đọc bằng localStorage
function mirrorMenu(data) {
    try { localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(data)); } catch (e) {}
}

try { localStorage.removeItem('coffee_menu_recipes_v1'); } catch (e) {}

const MenuAPI = {
    async deleteItem(id) {
        if ((window.InventoryAPI && InventoryAPI.reserved()[String(id)]) > 0) {
            throw new Error('Không thể xóa món đang có trong đơn hàng.');
        }
        await Api.del('menu&id=' + id);
        if (window.InventoryAPI) InventoryAPI.drop(id);
        return true;
    },
    async toggleHideItem(id) {
        let item = null;
        currentMenuData.forEach(c => c.items.forEach(it => { if (String(it.id) === String(id)) item = it; }));
        if (!item) throw new Error('Không tìm thấy món.');
        await Api.put('menu&id=' + id, { hidden: !item.hidden });
        return true;
    },
};

// ============================================================
// DỮ LIỆU PHP CHO CÁC THAO TÁC (tab Sửa món)
// ============================================================
const menuDataEl = document.getElementById('adminMenuData');
let currentMenuData = menuDataEl ? JSON.parse(menuDataEl.textContent) : [];

function attachMenuItemEvents() {
    document.querySelectorAll('#menuCategoryList button[data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const action = btn.dataset.action;
            const id = btn.dataset.id;

            if (action === 'edit') {
                openEditItemForm(id);
                return;
            }

            if (action === 'delete' && !confirm('Xoá món này?')) return;

            const rowEl = btn.closest('.menu-item');
            const rowButtons = rowEl ? rowEl.querySelectorAll('button[data-action]') : [btn];
            rowButtons.forEach(b => (b.disabled = true));

            try {
                if (action === 'delete') {
                    await MenuAPI.deleteItem(id);
                } else if (action === 'hide') {
                    await MenuAPI.toggleHideItem(id);
                }
                window.location.reload();
            } catch (err) {
                console.error('Không thực hiện được thao tác:', err);
                alert(err.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                rowButtons.forEach(b => (b.disabled = false));
            }
        });
    });
}

// ============================================================
// FORM "THÊM / SỬA MÓN"
// ============================================================
let pendingImg; // undefined = không đổi ảnh
const imgInputEl = document.getElementById('itemImage');
const imgPrevEl = document.getElementById('itemImagePreview');
function showImgPreview(src) { if (!imgPrevEl) return; imgPrevEl.style.display = src ? 'block' : 'none'; imgPrevEl.src = src || ''; }
if (imgInputEl) imgInputEl.addEventListener('change', () => {
    const f = imgInputEl.files[0]; if (!f) return;
    const fr = new FileReader();
    fr.onload = () => { const im = new Image(); im.onload = () => { // thu nhỏ còn 240px để không đầy localStorage
        const k = Math.min(1, 240 / Math.max(im.width, im.height)), c = document.createElement('canvas');
        c.width = im.width * k; c.height = im.height * k; c.getContext('2d').drawImage(im, 0, 0, c.width, c.height);
        pendingImg = c.toDataURL('image/jpeg', 0.8); showImgPreview(pendingImg); }; im.src = fr.result; };
    fr.readAsDataURL(f);
});
const addItemFormEl = document.querySelector('.add-item-form');
const itemCategorySelectEl = document.getElementById('itemCategory');

function resetAddFormToAddMode() {
    if (!addItemFormEl) return;
    addItemFormEl.reset();
    pendingImg = undefined; showImgPreview('');
    const editIdInput = document.getElementById('editItemId');
    if (editIdInput) editIdInput.value = '';
    const headerH2 = document.querySelector('#panel-add .menu-page-header h2');
    if (headerH2) headerH2.textContent = 'Thêm món mới';
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
        const imageDataEl = document.getElementById('itemImageData');
        if (pendingImg) {
            imageDataEl.name = 'img';
            imageDataEl.value = pendingImg;
        } else {
            imageDataEl.removeAttribute('name');
            imageDataEl.value = '';
        }
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

// ============================================================
// TÌM KIẾM MÓN (tab "Sửa món")
// ============================================================
function initMenuSearch() {
    const searchInput = document.getElementById('menuSearchInput');
    if (!searchInput) return;
    searchInput.addEventListener('input', () => {
        const keyword = searchInput.value.trim().toLocaleLowerCase('vi-VN');
        let visibleCount = 0;
        document.querySelectorAll('#menuCategoryList .menu-category').forEach(group => {
            let groupCount = 0;
            group.querySelectorAll('.menu-item').forEach(row => {
                const text = row.querySelector('.menu-item-name').textContent + ' ' +
                    (row.querySelector('.menu-item-desc')?.textContent || '') + ' ' + group.querySelector('h3').textContent;
                row.hidden = !text.toLocaleLowerCase('vi-VN').includes(keyword);
                if (!row.hidden) groupCount++;
            });
            group.hidden = groupCount === 0;
            visibleCount += groupCount;
        });
        document.getElementById('menuEmptyState').hidden = visibleCount > 0;
    });
}

// Giữ nguyên HTML PHP ban đầu, không gọi API để dựng lại danh sách.
mirrorMenu(currentMenuData);
attachMenuItemEvents();
initMenuSearch();
