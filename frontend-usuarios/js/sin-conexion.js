document.getElementById("btn-reintentar").addEventListener("click", function () {
  window.location.reload();
});

if (window.ColaOffline) {
  ColaOffline.listar().then(function (lista) {
    if (lista.length > 0) {
      document.getElementById("pendientes-sin-conexion").textContent =
        "Hay " + ColaOffline.textoDePendientes(lista.length) + " guardado" + (lista.length === 1 ? "" : "s") + " en este teléfono; se envían solos al volver la conexión.";
    }
  }).catch(function () { /* sin IndexedDB no hay nada que mostrar */ });
}
