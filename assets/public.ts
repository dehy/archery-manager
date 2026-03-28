import "./cookie-consent";
import "./styles/public.scss";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import { faCircleArrowLeft, faHeart } from "@fortawesome/free-solid-svg-icons";
import { faFacebook, faInstagram } from "@fortawesome/free-brands-svg-icons";

library.add(faCircleArrowLeft, faFacebook, faHeart, faInstagram);
dom.watch();
