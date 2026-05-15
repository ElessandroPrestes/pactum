<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pactum</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        main { max-width: 640px; width: 100%; }
        h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
        }
        p.lead { font-size: 1.05rem; line-height: 1.6; color: #94a3b8; margin: 0 0 2rem; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { margin-bottom: 0.75rem; }
        a {
            color: #38bdf8;
            text-decoration: none;
            border-bottom: 1px solid rgba(56, 189, 248, 0.3);
        }
        a:hover { border-bottom-color: #38bdf8; }
        code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.9em;
        }
        footer { margin-top: 3rem; color: #64748b; font-size: 0.85rem; }
    </style>
</head>
<body>
    <main>
        <h1>Pactum</h1>
        <p class="lead">
            ERP de contratos e serviços recorrentes. Esta instância serve a API REST sob
            <code>/api/v1</code>. Esta página existe apenas como ponto de entrada;
            a aplicação é consumida via HTTP autenticado.
        </p>
        <ul>
            <li><a href="/health">/health</a> &mdash; status (MySQL e Redis)</li>
            <li><a href="/api/v1/clients">/api/v1/clients</a> &mdash; requer <code>Authorization: Bearer &lt;token&gt;</code></li>
            <li><a href="/api/v1/contracts">/api/v1/contracts</a> &mdash; requer auth</li>
        </ul>
        <footer>
            Laravel {{ app()->version() }} &middot; PHP {{ PHP_VERSION }}
        </footer>
    </main>
</body>
</html>
