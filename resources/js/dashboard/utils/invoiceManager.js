export const moveToCorrective = (invoice, errorMessage) => {
  const correctiveData = JSON.parse(localStorage.getItem('correctiveBillingData') || '[]');

  // Check if the invoice is already in the corrective list by clave_acceso
  const existingIndex = correctiveData.findIndex(item => item.clave_acceso === invoice.clave_acceso);
  if (existingIndex !== -1) {
    // Invoice is already in the list, maybe update the error message
    correctiveData[existingIndex].errorInfo = errorMessage;
  } else {
    // Reconstruct the row for the corrective billing table from the invoice payload
    const payload = JSON.parse(invoice.payload);
    const newRow = {
      id: invoice.id || Math.random().toString(36).substr(2, 9), // Use invoice id or generate a new one
      Nombres: payload.razonSocialComprador,
      Cédula: payload.identificacionComprador,
      Evento: payload.detalles[0].descripcion,
      Precio: payload.importeTotal,
      Estado: 'No Facturado',
      errorInfo: errorMessage,
      Dirección: payload.direccionComprador,
      Código: payload.detalles[0].codigoPrincipal,
      Email: payload.infoAdicional.email,
      Teléfono: payload.infoAdicional.telefono,
      clave_acceso: invoice.clave_acceso, // Keep track of the original clave_acceso
    };
    correctiveData.push(newRow);
  }

  localStorage.setItem('correctiveBillingData', JSON.stringify(correctiveData));

  // Notify other components that the corrective data has been updated
  window.dispatchEvent(new Event('corrective-billing-update'));
};
