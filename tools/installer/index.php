<?php
/**
 * OneBox Migration Master - Instalador Inteligente Plug & Play
 * Detecta instalaciones antiguas, migra datos y actualiza a OneBox v2026
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción

// Configuración inicial
define('ONEBOX_VERSION', '2026.1.0');
define('MIGRATION_STATUS_FILE', __DIR__ . '/migration_status.json');

class MigrationMaster {
    private $db;
    private $steps = [
        'detect' => 'Detección del Sistema',
        'backup' => 'Respaldo de Seguridad',
        'migrate_schema' => 'Actualización de Esquema',
        'migrate_data' => 'Migración de Datos',
        'rebrand' => 'Rebranding OneBox',
        'validate' => 'Validación de Integridad',
        'finish' => 'Finalización'
    ];
    
    public function __construct() {
        $this->status = $this->loadStatus();
    }
    
    private function loadStatus() {
        if (file_exists(MIGRATION_STATUS_FILE)) {
            return json_decode(file_get_contents(MIGRATION_STATUS_FILE), true);
        }
        return ['step' => 'detect', 'completed' => false, 'logs' => []];
    }
    
    private function saveStatus() {
        file_put_contents(MIGRATION_STATUS_FILE, json_encode($this->status));
    }
    
    private function log($message, $type = 'info') {
        $this->status['logs'][] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'type' => $type
        ];
        $this->saveStatus();
    }
    
    public function detectOldSystem($config) {
        try {
            $this->db = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
            if ($this->db->connect_error) {
                throw new Exception("Error de conexión: " . $this->db->connect_error);
            }
            
            // Detectar si es PHP Point of Sale
            $result = $this->db->query("SHOW TABLES LIKE 'php_pos_settings'");
            $isLegacy = $result->num_rows > 0;
            
            // Verificar tablas críticas
            $tables = ['os_employees', 'os_items', 'os_sales', 'os_customers'];
            $missing = [];
            foreach ($tables as $table) {
                $result = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    $missing[] = $table;
                }
            }
            
            $this->log("Sistema detectado: " . ($isLegacy ? 'PHP Point of Sale Legacy' : 'OneBox/Moderno'));
            $this->log("Tablas faltantes: " . (empty($missing) ? 'Ninguna' : implode(', ', $missing)));
            
            return [
                'success' => empty($missing),
                'is_legacy' => $isLegacy,
                'missing_tables' => $missing,
                'message' => empty($missing) ? 'Sistema compatible detectado' : 'Faltan tablas críticas'
            ];
        } catch (Exception $e) {
            $this->log("Error en detección: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function createBackup($config) {
        try {
            $backupFile = __DIR__ . '/backups/backup_' . date('Ymd_His') . '.sql';
            if (!file_exists(__DIR__ . '/backups')) {
                mkdir(__DIR__ . '/backups', 0755, true);
            }
            
            $tables = [];
            $result = $this->db->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $sql = "-- OneBox Backup Generated: " . date('Y-m-d H:i:s') . "\n\n";
            foreach ($tables as $table) {
                $result = $this->db->query("SHOW CREATE TABLE $table");
                $create = $result->fetch_array();
                $sql .= $create[1] . ";\n\n";
                
                $result = $this->db->query("SELECT * FROM $table");
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($v) {
                        return $v === null ? 'NULL' : "'" . $this->db->real_escape_string($v) . "'";
                    }, array_values($row));
                    $sql .= "INSERT INTO $table VALUES (" . implode(',', $values) . ");\n";
                }
            }
            
            file_put_contents($backupFile, $sql);
            $this->log("Backup creado exitosamente: $backupFile");
            
            return ['success' => true, 'file' => $backupFile];
        } catch (Exception $e) {
            $this->log("Error creando backup: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function runMigration() {
        try {
            $this->log("Iniciando migración de esquema...");
            
            // Leer script maestro
            $migrationScript = __DIR__ . '/../../database/migrations/master_gold_release.sql';
            if (!file_exists($migrationScript)) {
                throw new Exception("Script de migración no encontrado");
            }
            
            $sql = file_get_contents($migrationScript);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $this->db->begin_transaction();
            $count = 0;
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) continue;
                $this->db->query($statement);
                $count++;
            }
            $this->db->commit();
            
            $this->log("Migración completada: $count sentencias ejecutadas");
            return ['success' => true, 'statements' => $count];
        } catch (Exception $e) {
            if ($this->db) $this->db->rollback();
            $this->log("Error en migración: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function applyRebranding() {
        try {
            $this->log("Aplicando rebranding a OneBox...");
            
            // Actualizar configuración general
            $this->db->query("UPDATE os_app_config SET config_value = 'OneBox' WHERE config_key = 'company'");
            $this->db->query("UPDATE os_app_config SET config_value = 'OneBox v" . ONEBOX_VERSION . "' WHERE config_key = 'version'");
            
            // Actualizar mensajes y textos
            $this->db->query("UPDATE os_modules SET name_desc = REPLACE(name_desc, 'PHP Point of Sale', 'OneBox')");
            
            $this->log("Rebranding aplicado exitosamente");
            return ['success' => true];
        } catch (Exception $e) {
            $this->log("Error en rebranding: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function validateIntegrity() {
        try {
            $this->log("Validando integridad del sistema...");
            
            $checks = [
                'Tablas OneBox' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'os_%'",
                'Usuarios activos' => "SELECT COUNT(*) FROM os_employees WHERE deleted = 0",
                'Productos migrados' => "SELECT COUNT(*) FROM os_items WHERE deleted = 0",
                'Configuración válida' => "SELECT COUNT(*) FROM os_app_config WHERE config_value IS NOT NULL"
            ];
            
            $results = [];
            foreach ($checks as $name => $query) {
                $result = $this->db->query($query);
                $row = $result->fetch_array();
                $results[$name] = $row[0];
            }
            
            $this->log("Validación completada: " . json_encode($results));
            return ['success' => true, 'details' => $results];
        } catch (Exception $e) {
            $this->log("Error en validación: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Manejo de solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $migration = new MigrationMaster();
    
    switch ($_POST['action']) {
        case 'detect':
            $config = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'user' => $_POST['db_user'],
                'pass' => $_POST['db_pass'],
                'name' => $_POST['db_name']
            ];
            echo json_encode($migration->detectOldSystem($config));
            break;
            
        case 'backup':
            echo json_encode($migration->createBackup($_POST));
            break;
            
        case 'migrate':
            echo json_encode($migration->runMigration());
            break;
            
        case 'rebrand':
            echo json_encode($migration->applyRebranding());
            break;
            
        case 'validate':
            echo json_encode($migration->validateIntegrity());
            break;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneBox Migration Master - Actualización a v2026</title>
    <style>
        :root {
            --primary: #4F46E5;
            --success: #10B981;
            --warning: #F59E0B;
            --error: #EF4444;
            --bg: #F3F4F6;
            --card: #FFFFFF;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        
        body { background: var(--bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .container { background: var(--card); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 800px; width: 100%; overflow: hidden; }
        
        .header { background: linear-gradient(135deg, var(--primary), #7C3AED); color: white; padding: 40px; text-align: center; }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        
        .steps { display: flex; padding: 30px; gap: 10px; overflow-x: auto; }
        .step { flex: 1; min-width: 120px; padding: 15px; border-radius: 8px; background: #F9FAFB; text-align: center; font-size: 0.9rem; border: 2px solid transparent; transition: all 0.3s; }
        .step.active { border-color: var(--primary); background: #EEF2FF; font-weight: 600; }
        .step.completed { background: #D1FAE5; border-color: var(--success); }
        
        .content { padding: 0 30px 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        
        .btn { background: var(--primary); color: white; border: none; padding: 14px 28px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { background: #9CA3AF; cursor: not-allowed; transform: none; }
        
        .progress-bar { height: 8px; background: #E5E7EB; border-radius: 4px; overflow: hidden; margin: 20px 0; }
        .progress-fill { height: 100%; background: var(--success); width: 0%; transition: width 0.5s; }
        
        .log-container { background: #1F2937; color: #10B981; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.85rem; max-height: 300px; overflow-y: auto; margin-top: 20px; }
        .log-entry { margin-bottom: 5px; }
        .log-error { color: #EF4444; }
        
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 OneBox Migration Master</h1>
            <p>Actualización automática a OneBox v2026 - Plug & Play</p>
        </div>
        
        <div class="steps">
            <?php foreach ($migration->steps as $key => $label): ?>
            <div class="step" id="step-<?= $key ?>"><?= $label ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="content">
            <div id="step-detect" class="step-content">
                <h3 style="margin-bottom: 20px;">Configuración de Base de Datos</h3>
                <div class="form-group">
                    <label>Servidor</label>
                    <input type="text" id="db_host" value="localhost" placeholder="localhost">
                </div>
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" id="db_user" placeholder="usuario_db">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="db_pass" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Base de Datos</label>
                    <input type="text" id="db_name" placeholder="nombre_base_datos">
                </div>
                <button class="btn" onclick="startDetection()">Detectar Sistema</button>
            </div>
            
            <div id="step-process" class="step-content hidden">
                <h3 style="margin-bottom: 20px;">Procesando Migración...</h3>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress"></div>
                </div>
                <p id="current-step" style="text-align: center; margin-bottom: 20px; font-weight: 500;"></p>
                <div class="log-container" id="logs"></div>
            </div>
            
            <div id="step-finish" class="step-content hidden">
                <h3 style="margin-bottom: 20px; color: var(--success);">¡Migración Completada!</h3>
                <p style="margin-bottom: 20px;">Tu sistema ha sido actualizado exitosamente a OneBox v2026.</p>
                <div style="background: #EEF2FF; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Próximos pasos:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Elimina la carpeta /tools/installer por seguridad</li>
                        <li>Inicia sesión con tus credenciales habituales</li>
                        <li>Explora las nuevas funcionalidades de IA y Smart Receiving</li>
                    </ul>
                </div>
                <button class="btn" onclick="window.location.href='../'">Ir a OneBox</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentStepIndex = 0;
        const steps = Object.keys(<?= json_encode($migration->steps) ?>);
        
        async function startDetection() {
            const config = {
                db_host: document.getElementById('db_host').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value,
                db_name: document.getElementById('db_name').value
            };
            
            if (!config.db_user || !config.db_name) {
                alert('Por favor completa todos los campos');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'detect');
            Object.keys(config).forEach(key => formData.append(key, config[key]));
            
            const response = await fetch('', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                startMigration(config);
            } else {
                alert('Error: ' + result.message);
            }
        }
        
        async function startMigration(config) {
            document.getElementById('step-detect').classList.add('hidden');
            document.getElementById('step-process').classList.remove('hidden');
            
            const actions = ['backup', 'migrate', 'rebrand', 'validate'];
            
            for (let i = 0; i < actions.length; i++) {
                updateStep(i);
                updateProgress((i + 1) / actions.length * 100);
                
                const formData = new FormData();
                formData.append('action', actions[i]);
                Object.keys(config).forEach(key => formData.append(key, config[key]));
                
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                addLog(`[${actions[i]}] ${result.success ? 'Completado' : 'Error: ' + result.message}`);
                
                if (!result.success) {
                    alert('Error en el paso ' + actions[i] + ': ' + result.message);
                    return;
                }
                
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            finishMigration();
        }
        
        function updateStep(index) {
            document.querySelectorAll('.step').forEach((step, i) => {
                step.classList.remove('active');
                if (i <= index) step.classList.add('completed');
                if (i === index) step.classList.add('active');
            });
            document.getElementById('current-step').textContent = Object.values(<?= json_encode($migration->steps) ?>)[index + 1];
        }
        
        function updateProgress(percent) {
            document.getElementById('progress').style.width = percent + '%';
        }
        
        function addLog(message) {
            const logs = document.getElementById('logs');
            const entry = document.createElement('div');
            entry.className = 'log-entry' + (message.includes('Error') ? ' log-error' : '');
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logs.appendChild(entry);
            logs.scrollTop = logs.scrollHeight;
        }
        
        function finishMigration() {
            document.getElementById('step-process').classList.add('hidden');
            document.getElementById('step-finish').classList.remove('hidden');
            document.querySelectorAll('.step').forEach(step => step.classList.add('completed'));
        }
    </script>
</body>
</html>
