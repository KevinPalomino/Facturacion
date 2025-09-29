// Chatbot OpenRouter API integration
const OPENROUTER_API_KEY = "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";
const OPENROUTER_API_URL = "https://openrouter.ai/api/v1/chat/completions";
const OPENROUTER_MODEL = "x-ai/grok-4-fast:free";

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
        "Authorization": `Bearer ${OPENROUTER_API_KEY}`,
        "HTTP-Referer": window.location.origin,
        "X-Title": "Sistema de Facturación"
      },
      body: JSON.stringify({
        model: OPENROUTER_MODEL,
        messages: [
          { role: "system", content: "Eres un asistente virtual para ayudar a los usuarios del sistema de facturación." },
          { role: "user", content: text }
        ]
      })
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
  // Cerrar con Escape
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") hideChatbot();
  });
});
