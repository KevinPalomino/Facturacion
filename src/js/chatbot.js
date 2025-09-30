// Chatbot OpenRouter integration
const OPENROUTER_API_KEY =
  "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";
const OPENROUTER_API_URL = "https://openrouter.ai/api/v1/chat/completions";

function showChatbot() {
  document.getElementById("chatbot-window").style.display = "flex";
}
function hideChatbot() {
  document.getElementById("chatbot-window").style.display = "none";
}
function appendMessage(text, sender) {
  const msgDiv = document.createElement("div");
  msgDiv.className = "chatbot-msg " + sender;
  msgDiv.textContent = text;
  document.getElementById("chatbot-messages").appendChild(msgDiv);
  document.getElementById("chatbot-messages").scrollTop =
    document.getElementById("chatbot-messages").scrollHeight;
}
async function sendMessage() {
  const input = document.getElementById("chatbot-text");
  const text = input.value.trim();
  if (!text) return;
  appendMessage(text, "user");
  input.value = "";
  // Llamada a OpenRouter API
  appendMessage("...", "bot");
  try {
    const res = await fetch(OPENROUTER_API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${OPENROUTER_API_KEY}`,
        "HTTP-Referer": window.location.origin,
        "X-Title": "Sistema de Facturación Chatbot",
      },
      body: JSON.stringify({
        model: "x-ai/grok-4-fast:free",
        messages: [
          {
            role: "system",
            content:
              "Eres un asistente virtual amigable y profesional para el sistema de facturación. Responde saludos de manera cordial y ofrece los servicios disponibles del sistema. Si el usuario pregunta algo fuera del contexto del sistema de facturación, discúlpate amablemente y muestra las funciones y módulos disponibles según los roles (administrador, cajero, etc). Cuando expliques pasos o instrucciones, hazlo de forma ordenada y breve, solo con información relevante a los módulos y funciones del sistema. No respondas sobre temas externos ni des pasos que no correspondan a las utilidades del sistema. Ejemplo de respuesta fuera de contexto: 'Lo siento, solo puedo ayudarte con información y funciones del sistema de facturación. ¿Te gustaría saber cómo registrar una factura, consultar ventas, gestionar créditos u otra función?'."
          },
          { role: "user", content: text },
        ],
      }),
    });
    const data = await res.json();
    const botMsg = data?.choices?.[0]?.message?.content || "No response.";
    // Reemplaza el mensaje de "..." por la respuesta
    const msgs = document.querySelectorAll(".chatbot-msg.bot");
    if (msgs.length) msgs[msgs.length - 1].textContent = botMsg;
    else appendMessage(botMsg, "bot");
  } catch (e) {
    appendMessage("Error al conectar con OpenRouter.", "bot");
  }
}
document.addEventListener("DOMContentLoaded", function () {
  document.body.insertAdjacentHTML(
    "beforeend",
    `
    <div id="chatbot-btn" title="Chatbot" onclick="showChatbot()">
      <img src="https://cdn-icons-png.flaticon.com/512/4712/4712027.png" alt="Chatbot" style="width:38px;height:38px;" />
    </div>
    <div id="chatbot-window">
      <div id="chatbot-header">
        Asistente Virtual
        <button id="chatbot-close" onclick="hideChatbot()">×</button>
      </div>
      <div id="chatbot-messages"></div>
      <form id="chatbot-input" onsubmit="event.preventDefault();sendMessage();">
        <input id="chatbot-text" type="text" placeholder="Escribe tu mensaje..." autocomplete="off" />
        <button type="submit">Enviar</button>
      </form>
    </div>
  `
  );
    // Mensaje intermitente en el botón
    const badge = document.createElement("span");
    badge.id = "chatbot-badge";
    badge.style.position = "absolute";
    badge.style.bottom = "70px";
    badge.style.right = "0";
    badge.style.left = "0";
    badge.style.width = "120px";
    badge.style.margin = "auto";
    badge.style.textAlign = "center";
    badge.style.fontSize = "15px";
    badge.style.color = "#2196f3";
    badge.style.fontWeight = "bold";
    badge.style.background = "#fff";
    badge.style.borderRadius = "8px";
    badge.style.padding = "2px 8px";
    badge.style.boxShadow = "0 2px 8px rgba(0,0,0,0.12)";
    badge.style.display = "none";
    badge.style.zIndex = "10000";
    document.getElementById("chatbot-btn").appendChild(badge);

    setInterval(() => {
      if (!document.getElementById("chatbot-window").style.display || document.getElementById("chatbot-window").style.display === "none") {
        badge.style.display = badge.style.display === "none" ? "block" : "none";
      } else {
        badge.style.display = "none";
      }
    }, 900);

    // Mostrar saludo al abrir el chat
    document.getElementById("chatbot-btn").addEventListener("click", function () {
      const messages = document.getElementById("chatbot-messages");
      if (!messages.innerHTML.trim()) {
        appendMessage("¡Hola! Soy FacturaBot, tu asistente virtual. ¿En qué puedo ayudarte hoy con el sistema de facturación?", "bot");
      }
    });

    // Cerrar con Escape
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") hideChatbot();
    });
});
