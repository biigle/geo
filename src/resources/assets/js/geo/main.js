import '@biigle/ol/ol.css';
import ImageLocationPanel from './imageLocationPanel.vue';
import Navbar from './navbar.vue';
import ProjectMap from './projectMap.vue';
import VolumeMap from './volumeMap.vue';

biigle.$mount('volume-geo-map', VolumeMap);
biigle.$mount('project-geo-map', ProjectMap);
biigle.$mount('geo-image-location-panel', ImageLocationPanel);
biigle.$mount('geo-navbar', Navbar);
