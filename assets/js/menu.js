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
    async getMenu() {
        const data = await Api.get('menu');
        mirrorMenu(data);
        return data;
    },
    // payload = { category, newCategory, name, price, img }
    async addItem(payload) {
        return Api.post('menu', payload);
    },
    async updateItem(id, payload) {
        return Api.put('menu&id=' + id, payload);
    },
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
// RENDER DANH SÁCH MÓN (tab Sửa món)
// ============================================================
let currentMenuData = [];

function escHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function formatPrice(price) {
    return price.toLocaleString('vi-VN') + 'đ';
}

function createActionButton(className, action, id, text) {
    const btn = document.createElement('button');
    btn.className = className;
    btn.dataset.action = action;
    btn.dataset.id = id;
    btn.textContent = text;
    return btn;
}

function createMenuItemEl(item, categoryName) {
    const itemEl = document.createElement('div');
    itemEl.className = 'menu-item';
    itemEl.dataset.itemId = item.id;

    const thumb = document.createElement('img');
    thumb.className = 'menu-item-thumb';
    thumb.alt = item.name;
    thumb.src = itemImage(item, categoryName);
    itemEl.appendChild(thumb);

    const leftEl = document.createElement('div');

    const nameEl = document.createElement('div');
    nameEl.className = 'menu-item-name';
    nameEl.textContent = item.name;
    leftEl.appendChild(nameEl);

    if (item.desc) {
        const descEl = document.createElement('div');
        descEl.className = 'menu-item-desc';
        descEl.textContent = item.desc;
        leftEl.appendChild(descEl);
    }

    const rightEl = document.createElement('div');
    rightEl.className = 'menu-item-right';

    const priceEl = document.createElement('div');
    priceEl.className = 'menu-item-price';
    priceEl.textContent = formatPrice(item.price);
    rightEl.appendChild(priceEl);

    const actionsEl = document.createElement('div');
    actionsEl.className = 'menu-item-actions';
    actionsEl.appendChild(createActionButton('btn-outline', 'edit', item.id, 'Sửa'));
    actionsEl.appendChild(createActionButton('btn-outline', 'hide', item.id, item.hidden ? 'Hiện' : 'Ẩn'));
    actionsEl.appendChild(createActionButton('btn-danger', 'delete', item.id, 'Xóa'));
    rightEl.appendChild(actionsEl);

    itemEl.appendChild(leftEl);
    itemEl.appendChild(rightEl);

    if (item.hidden) itemEl.style.opacity = '0.5';

    return itemEl;
}

function createCategoryEl(category) {
    const categoryEl = document.createElement('div');
    categoryEl.className = 'menu-category';

    const titleEl = document.createElement('h3');
    titleEl.textContent = category.category;
    categoryEl.appendChild(titleEl);

    category.items.forEach(item => {
        categoryEl.appendChild(createMenuItemEl(item, category.category));
    });

    return categoryEl;
}

function renderMenu(data) {
    const container = document.getElementById('menuCategoryList');
    if (!container) return;
    container.innerHTML = '';

    if (!data || data.length === 0) {
        const emptyEl = document.createElement('div');
        emptyEl.className = 'menu-empty-state';
        emptyEl.textContent = 'Chưa có món nào. Vào tab "Thêm món" để bắt đầu.';
        container.appendChild(emptyEl);
        return;
    }

    data.forEach(category => {
        container.appendChild(createCategoryEl(category));
    });

    attachMenuItemEvents();
}

async function reloadMenu() {
    const container = document.getElementById('menuCategoryList');
    if (!container) return;

    container.innerHTML = '';
    const loadingEl = document.createElement('div');
    loadingEl.className = 'menu-loading';
    loadingEl.textContent = 'Đang tải menu...';
    container.appendChild(loadingEl);

    try {
        currentMenuData = await MenuAPI.getMenu();
        renderMenu(currentMenuData);
        populateCategorySelect();
    } catch (err) {
        console.error('Không tải được menu:', err);
        container.innerHTML = '';
        const errorEl = document.createElement('div');
        errorEl.className = 'menu-error-state';
        errorEl.textContent = 'Không tải được menu. Vui lòng tải lại trang.';
        container.appendChild(errorEl);
    }
}

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
                await reloadMenu();
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

function populateCategorySelect() {
    if (!itemCategorySelectEl) return;
    const categories = currentMenuData.map(c => c.category);
    itemCategorySelectEl.innerHTML =
        '<option value="">-- Chọn danh mục --</option>' +
        categories.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
}

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

        const submitBtn = addItemFormEl.querySelector('.btn-submit');
        const originalBtnText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang lưu...';
        }

        try {
            const payload = {
                category: result.cleanCategory,
                newCategory: result.cleanNewCategory,
                name: result.cleanName,
                desc: description.trim(),
                price: result.cleanPrice,
                img: pendingImg,
            };
            if (editId) {
                await MenuAPI.updateItem(editId, payload);
            } else {
                await MenuAPI.addItem(payload);
            }
            resetAddFormToAddMode();
            await reloadMenu();
            document.querySelector('.topnav-item[data-action="edit"]').click();
        } catch (err) {
            console.error('Không lưu được món:', err);
            alert(err.message || 'Có lỗi xảy ra, không lưu được món. Vui lòng thử lại.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }
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
function filterMenuData(data, keyword) {
    if (!keyword) return data;
    const lowerKeyword = keyword.trim().toLowerCase();
    if (!lowerKeyword) return data;
    return data
        .map(cat => ({
            category: cat.category,
            items: cat.items.filter(it => (it.name + ' ' + (it.desc || '') + ' ' + cat.category).toLowerCase().includes(lowerKeyword)),
        }))
        .filter(cat => cat.items.length > 0);
}

function initMenuSearch() {
    const searchInput = document.getElementById('menuSearchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', () => {
        renderMenu(filterMenuData(currentMenuData, searchInput.value));
    });
}

// ============================================================
// KHỞI ĐỘNG TRANG
// ============================================================
initMenuSearch();
reloadMenu();
