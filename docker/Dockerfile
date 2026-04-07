# =============================================================================
# Stage 1: base — Ubuntu 24.04 + runtime packages + symfony user (UID 1000)
# Contains only what is needed to RUN the application; no build tools.
# =============================================================================
FROM ubuntu:24.04 AS base

ARG BUILD_DATE
ARG VCS_REF

ENV TZ="Europe/Paris"

VOLUME /var/lib/php/sessions
EXPOSE 80

COPY ./docker/build /docker/build
RUN /bin/bash /docker/build/base_setup.sh

COPY ./docker/config /etc

# =============================================================================
# Stage 2: builder — adds Composer, Node.js, native build deps, compiles assets
# Never deployed; only used as an artefact source for the runtime stage.
# =============================================================================
FROM base AS builder

ARG BUILD_DATE
ARG VCS_REF
ARG VERSION

ENV HOME=/app BUILD_TARGET=prod

COPY ./docker/build/builder_setup.sh /docker/build/builder_setup.sh
RUN /bin/bash /docker/build/builder_setup.sh

COPY . /app
COPY ./docker/post_build/configure.sh /docker/post_build/configure.sh
RUN /bin/bash /docker/post_build/configure.sh && rm -rf /docker

# =============================================================================
# Stage 3: dev — like builder but adds xdebug; entrypoint handles runtime setup
# NOTE: no USER directive — entrypoint needs root briefly to fix bind-mount ownership
# =============================================================================
FROM base AS dev

ARG BUILD_DATE
ARG VCS_REF

ENV HOME=/app BUILD_TARGET=dev

COPY ./docker/build/builder_setup.sh /docker/build/builder_setup.sh
RUN /bin/bash /docker/build/builder_setup.sh && \
    apt-get update && \
    apt-get install -y --no-install-recommends php8.4-xdebug vim wget && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN ln -sf /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini

# Pre-populate /app with source + vendor (overridden by bind-mount at dev runtime)
COPY . /app
RUN setpriv --reuid=symfony --regid=symfony --init-groups -- \
    /usr/local/bin/composer install --prefer-dist --no-interaction

COPY ./docker/post_build/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]

# =============================================================================
# Stage 4: runtime — minimal deployable image; all processes run as symfony (UID 1000)
# Source files are root:root (read-only for the app); only /app/var is symfony-writable.
# =============================================================================
FROM base AS runtime

ARG VCS_REF

ENV HOME=/app APP_ENV=prod

# Source code — root:root (var/, vendor/, public/build/, public/bundles/ excluded by .dockerignore)
COPY . /app

# Production-built artefacts from the builder stage
COPY --from=builder /app/vendor /app/vendor
COPY --from=builder /app/public/build /app/public/build
COPY --from=builder /app/public/bundles /app/public/bundles

# Link PHP-FPM php.ini for CLI and stamp the revision
RUN ln -sf /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini && \
    echo "${VCS_REF:-}" > /app/REVISION

# Drop privileges — all runtime processes run as symfony (UID 1000)
USER symfony

COPY ./docker/post_build/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]
