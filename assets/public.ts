import "./styles/public.scss";

import {
    InjectCSS,
    MissingIconIndicator,
    ReplaceElements,
    register,
} from "@fortawesome/fontawesome-svg-core/plugins";

const api = register([InjectCSS, ReplaceElements, MissingIconIndicator]);

import { faArrowCircleLeft } from "@fortawesome/free-solid-svg-icons/faArrowCircleLeft";
import { faHeart } from "@fortawesome/free-solid-svg-icons/faHeart";
import { faFacebook } from "@fortawesome/free-brands-svg-icons/faFacebook";
import { faInstagram } from "@fortawesome/free-brands-svg-icons/faInstagram";

api.library.add(faArrowCircleLeft, faFacebook, faHeart, faInstagram);

(() => {
    api.dom.i2svg();
    api.dom.watch();
})();
