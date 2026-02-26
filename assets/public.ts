import "./cookie-consent";
import "./styles/public.scss";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import { faArrowCircleLeft, faHeart } from "@fortawesome/pro-solid-svg-icons";
import { faFacebook, faInstagram } from "@fortawesome/free-brands-svg-icons";

library.add(faArrowCircleLeft, faFacebook, faHeart, faInstagram);
dom.watch();
