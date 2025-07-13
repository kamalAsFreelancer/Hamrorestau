fetch('/api/update_order_status.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    order_id: 12,
    new_status: 'preparing'
  })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    console.log('Updated successfully');
  } else {
    alert(data.error);
  }
});
