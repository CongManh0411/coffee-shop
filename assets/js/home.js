(() => {
    const form = document.getElementById('reservationForm');
    if (!form) return;

    const dateInput = document.getElementById('reservedAt');
    const message = document.getElementById('reservationMessage');
    const submit = form.querySelector('[type="submit"]');
    const minDate = new Date(Date.now() + 30 * 60 * 1000);
    minDate.setMinutes(Math.ceil(minDate.getMinutes() / 15) * 15, 0, 0);
    const localDate = new Date(minDate.getTime() - minDate.getTimezoneOffset() * 60000);
    dateInput.min = localDate.toISOString().slice(0, 16);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.hidden = true;
        submit.disabled = true;
        submit.textContent = 'Đang gửi yêu cầu…';
        const data = Object.fromEntries(new FormData(form).entries());
        data.partySize = Number(data.partySize);
        try {
            await Api.post('reservations/public', data);
            message.textContent = 'Quán đã nhận yêu cầu đặt bàn. Nhân viên sẽ sớm liên hệ để xác nhận.';
            message.className = 'reservation-message success';
            form.reset();
            dateInput.min = localDate.toISOString().slice(0, 16);
        } catch (error) {
            message.textContent = error.message || 'Chưa gửi được yêu cầu. Vui lòng thử lại.';
            message.className = 'reservation-message error';
        } finally {
            message.hidden = false;
            submit.disabled = false;
            submit.innerHTML = 'Gửi yêu cầu đặt bàn <span aria-hidden="true">→</span>';
        }
    });
})();
