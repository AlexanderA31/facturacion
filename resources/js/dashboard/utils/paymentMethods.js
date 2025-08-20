const paymentMethodMap = {
  'sin utilizacion del sistema financiero': '01',
  'efectivo': '01',
  'compensacion de deudas': '15',
  'tarjeta de debito': '16',
  'dinero electronico': '17',
  'tarjeta prepago': '18',
  'tarjeta de credito': '19',
  'otros con utilizacion del sistema financiero': '20',
  'endoso de titulos': '21',
};

export const parsePaymentMethods = (paymentString, totalAmount) => {
  if (!paymentString || String(paymentString).trim() === '') {
    return [{ formaPago: '01', total: totalAmount }];
  }

  const payments = [];
  const parts = String(paymentString).split(',').map(p => p.trim());
  let totalFromParts = 0;

  for (const part of parts) {
    const [methodName, amountStr] = part.split(':').map(p => p.trim());
    const normalizedMethodName = methodName.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const formaPago = paymentMethodMap[normalizedMethodName];

    if (!formaPago) {
      throw new Error(`Método de pago no reconocido: "${methodName}"`);
    }

    if (!amountStr) {
        throw new Error(`Monto no especificado para el método de pago: "${methodName}"`);
    }

    const total = parseFloat(amountStr);
    if (isNaN(total)) {
        throw new Error(`Monto no válido para el método de pago: "${methodName}"`);
    }

    payments.push({ formaPago, total });
    totalFromParts += total;
  }

  if (Math.abs(totalFromParts - totalAmount) > 0.01) {
    throw new Error(`La suma de los pagos (${totalFromParts.toFixed(2)}) no coincide con el total de la factura (${totalAmount.toFixed(2)}).`);
  }

  return payments;
};
