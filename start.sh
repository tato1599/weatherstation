#!/usr/bin/env bash

# ============================================================
#  Meteo-Lab / Clima Visor — Script de Inicio Rapido
# ============================================================
#  Levanta el servidor de desarrollo con todos los servicios:
#  - Servidor web Laravel (php artisan serve)
#  - Worker de colas (php artisan queue:listen)
#  - Logs en tiempo real (php artisan pail)
#  - Servidor Vite HMR (npm run dev)
# ============================================================

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Meteo-Lab — Iniciando Servidor${NC}"
echo -e "${BLUE}========================================${NC}"

if [ ! -f .env ]; then
    echo -e "${YELLOW}No se encontro .env. Ejecuta primero: ./setup.sh${NC}"
    exit 1
fi

if [ ! -d vendor ] || [ ! -d node_modules ]; then
    echo -e "${YELLOW}Faltan dependencias. Ejecuta primero: ./setup.sh${NC}"
    exit 1
fi

echo -e "${GREEN}Levantando servicios...${NC}"
echo -e "${BLUE}Presiona Ctrl+C para detener todos los servicios.${NC}\n"

composer run dev
