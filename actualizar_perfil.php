<?php 
// Iniciar sesión
session_start();

if (!extension_loaded('imagick')) {
    die('La extensión Imagick no está cargada.');
}


// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit;
}

// Incluir archivo de conexión
require_once 'conexion.php';

// Generar nuevo token CSRF para la próxima solicitud
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Obtener ID del usuario de la sesión
$id_usuario = $_SESSION['id_usuario'];

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $rut = trim($_POST['rut']);
    
    $errores = [];

    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre no puede exceder los 100 caracteres";
    }

    if (empty($rut)) {
        $errores[] = "El RUT es requerido";
    } else {
        // Aquí puedes agregar tu lógica adicional de validación del RUT si la tienes
    }

    if (empty($errores)) {
        $query = "UPDATE usuarios SET nombre = ?, rut = ?";
        $params = [$nombre, $rut];
        $types = "ss";

        // Verificar si se subió una nueva imagen
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['foto_perfil'];
            $tamaño_maximo = 5 * 1024 * 1024;

            if ($archivo['size'] > $tamaño_maximo) {
                $_SESSION['mensaje'] = "La imagen es demasiado grande. El tamaño máximo permitido es 5MB.";
                $_SESSION['tipo_mensaje'] = "danger";
                header('Location: editperfil.php');
                exit;
            }

            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            $tipo_archivo = mime_content_type($archivo['tmp_name']);

            if (!in_array($tipo_archivo, $tipos_permitidos)) {
                $_SESSION['mensaje'] = "Solo se permiten imágenes en formato JPG, PNG o GIF.";
                $_SESSION['tipo_mensaje'] = "danger";
                header('Location: editperfil.php');
                exit;
            }

            // Función para redimensionar la imagen
            function redimensionarImagen($archivo_tmp, $ancho_maximo = 800, $alto_maximo = 800) {
                try {
                    $imagen = new Imagick($archivo_tmp);
                    $imagen->autoOrient();
                    $imagen->thumbnailImage($ancho_maximo, $alto_maximo, true);
                    $imagen->setImageFormat('jpeg');
                    $imagen->setImageCompression(Imagick::COMPRESSION_JPEG);
                    $imagen->setImageCompressionQuality(85);
                    return $imagen->getImageBlob();
                } catch (Exception $e) {
                    error_log("Error con Imagick: " . $e->getMessage());
                    return false;
                }
            }

            // Procesar y codificar la imagen
            $contenido_imagen = redimensionarImagen($archivo['tmp_name'], 800, 800);
            if ($contenido_imagen === false) {
                $_SESSION['mensaje'] = "Error al procesar la imagen.";
                $_SESSION['tipo_mensaje'] = "danger";
                header('Location: editperfil.php');
                exit;
            }

            $foto_perfil = base64_encode($contenido_imagen);
            $query .= ", foto_perfil = ?";
            $params[] = $foto_perfil;
            $types .= "s";
        }

        $query .= " WHERE id_usuario = ?";
        $params[] = $id_usuario;
        $types .= "i";

        $stmt = $conexion->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['nombre_usuario'] = $nombre;
            if (isset($foto_perfil)) {
                $_SESSION['foto_perfil'] = 'data:image/jpeg;base64,' . $foto_perfil;
            }
            $_SESSION['mensaje'] = "Perfil actualizado con éxito.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar el perfil: " . $conexion->error;
            $_SESSION['tipo_mensaje'] = "danger";
            error_log("Error al actualizar perfil (ID: $id_usuario): " . $conexion->error);
        }
    } else {
        $_SESSION['mensaje'] = "Errores en el formulario: " . implode(", ", $errores);
        $_SESSION['tipo_mensaje'] = "danger";
    }

    header('Location: editperfil.php');
    exit;
} else {
    header('Location: editperfil.php');
    exit;
}
?>
