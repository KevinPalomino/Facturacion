// Variables del DOM
const chatContainer = document.getElementById('chat-container');
const openButton = document.getElementById('open-assistant');
const closeButton = document.getElementById('close-assistant');
const chatInput = document.getElementById('chat-input');
const sendButton = document.getElementById('send-message');
const chatMessages = document.getElementById('chat-messages');

// Evento para abrir el chat
openButton.addEventListener('click', () => {
    chatContainer.classList.remove('hidden');
    chatInput.focus();
});

// Evento para cerrar el chat
closeButton.addEventListener('click', () => {
    chatContainer.classList.add('hidden');
});

// Función para agregar un mensaje al chat
function addMessage(message, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
    messageDiv.textContent = message;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Función para mostrar indicador de carga
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading';
    loadingDiv.innerHTML = `
        <div class="loading-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;
    chatMessages.appendChild(loadingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return loadingDiv;
}

// Función para enviar mensaje al asistente
async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    // Limpiar input
    chatInput.value = '';
    
    // Mostrar mensaje del usuario
    addMessage(message, true);
    
    // Mostrar indicador de carga
    const loadingIndicator = showLoading();
    
    try {
        console.log('Enviando mensaje a la IA...');
        const response = await fetch('/PruebaFacturacion/src/includes/asistente_ia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message })
        });
        
        const data = await response.json();
        console.log('Respuesta recibida:', data);
        
        // Remover indicador de carga
        loadingIndicator.remove();
        
        if (data.success) {
            addMessage(data.response);
        } else {
            addMessage('Error: ' + data.error);
        }
    } catch (error) {
        // Remover indicador de carga
        loadingIndicator.remove();
        addMessage('Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.');
    }
}

// Evento para enviar mensaje
sendButton.addEventListener('click', sendMessage);

// Evento para enviar mensaje con Enter
chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Mensaje inicial
addMessage('¡Hola! Soy Conta Assistant, tu asistente virtual. ¿En qué puedo ayudarte hoy?');