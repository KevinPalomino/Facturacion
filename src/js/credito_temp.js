// Función para formatear números con separadores de miles
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: true
    }).format(numero);
}

// Función para parsear números con formato colombiano
function parsearNumeroFormateado(texto) {
    // Primero, limpiamos el texto de cualquier símbolo de moneda y espacios
    let numeroLimpio = texto.replace(/[^\d,\.]/g, '');
    // Quitamos los puntos de los miles
    numeroLimpio = numeroLimpio.replace(/\./g, '');
    // Reemplazamos la coma decimal por punto para que JavaScript lo interprete
    numeroLimpio = numeroLimpio.replace(',', '.');
    // Convertir a número y verificar que sea válido
    const numero = parseFloat(numeroLimpio);
    console.log('Texto original:', texto);
    console.log('Número procesado:', numero);
    return numero || 0;
}

// Función para calcular el total de la factura actual
function calcularTotalFactura() {
    const h3Total = document.querySelector('.factura-container h3:last-of-type');
    if (h3Total) {
        const totalText = h3Total.textContent;
        // Procesar el texto del total con el nuevo parser
        const total = parsearNumeroFormateado(totalText);
        console.log('Total en texto:', totalText);
        console.log('Total calculado:', total);
        return total;
    }
    return 0;
}

// Función para mostrar/ocultar las opciones de crédito
function mostrarOpcionesCredito() {
    const select = document.getElementById('metodo_pago');
    const option = select.options[select.selectedIndex];
    const opcionesCredito = document.getElementById('opciones_credito');
    const infoCredito = document.getElementById('info_credito');
    
    if (option.dataset.esCredito === 'true') {
        opcionesCredito.style.display = 'block';
        actualizarInfoCredito();
    } else {
        opcionesCredito.style.display = 'none';
        infoCredito.style.display = 'none';
    }
}

// Función para actualizar la información del crédito
function actualizarInfoCredito() {
    const totalFactura = calcularTotalFactura();
    const plazoSelect = document.getElementById('plazo_meses');
    const tipoSelect = document.getElementById('tipo_pago');
    const infoCredito = document.getElementById('info_credito');
    
    console.log('Total factura para crédito:', totalFactura);

    if (totalFactura > 0 && plazoSelect && tipoSelect) {
        const plazoMeses = parseInt(plazoSelect.value);
        const esBimensual = tipoSelect.value === 'bimensual';
        const tasaInteres = esBimensual ? 0.01 : 0.02;
        const numCuotas = esBimensual ? (plazoMeses * 2) : plazoMeses;
        
        const montoInteresPorCuota = totalFactura * tasaInteres;
        const montoCuotaCapital = totalFactura / numCuotas;
        const totalPorCuota = montoCuotaCapital + montoInteresPorCuota;
        const totalAPagar = totalPorCuota * numCuotas;
        
        // Actualizar la información en pantalla
        document.getElementById('monto_total').textContent = `$${formatearNumero(totalFactura)}`;
        document.getElementById('valor_cuota').textContent = `$${formatearNumero(totalPorCuota)}`;
        document.getElementById('interes_cuota').textContent = `$${formatearNumero(montoInteresPorCuota)} (${(tasaInteres * 100)}%)`;
        document.getElementById('total_pagar').textContent = `$${formatearNumero(totalAPagar)}`;
        
        infoCredito.style.display = 'block';
    }
}

// Actualizar información del crédito cuando cambie el total de la factura
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.target.classList.contains('subtotal')) {
                actualizarInfoCredito();
            }
        });
    });
    
    document.querySelectorAll('.subtotal').forEach(function(element) {
        observer.observe(element, { childList: true });
    });
});