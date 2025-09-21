<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Tidak Tersedia</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px;
        }
        .error-code {
            font-size: 4rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .error-description {
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">503</div>
        <div class="error-message">Service Tidak Tersedia</div>
        <div class="error-description">
            Maaf, server sedang mengalami masalah koneksi database. 
            Silakan coba lagi dalam beberapa saat.
        </div>
        <a href="javascript:history.back()" class="btn">Kembali</a>
    </div>
</body>
</html>
