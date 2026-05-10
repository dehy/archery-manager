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
    // Restrict trace header propagation to same-origin requests only to avoid
    // CORS preflight failures on cross-origin endpoints (e.g. the Faro collector).
    new TracingInstrumentation({
      instrumentationOptions: {
        propagateTraceHeaderCorsUrls: [new RegExp(`^${window.location.origin}`)],
      },
    }),
  ],
});