const Api = {
    async call(route, method = 'GET', body) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch((window.COFFEE_ROOT || './') + 'api/index.php?route=' + route, {
            method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: body ? JSON.stringify(body) : undefined
        });
        let json;
        try { json = await res.json(); }
        catch (e) { throw new Error('Máy chủ trả về dữ liệu không hợp lệ.'); }
        if (res.status === 401 && new URLSearchParams(location.search).get('page') !== 'login') {
            window.SERVER_USER = null;
            location.replace((window.COFFEE_ROOT || './') + 'admin/index.php?page=login');
        }
        if (!json.ok) throw new Error(json.error || 'Có lỗi xảy ra.');
        return json.data;
    },
    get(route) { return Api.call(route); },
    post(route, body) { return Api.call(route, 'POST', body); },
    put(route, body) { return Api.call(route, 'PUT', body); },
    del(route) { return Api.call(route, 'DELETE'); }
};
