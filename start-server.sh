#!/bin/bash

echo "🚀 Iniciando servidor PHP para JC3Design..."
echo "📍 Puerto: 8000"
echo "🌐 URL: http://localhost:8000"
echo "📁 Directorio: $(pwd)"
echo ""
echo "Para detener el servidor, presiona Ctrl+C"
echo ""

# Iniciar servidor PHP
php -S localhost:8000

echo ""
echo "✅ Servidor detenido"
