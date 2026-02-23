FROM ubuntu:22.04

ARG BUILD_DATE
ARG VCS_REF
ARG BUILD_TARGET=dev

ENV TZ="Europe/Paris"
ENV BUILD_TARGET=${BUILD_TARGET}

VOLUME /var/lib/php/sessions

EXPOSE 80

COPY ./docker/build /docker/build
RUN /bin/bash /docker/build/image_build.sh

COPY ./docker/config /etc
COPY --chown=symfony:symfony . /app

COPY ./docker/post_build/configure.sh /docker/post_build/configure.sh
RUN --mount=type=secret,id=fontawesome_npm_auth_token /bin/bash /docker/post_build/configure.sh && rm -rf /docker

COPY ./docker/post_build/entrypoint.sh /entrypoint.sh
RUN echo $VCS_REF > /app/REVISION

ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]
