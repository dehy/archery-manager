import { getWebInstrumentations, initializeFaro } from '@grafana/faro-web-sdk';
import { TracingInstrumentation } from '@grafana/faro-web-tracing';

import config from './config';

initializeFaro({
  url: config.get('observabilityCollectorUrl'),
  app: {
    name: 'Archery Manager',
    version: config.get('appRevision'),
    environment: config.get('appEnvironment')
  },

  instrumentations: [
    // Mandatory, omits default instrumentations otherwise.
    ...getWebInstrumentations(),

    // Tracing package to get end-to-end visibility for HTTP requests.
    new TracingInstrumentation(),
  ],
});