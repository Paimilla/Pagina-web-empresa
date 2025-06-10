<?php
/**
 * funciones.php - Archivo con funciones básicas para el sistema de control de asistencia
 * Este archivo contiene funciones utilitarias usadas en todo el sistema
 */

/**
 * Obtiene información del usuario desde la base de datos y normaliza los campos
 * 
 * @param int $id_usuario ID del usuario
 * @return array Datos del usuario normalizados
 */
function obtener_usuario($id_usuario) {
    global $conexion;
    
    try {
        // Validación más estricta del ID
        if (!is_numeric($id_usuario)) {
            throw new Exception("ID de usuario inválido");
        }
        
        $id_usuario = $conexion->real_escape_string($id_usuario);
        
        // Determinar cuál es el nombre de la columna de ID en la tabla usuarios
        $result = $conexion->query("SHOW COLUMNS FROM usuarios");
        $id_column = 'id'; // Valor predeterminado
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Si encontramos una columna con 'id' en el nombre y es una clave primaria, la usamos
                if ((stripos($row['Field'], 'id') !== false || stripos($row['Field'], 'codigo') !== false) && 
                    ($row['Key'] == 'PRI' || $row['Key'] == 'UNI')) {
                    $id_column = $row['Field'];
                    break;
                }
            }
        }
        
        // Consulta adaptativa según la columna ID encontrada
        $sql = "SELECT * FROM usuarios WHERE $id_column = '$id_usuario'";
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception("Error en la consulta: " . $conexion->error);
        }
        
        if ($resultado->num_rows === 0) {
            return [];
        }
        
        $usuario = $resultado->fetch_assoc();
        
        // Convertir nombres de campo a un formato estándar
        $usuario_normalizado = [];
        
        // Mapear campos comunes con diferentes nombres posibles
        $campos_mapeados = [
            'id' => ['id', 'id_usuario', 'usuario_id', 'codigo', 'codigo_usuario'],
            'nombre' => ['nombre', 'nombre_completo', 'nombres', 'nombre_usuario'],
            'rut' => ['rut', 'documento', 'dni', 'identificacion'],
            'foto_perfil' => ['foto_perfil', 'avatar', 'imagen', 'foto'],
            'rol' => ['rol', 'tipo', 'nivel', 'perfil', 'tipo_usuario']
        ];
        
        // Normalizar nombres de campos
        foreach ($campos_mapeados as $campo_estandar => $posibles_nombres) {
            foreach ($posibles_nombres as $posible_nombre) {
                if (isset($usuario[$posible_nombre])) {
                    $usuario_normalizado[$campo_estandar] = $usuario[$posible_nombre];
                    break;
                }
            }
        }
        
        // Si no se encontró un campo normalizado para ID, usar el valor original
        if (!isset($usuario_normalizado['id']) && isset($usuario[$id_column])) {
            $usuario_normalizado['id'] = $usuario[$id_column];
        }
        
        // Validar y formatear la foto de perfil
        if (!empty($usuario_normalizado['foto_perfil'])) {
            // Si es base64, asegurarse que esté bien formado
            if (strpos($usuario_normalizado['foto_perfil'], 'base64') !== false) {
                $usuario_normalizado['foto_perfil'] = filter_var($usuario_normalizado['foto_perfil'], 
                                              FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
        } else {
            $usuario_normalizado['foto_perfil'] = 'assets/default-profile.jpg';
        }
        
        return $usuario_normalizado;
    } catch (Exception $e) {
        error_log("Error al obtener usuario ID $id_usuario: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica el estado actual del usuario (dentro o fuera de la dependencia)
 * 
 * @param int $id_usuario ID del usuario
 * @return string 'dentro' o 'fuera'
 */
function verificar_estado_usuario($id_usuario) {
    global $conexion;
    
    try {
        // Obtenemos el último registro de asistencia del usuario
        $id_usuario = $conexion->real_escape_string($id_usuario);
        $sql = "
            SELECT tipo 
            FROM registros_asistencia 
            WHERE id_usuario = '$id_usuario' 
            ORDER BY fecha_hora DESC 
            LIMIT 1
        ";
        
        $resultado = $conexion->query($sql);
        
        // Si no hay registros o el último fue de salida, está fuera
        if ($resultado->num_rows === 0) {
            return 'fuera';
        }
        
        $ultimo_registro = $resultado->fetch_assoc();
        if ($ultimo_registro['tipo'] === 'salida') {
            return 'fuera';
        }
        
        // Si el último registro fue de entrada, está dentro
        return 'dentro';
    } catch (Exception $e) {
        error_log("Error al verificar estado del usuario ID $id_usuario: " . $e->getMessage());
        return 'fuera'; // Por defecto, asumimos que está fuera
    }
}

/**
 * Registra una entrada en el sistema
 * 
 * @param int $id_usuario ID del usuario
 * @param string $tipo Tipo de registro ('entrada' o 'salida')
 * @param string $comentario Comentario opcional
 * @return bool True si se registró correctamente, False si hubo un error
 */
function registrar_asistencia($id_usuario, $tipo, $comentario = '') {
    global $conexion;
    
    try {
        // Sanitizar inputs
        $id_usuario = $conexion->real_escape_string($id_usuario);
        $tipo = $conexion->real_escape_string($tipo);
        $comentario = $conexion->real_escape_string($comentario);
        
        $sql = "
            INSERT INTO registros_asistencia (id_usuario, tipo, comentario, fecha_hora) 
            VALUES ('$id_usuario', '$tipo', '$comentario', NOW())
        ";
        
        return $conexion->query($sql);
    } catch (Exception $e) {
        error_log("Error al registrar asistencia del usuario ID $id_usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el historial de asistencia de un usuario
 * 
 * @param int $id_usuario ID del usuario
 * @param string $fecha_inicio Fecha de inicio (formato Y-m-d)
 * @param string $fecha_fin Fecha de fin (formato Y-m-d)
 * @return array Registros de asistencia
 */
function obtener_historial_asistencia($id_usuario, $fecha_inicio = null, $fecha_fin = null) {
    global $conexion;
    
    try {
        // Sanitizar inputs
        $id_usuario = $conexion->real_escape_string($id_usuario);
        
        $sql = "SELECT * FROM registros_asistencia WHERE id_usuario = '$id_usuario'";
        
        // Agregamos filtro por fecha si se especifica
        if ($fecha_inicio) {
            $fecha_inicio = $conexion->real_escape_string($fecha_inicio);
            $sql .= " AND DATE(fecha_hora) >= '$fecha_inicio'";
        }
        
        if ($fecha_fin) {
            $fecha_fin = $conexion->real_escape_string($fecha_fin);
            $sql .= " AND DATE(fecha_hora) <= '$fecha_fin'";
        }
        
        $sql .= " ORDER BY fecha_hora DESC";
        
        $resultado = $conexion->query($sql);
        
        $registros = [];
        while ($fila = $resultado->fetch_assoc()) {
            $registros[] = $fila;
        }
        
        return $registros;
    } catch (Exception $e) {
        error_log("Error al obtener historial del usuario ID $id_usuario: " . $e->getMessage());
        return [];
    }
}

/**
 * Genera un reporte de usuarios presentes en la dependencia
 * 
 * @return array Lista de usuarios dentro de la dependencia
 */
function usuarios_presentes() {
    global $conexion;
    
    try {
        // Esta consulta obtiene los usuarios cuyo último registro fue una entrada
        $sql = "
            SELECT u.*, r1.fecha_hora as hora_entrada
            FROM usuarios u
            JOIN registros_asistencia r1 ON u.id = r1.id_usuario
            LEFT JOIN registros_asistencia r2 ON (
                u.id = r2.id_usuario AND 
                r1.fecha_hora < r2.fecha_hora
            )
            WHERE r2.id IS NULL AND r1.tipo = 'entrada'
            ORDER BY r1.fecha_hora DESC
        ";
        
        $resultado = $conexion->query($sql);
        
        $usuarios = [];
        while ($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $fila;
        }
        
        return $usuarios;
    } catch (Exception $e) {
        error_log("Error al obtener usuarios presentes: " . $e->getMessage());
        return [];
    }
}



/**
 * Verifica si un usuario tiene rol de administrador
 * 
 * @param int $id_usuario ID del usuario
 * @return bool True si es admin, False si no
 */
function es_administrador($id_usuario) {
    $usuario = obtener_usuario($id_usuario);
    return isset($usuario['rol']) && (strtolower($usuario['rol']) === 'admin' || strtolower($usuario['rol']) === 'administrator');
}



