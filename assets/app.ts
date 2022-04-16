/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

// Bootstrap Javascript
import 'bootstrap';

import { library, dom } from '@fortawesome/fontawesome-svg-core';
import { faAddressCard, faArrowRightFromBracket, faBullseye, faCalendarDays } from '@fortawesome/free-solid-svg-icons';

library.add(faAddressCard, faArrowRightFromBracket, faBullseye, faCalendarDays);
dom.watch();