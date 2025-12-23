# 📚 Sistema de Préstamo de Libros

Aplicación web completa para gestionar préstamos de libros entre usuarios aficionados a la lectura.

## 🎯 Características

### Funcionalidades Básicas
- ✅ Registro e inicio de sesión de usuarios
- ✅ Gestión completa de libros (CRUD)
- ✅ Sistema de préstamos entre usuarios
- ✅ Búsqueda avanzada por título, autor y género
- ✅ Cálculo automático de días de préstamo
- ✅ Panel de administración completo
- ✅ Diseño responsive (móvil y desktop)

### Funcionalidades Extras
- ✅ Importación masiva de libros desde CSV
- ⏳ Autenticación con Google (opcional - no implementado)

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 7.4+** - Lenguaje del servidor
- **MySQL** - Base de datos
- **PDO** - Conexión segura a la base de datos
- **API REST** - Arquitectura de comunicación

### Frontend
- **HTML5** - Estructura semántica
- **CSS3** - Estilos modernos con variables CSS
- **JavaScript ES6+** - Lógica de la aplicación
- **Fetch API** - Comunicación con el backend

### Diseño
- **Glassmorphism** - Efectos de cristal esmerilado
- **Gradientes** - Colores vibrantes
- **Animaciones CSS** - Transiciones suaves
- **Responsive Design** - Adaptable a todos los dispositivos

## 📋 Requisitos Previos

- **XAMPP** (o similar) con:
  - Apache 2.4+
  - PHP 7.4+
  - MySQL 5.7+
- **Navegador web moderno** (Chrome, Firefox, Edge, Safari)
- **Editor de código** (VS Code, Sublime Text, etc.)

## 🚀 Instalación

### 1. Clonar o descargar el proyecto

Coloca la carpeta `Biblioteca` en la carpeta `htdocs` de XAMPP:
```
C:\xampp\htdocs\Biblioteca\
```

### 2. Crear la base de datos

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada `biblioteca`
3. Importa el archivo `sql/biblioteca.sql`:
   - Haz clic en la base de datos `biblioteca`
   - Ve a la pestaña "Importar"
   - Selecciona el archivo `sql/biblioteca.sql`
   - Haz clic en "Continuar"

### 3. Configurar la conexión a la base de datos

Abre el archivo `php/config/database.php` y verifica/modifica las credenciales:

```php
private $host = 'localhost';
private $db_name = 'biblioteca';
private $username = 'root';      // Tu usuario de MySQL
private $password = '';          // Tu contraseña de MySQL
```

### 4. Iniciar los servicios

1. Abre el **Panel de Control de XAMPP**
2. Inicia **Apache**
3. Inicia **MySQL**

### 5. Acceder a la aplicación

Abre tu navegador y ve a:
```
http://localhost/Biblioteca
```

## 👤 Usuarios de Prueba

### Usuario Administrador
- **Email:** `admin@biblioteca.com`
- **Contraseña:** `admin123`
- **Permisos:** Acceso completo al panel de administración

### Usuarios Normales
- **Email:** `juan@email.com` / **Contraseña:** `admin123`
- **Email:** `maria@email.com` / **Contraseña:** `admin123`
- **Email:** `carlos@email.com` / **Contraseña:** `admin123`

## 📖 Guía de Uso

### Para Usuarios

#### 1. Registrarse
1. Haz clic en "Registrarse"
2. Completa el formulario con tus datos
3. Haz clic en "Crear Cuenta"

#### 2. Agregar Libros
1. Ve a "Mis Libros"
2. Haz clic en "➕ Agregar Libro"
3. Completa el formulario
4. Haz clic en "Guardar"

#### 3. Importar Libros desde CSV
1. Ve a "Mis Libros"
2. Haz clic en "📄 Importar CSV"
3. Selecciona tu archivo CSV (ver formato en `data/ejemplo.csv`)
4. Haz clic en "Importar"

**Formato del CSV:**
```csv
titulo,autor,genero,año
El señor de los anillos,J.R.R. Tolkien,Fantasía,1954
```

#### 4. Buscar Libros
1. Ve a "Catálogo"
2. Escribe en el buscador
3. Selecciona el campo (título, autor o género)
4. Haz clic en "Buscar"

#### 5. Pedir Libro Prestado
1. Ve a "Catálogo"
2. Encuentra un libro disponible
3. Haz clic en "📖 Pedir Prestado"
4. Confirma la acción

#### 6. Devolver Libro
1. Ve a "Mis Préstamos"
2. En la pestaña "Libros Pedidos"
3. Haz clic en "✓ Devolver"
4. Confirma la devolución

### Para Administradores

#### Panel de Administración
1. Inicia sesión con cuenta de administrador
2. Ve a "Admin"
3. Visualiza estadísticas del sistema
4. Gestiona préstamos y usuarios

#### Eliminar Usuarios
- Solo se pueden eliminar usuarios sin préstamos activos
- No se puede eliminar el administrador principal

## 📁 Estructura del Proyecto

```
Biblioteca/
├── index.html              # Aplicación principal
├── css/
│   └── estilos.css        # Estilos de la aplicación
├── js/
│   ├── app.js             # Lógica principal
│   ├── api.js             # Comunicación con backend
│   ├── auth.js            # Autenticación
│   ├── libros.js          # Gestión de libros
│   ├── prestamos.js       # Gestión de préstamos
│   └── utils.js           # Utilidades
├── php/
│   ├── config/
│   │   └── database.php   # Configuración de BD
│   ├── api/
│   │   ├── usuarios.php   # API de usuarios
│   │   ├── libros.php     # API de libros
│   │   └── prestamos.php  # API de préstamos
│   ├── auth/
│   │   ├── login.php      # Login
│   │   ├── registro.php   # Registro
│   │   └── logout.php     # Logout
│   └── utils/
│       └── funciones.php  # Funciones auxiliares
├── sql/
│   └── biblioteca.sql     # Script de base de datos
├── data/
│   └── ejemplo.csv        # Ejemplo de CSV
└── README.md              # Este archivo
```

## 🎨 Características del Diseño

### Colores
- **Primario:** Azul índigo (#6366f1)
- **Secundario:** Violeta (#8b5cf6)
- **Éxito:** Verde (#10b981)
- **Peligro:** Rojo (#ef4444)
- **Advertencia:** Ámbar (#f59e0b)

### Efectos
- **Glassmorphism:** Fondos translúcidos con blur
- **Gradientes:** Transiciones de color suaves
- **Sombras:** Profundidad y elevación
- **Animaciones:** Transiciones fluidas

### Responsive
- **Móvil:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Prepared statements para prevenir SQL injection
- ✅ Sanitización de datos de entrada
- ✅ Validación de sesiones PHP
- ✅ Control de permisos por rol

## 📚 Recursos Adicionales

- [Documentación de PHP](https://www.php.net/manual/es/)
- [Documentación de MySQL](https://dev.mysql.com/doc/)
- [MDN Web Docs](https://developer.mozilla.org/es/)
- [CSS Tricks](https://css-tricks.com/)

## 👨‍💻 Autor

Desarrollado como proyecto intermodular para el curso 2025-2026.

## 📄 Licencia

Este proyecto es de uso educativo.

---

¡Disfruta compartiendo libros! 📚✨
