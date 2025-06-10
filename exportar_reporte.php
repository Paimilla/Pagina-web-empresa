<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Descargando Excel...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .mensaje {
            background: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .spinner {
            margin: 20px auto;
            width: 40px;
            height: 40px;
            border: 4px solid #ddd;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
       

        @media (max-width: 768px) {
            .mensaje {
            width: 98%;
            padding: 30px;
            font-size: 1.3em;
            }
            .spinner {
            width: 60px;
            height: 60px;
            }
            h2 {
            font-size: 2em;
            }
            p {
            font-size: 1.2em;
            }
        }

    </style>
</head>
<body>
    <div class="mensaje">
        <div class="spinner"></div>
        <h2>Se está descargando el archivo Excel...</h2>
        <p>Por favor, espera unos segundos.</p>
    </div>
</body>
</html>