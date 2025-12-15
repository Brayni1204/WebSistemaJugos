FROM nginx:1.19-alpine

# Copia el archivo de configuración de Nginx al lugar correcto dentro de la imagen.
# El contexto de construcción será el directorio 'docker/nginx', por lo que podemos referenciarlo directamente.
COPY default.conf /etc/nginx/conf.d/default.conf
