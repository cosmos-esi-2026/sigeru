# Encuesta y entrevista de Emprendedurismo y Gestión (3ra entrega)

> Generado por `generadores/emprend3_instrumentos.py`. La encuesta de vecinos y la entrevista alimentan el **Plan de
> Comercialización** (`../Plan_de_Comercializacion_SiGeRU.pdf`). **No se inventa ninguna respuesta**: mientras no haya
> datos reales, el plan lo dice con un `[Pendiente: ...]` visible.

## Qué hay en esta carpeta

| Archivo | Para qué |
|---|---|
| `../../proyecto-utu-lab/validacion-3ra/encuesta-emprendedurismo.md` | Las 12 preguntas, para pegar en Google Forms |
| `Encuesta_Vecinos_Papel_SiGeRU.pdf` | La misma encuesta para responder en papel (si no hay internet) |
| `Guia_Entrevista_Funcionario_Intendencia_SiGeRU.pdf` | 10 preguntas y hoja de respuestas para entrevistar a un funcionario |
| `plantilla-respuestas.csv` | El encabezado exacto que lee el programa |
| `respuestas.csv` | **Lo pone el equipo**: las respuestas reales, sin tocar |

## Cómo se hace

1. **Armar el formulario** en Google Forms con `encuesta-emprendedurismo.md` (una pregunta por bloque; las seis primeras
   obligatorias). Es anónima: no pedir nombre ni correo.
2. **Hacerla circular** el sábado 24 y domingo 25/10, en las sesiones de validación de UTU LAB (después de la sesión) y por enlace en
   grupos de WhatsApp (familia, liceo, barrio). Meta: **20 a 30 respuestas reales**.
3. **Exportar**: en el formulario, pestaña «Respuestas» → «Vincular con Hojas de cálculo» → Archivo → Descargar → `.csv`.
   Guardarlo acá como **`respuestas.csv`**, sin editar nada (ni el encabezado ni las respuestas).
4. **Respuestas en papel**: pasarlas al `plantilla-respuestas.csv` (una fila por hoja, tal cual) y guardarlo como `respuestas.csv`
   (o sumarlas al exportado). Guardar las hojas o su foto.
5. **Regenerar el plan**: `herramientas\python\python.exe generadores\doc18_comercializacion.py`. La sección 3 pasa de
   «pendiente» a mostrar los resultados y las conclusiones, que salen sólo de las respuestas.
6. **Entrevista**: hacerla con la guía, anotar textual y pasar las respuestas al plan (sección 3.4) cuando exista.

## Reglas

- **Nada se completa, corrige ni descarta.** El programa sólo ignora filas totalmente vacías.
- Un archivo con `PRUEBA` en el nombre o una columna `PRUEBA` se rechaza: sirve para probar el programa, nunca llega a un entregable.
- Con **menos de 20 respuestas** los resultados se rotulan «exploratorios: no permiten generalizar».
- Un cruce (por ejemplo, edad) sólo se muestra si el grupo tiene 3 personas o más.
- Pruebas del lector: `herramientas\python\python.exe generadores\prueba_encuesta.py`.
