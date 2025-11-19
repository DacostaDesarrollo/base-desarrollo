#!/bin/bash

# Script para demostrar el sistema de logging
# Ejecutar: docker-compose exec app bash /var/www/html/scripts/test-logging.sh

echo "=== Sistema de Monitoreo de Errores ==="
echo ""

# Crear directorio logs si no existe
mkdir -p /var/www/html/logs

# Dar permisos
chmod 777 /var/www/html/logs

echo "✅ Directorio de logs preparado"
echo ""

# Mostrar estructura
echo "📁 Estructura de logs:"
ls -lh /var/www/html/logs/ 2>/dev/null || echo "  (vacío - se crearán al registrar primer error)"
echo ""

echo "📊 Archivos que se crearán automáticamente:"
echo "  - logs/errors.log       (Errores 500+)"
echo "  - logs/warnings.log     (Advertencias 400+)"
echo "  - logs/info.log         (Eventos informativos)"
echo ""

echo "🔍 Para ver logs en tiempo real:"
echo "  docker-compose exec app tail -f /var/www/html/logs/errors.log"
echo ""

echo "📈 Para buscar errores específicos:"
echo "  docker-compose exec app grep 'Exception' /var/www/html/logs/errors.log"
echo ""

echo "✅ Sistema de logging configurado correctamente"
echo ""
echo "💡 TIP: Los errores se registran automáticamente."
echo "   No necesitas hacer nada especial, solo usa try-catch normalmente."
echo ""
