import { SSE } from "./ssejs260/lib/sse.js";

const token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDg0MjU5MjcuNjI2NTExLCJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOlsiKiJdfX0.5sSLFZ8NVlbswn_BwqoLbLPTSZZ0-QKFzaGZ1hlMy7Q';
const url = new URL('https://test.famtradition.ru/.well-known/mercure');
url.searchParams.append('topic', '/events');

var eventSource = new SSE(url, { headers: { Authorization: `Bearer ${token}` } });

eventSource.onopen = () => console.log('Mercure connection established');
eventSource.onerror = (e) => console.error('Mercure error:', e);

eventSource.addEventListener("message", function (e) {
  if (!e.data) {
    return;
  }
  const data = JSON.parse(e.data);
  console.log('Received event:', e);
  switch(data.type) {
    case 'registration':
      showNotification(`Зарегистрирован новый пользователь: ${data.data.email}`, 'success');
      break;
    case 'login':
      showNotification(`Пользователь ${data.data.email} вошёл в систему`, 'info');
      break;
    case 'data_update':
      showNotification('Обновление данных портала', 'warning');
      break;
    case 'ping':
      // keep-alive
      break;
  }
});

function showNotification(message, type = 'info') {
  const notification = document.getElementById('notification');
  const content = document.getElementById('notification-content');
  const colors = {
    'info': '#2196F3',
    'success': '#4CAF50',
    'warning': '#FF9800'
  };

  notification.style.background = colors[type] || colors['info'];
  content.textContent = message;
  notification.classList.remove('hide');
  notification.classList.add('show');

  setTimeout(() => {
    notification.classList.remove('show');
    notification.classList.add('hide');

    setTimeout(() => {
      notification.style.removeProperty('background');
      content.textContent = '';
    }, 500);
  }, 5000);
}