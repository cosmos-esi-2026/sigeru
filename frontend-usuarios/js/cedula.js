// Cédula de identidad uruguaya: 7 u 8 dígitos y el último es el verificador. Es el mismo cálculo que hace la API
// (Validacion::cedula); acá sirve para avisar antes de enviar y para mostrarla con puntos y guion. La que decide es la API.
function soloDigitos(valor) {
  return String(valor === null || valor === undefined ? "" : valor).replace(/\D+/g, "");
}

function cedulaUruguayaValida(valor) {
  if (/[^\d.\-\s]/.test(String(valor === null || valor === undefined ? "" : valor))) return false;
  const digitos = soloDigitos(valor);
  if (digitos.length < 7 || digitos.length > 8) return false;
  const cuerpo = digitos.slice(0, -1).padStart(7, "0");
  const pesos = [2, 9, 8, 7, 6, 3, 4];
  const suma = pesos.reduce(function (total, peso, i) { return total + Number(cuerpo[i]) * peso; }, 0);
  return Number(digitos.slice(-1)) === (10 - (suma % 10)) % 10;
}

// 41000019 -> 4.100.001-9 (y 7 dígitos: 1234567 -> 123.456-7). Si no tiene el largo de una cédula, la devuelve como vino.
function formatearCedula(valor) {
  const digitos = soloDigitos(valor);
  if (digitos.length < 7 || digitos.length > 8) return valor;
  const cuerpo = digitos.slice(0, -1);
  return cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + digitos.slice(-1);
}
