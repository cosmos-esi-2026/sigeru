Sesion.exigir();

const COLUMNAS = 7;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const filtroEstado = document.getElementById("filtro-estado");

function kg(valor) {
  return Number(valor).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

async function cargarRecepciones() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 3);
  try {
    const respuesta = await apiGet(API.gestion + "/recepciones?estado=" + encodeURIComponent(filtroEstado.value));
    dibujarTabla(respuesta.datos || []);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(filas) {
  if (filas.length === 0) {
    const pendientes = filtroEstado.value === "pendiente";
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      pendientes ? "No hay nada para recibir" : "Todavía no hay recepciones",
      pendientes ? "Cuando un camión salga hacia tu centro, la recolección aparece acá." : "Las que confirmes quedan en esta lista."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = filas.map(function (r) {
    const destino = "recepcion.html?id=" + $entero(r.id_recoleccion);
    return '<tr role="row">' +
      celdaEnlace("Recolección", r.numero, destino) +
      celda("Fecha", fechaLegible(r.fecha_hora_inicio), r.fecha_hora_inicio) +
      celda("Ruta y zona", r.ruta + " · " + r.zona) +
      celda("Camión", r.matricula) +
      celda("Esperado", kg(r.peso_esperado_kg), r.peso_esperado_kg) +
      (r.recibida
        ? celdaEstado("Recepción", "Recibida: " + kg(r.peso_recibido_kg), "verde")
        : celdaEstado("Recepción", r.estado === "en_curso" ? "Pendiente (en camino)" : "Pendiente", "naranja")) +
      '<td role="cell" data-etiqueta="Acciones"><a class="boton boton-secundario boton-chico" href="' + destino + '">' +
      (r.recibida ? "Ver" : "Recibir") + '<span class="solo-lectores"> ' + escaparHtml(r.numero) + "</span></a></td>" +
      "</tr>";
  }).join("");

  actualizarContador();
}

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarRecepciones();
  }
});
filtroEstado.addEventListener("change", cargarRecepciones);
document.getElementById("form-filtros").addEventListener("submit", function (evento) { evento.preventDefault(); });
document.getElementById("btn-recargar").addEventListener("click", cargarRecepciones);

activarOrden("tabla-recepciones");
cargarRecepciones();
