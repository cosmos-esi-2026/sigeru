# Guía de defensa de Proyecto UTU LAB — tercera entrega de SiGeRU

**Para estudiar antes de la defensa (16 al 19/11/2026). Docente: Diego Pereira. Equipo COSMOS.**  
**Corte que se defiende:** la tercera entrega del **06/11/2026**. Esta guía se armó el **23/10/2026**; lo que cambie hasta el 06/11 (sobre todo los resultados de las sesiones con usuarios) hay que actualizarlo acá y volver a generar el PDF con `generadores/guia_defensa_utulab_3ra.py`.

> **Idea central:** en julio definimos el problema y dibujamos ocho pantallas; en septiembre las hicimos recorribles; en octubre el sistema funciona con 800 contenedores reales de la Intendencia y preparamos una etiqueta con QR para conectar el contenedor con la pantalla. Cada nivel de prototipo encontró errores que el anterior no veía. La validación con personas sigue pendiente y se dice así, sin inventar resultados.

## Cómo estudiar esta guía

1. **Si tenés 15 minutos:** leé la «Hoja de último repaso» del final y ensayá tu bloque en voz alta.
2. **Si tenés una hora:** leé los capítulos 1 a 7 y hacé el recorrido de demostración con la página abierta.
3. **Si tenés más tiempo:** tapá las respuestas del capítulo 8 y contestá en voz alta.
4. **Todo se puede explicar con un solo caso:** el contenedor **MVD-172712** del Municipio A, que alguien encuentra desbordado.

---

## 1. La historia entera en una página

**El problema (julio).** La información de la recolección está dispersa: el aviso llega por teléfono, el contenedor está en una planilla, el camión en otra, y nadie sabe si la cuadrilla fue. El vecino no sabe a quién avisar. La *persona* de la primera entrega, Mariana Pereira, es la supervisora que necesita ver qué pasa y quién se ocupa.

**El prototipo navegable (septiembre).** Ocho pantallas HTML en grises, con un selector para recorrerlas como los cuatro roles. Sirvió para encontrar un error que el dibujo no mostraba: el vecino veía las incidencias de toda la ciudad. Se preparó una prueba con usuarios que **no se hizo**.

**El sistema (octubre).** La página real, con tres APIs, base de datos y 800 contenedores reales de 8 municipios (datos abiertos de la Intendencia de Montevideo). El vecino avisa, el administrador prioriza y arma rutas, la cuadrilla ve solo su ruta, el operario recibe lo recolectado. Eso es el **prototipo de alta fidelidad**.

**La pieza analógica (octubre).** Una etiqueta de papel con QR, preparada para pegar en un contenedor: el vecino la escanea y llega al formulario para avisar. Para que funcione hubo que cambiar el sistema (el login tenía que volver al contenedor) y cuidar la seguridad (que esa vuelta no sirva para mandar a la gente a otro sitio). Falta la foto de la etiqueta colocada.

**La validación (octubre-noviembre).** Una evaluación heurística hecha con IA encontró 11 problemas; 6 se arreglaron el mismo día. Dos eran errores graves que las pruebas automáticas no veían. El kit para sesiones de 15-20 minutos por persona está preparado, pero todavía no hay resultados de usuarios.

**Una respuesta de 20 segundos:** «Pasamos de un dibujo a un sistema que funciona con datos reales. Preparamos una etiqueta QR para conectar el contenedor con la página: esa es nuestra combinación de papel y pantalla. Revisamos la página con las heurísticas de Nielsen y arreglamos los problemas más graves. La prueba con usuarios sigue pendiente; tenemos el kit listo, pero no vamos a inventar resultados.»

---

## 2. Qué pide la letra y qué hay para mostrar

Fuente: **sección 9.2** de la consigna general (tercera entrega) y **10.2** (rúbrica).

| Requisito | En palabras simples | Qué mostrar | Estado honesto (23/10) |
|---|---|---|---|
| Prototipo high-fidelity | Algo muy parecido al sistema final | La página funcionando, con la base de defensa | Hecho |
| Combinar analógico y digital | Papel + pantalla, maqueta + pantalla | La etiqueta QR y el recorrido del celular hacia «Informar un problema» | Recorrido preparado; falta la foto real de la etiqueta colocada |
| Validación final | Probar con usuarios y comparar inicial con final | Kit, fichas, tabla de hallazgos, comparación de versiones | Heurística hecha; sesiones con personas pendientes |
| Informe reflexivo | Qué aprendimos, errores, impacto | Sección 6 del documento de UTU LAB | Hecho |
| Documentación visual | Big Picture, línea de tiempo, capturas | Figuras 8 y 9 y la tabla de capturas | Hecho |
| Preparar la defensa | Decisiones de diseño, todos participan | Esta guía y los cuatro bloques del capítulo 7 | Reparto pendiente |

---

## 3. El prototipo de alta fidelidad

**Qué decir:** «Nuestro prototipo de alta fidelidad es el sistema construido. No hicimos una maqueta aparte porque habría sido una copia peor de algo que ya funciona.»

**Qué lo hace de alta fidelidad:** datos reales (800 contenedores con su código y su municipio), cuatro roles reales controlados por el servidor, mapa con rutas trazadas por las calles, modo claro y oscuro, uso en el celular y sin señal para la cuadrilla, y más de 2.000 pruebas automáticas.

**Qué no decir:** que «está en uso» o que «la Intendencia lo usa». No está en uso real: es un prototipo funcional con datos públicos.

---

## 4. El QR: la pieza analógica

**Por qué esta y no una maqueta de cartón:** responde a un problema del relevamiento —el vecino no sabe a quién avisar— y pone la respuesta en el lugar donde aparece la pregunta. Además, la pantalla ya decía «el código está pegado en el contenedor»: la etiqueta hace verdadera esa frase.

**Qué hubo que cambiar (para contar si preguntan):**

1. **El login vuelve a donde estabas.** Antes, si escaneabas sin sesión, el login te llevaba al panel y se perdía el contenedor.
2. **Pero solo a pantallas del propio sistema.** Si el login aceptara cualquier dirección, alguien podría armar un enlace que use nuestra página para mandar a la gente a un sitio falso. Eso se llama **redirección abierta** (OWASP A01). Lo probamos con 24 casos, incluidos los intentos de engaño.
3. **El servidor de la PC solo atendía a la misma PC.** Para la demo con el celular se arranca con `iniciar-xampp.cmd red`, que lo abre solo a la red local. El arranque normal sigue cerrado.

### Demo en vivo del QR (2 minutos)

```
Antes de la defensa:
  1. herramientas\xampp\restaurar-base-defensa.cmd      (unos 2,5 minutos)
  2. herramientas\xampp\iniciar-xampp.cmd red          (anotar la IP que muestra)
  3. generadores\utulab3_qr.py --codigos MVD-101602 --base http://LA-IP/sigeru
  4. Imprimir la etiqueta y pegarla en algo (un tacho, una caja).
  5. Celular en la MISMA wifi que la PC. Probar una vez el escaneo.
En la defensa:
  6. Escanear la etiqueta con la camara del celular.
  7. Entrar como vecino: vuelve solo a "Informar un problema" con el contenedor puesto.
  8. Elegir "Contenedor lleno", escribir una frase, enviar: aparece el numero de reclamo.
  9. En la PC, como administrador: el aviso aparece en el panel con prioridad media.
Plan B si la wifi no deja: abrir el mismo enlace en la PC (la etiqueta lo trae impreso).
```

---

## 5. La validación: qué se hizo y qué no

| Qué | Se hizo | Qué es y qué no es |
|---|---|---|
| Evaluación heurística | Sí, el 23/10 | Una revisión por experto con las 10 heurísticas de Nielsen, hecha con IA. **No** es una prueba con usuarios. Encontró 11 problemas; 6 arreglados. |
| Kit de validación | Sí | Guion, tarjetas, consentimiento, fichas con casillas y cuestionario SUS. Lo puede usar cualquiera del equipo. |
| Sesiones con personas | **Completar el 06/11** | Si se hicieron: cuántas, con quién (alias), qué tareas fallaron, el puntaje SUS. Si no: decirlo. |
| Comparación inicial y final | Sí | Entre el prototipo de septiembre y el sistema de octubre. No hay una «validación inicial con usuarios» porque no se hizo; se dice así. |

**Los dos hallazgos que conviene contar** (muestran que la revisión sirvió):

- **H-01, prioridad:** el formulario siempre mandaba «media», así que un contenedor desbordado nunca quedaba en alta, aunque esa regla la había pedido el docente de Programación. Las pruebas automáticas no lo veían porque probaban la API sin ese dato. Se arregló y se verificó: antes media, después alta.
- **H-05, dirección:** la «dirección» de los contenedores reales era «circuito A_DU_RM_CL_116, id 172712». No le sirve a un vecino. Es un problema que solo apareció con datos reales. Lo mitigamos el 24/10 (la dirección ya no repite el id y la ficha y el reclamo tienen «Ver en el mapa») pero no tiene solución de modelo: la fuente no trae calles.

**SUS en una frase:** son diez afirmaciones que la persona puntúa de 1 a 5; da un número de 0 a 100. El promedio de muchos sistemas es 68.

---

## 6. Los errores y cómo los contamos

| Error | Cómo decirlo |
|---|---|
| La validación con usuarios se postergó dos entregas | «Fue nuestro error principal de UTU LAB. Preparamos un kit para hacer sesiones de 15 minutos, pero todavía no tenemos resultados de personas.» |
| No seguimos el estándar documental de ESI en la 2da | «Nos costó 1,8 en Sistemas Operativos. Desde entonces todo entra en un integrador con el estándar.» |
| Usamos IA sin declararla | «Nos restaron un punto. Ahora cada documento dice qué hizo la IA y qué hizo el equipo.» |
| El trabajo se concentró en una persona | «Es cierto y no lo resolvimos: los planes de reparto no se cumplieron. Lo decimos porque es parte de la reflexión que pide la letra.» |
| Confiamos solo en las pruebas automáticas | «Dos errores graves pasaban todas las pruebas. Aprendimos a recorrer el sistema como lo usa una persona.» |

---

## 7. El reparto de la defensa

La letra pide **participación activa de todos**. Cuatro bloques de igual peso. **Asignar un integrante a cada uno y ensayar con la página abierta.**

| Bloque | Qué cuenta (60-90 segundos) | Qué muestra | Preguntas que le pueden tocar |
|---|---|---|---|
| 1. Problema y proceso | La historia del capítulo 1; la persona; por qué observar y no preguntar | Big Picture y línea de tiempo | ¿Qué es el pensamiento de diseño? ¿Qué cambió del problema desde julio? |
| 2. El prototipo | Qué hace cada rol; por qué el sistema es el prototipo hi-fi | Un recorrido como administrador o cuadrilla | ¿Por qué no Figma? ¿Qué ve la cuadrilla y por qué? |
| 3. QR y validación | La etiqueta, la demo, la heurística y las sesiones | La demo del capítulo 4 y la tabla de hallazgos | ¿Qué es la redirección abierta? ¿Qué es SUS? ¿Cuántos usuarios? |
| 4. Reflexión | Errores, aprendizajes, impacto, retrospectiva | La tabla de errores y la de empezar, dejar, continuar | ¿Qué harían distinto? ¿Cómo repartieron el trabajo? |

---

## 8. Preguntas probables

**¿Por qué el sistema construido cuenta como prototipo de alta fidelidad?**  
Porque la letra pide un modelo terminado o muy cercano al final; el sistema lo es. Una maqueta aparte habría sido menos fiel.

**¿Dónde está lo analógico?**  
En la etiqueta de papel con el QR, preparada para pegar en un contenedor. Es la parte física prevista para el recorrido; todavía falta la foto de una etiqueta colocada.

**¿Cuántos usuarios probaron el sistema?**  
Decir el número real de fichas. Si no hubo sesiones, decirlo, y explicar qué se hizo en su lugar: la evaluación heurística, presentada como lo que es.

**¿Qué es una evaluación heurística?**  
Revisar la interfaz contra principios conocidos de usabilidad (las 10 heurísticas de Nielsen) y puntuar la gravedad de cada problema de 0 a 4. Encuentra problemas evidentes rápido; no reemplaza ver a una persona usándolo.

**¿Quién hizo la evaluación heurística?**  
La hizo Claude, una IA, recorriendo la página con las cuentas de cada rol. Fue una revisión asistida, no una sesión con usuarios.

**¿Qué cambió entre la versión inicial y la final?**  
Quién informa (antes el administrador, ahora el vecino y la cuadrilla), la prioridad (antes la elegía quien informaba, ahora la pone el sistema según el tipo), los datos (inventados, ahora reales), y las rutas (no existían).

**¿Por qué el vecino tiene que estar registrado para avisar?**  
Para poder avisarle cuando se resuelve y para frenar avisos falsos.

**¿Qué impacto tiene para la comunidad?**  
Potencial, no medido: el vecino sabe dónde avisar y se entera del resultado; la Intendencia atiende primero lo urgente; hay cifras públicas del estado del sistema.

**¿Qué aprendieron?**  
Que observar sirve más que preguntar; que cada nivel de prototipo encuentra errores nuevos; que los datos reales cambian el diseño; que una API que anda no garantiza una pantalla que anda.

**¿Cómo se repartieron el trabajo?**  
Con honestidad: la mayor parte del trabajo lo hizo una persona, con asistencia de IA. Lo reconocemos en la retrospectiva como el problema principal del equipo.

**¿Qué harían distinto?**  
Probar con personas en cada entrega, aunque sea con dos, y repartir tareas concretas con fecha y responsable desde el principio.

**¿Por qué la dirección de los contenedores es tan rara?**  
Porque los datos abiertos de la Intendencia traen el circuito y la ubicación, no la calle. Lo detectamos en la revisión (H-05) y lo mitigamos con «Ver en el mapa»; una calle inventada sería peor, y consultar un servicio público por cada uno de los 800 puntos va contra su política.

---

## 9. Lo que no hay que decir

- «Los usuarios dijeron...» si no hay una ficha que lo respalde.
- «El sistema está en uso» o «la Intendencia lo aprobó».
- «Lo hizo todo la IA» o «no usamos IA»: lo correcto es lo que dice la declaración.
- Números de memoria: si no te acordás, mostrá la tabla.

---

## 10. Recorrido de demostración (5 minutos)

1. Portada pública: cifras reales y el mapa de contenedores.
2. Escanear la etiqueta como vecino y avisar (capítulo 4).
3. Como administrador: el aviso en el panel, abrirlo, asignar una cuadrilla.
4. Como administrador: «Armar ruta» con el recorrido por las calles.
5. Como cuadrilla: «Mis tareas», su ruta y la parada marcada como urgente.
6. Cerrar con el Big Picture: todo lo que se vio en una sola figura.

---

## Hoja de último repaso

- **Alta fidelidad:** el sistema construido, con 800 contenedores reales.
- **Analógico + digital:** etiqueta de papel con QR preparada para el contenedor; falta la foto de su colocación.
- **Seguridad del QR:** el login vuelve solo a pantallas propias (contra la redirección abierta).
- **Validación:** heurística (11 problemas, 6 arreglados) + kit para sesiones de 15 minutos + SUS.
- **Errores:** validación tarde, estándar de ESI, IA sin declarar, trabajo concentrado.
- **Impacto:** potencial, no medido.
- **Si no sabés algo:** decir «no lo sé» y mostrar dónde está escrito.
