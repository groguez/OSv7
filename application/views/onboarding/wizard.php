<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/phppointofsale/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/phppointofsale/font-awesome.min.css'); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .wizard-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 95%;
            padding: 40px;
            margin: 20px;
        }
        .wizard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .wizard-header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            margin: 20px 0;
            background: #e9ecef;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        .step-dot.active {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        .step-dot.completed {
            background: #28a745;
            color: white;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .industry-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .industry-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .industry-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .industry-icon {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 10px;
        }
        .btn-wizard {
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .info-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1><i class="fa fa-rocket"></i> Bienvenido a OneBox</h1>
            <p class="text-muted">Configura tu sistema en 7 sencillos pasos</p>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 14.28%"></div>
        </div>

        <!-- Indicadores de paso -->
        <div class="step-indicator">
            <div class="step-dot active" data-step="1">1</div>
            <div class="step-dot" data-step="2">2</div>
            <div class="step-dot" data-step="3">3</div>
            <div class="step-dot" data-step="4">4</div>
            <div class="step-dot" data-step="5">5</div>
            <div class="step-dot" data-step="6">6</div>
            <div class="step-dot" data-step="7">7</div>
        </div>

        <form id="onboardingForm" method="post">
            <!-- Paso 1: Información del negocio -->
            <div class="step-content active" data-step="1">
                <h3><i class="fa fa-building"></i> Información de tu Negocio</h3>
                <div class="info-box">
                    <i class="fa fa-info-circle"></i> Comencemos con los datos básicos de tu empresa
                </div>
                
                <div class="form-group">
                    <label for="business_name">Nombre del Negocio *</label>
                    <input type="text" class="form-control" id="business_name" name="business_name" required placeholder="Ej. Mi Tienda S.A. de C.V.">
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Correo Electrónico de Contacto *</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" required placeholder="admin@minegocio.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="(55) 1234-5678">
                </div>
                
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <textarea class="form-control" id="address" name="address" rows="3" placeholder="Calle, Número, Colonia, Ciudad, CP"></textarea>
                </div>
            </div>

            <!-- Paso 2: Industria -->
            <div class="step-content" data-step="2">
                <h3><i class="fa fa-industry"></i> Tipo de Industria</h3>
                <div class="info-box">
                    <i class="fa fa-lightbulb"></i> Selecciona la industria que mejor describa tu negocio
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="industry-card" data-value="retail">
                            <div class="industry-icon"><i class="fa fa-store"></i></div>
                            <h5>Retail / Tienda</h5>
                            <p class="small text-muted">Abarrotes, ropa, farmacia, ferretería</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="industry-card" data-value="restaurant">
                            <div class="industry-icon"><i class="fa fa-utensils"></i></div>
                            <h5>Restaurante</h5>
                            <p class="small text-muted">Restaurantes, cafeterías, bares</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="industry-card" data-value="service">
                            <div class="industry-icon"><i class="fa fa-tools"></i></div>
                            <h5>Servicios</h5>
                            <p class="small text-muted">Talleres, lavanderías, reparaciones</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="industry-card" data-value="health">
                            <div class="industry-icon"><i class="fa fa-stethoscope"></i></div>
                            <h5>Salud</h5>
                            <p class="small text-muted">Clínicas, consultorios médicos</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="industry-card" data-value="wholesale">
                            <div class="industry-icon"><i class="fa fa-truck-loading"></i></div>
                            <h5>Mayorista</h5>
                            <p class="small text-muted">Distribuidores, venta a negocios</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="industry-card" data-value="beauty">
                            <div class="industry-icon"><i class="fa fa-spa"></i></div>
                            <h5>Belleza</h5>
                            <p class="small text-muted">Salones, barberías, spas</p>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="industry_type" name="industry_type" required>
                
                <div class="form-group mt-3">
                    <label for="business_size">Tamaño del Negocio</label>
                    <select class="form-control" id="business_size" name="business_size">
                        <option value="micro">Micro (1-5 empleados)</option>
                        <option value="small">Pequeño (6-20 empleados)</option>
                        <option value="medium">Mediano (21-100 empleados)</option>
                        <option value="large">Grande (+100 empleados)</option>
                    </select>
                </div>
            </div>

            <!-- Paso 3: Inventario -->
            <div class="step-content" data-step="3">
                <h3><i class="fa fa-boxes"></i> Gestión de Inventario</h3>
                <div class="info-box">
                    <i class="fa fa-clipboard-list"></i> ¿Cómo deseas controlar tu inventario?
                </div>
                
                <div class="form-group">
                    <label>Tipo de Control de Inventario *</label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="inventory_mode" value="simple" checked>
                            <strong>Simple:</strong> Control básico de cantidades
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="inventory_mode" value="advanced">
                            <strong>Avanzado:</strong> Lotes, caducidades, múltiples ubicaciones
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="inventory_mode" value="services">
                            <strong>Servicios:</strong> Sin inventario (solo servicios)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="initial_products">Cantidad Estimada de Productos</label>
                    <select class="form-control" id="initial_products" name="initial_products">
                        <option value="less_100">Menos de 100</option>
                        <option value="100_500">100 - 500</option>
                        <option value="500_1000">500 - 1,000</option>
                        <option value="1000_plus">Más de 1,000</option>
                    </select>
                </div>
            </div>

            <!-- Paso 4: Ventas -->
            <div class="step-content" data-step="4">
                <h3><i class="fa fa-shopping-cart"></i> Proceso de Ventas</h3>
                <div class="info-box">
                    <i class="fa fa-cash-register"></i> Configura cómo operarás tus ventas
                </div>
                
                <div class="form-group">
                    <label>Modelo de Operación en Ventas *</label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="sales_mode" value="pos" checked>
                            <strong>Punto de Venta:</strong> Cobro rápido en mostrador
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="sales_mode" value="orders">
                            <strong>Órdenes:</strong> Ventas con órdenes de trabajo/servicio
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="sales_mode" value="invoices">
                            <strong>Facturación:</strong> Ventas con facturación y crédito
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="number_of_registers">Número de Cajas/Puntos de Venta</label>
                    <input type="number" class="form-control" id="number_of_registers" name="number_of_registers" value="1" min="1">
                </div>
            </div>

            <!-- Paso 5: Facturación -->
            <div class="step-content" data-step="5">
                <h3><i class="fa fa-file-invoice-dollar"></i> Facturación y Fiscal</h3>
                <div class="info-box">
                    <i class="fa fa-money-bill-wave"></i> Configura el tipo de facturación que utilizarás
                </div>
                
                <div class="form-group">
                    <label>Tipo de Facturación *</label>
                    <select class="form-control" id="billing_mode" name="billing_mode" required>
                        <option value="non_fiscal">No Fiscal (Tickets simples)</option>
                        <option value="fiscal">Fiscal (CFDI 4.0 México)</option>
                        <option value="both">Ambos (Flexible)</option>
                    </select>
                </div>
                
                <div id="fiscal_fields" style="display:none;">
                    <div class="form-group">
                        <label for="tax_id">RFC del Emisor</label>
                        <input type="text" class="form-control" id="tax_id" name="tax_id" placeholder="AAA010101XXX">
                    </div>
                    <div class="form-group">
                        <label for="regimen_fiscal">Régimen Fiscal</label>
                        <select class="form-control" id="regimen_fiscal" name="regimen_fiscal">
                            <option value="">Seleccionar...</option>
                            <option value="601">General de Ley Personas Morales</option>
                            <option value="603">Personas Morales con Fines no Lucrativos</option>
                            <option value="612">Personas Físicas con Actividades Empresariales</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Paso 6: Usuarios -->
            <div class="step-content" data-step="6">
                <h3><i class="fa fa-users"></i> Usuarios y Accesos</h3>
                <div class="info-box">
                    <i class="fa fa-user-shield"></i> Define la estructura de usuarios de tu sistema
                </div>
                
                <div class="form-group">
                    <label for="number_of_users">Número de Usuarios que usarán el sistema</label>
                    <input type="number" class="form-control" id="number_of_users" name="number_of_users" value="1" min="1">
                </div>
                
                <div class="form-group">
                    <label for="store_count">Número de Tiendas/Sucursales (Planificadas)</label>
                    <input type="number" class="form-control" id="store_count" name="store_count" value="1" min="1">
                </div>
                
                <div class="info-box mt-3">
                    <i class="fa fa-check-circle"></i> 
                    <strong>Roles disponibles:</strong><br>
                    • <strong>Super Admin:</strong> Acceso total al sistema<br>
                    • <strong>Admin Tienda:</strong> Gestión de sucursal<br>
                    • <strong>Empleado:</strong> Operación básica (ventas, caja)
                </div>
            </div>

            <!-- Paso 7: Resumen -->
            <div class="step-content" data-step="7">
                <h3><i class="fa fa-check-circle"></i> Resumen y Activación</h3>
                <div class="info-box">
                    <i class="fa fa-rocket"></i> ¡Casi listo! Revisa la configuración antes de activar
                </div>
                
                <div id="summary_content" class="mt-4">
                    <!-- Se llenará dinámicamente -->
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fa fa-info-circle"></i> 
                    Podrás modificar cualquier configuración desde el menú de Administración después de completar este asistente.
                </div>
            </div>

            <!-- Botones de navegación -->
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary btn-wizard" id="prevBtn" style="visibility: hidden;">
                    <i class="fa fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary btn-wizard" id="nextBtn">
                    Siguiente <i class="fa fa-arrow-right"></i>
                </button>
                <button type="submit" class="btn btn-success btn-wizard" id="finishBtn" style="display: none;">
                    <i class="fa fa-check"></i> Activar Sistema
                </button>
            </div>
        </form>
    </div>

    <script src="<?php echo base_url('assets/js/phppointofsale/jquery-3.6.0.min.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            let currentStep = 1;
            const totalSteps = 7;

            // Selección de industria
            $('.industry-card').click(function() {
                $('.industry-card').removeClass('selected');
                $(this).addClass('selected');
                $('#industry_type').val($(this).data('value'));
            });

            // Mostrar campos fiscales si corresponde
            $('#billing_mode').change(function() {
                if ($(this).val() === 'fiscal' || $(this).val() === 'both') {
                    $('#fiscal_fields').slideDown();
                } else {
                    $('#fiscal_fields').slideUp();
                }
            });

            // Navegación
            $('#nextBtn').click(function() {
                if (validateStep(currentStep)) {
                    goToStep(currentStep + 1);
                }
            });

            $('#prevBtn').click(function() {
                goToStep(currentStep - 1);
            });

            // Envío del formulario
            $('#onboardingForm').submit(function(e) {
                e.preventDefault();
                
                $('#finishBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
                
                $.ajax({
                    url: '<?php echo site_url("onboarding/save_configuration"); ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#finishBtn').html('<i class="fa fa-check"></i> ¡Listo!');
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        } else {
                            alert(response.message);
                            $('#finishBtn').prop('disabled', false).html('<i class="fa fa-check"></i> Activar Sistema');
                        }
                    },
                    error: function() {
                        alert('Error al guardar configuración. Intente nuevamente.');
                        $('#finishBtn').prop('disabled', false).html('<i class="fa fa-check"></i> Activar Sistema');
                    }
                });
            });

            function goToStep(step) {
                // Ocultar paso actual
                $(`.step-content[data-step="${currentStep}"]`).removeClass('active');
                
                // Actualizar indicadores
                $(`.step-dot[data-step="${currentStep}"]`).removeClass('active').addClass('completed');
                $(`.step-dot[data-step="${currentStep}"]`).html('<i class="fa fa-check"></i>');
                
                currentStep = step;
                
                // Mostrar nuevo paso
                $(`.step-content[data-step="${currentStep}"]`).addClass('active');
                $(`.step-dot[data-step="${currentStep}"]`).addClass('active');
                
                // Actualizar barra de progreso
                const progress = (currentStep / totalSteps) * 100;
                $('#progressFill').css('width', progress + '%');
                
                // Actualizar botones
                if (currentStep === 1) {
                    $('#prevBtn').css('visibility', 'hidden');
                } else {
                    $('#prevBtn').css('visibility', 'visible');
                }
                
                if (currentStep === totalSteps) {
                    $('#nextBtn').hide();
                    $('#finishBtn').show();
                    loadSummary();
                } else {
                    $('#nextBtn').show();
                    $('#finishBtn').hide();
                }
                
                // Scroll al inicio
                $('html, body').animate({scrollTop: 0}, 300);
            }

            function validateStep(step) {
                let valid = true;
                const stepContent = $(`.step-content[data-step="${step}"]`);
                
                // Validaciones por paso
                switch(step) {
                    case 1:
                        if (!$('#business_name').val() || !$('#contact_email').val()) {
                            alert('Por favor completa los campos obligatorios');
                            valid = false;
                        }
                        break;
                    case 2:
                        if (!$('#industry_type').val()) {
                            alert('Selecciona una industria');
                            valid = false;
                        }
                        break;
                    case 3:
                        if (!$('input[name="inventory_mode"]:checked').val()) {
                            alert('Selecciona un tipo de control de inventario');
                            valid = false;
                        }
                        break;
                    case 4:
                        if (!$('input[name="sales_mode"]:checked').val()) {
                            alert('Selecciona un modelo de operación en ventas');
                            valid = false;
                        }
                        break;
                    case 5:
                        if (!$('#billing_mode').val()) {
                            alert('Selecciona un tipo de facturación');
                            valid = false;
                        }
                        break;
                }
                
                return valid;
            }

            function loadSummary() {
                const summary = `
                    <div class="card">
                        <div class="card-body">
                            <h5><i class="fa fa-building"></i> ${$('#business_name').val()}</h5>
                            <p><strong>Industria:</strong> ${$('.industry-card.selected h5').text()}</p>
                            <p><strong>Inventario:</strong> ${$('input[name="inventory_mode"]:checked').parent().text().replace(':', '')}</p>
                            <p><strong>Ventas:</strong> ${$('input[name="sales_mode"]:checked').parent().text().replace(':', '')}</p>
                            <p><strong>Facturación:</strong> ${$('#billing_mode option:selected').text()}</p>
                            <p><strong>Usuarios:</strong> ${$('#number_of_users').val()} | <strong>Tiendas:</strong> ${$('#store_count').val()}</p>
                        </div>
                    </div>
                `;
                $('#summary_content').html(summary);
            }
        });
    </script>
</body>
</html>
