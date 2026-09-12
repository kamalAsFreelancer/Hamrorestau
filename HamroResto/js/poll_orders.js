function fetchNewOrders() {
    fetch('/manager/get_new_orders.php')
        .then(res => res.json())
        .then(data => {
            console.log("New/updated orders:", data);
            updateOrderUI(data); // You define this function to refresh your HTML
        })
        .catch(err => {
            console.error("Polling failed", err);
        });
}

// Poll every 5 seconds
setInterval(fetchNewOrders, 5000);

// On first load
fetchNewOrders();

function updateOrderUI(orders) {
    const orderList = document.getElementById("live-orders");
    orderList.innerHTML = ""; // Clear existing

    orders.forEach(order => {
        const div = document.createElement("div");
        div.className = "order-card";
        div.innerHTML = `
            <strong>Order #${order.id}</strong><br>
            Table: ${order.table_number}<br>
            Status: ${order.status}<br>
            Updated: ${order.updated_at}<br>
            <hr>
        `;
        orderList.appendChild(div);
    });
}

