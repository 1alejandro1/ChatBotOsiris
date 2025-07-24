const chatWindow = document.getElementById('chatWindow');
const userInput  = document.getElementById('userInput');

function scrollToBottom() {
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function appendText(who, text) {
  const p = document.createElement('p');
  p.className = who;
  p.innerHTML = text.replace(/\n/g,'<br>');
  chatWindow.appendChild(p);
  scrollToBottom();
}

function appendProduct(prod) {
  const card = document.createElement('div');
  card.className = 'product-card';
  card.innerHTML = `
    <img src="${prod.url_imagen}" alt="${prod.nombre}">
    <div class="details">
      <h3>${prod.nombre}</h3>
      <p>${prod.descripcion}</p>
      <p class="price">$${prod.precio}</p>
      <a href="${prod.url_producto}" target="_blank">Ver detalle</a>
    </div>`;
  chatWindow.appendChild(card);
  scrollToBottom();
}

async function sendMessage() {
  const msg = userInput.value.trim();
  if (!msg) return;
  appendText('user', msg);
  userInput.value = '';
  appendText('bot', 'Escribiendo...');
  
  try {
    const res = await fetch('chatbot.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg })
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status} – ${res.statusText}`);
    }
    
    // Leer JSON completo
    const json = await res.json();
    
    // Quitar el “Escribiendo…”
    chatWindow.lastChild.remove();
    
    // Si es producto, usa ficha; si no, muestra texto
    if (json.type === 'product') {
      appendProduct(json.data);
    } else {
      // data debe ser string
      const text = typeof json.data === 'string'
        ? json.data
        : JSON.stringify(json.data);
      appendText('bot', text);
    }
  } catch (err) {
    console.error('Fetch error:', err);
    chatWindow.lastChild.remove();
    appendText('bot', 'Error conectando con el servidor: ' + err.message);
  }
  
  scrollToBottom();
}
