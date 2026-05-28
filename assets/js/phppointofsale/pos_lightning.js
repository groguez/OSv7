/**
 * OneBox POS Lightning - Optimización de Interfaz y Rendimiento
 * 
 * Maneja la interfaz adaptativa del punto de venta según la industria,
 * optimiza eventos, precarga datos y gestiona el modo offline.
 * 
 * @package OneBox\Assets\JS
 * @version 2.0
 */

(function($) {
    'use strict';

    // Configuración global del contexto de ventas
    var SalesContext = {
        industry: 'retail',
        labels: {},
        features: [],
        uiMode: 'speed',
        requiredFields: [],
        visibility: {},
        
        init: function(config) {
            this.industry = config.industry || 'retail';
            this.labels = config.labels || {};
            this.features = config.features || [];
            this.uiMode = config.ui_mode || 'speed';
            this.requiredFields = config.required_fields || [];
            this.visibility = config.visibility || {};
            
            this.applyLabels();
            this.setupUILayout();
            this.toggleFeatures();
            this.optimizeEvents();
        },
        
        // Aplica etiquetas personalizadas a toda la interfaz
        applyLabels: function() {
            if (this.labels.product) {
                $('.product-label, .item-label, td:contains("Producto"), th:contains("Producto")')
                    .text(this.labels.product);
            }
            if (this.labels.customer) {
                $('.customer-label, td:contains("Cliente"), th:contains("Cliente")')
                    .text(this.labels.customer);
            }
            if (this.labels.transaction) {
                $('.transaction-label, .sale-label').text(this.labels.transaction);
            }
            
            // Actualizar título de la página
            document.title = this.labels.transaction + ' - OneBox POS';
        },
        
        // Configura el layout de UI según el modo
        setupUILayout: function() {
            var $posContainer = $('#pos-container');
            
            // Remover clases previas de modo
            $posContainer.removeClass('mode-speed mode-visual mode-detail');
            
            // Agregar clase del modo actual
            $posContainer.addClass('mode-' + this.uiMode);
            
            // Ajustes específicos por modo
            if (this.uiMode === 'speed') {
                // Retail: maximizar espacio de productos, minimizar formularios
                $('#product-grid').addClass('large-tiles');
                $('#customer-form').addClass('compact');
                this.enableBarcodeFocus();
            } else if (this.uiMode === 'visual') {
                // Restaurante: grilla visual con imágenes grandes
                $('#product-grid').addClass('image-focused');
                this.showTableSelector();
            } else if (this.uiMode === 'detail') {
                // Talleres/Clínicas: formularios extensos visibles
                $('#transaction-details').show();
                $('#additional-fields-panel').show();
            }
        },
        
        // Muestra/oculta características según la industria
        toggleFeatures: function() {
            // Características obligatorias
            var mandatoryFeatures = ['pos', 'payments'];
            
            // Mostrar solo features activas
            $('.feature-panel').each(function() {
                var featureName = $(this).data('feature');
                if (this.features.indexOf(featureName) === -1 && 
                    mandatoryFeatures.indexOf(featureName) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            }.bind(this));
            
            // Control de visibilidad de elementos específicos
            if (!this.visibility.inventory_levels) {
                $('#inventory-level-indicator').hide();
            }
            if (!this.visibility.expiration_dates) {
                $('.expiration-date-col').hide();
            }
            if (!this.visibility.table_assignment) {
                $('#table-selector').hide();
            }
            if (!this.visibility.appointment_linking) {
                $('#appointment-linker').hide();
            }
            if (!this.visibility.signature_capture) {
                $('#signature-pad').hide();
            }
        },
        
        // Habilita enfoque automático en lector de código de barras
        enableBarcodeFocus: function() {
            if (this.visibility.barcode_focus_default !== false) {
                // Mantener foco en input de búsqueda/código de barras
                setInterval(function() {
                    if (!$(document.activeElement).is('input[type="text"], textarea')) {
                        $('#barcode-input').focus();
                    }
                }, 2000);
            }
        },
        
        // Muestra selector de mesas para restaurantes
        showTableSelector: function() {
            if (this.visibility.table_assignment) {
                $('#table-selector-modal').modal('show');
            }
        },
        
        // Optimiza eventos para mejor rendimiento
        optimizeEvents: function() {
            // Delegación de eventos para mejor performance
            var $productGrid = $('#product-grid');
            
            // Usar event delegation en lugar de bind individual
            $productGrid.off('click', '.product-item');
            $productGrid.on('click', '.product-item', function(e) {
                e.preventDefault();
                var itemId = $(this).data('item-id');
                if (itemId) {
                    POS.addItem(itemId, 1);
                }
            });
            
            // Optimizar scroll con throttle
            var $scrollContainer = $('.scrollable-container');
            var isThrottled = false;
            
            $scrollContainer.off('scroll');
            $scrollContainer.on('scroll', function() {
                if (isThrottled) return;
                
                isThrottled = true;
                setTimeout(function() {
                    POS.loadMoreItems();
                    isThrottled = false;
                }, 100);
            });
        }
    };

    // Objeto principal del POS
    window.POS = window.POS || {
        cart: [],
        customerId: null,
        orderId: null,
        
        addItem: function(itemId, quantity) {
            // Lógica optimizada de agregado de items
            console.log('Adding item:', itemId, quantity);
            // Implementación real va aquí
        },
        
        loadMoreItems: function() {
            // Carga diferida de productos
            console.log('Loading more items...');
        },
        
        validateTransaction: function() {
            // Validación según contexto
            return SalesContext.requiredFields.every(function(field) {
                // Verificar que cada campo requerido tenga valor
                return true; // Placeholder
            });
        }
    };

    // Inicialización cuando el documento esté listo
    $(document).ready(function() {
        // Obtener configuración del backend (inyectada en la vista)
        if (typeof oneboxSalesConfig !== 'undefined') {
            SalesContext.init(oneboxSalesConfig);
        }
        
        // Precargar datos críticos
        this.preloadCriticalData();
    });

    // Precarga de datos para operaciones rápidas
    SalesContext.preloadCriticalData = function() {
        // Precargar clientes frecuentes
        $.get('/customers/get_frequent.json', function(data) {
            localStorage.setItem('frequent_customers', JSON.stringify(data));
        });
        
        // Precargar métodos de pago
        $.get('/sales/get_payment_methods.json', function(data) {
            localStorage.setItem('payment_methods', JSON.stringify(data));
        });
        
        // Precargar configuración de impuestos
        $.get('/config/get_taxes.json', function(data) {
            localStorage.setItem('tax_rates', JSON.stringify(data));
        });
    };

    // Soporte offline básico
    window.addEventListener('online', function() {
        console.log('Conexión restaurada - sincronizando...');
        POS.syncOfflineData();
    });

    window.addEventListener('offline', function() {
        console.log('Modo offline activado');
        $('#offline-indicator').show();
    });

    // Exponer objetos globales
    window.SalesContext = SalesContext;

})(jQuery);

// Estilos CSS dinámicos inyectados
var style = document.createElement('style');
style.textContent = `
    .mode-speed #product-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
    .mode-speed .product-item { height: 120px; }
    
    .mode-visual #product-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    .mode-visual .product-item { height: 180px; }
    .mode-visual .product-image { max-height: 120px; }
    
    .mode-detail #transaction-details { display: block !important; }
    .mode-detail .form-row { margin-bottom: 15px; }
    
    #offline-indicator {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #ff9800;
        color: white;
        text-align: center;
        padding: 10px;
        z-index: 9999;
        display: none;
    }
`;
document.head.appendChild(style);
