# Use imagem base do PHP com cURL
FROM php:8.1-cli

# Copia os arquivos do projeto para o container
COPY . /app

# Define diretório padrão de trabalho
WORKDIR /app

# Expõe a porta padrão
EXPOSE 80

# Inicia o servidor embutido do PHP
CMD ["php", "-S", "0.0.0.0:80"]
