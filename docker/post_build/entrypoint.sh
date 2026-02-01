#!/bin/bash

set -eux

APP_ROOT_PATH=/app

export DEBIAN_FRONTEND=noninteractive
export FORCE_COLOR=0
GOSU="/usr/sbin/gosu symfony"

for dir in $(mount | grep "${APP_ROOT_PATH}" | grep 'rw' | awk '{ print $3 }')
do
  chown symfony: "${dir}"
done

cd "${APP_ROOT_PATH}"

if [ -z "${APP_ENV:-}" ]
then
    echo "!!! Error !!! Missing APP_ENV environment variable"
    exit 1
fi

ENV_FILE=""
if [ -r "${APP_ROOT_PATH}/.env.$APP_ENV.local" ]
then
    ENV_FILE=".env.$APP_ENV.local"
elif [ -r "${APP_ROOT_PATH}/.env.$APP_ENV" ]
then
    ENV_FILE=".env.$APP_ENV"
elif [ -r "${APP_ROOT_PATH}/.env.local" ]
then
    ENV_FILE=".env.local"
fi

ORIGINAL_APP_ENV=${APP_ENV}
echo "! Loading .env file"
source "${APP_ROOT_PATH}/.env"
if [ -n "${ENV_FILE}" ] && [ -r "${APP_ROOT_PATH}/${ENV_FILE}" ]
then
    echo "! Loading ${ENV_FILE}"
    # shellcheck disable=SC1090
    source "${APP_ROOT_PATH}/${ENV_FILE}"
fi
export APP_ENV=${ORIGINAL_APP_ENV}

if [[ -z "${1:-}" && ("${APP_ENV}" == "dev" || "${APP_ENV}" == "test") ]]; then
    mkdir -p ${APP_ROOT_PATH}/{vendor,node_modules,var/log}
    chown symfony: ${APP_ROOT_PATH}/node_modules
    chown symfony: ${APP_ROOT_PATH}/vendor
    chown symfony: ${APP_ROOT_PATH}/var/log

    if [[ "${APP_ENV}" == "dev" ]]; then
        sed -i \
            -e 's!\(error_reporting\) = .*!\1 = E_ALL!' \
            -e 's!\(display_errors\) = off!\1 = on!' \
            -e 's!\(display_startup_errors\) = off!\1 = on!' \
            /etc/php/8.4/fpm/conf.d/99-symfony.ini

        apt-get update
        apt-get install -y --no-install-recommends php8.4-xdebug
        apt-get install -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev
        apt-get autoremove -y
    fi

    ${GOSU} composer install --prefer-dist
    # Yarn is run on host in dev mode, not in container
    # [[ "${APP_ENV}" == "dev" ]] && ${GOSU} yarn
fi

DATABASE_URL_PARTS=$(php -r "echo json_encode(parse_url('${DATABASE_URL}'));")
DATABASE_HOST=$(echo "$DATABASE_URL_PARTS" | jq -r ".host")
DATABASE_PORT=$(echo "$DATABASE_URL_PARTS" | jq -r ".port")

while ! nc -w 1 -vz "${DATABASE_HOST}" "${DATABASE_PORT}"; do
    echo "Waiting for database..."
    sleep 1;
done

if [[ -z "${1:-}" ]]; then
    # Executing migrations
    ${GOSU} php bin/console doctrine:migrations:migrate --no-interaction
fi

# System Under Test
if [[ "${1:-}" == "sut" ]]; then
    ${GOSU} composer install --prefer-dist

    # Executing migrations
    ${GOSU} php bin/console doctrine:migrations:migrate --no-interaction

    CLOVER_FILEPATH="${APP_ROOT_PATH}/tests/logs/clover.xml"
    JUNIT_FILEPATH="${APP_ROOT_PATH}/tests/logs/report.xml"
    ${GOSU} mkdir -p "$(dirname ${CLOVER_FILEPATH})"

    PATH=$PATH:$(${GOSU} composer global config bin-dir --absolute)
    export PATH
    GIT_DISCOVERY_ACROSS_FILESYSTEM=1
    export GIT_DISCOVERY_ACROSS_FILESYSTEM

    # Prepare test environment
    ${GOSU} php bin/console hautelook:fixtures:load -e "${APP_ENV}" --no-interaction

    # Install phpunit environment by executing a dummy command
    ${GOSU} php bin/phpunit --version

    # Run tests
    ${GOSU} php bin/phpunit --coverage-clover "${CLOVER_FILEPATH}" --log-junit "${JUNIT_FILEPATH}" && TEST_RESULT=0 || TEST_RESULT=$?

    # Execute sonar-scanner only on CI
    if [[ "${CI:-}" == "true" && -n "${SONAR_TOKEN}" ]]; then
      # Install dependencies for sonar-scanner: Java Runtime Engine (JRE), unzip and sha256sum
      apt-get install -y --no-install-recommends curl unzip

      # Install sonar-scanner
      SONAR_CLI_VERSION="6.2.0.4584-linux-x64"
      SONAR_CLI_SHA256_SUM="bc77135e0755bacb1049635928027f3e6c9fec6d728134935df0f43c77108e35"
      SONAR_CLI_DIRNAME="sonar-scanner-cli-${SONAR_CLI_VERSION}"
      SONAR_CLI_FILENAME="${SONAR_CLI_DIRNAME}.zip"
      SONAR_CLI_FILEPATH="/tmp/${SONAR_CLI_FILENAME}"
      curl -sSL -o "/tmp/${SONAR_CLI_FILENAME}" "https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/${SONAR_CLI_FILENAME}"
      echo "${SONAR_CLI_SHA256_SUM} ${SONAR_CLI_FILEPATH}" | sha256sum -c || exit 1
      unzip -q "${SONAR_CLI_FILEPATH}"
      rm "${SONAR_CLI_FILEPATH}"

      HEAD_BRANCH=${GITHUB_HEAD_REF##refs/heads/}
      BASE_BRANCH=${GITHUB_BASE_REF##refs/heads/}

      SONAR_PARAMETERS=""
      # If Pull Request
      if [[ -n "${HEAD_BRANCH:-}" ]] && [[ -n "${BASE_BRANCH:-}" ]]; then
        PULL_REQUEST_KEY=$(echo "${GITHUB_REF}" | sed -n -e 's/refs\/pull\/\([^\/]*\)\/merge/\1/p')
        SONAR_PARAMETERS="-Dsonar.pullrequest.key=${PULL_REQUEST_KEY} -Dsonar.pullrequest.branch=${HEAD_BRANCH} -Dsonar.pullrequest.base=${BASE_BRANCH}"
      else
        BRANCH=${GITHUB_REF##refs/heads/}
        SONAR_PARAMETERS="-Dsonar.branch.name=${BRANCH}"
      fi

      # Scan project
      # shellcheck disable=SC2086
      ./sonar-scanner-${SONAR_CLI_VERSION}/bin/sonar-scanner \
          -Dsonar.token="${SONAR_TOKEN}" \
          -Dsonar.qualitygate.wait=true \
          ${SONAR_PARAMETERS} || true # Do not fail on Quality Gate is not PASSED
    fi

    exit $TEST_RESULT
fi

if [[ "${1:-}" == "messenger-async" ]]; then
  echo "+ Launch bin/console messenger:consume async"
  exec /usr/bin/php /app/bin/console messenger:consume async
elif [[ "${1:-}" == "scheduler-ffta_licensees" ]]; then
  echo "+ Launch bin/console messenger:consume scheduler_ffta_licensees"
  exec /usr/bin/php /app/bin/console messenger:consume scheduler_ffta_licensees
else
  echo "+ Launching services..."
  exec supervisord -c /etc/supervisor/supervisord.conf
fi
