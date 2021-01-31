FROM php:7.4-cli-alpine
RUN apk add --no-cache wget && \
    wget "https://get.blackfire.io/blackfire-player.phar" && \
    mv blackfire-player.phar /usr/local/bin/blackfire-player && \
    chmod +x /usr/local/bin/blackfire-player

ENTRYPOINT ["/usr/local/bin/blackfire-player"]
