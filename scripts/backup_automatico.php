<?php
/**
 * Script de Backup Automático para JC3Design
 * 
 * Este script se puede ejecutar manualmente o programar con cron:
 * 
 * Ejemplo de cron para backup diario a las 2:00 AM:
 * 0 2 * * * /usr/bin/php /ruta/a/tu/proyecto/scripts/backup_automatico.php
 * 
 * Ejemplo de cron para backup semanal los domingos a las 3:00 AM:
 * 0 3 * * 0 /usr/bin/php /ruta/a/tu/proyecto/scripts/backup_automatico.php
 */

// Configuración
$config = [
    'backup_dir' => __DIR__ . '/../backups/',
    'max_backups' => 30, // Mantener solo los últimos 30 backups
    'compress' => true,   // Comprimir backups antiguos
    'log_file' => __DIR__ . '/../logs/backup.log'
];

// Incluir configuración de la base de datos
require_once __DIR__ . '/../database/config.php';

// Función para logging
function logBackup($message) {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}" . PHP_EOL;
    
    if (file_exists($config['log_file'])) {
        file_put_contents($config['log_file'], $log_message, FILE_APPEND | LOCK_EX);
    }
    
    // También mostrar en consola si se ejecuta desde terminal
    if (php_sapi_name() === 'cli') {
        echo $log_message;
    }
}

try {
    logBackup('Iniciando backup automático...');
    
    // Crear directorio de backups si no existe
    if (!is_dir($config['backup_dir'])) {
        mkdir($config['backup_dir'], 0755, true);
        logBackup('Directorio de backups creado');
    }
    
    // Generar nombre del archivo
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "jc3design_backup_{$timestamp}.sql";
    $filepath = $config['backup_dir'] . $filename;
    
    // Comando mysqldump
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );
    
    logBackup("Ejecutando: {$command}");
    
    // Ejecutar backup
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filepath)) {
        $file_size = filesize($filepath);
        $file_size_mb = round($file_size / 1024 / 1024, 2);
        
        logBackup("Backup creado exitosamente: {$filename} ({$file_size_mb} MB)");
        
        // Comprimir backup si está habilitado
        if ($config['compress'] && $file_size > 1024 * 1024) { // Solo comprimir si es mayor a 1MB
            $compressed_file = $filepath . '.gz';
            $gz = gzopen($compressed_file, 'w9');
            
            if ($gz) {
                gzwrite($gz, file_get_contents($filepath));
                gzclose($gz);
                
                // Eliminar archivo original sin comprimir
                unlink($filepath);
                
                $compressed_size = filesize($compressed_file);
                $compressed_size_mb = round($compressed_size / 1024 / 1024, 2);
                $compression_ratio = round((1 - $compressed_size / $file_size) * 100, 1);
                
                logBackup("Backup comprimido: {$filename}.gz ({$compressed_size_mb} MB, {$compression_ratio}% compresión)");
            }
        }
        
        // Limpiar backups antiguos
        $backups = glob($config['backup_dir'] . '*.sql*');
        if (count($backups) > $config['max_backups']) {
            // Ordenar por fecha de modificación (más antiguos primero)
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $to_delete = array_slice($backups, 0, count($backups) - $config['max_backups']);
            
            foreach ($to_delete as $old_backup) {
                if (unlink($old_backup)) {
                    $old_filename = basename($old_backup);
                    logBackup("Backup antiguo eliminado: {$old_filename}");
                }
            }
        }
        
        logBackup('Backup automático completado exitosamente');
        
    } else {
        throw new Exception('Error al ejecutar mysqldump (código: ' . $return_var . ')');
    }
    
} catch (Exception $e) {
    logBackup('ERROR: ' . $e->getMessage());
    exit(1);
}

// Si se ejecuta desde terminal, mostrar resumen
if (php_sapi_name() === 'cli') {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "BACKUP AUTOMÁTICO COMPLETADO\n";
    echo str_repeat('=', 50) . "\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
    echo "Archivo: {$filename}\n";
    echo "Tamaño: {$file_size_mb} MB\n";
    echo "Estado: EXITOSO\n";
    echo str_repeat('=', 50) . "\n\n";
}
?>
