FROM ghcr.io/laracord/egg:php8

USER root

RUN apk update \
  && apk add --no-cache --repository https://dl-cdn.alpinelinux.org/alpine/edge/testing watchexec ripgrep

USER vscode
ENV USER vscode
ENV HOME /home/vscode

WORKDIR /app
