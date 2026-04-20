# Documento de Diseño (UI/UX) - Proyecto Biblioteca
**Destino:** Google Stitch / Agente de Rediseño Frontend
**Objetivo:** Implementar una mejora visual considerable (Premium, Moderna y Dinámica) sobre el código HTML/CSS/JS existente sin alterar la lógica de negocio ni el backend en PHP.

---

## 1. Visión General del Estilo (Aesthetics)
La aplicación debe abandonar la apariencia de "proyecto escolar" y adoptar un diseño de nivel empresarial o producto SaaS moderno.
- **Estilo principal:** **Glassmorphism** (Efecto de cristal líquido) combinado con diseño de tarjetas limpias (Clean Cards).
- **Sensación:** Premium, dinámica, rápida y altamente interactiva.
- **Tipografía:** `Inter`, `Outfit` o `Plus Jakarta Sans` (Google Fonts). Dejar atrás fuentes genéricas. Interlineados generosos (1.5) y jerarquía clara mediante grosores (Weights: 400, 500, 700).

---

## 2. Paleta de Colores (Design Tokens)
Se debe utilizar CSS Variables (`:root`) para mantener consistencia.
- **Color Primario (Acento):** Azul/Índigo vibrante (Ej: `hsl(230, 85%, 60%)`). Debe captar la atención en botones y enlaces.
- **Color Secundario (Éxito/Disponible):** Verde Esmeralda (Ej: `hsl(150, 70%, 45%)`).
- **Color de Advertencia (Prestado):** Naranja/Ambar (Ej: `hsl(35, 90%, 55%)`).
- **Fondos (Backgrounds):** 
  - *Light Mode:* Fondo principal Gris muy claro/Hielo (`#f8f9fa` o `hsl(210, 20%, 98%)`).
  - *Elementos/Cards:* Blanco puro (`#ffffff`) con sombras suaves y difusas.
- **Textos:**
  - *Principal:* Gris carbón oscuro (`#1e293b`).
  - *Secundario:* Gris plomo (`#64748b`).

---

## 3. Especificaciones de Componentes

### A. Barra de Navegación (Navbar)
- **Efecto:** Flotante y pegajosa en la parte superior (`position: sticky`).
- **Estilo:** Fondo translúcido con desenfoque. Usar `backdrop-filter: blur(12px); background: rgba(255, 255, 255, 0.8)`.
- **Interacción:** Los enlaces deben tener un subrayado animado o un fondo tenue al hacer hover.

### B. Tarjetas de Libros (Book Cards)
- **Estructura:** Contenedor blanco con `border-radius: 16px` y `overflow: hidden`.
- **Sombra (Soft Shadow):** `box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01)`.
- **Imágenes:** Las portadas de los libros deben ocupar la mitad superior de la tarjeta con `object-fit: cover`.
- **Interacción (Hover):** Al pasar el ratón, la tarjeta debe elevarse suavemente (`transform: translateY(-5px)`) y la sombra debe hacerse más pronunciada. Transición de `0.3s ease`.
- **Badges (Insignias):** Las etiquetas de "Disponible" o "Devuelto" deben tener fondos translúcidos del color de acento correspondiente (Ej: Fondo verde al 15% de opacidad y texto verde oscuro).

### C. Botones (Buttons)
- **Forma:** Completamente redondeados (Pill shape) o con `border-radius: 8px`.
- **Estilo Primario:** Fondo con un sutil gradiente lineal o color sólido vibrante. Sin bordes (`border: none`).
- **Interacción:** Efecto *pulse* al hacer click. Al hacer hover, ligero oscurecimiento o brillo, y un pequeño desplazamiento hacia arriba (`transform: translateY(-2px)`).

### D. Formularios y Entradas (Inputs)
- **Campos de texto:** Grandes, con mucho padding (Ej: `12px 16px`).
- **Bordes:** Gris muy claro, pero al hacer focus (`:focus`), el borde debe cambiar al Color Primario y mostrar un anillo de resplandor (`box-shadow: 0 0 0 3px rgba(Primary, 0.2)`).
- **Animaciones:** Se recomienda implementar *Floating Labels* (etiquetas que se mueven hacia arriba cuando el usuario escribe).

### E. Notificaciones (Toasts)
- **Ubicación:** Esquina inferior derecha o superior centro.
- **Animación:** Entrada con deslizamiento desde fuera de la pantalla (`slide-in`) y salida con `fade-out`.
- **Diseño:** Bordes redondeados, iconos SVG a la izquierda del texto y colores semánticos (Rojo error, Verde éxito).

---

## 4. Estructura y Disposición (Layout)
- **Grid de Catálogo:** Utilizar CSS Grid para que el catálogo sea perfectamente responsivo:
  - Móviles: 1 columna.
  - Tablets: 2-3 columnas (`grid-template-columns: repeat(auto-fill, minmax(250px, 1fr))`).
  - Escritorio: 4-5 columnas.
- **Espaciado (White Space):** Aumentar considerablemente los márgenes y paddings entre secciones. Una interfaz moderna respira. No agrupar demasiada información en poco espacio.

---

## 5. Instrucciones Técnicas para el Agente (Google Stitch)
1. **Analiza** el archivo `index.html` actual. Asegúrate de no romper los IDs (`id=""`) necesarios para la lógica de JavaScript (`js/app.js`, `js/libros.js`, etc.).
2. **Reescribe** el archivo `css/estilos.css` desde cero o refactoriza fuertemente el actual.
3. **Inyecta** clases nuevas en el `innerHTML` de los scripts JS donde se renderizan las tarjetas de libros (`js/libros.js` y `js/prestamos.js`) para adaptarlas al nuevo diseño CSS.
4. **Validación:** Verifica que las vistas generadas dinámicamente (`renderizarLibros()`, `renderizarPrestamos()`) contengan la estructura HTML necesaria para que el CSS moderno surta efecto.
5. NO utilizar frameworks externos tipo Tailwind o Bootstrap a menos que se configure explícitamente; basar el diseño en Vanilla CSS3.

---

## 6. Vistas de la Aplicación (Pantallas)
El rediseño debe aplicarse manteniendo la estructura lógica de secciones (SPA) actual. A continuación, el detalle estético esperado por vista:

### A. Vista de Acceso (Login / Registro)
- **Contenedor:** `#vista-auth`
- **Diseño:** *Card* central flotando sobre un fondo con gradiente dinámico o imagen difuminada. 
- **Mejoras:** Separador estilizado (`--- O ---`) entre el formulario tradicional y el botón de *Google Sign-In*. Animaciones suaves (Fade o Flip) al cambiar entre la pestaña de Acceso y Registro.

### B. Vista del Catálogo Principal
- **Contenedor:** `#seccion-catalogo`
- **Diseño:** *Hero section* o banner limpio en la parte superior. 
- **Mejoras:** Barra de búsqueda estilo píldora (*pill-shape*) flotante con sombra suave. El grid de libros debe cargar con una animación de entrada en cascada (*staggered fade-in/slide-up*).

### C. Vista: Mis Libros
- **Contenedor:** `#seccion-mis-libros`
- **Diseño:** *Header* alineado con botones de acción primarios ("Agregar Libro") a la derecha.
- **Mejoras:** Los modales de creación/edición deben oscurecer el fondo con `backdrop-filter: blur(5px)`. Las tarjetas de libros propios deben destacar la opción de borrar de forma más elegante (ej: botón de papelera rojo que aparece solo al hacer hover).

### D. Vista: Mis Préstamos
- **Contenedor:** `#seccion-mis-prestamos`
- **Diseño:** Navegación por pestañas (Tabs) fluida.
- **Mejoras:** Transformar los botones de "Pedidos" y "Prestados" en un control segmentado estilo iOS. Las tarjetas de los préstamos deben adoptar un formato horizontal (*List view*) para priorizar la lectura de fechas y usuarios involucrados.

### E. Panel de Administración
- **Contenedor:** `#seccion-admin`
- **Diseño:** Interfaz estilo *Dashboard* analítico.
- **Mejoras:** Las tarjetas superiores de estadísticas (`.stat-card`) deben tener números grandes, legibles y con iconos destacados dentro de círculos de colores pastel. Los botones de acción crítica como "🔄 Forzar Devolución" deben tener estilo de advertencia (bordes amarillos o naranjas, sin fondo hasta hacer hover).
