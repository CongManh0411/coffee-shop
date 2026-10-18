const Api = {
    async call(route, method = 'GET', body) {
        const res = await fetch('api/index.php?route=' + route, {
            method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        });
        let json;
        try { json = await res.json(); }
        catch (e) { throw new Error('Máy chủ trả về dữ liệu không hợp lệ.'); }
        if (res.status === 401 && !location.pathname.endsWith('Login.html')) {
            localStorage.removeItem('coffee_auth_session_v1');
            location.replace('Login.html');
        }
        if (!json.ok) throw new Error(json.error || 'Có lỗi xảy ra.');
        return json.data;
    },
    get(route) { return Api.call(route); },
    post(route, body) { return Api.call(route, 'POST', body); },
    put(route, body) { return Api.call(route, 'PUT', body); },
    del(route) { return Api.call(route, 'DELETE'); }
};