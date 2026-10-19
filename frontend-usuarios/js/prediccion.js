// Prediccion de llenado (extras, parte 16): estimacion estadistica, NO inteligencia artificial.
const TEXTO_CONFIANZA = { alta: "confianza alta", media: "confianza media", baja: "confianza baja" };

const EXPLICACION_PREDICCION =
  "<p>No es inteligencia artificial: es una cuenta simple que se puede hacer a mano. Miramos <strong>cada cuánto se vació</strong> el contenedor " +
  "(el intervalo medio entre vaciados) y <strong>cuánto pasó desde el último</strong>. Si se llena parejo, llega al 90 % cuando pasó el 90 % de ese intervalo: " +
  "<em>horas hasta el 90 % = 0,9 × intervalo medio − horas desde el último vaciado</em> (nunca menos de cero).</p>" +
  "<p>Hacen falta al menos <strong>3 vaciados</strong>; con menos decimos «sin datos suficientes». La <strong>confianza</strong> es alta con 8 o más vaciados y un ritmo parejo, " +
  "media con 5 o más y un ritmo bastante parejo, y baja en los demás casos. Un contenedor que ya figura como lleno no se estima. Es una guía para planificar, no una certeza.</p>";

function textoDePrediccion(p) {
  if (!p) {
    return "-";
  }
  if (p.estado === "lleno") {
    return "Ya está lleno";
  }
  if (p.estado === "sin_datos") {
    return "Sin datos suficientes";
  }
  return p.texto + " (" + TEXTO_CONFIANZA[p.confianza] + ")";
}

// Trae las predicciones: { mapa: { id_contenedor: prediccion }, filas: [...] }. dentroDe (opcional): solo los que se llenan en ese plazo.
async function traerPredicciones(dentroDe) {
  const respuesta = await apiGet(API.gestion + "/contenedores/prediccion" + (dentroDe ? "?dentro_de=" + encodeURIComponent(dentroDe) : ""));
  const mapa = {};
  (respuesta.datos || []).forEach(function (f) { mapa[f.id_contenedor] = f.prediccion; });
  return { mapa: mapa, filas: respuesta.datos || [] };
}
