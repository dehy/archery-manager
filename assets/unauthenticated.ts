import "./styles/unauthenticated.scss";

// Bootstrap Javascript - import only what we need
import { Tooltip } from "bootstrap";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import {
  faArrowLeft,
  faArrowRightToBracket,
  faCircleInfo,
  faExclamationTriangle,
  faFileContract,
  faHeart,
  faUserPlus,
} from "@fortawesome/pro-solid-svg-icons";

library.add(
  faArrowLeft,
  faArrowRightToBracket,
  faCircleInfo,
  faExclamationTriangle,
  faFileContract,
  faHeart,
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
