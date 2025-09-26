// Función para calcular el total de la factura actual
function calcularTotalFactura() {
    const h3Total = document.querySelector('.factura-container h3:last-of-type');
    if (h3Total) {
        const totalText = h3Total.textContent;
        return parseFloat(totalText.replace('Total: $', '').replace(',', '')) || 0;
    }
    return 0;
}

// Función para mostrar/ocultar las opciones de crédito
function mostrarOpcionesCredito() {
    const select = document.getElementById('metodo_pago');
    const option = select.options[select.selectedIndex];
    const opcionesCredito = document.getElementById('opciones_credito');
    const infoCredito = document.getElementById('info_credito');
    
    console.log('Método de pago seleccionado:', option.text);
    console.log('Es crédito:', option.dataset.esCredito);
    
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
    
    if (totalFactura > 0 && plazoSelect && tipoSelect) {
        const plazo = parseInt(plazoSelect.value);
        const esBimensual = tipoSelect.value === 'bimensual';
        const tasaInteres = esBimensual ? 0.01 : 0.02;
        
        const montoInteresPorCuota = totalFactura * tasaInteres;
        const montoCuotaCapital = totalFactura / plazo;
        const totalPorCuota = montoCuotaCapital + montoInteresPorCuota;
        const totalAPagar = totalPorCuota * plazo;
        
        // Actualizar la información en pantalla
        document.getElementById('monto_total').textContent = `$${totalFactura.toFixed(2)}`;
        document.getElementById('valor_cuota').textContent = `$${totalPorCuota.toFixed(2)}`;
        document.getElementById('interes_cuota').textContent = `$${montoInteresPorCuota.toFixed(2)} (${(tasaInteres * 100)}%)`;
        document.getElementById('total_pagar').textContent = `$${totalAPagar.toFixed(2)}`;
        
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