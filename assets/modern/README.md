# OneBox Modern Assets

## Stack Tecnológico Moderno

### Desarrollo (Build Time)
- **Vite**: Build tool ultrarrápido con HMR (Hot Module Replacement)
- **Tailwind CSS**: Framework CSS utilitario para diseño moderno
- **PostCSS + Autoprefixer**: Procesamiento CSS automático
- **Node.js/npm**: Gestión de dependencias y build (solo desarrollo)

### Runtime (Producción)
- **Alpine.js**: Framework reactivo ligero (20kb) para interactividad
- **Chart.js 4.x**: Gráficos modernos y performantes
- **SweetAlert2**: Notificaciones y modales elegantes
- **Phosphor Icons**: Iconografía moderna y consistente
- **Axios**: Cliente HTTP moderno (reemplaza jQuery AJAX)

### Migración desde Legacy
- **jQuery** → Alpine.js + Vanilla JS
- **Bootstrap CSS** → Tailwind CSS
- **Bootstrap Table** → Grid.js o DataTables 2.x
- **Glyphicons** → Phosphor Icons
- **alert/confirm** → SweetAlert2
- **cURL nativo** → Guzzle (PHP)

## Comandos Disponibles

```bash
# Instalación inicial (solo una vez)
npm install

# Modo desarrollo con HMR
npm run dev

# Build para producción
npm run build

# Preview del build
npm run preview
```

## Estructura de Archivos

```
assets/
├── modern/              # Código fuente moderno
│   ├── js/
│   │   ├── main.js      # Entry point principal
│   │   ├── pos.js       # Módulo POS
│   │   ├── dashboard.js # Dashboard
│   │   └── utils/       # Utilidades reutilizables
│   ├── css/
│   │   └── style.css    # Estilos Tailwind
│   └── components/      # Componentes reutilizables
├── dist/                # Output compilado (producción)
│   ├── js/              # JS minificado con hash
│   └── css/             # CSS minificado con hash
└── [legacy]/            # Assets antiguos (fase de transición)
```

## Integración con PHP

Los assets compilados se incluyen en las vistas PHP:

```php
<!-- En tus vistas -->
<link rel="stylesheet" href="<?= base_url('assets/dist/css/style.[hash].css') ?>">
<script type="module" src="<?= base_url('assets/dist/js/main.[hash].js') ?>"></script>
```

## Ventajas de esta Arquitectura

1. **Rendimiento**: Assets minificados, tree-shaking, code splitting
2. **Desarrollo rápido**: HMR, recarga instantánea de cambios
3. **Mantenible**: Código modular, tipado, componentes reutilizables
4. **Compatible**: Funciona en cualquier hosting PHP estándar
5. **Progresivo**: Migración gradual sin romper funcionalidad existente
