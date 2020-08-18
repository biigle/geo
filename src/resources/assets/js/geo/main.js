import 'ol/ol.css';
import ImageLocationPanel from './imageLocationPanel';
import Navbar from './navbar';
import ProjectMap from './projectMap';
import Sidebar from './sidebar';
import VolumeMap from './volumeMap';

biigle.$mount('volume-geo-map', VolumeMap);
biigle.$mount('project-geo-map', ProjectMap);
biigle.$mount('geo-image-location-panel', ImageLocationPanel);
biigle.$mount('geo-navbar', Navbar);
biigle.$mount('geo-sidebar', Sidebar);
