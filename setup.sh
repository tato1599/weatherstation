#!/usr/bin/env bash
set -e

# ============================================================
#  Meteo-Lab / Clima Visor — Script de Instalacion Rapida
# ============================================================
#  Este script automatiza la instalacion completa del proyecto.
#  Requisitos: PHP 8.3+, Composer, Node.js 20+, PostgreSQL 14+
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Meteo-Lab — Instalador Automatico${NC}"
echo -e "${BLUE}========================================${NC}"

# -----------------------------------------------------------
# 1. Verificar prerequisitos
# -----------------------------------------------------------

echo -e "\n${BLUE}[1/8] Verificando prerequisitos...${NC}"

MISSING=0

if ! command -v php &> /dev/null; then
    echo -e "${RED}  ✗ PHP no esta instalado.${NC}"
    MISSING=1
else
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo -e "${GREEN}  ✓ PHP ${PHP_VERSION}${NC}"
fi

if ! command -v composer &> /dev/null; then
    echo -e "${RED}  ✗ Composer no esta instalado.${NC}"
    MISSING=1
else
    echo -e "${GREEN}  ✓ Composer${NC}"
fi

if ! command -v node &> /dev/null; then
    echo -e "${RED}  ✗ Node.js no esta instalado.${NC}"
    MISSING=1
else
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}  ✓ Node.js ${NODE_VERSION}${NC}"
fi

if ! command -v npm &> /dev/null; then
    echo -e "${RED}  ✗ npm no esta instalado.${NC}"
    MISSING=1
else
    echo -e "${GREEN}  ✓ npm${NC}"
fi

if ! command -v psql &> /dev/null; then
    echo -e "${RED}  ✗ PostgreSQL (psql) no esta instalado.${NC}"
    MISSING=1
else
    echo -e "${GREEN}  ✓ PostgreSQL (psql)${NC}"
fi

if [ $MISSING -eq 1 ]; then
    echo -e "\n${RED}Por favor instala los prerequisitos faltantes antes de continuar.${NC}"
    exit 1
fi

# -----------------------------------------------------------
# 2. Configurar .env
# -----------------------------------------------------------

echo -e "\n${BLUE}[2/8] Configurando entorno...${NC}"

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}  ✓ .env creado desde .env.example${NC}"
    else
        echo -e "${RED}  ✗ No existe .env ni .env.example${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}  ! .env ya existe, se conserva${NC}"
fi

# Generar APP_KEY si esta vacia
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
    php artisan key:generate --quiet
    echo -e "${GREEN}  ✓ APP_KEY generada${NC}"
else
    echo -e "${YELLOW}  ! APP_KEY ya configurada${NC}"
fi

# -----------------------------------------------------------
# 3. Instalar dependencias PHP
# -----------------------------------------------------------

echo -e "\n${BLUE}[3/8] Instalando dependencias PHP (Composer)...${NC}"
composer install --no-interaction --prefer-dist

# -----------------------------------------------------------
# 4. Instalar dependencias frontend
# -----------------------------------------------------------

echo -e "\n${BLUE}[4/8] Instalando dependencias frontend (npm)...${NC}"
npm install

# -----------------------------------------------------------
# 5. Crear base de datos si no existe
# -----------------------------------------------------------

echo -e "\n${BLUE}[5/8] Verificando base de datos PostgreSQL...${NC}"

# Extraer valores del .env
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d '"')
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2 | tr -d '"')
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"')
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"')
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"')

# Valores por defecto si estan vacios
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-clima_visor}

if [ -z "$DB_USERNAME" ]; then
    echo -e "${YELLOW}  ! DB_USERNAME esta vacio en .env${NC}"
    echo -e "${YELLOW}    Se intentara conectarse como usuario 'postgres' por defecto.${NC}"
    DB_USERNAME="postgres"
fi

# Verificar conexion y crear base de datos
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '$DB_DATABASE';" | grep -q 1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}  ✓ La base de datos '${DB_DATABASE}' ya existe${NC}"
else
    echo -e "${YELLOW}  ! La base de datos '${DB_DATABASE}' no existe. Creandola...${NC}"
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d postgres -c "CREATE DATABASE $DB_DATABASE;"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}  ✓ Base de datos '${DB_DATABASE}' creada${NC}"
    else
        echo -e "${RED}  ✗ No se pudo crear la base de datos.${NC}"
        echo -e "${RED}    Verifica que el usuario '${DB_USERNAME}' tenga permisos y que PostgreSQL este corriendo.${NC}"
        exit 1
    fi
fi

# -----------------------------------------------------------
# 6. Ejecutar migraciones
# -----------------------------------------------------------

echo -e "\n${BLUE}[6/8] Ejecutando migraciones...${NC}"
php artisan migrate --force --no-interaction

# -----------------------------------------------------------
# 7. Compilar assets frontend
# -----------------------------------------------------------

echo -e "\n${BLUE}[7/8] Compilando assets con Vite...${NC}"
npm run build

# -----------------------------------------------------------
# 8. Resumen y siguiente paso
# -----------------------------------------------------------

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  Instalacion completada exitosamente${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "\n${BLUE}Para iniciar el proyecto en modo desarrollo, ejecuta:${NC}"
echo -e "  ${YELLOW}composer run dev${NC}"
echo -e ""
echo -e "${BLUE}O usa el script rapido:${NC}"
echo -e "  ${YELLOW}./start.sh${NC}"
echo -e ""
echo -e "${BLUE}Luego abre tu navegador en:${NC}"
echo -e "  ${YELLOW}http://localhost:8000${NC}"
echo -e ""
echo -e "${BLUE}Rutas importantes:${NC}"
echo -e "  Landing publica: ${YELLOW}http://localhost:8000/${NC}"
echo -e "  Panel tecnico:   ${YELLOW}http://localhost:8000/monitor${NC}"
echo -e "  Hardware:        ${YELLOW}http://localhost:8000/hardware${NC}"
echo -e "  Tiempo real:     ${YELLOW}http://localhost:8000/live${NC}"
echo -e ""
echo -e "${BLUE}Recuerda configurar tu Arduino y el puerto serial en:${NC}"
echo -e "  ${YELLOW}.env${NC} -> ${YELLOW}WEATHER_SERIAL_PORT${NC}"
