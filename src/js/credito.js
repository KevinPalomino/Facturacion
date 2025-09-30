// Función para formatear números con formato colombiano (1.790.000,00)
function formatearNumero(numero) {
  // Asegurarse de que sea un número
  numero = parseFloat(numero);
  if (isNaN(numero)) return "0,00";

  // Formatear con puntos como separador de miles y coma para decimales
  let partes = numero.toFixed(2).split(".");
  partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  return partes.join(",");
}

// Función para parsear números con formato colombiano (1.790.000,00)
function parsearNumeroFormateado(texto) {
  try {
    console.log("Parsing texto:", texto);

    // Eliminar espacios, símbolo de moneda y texto
    texto = texto.replace(/[Total:\s$]/g, "");

    // Si el texto está vacío o no es válido, retornar 0
    if (!texto || texto === ".") return 0;

    // Primero guardar los decimales si existen (después de la coma)
    let decimales = "00";
    if (texto.includes(",")) {
      let partes = texto.split(",");
      decimales = partes[1] || "00";
      texto = partes[0];
    }

    // Eliminar los puntos de miles
    texto = texto.replace(/\./g, "");

    // Reconstruir el número con punto decimal para JavaScript
    let numeroStr = texto + "." + decimales;
    console.log("Número procesado:", numeroStr);

    // Convertir a número
    let resultado = parseFloat(numeroStr);

    // Validar el resultado
    if (isNaN(resultado)) {
      console.error("Error al parsear:", texto);
      return 0;
    }

    console.log("Resultado final:", resultado);
    return resultado;
  } catch (e) {
    console.error("Error al parsear número:", e);
    return 0;
  }
}

// Función para calcular el total de la factura actual
function calcularTotalFactura() {
  const h3Total = document.querySelector(".factura-container h3:last-of-type");
  if (h3Total) {
    const totalText = h3Total.textContent;
    const total = parsearNumeroFormateado(totalText);
    console.log("Total texto:", totalText, "Total calculado:", total);
    return total;
  }
  return 0;
}

// Función para mostrar/ocultar las opciones de crédito
function mostrarOpcionesCredito() {
  const select = document.getElementById("metodo_pago");
  const opcionesCredito = document.getElementById("opciones_credito");
  const infoCredito = document.getElementById("info_credito");
  let esCredito = false;

  // Detecta si el select es un <select>
  if (select && select.tagName === "SELECT") {
    const option = select.options[select.selectedIndex];
    // Detecta por atributo o por texto
    esCredito = (option && (option.getAttribute("data-es-credito") === "true" || option.text.toLowerCase().includes("credito")));
  }

  if (esCredito) {
    opcionesCredito.style.display = "block";
    actualizarInfoCredito();
  } else {
    opcionesCredito.style.display = "none";
    infoCredito.style.display = "none";
  }
}

// Función para actualizar la información del crédito
function actualizarInfoCredito() {
  const totalFactura = calcularTotalFactura();
  const plazoSelect = document.getElementById("plazo_meses");
  const tipoSelect = document.getElementById("tipo_pago");
  const infoCredito = document.getElementById("info_credito");

  if (totalFactura > 0 && plazoSelect && tipoSelect) {
    const plazoMeses = parseInt(plazoSelect.value);
    const esBimensual = tipoSelect.value === "bimensual";
    const tasaInteres = esBimensual ? 0.01 : 0.02;
    const numCuotas = esBimensual ? plazoMeses * 2 : plazoMeses;

    const montoInteresPorCuota = totalFactura * tasaInteres;
    const montoCuotaCapital = totalFactura / numCuotas;
    const totalPorCuota = montoCuotaCapital + montoInteresPorCuota;
    const totalAPagar = totalPorCuota * numCuotas;

    // Actualizar la información en pantalla con el nuevo formato
    document.getElementById("monto_total").textContent = `$${formatearNumero(
      totalFactura
    )}`;
    document.getElementById("valor_cuota").textContent = `$${formatearNumero(
      totalPorCuota
    )}`;
    document.getElementById("interes_cuota").textContent = `$${formatearNumero(
      montoInteresPorCuota
    )} (${tasaInteres * 100}%)`;
    document.getElementById("total_pagar").textContent = `$${formatearNumero(
      totalAPagar
    )}`;

    // Guardar los valores sin formato para cálculos posteriores
    document.getElementById("monto_total").dataset.valor = totalFactura;
    document.getElementById("valor_cuota").dataset.valor = totalPorCuota;

    infoCredito.style.display = "block";
  }
}

// Actualizar información del crédito cuando cambie el total de la factura
document.addEventListener("DOMContentLoaded", function () {
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (
        mutation.type === "childList" &&
        mutation.target.classList.contains("subtotal")
      ) {
        actualizarInfoCredito();
      }
    });
  });

  document.querySelectorAll(".subtotal").forEach(function (element) {
    observer.observe(element, { childList: true });
  });
});

// Agregar event listeners cuando el DOM esté listo

document.addEventListener("DOMContentLoaded", function () {
  const plazoElement = document.getElementById("plazo");
  const cuotaInicialElement = document.getElementById("cuota_inicial");
  const metodoPagoElement = document.getElementById("metodo_pago");

  if (plazoElement) {
    plazoElement.addEventListener("change", calcularCuotas);
  }
  if (cuotaInicialElement) {
    cuotaInicialElement.addEventListener("input", calcularCuotas);
  }
  if (metodoPagoElement) {
    metodoPagoElement.addEventListener("change", mostrarOpcionesCredito);
    // Ejecutar al cargar por si ya está seleccionado crédito
    mostrarOpcionesCredito();
  }
});

// Función para calcular cuotas
function calcularCuotas() {
  const plazo = parseInt(document.getElementById("plazo").value) || 0;
  const cuotaInicialInput = document.getElementById("cuota_inicial").value;
  const cuotaInicial = parsearNumeroFormateado(cuotaInicialInput);
  const total = calcularTotalFactura();

  console.log("Calculando cuotas:", {
    plazo,
    cuotaInicialInput,
    cuotaInicial,
    total,
  });

  // Validar que el total sea mayor que 0
  if (total <= 0) {
    alert("El total de la factura debe ser mayor a 0");
    return;
  }

  // Validar que la cuota inicial no sea mayor que el total
  if (cuotaInicial > total) {
    alert("La cuota inicial no puede ser mayor que el total de la factura");
    document.getElementById("cuota_inicial").value = "";
    return;
  }

  // Calcular el saldo a financiar
  const saldoFinanciar = total - cuotaInicial;

  // Calcular el valor de cada cuota (sin intereses por ahora)
  const valorCuota = plazo > 0 ? saldoFinanciar / plazo : 0;

  console.log("Resultados:", {
    saldoFinanciar,
    valorCuota,
  });

  // Mostrar los resultados formateados
  document.getElementById("saldo_financiar").textContent =
    formatearNumero(saldoFinanciar);
  document.getElementById("valor_cuota").textContent =
    formatearNumero(valorCuota);

  // Actualizar el campo oculto con el valor sin formato para el formulario
  document.getElementById("valor_cuota_hidden").value = valorCuota.toFixed(2);

  // Formatear la cuota inicial en el campo de entrada
  if (cuotaInicialInput && cuotaInicial > 0) {
    document.getElementById("cuota_inicial").value =
      formatearNumero(cuotaInicial);
  }
}
