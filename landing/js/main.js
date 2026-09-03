const API_USUARIOS = "/sigeru/api-usuarios";


const formulario = document.getElementById("form-registro");
const mensaje = document.getElementById("mensaje-registro");


formulario.addEventListener("submit", async function (evento) {
  
  evento.preventDefault();


  const datos = {
    nombre:    formulario.nombre.value,
    apellido:  formulario.apellido.value,
    email:     formulario.email.value,
    telefono:  formulario.telefono.value,
    direccion: formulario.direccion.value,
  };

  try {
   
    const respuesta = await fetch(API_USUARIOS + "/solicitudes", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });

    const resultado = await respuesta.json();

    if (respuesta.ok) {
      mostrarMensaje("Solicitud enviada correctamente. Te contactaremos.", "ok");
      formulario.reset(); 
    } else {
      mostrarMensaje(resultado.mensaje || "No se pudo enviar la solicitud.", "error");
    }
  } catch (error) {

    mostrarMensaje(
      "No se pudo conectar con el servidor. Verifica que la API este corriendo e intenta de nuevo.",
      "error"
    );
  }
});

function mostrarMensaje(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = "mensaje " + tipo;
}