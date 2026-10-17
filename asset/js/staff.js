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
const STAFF_KEY = 'coffee_staff_v1';
function loadStaff() {
    try { const s = JSON.parse(localStorage.getItem(STAFF_KEY)); if (Array.isArray(s) && s.length) return s; } catch (e) {}
    return [
        { id: 1, fullName: 'Nguyễn Văn A', username: 'quanly',  password: '123456', role: 'Quản lý' },
        { id: 2, fullName: 'Phạm Văn D',   username: 'bep',     password: '123456', role: 'Bếp' },
        { id: 3, fullName: 'Lê Văn C',     username: 'phucvu',  password: '123456', role: 'Phục vụ' },
        { id: 4, fullName: 'Trần Thị B',   username: 'thungan', password: '123456', role: 'Thu ngân' }
    ];
}
function saveStaff() { try { localStorage.setItem(STAFF_KEY, JSON.stringify(staffList)); } catch (e) {} }
let staffList = loadStaff();

let currentEditingId = null;
let nextId = Math.max(0, ...staffList.map(x => x.id)) + 1;

// ==================== Initialize Page ====================
function initStaffManagement() {
    // Không cho chọn ngày sinh trong tương lai
    document.getElementById('birthDate').max = new Date().toISOString().slice(0, 10);

    attachTabListeners();
    renderStaffList();
    document.getElementById('staffForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('btnCancelEdit').addEventListener('click', () => {
        resetForm();
        switchStaffTab('list');
    });
}

// ==================== Render Functions ====================
function renderStaffList() {
    saveStaff();
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
function handleFormSubmit(e) {
    e.preventDefault();

    const fullName = document.getElementById('fullName').value.trim();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.getElementById('role').value;
    const phone = document.getElementById('phone').value.trim().replace(/[\s.-]/g, '');
    const email = document.getElementById('email').value.trim();
    const gender = document.getElementById('gender').value;
    const birthDate = document.getElementById('birthDate').value;

    if (!fullName || !username || !password || !role) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return;
    }

    if (staffList.some(x => x.username.toLowerCase() === username.toLowerCase() && x.id !== currentEditingId)) {
        alert('Tài khoản "' + username + '" đã tồn tại, hãy chọn tên khác.');
        return;
    }

    if (!/^0\d{9}$/.test(phone)) {
        alert('Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.');
        return;
    }
    if (email && !/^\S+@\S+\.\S+$/.test(email)) {
        alert('Email chưa hợp lệ.');
        return;
    }
    if (birthDate) {
        const age = ageOf(birthDate);
        if (age < 16 || age > 80) {
            alert('Ngày sinh không hợp lệ (nhân viên phải từ 16 đến 80 tuổi).');
            return;
        }
    }

    if (currentEditingId !== null) {
        const staffIndex = staffList.findIndex(s => s.id === currentEditingId);
        if (staffIndex !== -1) {
            staffList[staffIndex] = { ...staffList[staffIndex], fullName, username, password, role, phone, email, gender, birthDate };
        }
    } else {
        staffList.push({ id: nextId++, fullName, username, password, role, phone, email, gender, birthDate });
    }

    resetForm();
    renderStaffList();
    switchStaffTab('list');
}

function resetForm() {
    document.getElementById('staffForm').reset();
    document.getElementById('submitBtn').textContent = 'Tạo tài khoản';
    document.getElementById('staffFormTitle').textContent = 'Thêm nhân viên mới';
    document.getElementById('editModeIndicator').classList.remove('active');
    document.getElementById('btnCancelEdit').style.display = 'none';
    currentEditingId = null;
}

// ==================== Edit Function ====================
function editStaff(id) {
    const staff = staffList.find(s => s.id === id);
    if (!staff) return;

    document.getElementById('fullName').value = staff.fullName;
    document.getElementById('username').value = staff.username;
    document.getElementById('password').value = staff.password;
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
function deleteStaff(id) {
    const me = (typeof AuthAPI !== 'undefined') ? AuthAPI.getSession() : null;
    if (me && me.staffId != null && String(me.staffId) === String(id)) {
        alert('Bạn không thể xóa tài khoản đang đăng nhập.');
        return;
    }
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này? Các ca đã xếp của họ cũng sẽ bị xóa.')) {
        staffList = staffList.filter(staff => staff.id !== id);
        // dọn các ca đã xếp của người này để không còn ca "mồ côi" trong lịch
        try {
            const shifts = JSON.parse(localStorage.getItem('coffee_shifts_v1'));
            if (Array.isArray(shifts)) localStorage.setItem('coffee_shifts_v1', JSON.stringify(shifts.filter(x => String(x.staffId) !== String(id))));
        } catch (e) {}
        if (currentEditingId === id) resetForm();
        renderStaffList();
    }
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