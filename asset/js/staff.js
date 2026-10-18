// ============================================================
// TRANG NHÂN VIÊN (Staff.html) — chỉ Quản lý vào được
// Mỗi nhân viên: { id, fullName, username, password, role, phone, email, gender, birthDate }
// phone / email / gender / birthDate là thông tin liên hệ, chỉ hiển thị ở trang này.
// ============================================================

// ==================== Tab (topnav) ====================
function switchStaffTab(action) {
    document.querySelectorAll('.topnav-item').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.action === action);
    });
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.toggle('active', panel.id === 'panel-' + action);
    });
}

function attachTabListeners() {
    document.querySelectorAll('.topnav-item').forEach(btn => {
        btn.addEventListener('click', () => switchStaffTab(btn.dataset.action));
    });
}

// ==================== State Management ====================
// ==================== State Management ====================
let staffList = [];
let currentEditingId = null;

async function loadStaff() {
    try {
        staffList = await Api.get('staff');
    } catch (err) {
        alert(err.message);
        staffList = [];
    }
    renderStaffList();
}
// ==================== Initialize Page ====================
function initStaffManagement() {
    // Không cho chọn ngày sinh trong tương lai
    document.getElementById('birthDate').max = new Date().toISOString().slice(0, 10);

    attachTabListeners();
    loadStaff();
    document.getElementById('staffForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('btnCancelEdit').addEventListener('click', () => {
        resetForm();
        switchStaffTab('list');
    });
}

// ==================== Render Functions ====================
function renderStaffList() {
    const staffListContainer = document.getElementById('staffList');

    if (staffList.length === 0) {
        staffListContainer.innerHTML = '<div class="menu-empty-state">Chưa có nhân viên nào. Vào tab "Thêm nhân viên" để bắt đầu.</div>';
        return;
    }

    staffListContainer.innerHTML = staffList.map(staff => `
        <div class="staff-item">
            <div class="staff-info">
                <div class="staff-name">${escapeHtml(staff.fullName)}</div>
                <div class="staff-username">@${escapeHtml(staff.username)}</div>
                <div class="staff-contact">
                    <span><i class="ri-phone-line"></i> ${staff.phone ? escapeHtml(staff.phone) : '<em>Chưa có SĐT</em>'}</span>
                    <span><i class="ri-mail-line"></i> ${staff.email ? escapeHtml(staff.email) : '<em>Chưa có email</em>'}</span>
                    <span><i class="ri-user-line"></i> ${staff.gender ? escapeHtml(staff.gender) : '<em>Chưa rõ giới tính</em>'}</span>
                    <span><i class="ri-cake-2-line"></i> ${staff.birthDate ? fmtBirth(staff.birthDate) + ' (' + ageOf(staff.birthDate) + ' tuổi)' : '<em>Chưa có ngày sinh</em>'}</span>
                </div>
            </div>
            <div class="staff-actions">
                <div class="staff-role">${escapeHtml(staff.role)}</div>
                <div class="action-buttons">
                    <button type="button" class="btn-outline" onclick="editStaff(${staff.id})">Sửa</button>
                    <button type="button" class="btn-danger" onclick="deleteStaff(${staff.id})">Xóa</button>
                </div>
            </div>
        </div>
    `).join('');
}

// ==================== Form Handling ====================
async function handleFormSubmit(e) {
    e.preventDefault();

    const fullName = document.getElementById('fullName').value.trim();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.getElementById('role').value;
    const phone = document.getElementById('phone').value.trim().replace(/[\s.-]/g, '');
    const email = document.getElementById('email').value.trim();
    const gender = document.getElementById('gender').value;
    const birthDate = document.getElementById('birthDate').value;

    if (!fullName || !username || !role || (currentEditingId === null && !password)) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return;
    }
    if (birthDate) {
        const age = ageOf(birthDate);
        if (age < 16 || age > 80) {
            alert('Ngày sinh không hợp lệ (nhân viên phải từ 16 đến 80 tuổi).');
            return;
        }
    }

    const payload = { fullName, username, password, role, phone, email, gender, birthDate };
    try {
        if (currentEditingId !== null) await Api.put('staff&id=' + currentEditingId, payload);
        else await Api.post('staff', payload);
    } catch (err) {
        alert(err.message);
        return;
    }

    resetForm();
    await loadStaff();
    switchStaffTab('list');
}

function resetForm() {
    document.getElementById('staffForm').reset();
    document.getElementById('submitBtn').textContent = 'Tạo tài khoản';
    document.getElementById('staffFormTitle').textContent = 'Thêm nhân viên mới';
    document.getElementById('editModeIndicator').classList.remove('active');
    document.getElementById('btnCancelEdit').style.display = 'none';
    document.getElementById('password').placeholder = 'Nhập mật khẩu';
    currentEditingId = null;
}

// ==================== Edit Function ====================
function editStaff(id) {
    const staff = staffList.find(s => s.id === id);
    if (!staff) return;

    document.getElementById('fullName').value = staff.fullName;
    document.getElementById('username').value = staff.username;
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Để trống nếu không đổi mật khẩu';
    document.getElementById('role').value = staff.role;
    document.getElementById('phone').value = staff.phone || '';
    document.getElementById('email').value = staff.email || '';
    document.getElementById('gender').value = staff.gender || '';
    document.getElementById('birthDate').value = staff.birthDate || '';

    document.getElementById('submitBtn').textContent = 'Lưu thay đổi';
    document.getElementById('staffFormTitle').textContent = 'Sửa nhân viên';
    document.getElementById('editModeIndicator').classList.add('active');
    document.getElementById('btnCancelEdit').style.display = 'block';
    currentEditingId = id;

    switchStaffTab('add');
}

// ==================== Delete Function ====================
async function deleteStaff(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) return;
    try {
        await Api.del('staff&id=' + id);
    } catch (err) {
        alert(err.message);
        return;
    }
    if (currentEditingId === id) resetForm();
    await loadStaff();
}

// ==================== Utility Functions ====================
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
}

// '2000-05-21' -> '21/05/2000'
function fmtBirth(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

// Tính tuổi từ ngày sinh dạng 'YYYY-MM-DD'
function ageOf(iso) {
    const b = new Date(iso + 'T00:00:00'), n = new Date();
    let a = n.getFullYear() - b.getFullYear();
    if (n.getMonth() < b.getMonth() || (n.getMonth() === b.getMonth() && n.getDate() < b.getDate())) a--;
    return a;
}

// ==================== Initialize on Page Load ====================
document.addEventListener('DOMContentLoaded', initStaffManagement);