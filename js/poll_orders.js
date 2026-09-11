function fetchNewOrders() {
    fetch('../manager/get_new_orders.php', { credentials: 'same-origin' })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => updateOrderUI(Array.isArray(data) ? data : []))
        .catch(err => console.error('Polling failed:', err));
}

function updateOrderUI(orders) {
    const orderList = document.getElementById('live-orders');
    if (!orderList) return;

    orderList.replaceChildren();
    orders.forEach(order => {
        const div = document.createElement('div');
        div.className = 'order-card';

        const title = document.createElement('strong');
        title.textContent = `Order #${order.id}`;
        div.appendChild(title);
        div.appendChild(document.createElement('br'));
        div.append(`Table: ${order.table_number ?? 'N/A'}`);
        div.appendChild(document.createElement('br'));
        div.append(`Status: ${order.status ?? ''}`);
        div.appendChild(document.createElement('br'));
        div.append(`Created: ${order.created_at ?? ''}`);
        orderList.appendChild(div);
    });
}

fetchNewOrders();
setInterval(fetchNewOrders, 5000);
