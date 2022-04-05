#!/bin/bash

set -eux

APP_ROOT_PATH=/app

export DEBIAN_FRONTEND=noninteractive
export FORCE_COLOR=0
GOSU="/usr/sbin/gosu symfony"

for dir in $(mount | grep "${APP_ROOT_PATH}" | grep 'rw' | awk '{ print $3 }')
do
  chown -R symfony: "${dir}"
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

if [[ "${APP_ENV}" == "dev" ]]; then
    sed -i \
        -e 's!\(error_reporting\) = .*!\1 = E_ALL!' \
        -e 's!\(display_errors\) = off!\1 = on!' \
        -e 's!\(display_startup_errors\) = off!\1 = on!' \
        /etc/php/8.0/fpm/conf.d/99-symfony.ini

    apt-get update
    apt-get install -y --no-install-recommends php8.0-xdebug

    apt-get autoremove -y
fi

if [[ "${APP_ENV}" == "dev" || "${APP_ENV}" == "test"  ]]; then
    mkdir -p ${APP_ROOT_PATH}/{vendor,node_modules,var/log}
    chown symfony: ${APP_ROOT_PATH}/node_modules
    chown symfony: ${APP_ROOT_PATH}/vendor
    chown symfony: ${APP_ROOT_PATH}/var/log
fi

DATABASE_URL_PARTS=$(php -r "echo json_encode(parse_url('${DATABASE_URL}'));")
DATABASE_HOST=$(echo "$DATABASE_URL_PARTS" | jq -r ".host")
DATABASE_PORT=$(echo "$DATABASE_URL_PARTS" | jq -r ".port")

while ! nc -w 1 -vz "${DATABASE_HOST}" "${DATABASE_PORT}"; do
    echo "Waiting for database..."
    sleep 1;
done

# Executing migrations
${GOSU} php bin/console doctrine:migrations:migrate --no-interaction

# System Under Test
if [[ "${1:-}" == "sut" ]]; then
    ${GOSU} composer install --prefer-dist

    CLOVER_FILEPATH="${APP_ROOT_PATH}/tests/logs/clover.xml"
    JUNIT_FILEPATH="${APP_ROOT_PATH}/tests/logs/report.xml"
    ${GOSU} mkdir -p "$(dirname ${CLOVER_FILEPATH})"

    PATH=$PATH:$(${GOSU} composer global config bin-dir --absolute)
    export PATH
    GIT_DISCOVERY_ACROSS_FILESYSTEM=1
    export GIT_DISCOVERY_ACROSS_FILESYSTEM

    # Prepare test environment
    ${GOSU} php bin/console doctrine:fixtures:load -e "${APP_ENV}"

    # Install phpunit environment by executing a dummy command
    ${GOSU} php bin/phpunit --version

    # Run tests
    ${GOSU} php bin/phpunit --coverage-clover "${CLOVER_FILEPATH}" --log-junit "${JUNIT_FILEPATH}" && TEST_RESULT=0 || TEST_RESULT=$?

    # Execute sonar-scanner only on CI
    if [[ "${CI:-}" == "true" && -n "${SONARQUBE_TOKEN}" ]]; then
      # Install dependencies for sonar-scanner: Java Runtime Engine (JRE), unzip and sha256sum
      apt-get install -y --no-install-recommends curl unzip

      # Install sonar-scanner
      SONAR_CLI_VERSION="4.6.2.2472-linux"
      SONAR_CLI_SHA256_SUM="9411331814c1d002bd65d37758b872918b7602e7cf3ca5b83a3e19a729b2be05"
      SONAR_CLI_DIRNAME="sonar-scanner-cli-${SONAR_CLI_VERSION}"
      SONAR_CLI_FILENAME="${SONAR_CLI_DIRNAME}.zip"
      SONAR_CLI_FILEPATH="/tmp/${SONAR_CLI_FILENAME}"
      curl -sSL -o "/tmp/${SONAR_CLI_FILENAME}" "https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/${SONAR_CLI_FILENAME}"
      echo "${SONAR_CLI_SHA256_SUM} ${SONAR_CLI_FILEPATH}" | sha256sum -c || exit 1
      unzip "${SONAR_CLI_FILEPATH}"
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
          -Dsonar.login="${SONARQUBE_TOKEN}" \
          -Dsonar.qualitygate.wait=true \
          ${SONAR_PARAMETERS} || true # Do not fail on Quality Gate is not PASSED
    fi

    exit $TEST_RESULT
fi

echo "+ Launching services..."
exec supervisord -c /etc/supervisor/supervisord.conf
