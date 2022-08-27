FROM ubuntu:22.04

ARG BUILD_DATE
ARG VCS_REF
ARG VERSION
LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="Symfony Project" \
      org.label-schema.url="https://..." \
      org.label-schema.vcs-ref=$VCS_REF \
      org.label-schema.vcs-url="https://github.com/dehy/template-symfony" \
      org.label-schema.vendor="Arnaud de Mouhy" \
      org.label-schema.version=$VERSION \
      org.label-schema.schema-version="1.0"

ENV TZ="Europe/Paris"

VOLUME /var/lib/php/sessions

EXPOSE 80

COPY ./docker/build /docker/build
RUN /bin/bash /docker/build/image_build.sh

COPY ./docker/config /etc
COPY --chown=symfony:symfony . /app

COPY ./docker/post_build/configure.sh /docker/post_build/configure.sh
RUN /bin/bash /docker/post_build/configure.sh && rm -rf /docker

COPY ./docker/post_build/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]
