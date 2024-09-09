import Plugin from './components/settingsTabPlugin';
import {SettingsTabPlugins} from './import';

/**
 * The plugin component to modify the context layer in the annotation tool.
 *
 * @type {Object}
 */
if (SettingsTabPlugins) {
    SettingsTabPlugins.contextLayer = Plugin;
}