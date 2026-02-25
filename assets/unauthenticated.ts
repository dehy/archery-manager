import "./cookie-consent";
import "./styles/unauthenticated.scss";

// start the Stimulus application
import "./bootstrap";

// Bootstrap Javascript - import only what we need
import { Tooltip } from "bootstrap";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import {
  faArrowLeft,
  faArrowRightToBracket,
  faCheckCircle,
  faCircleInfo,
  faExclamationTriangle,
  faFileContract,
  faHeart,
  faShieldHalved,
  faSpinner,
  faUserPlus,
} from "@fortawesome/pro-solid-svg-icons";

library.add(
  faArrowLeft,
  faArrowRightToBracket,
  faCheckCircle,
  faCircleInfo,
  faExclamationTriangle,
  faFileContract,
  faHeart,
  faShieldHalved,
  faSpinner,
  faUserPlus
);

// Initialize Font Awesome and Bootstrap after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    dom.watch();
    
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = Array.prototype.slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach(
        (tooltipTriggerEl) => new Tooltip(tooltipTriggerEl)
    );
});
