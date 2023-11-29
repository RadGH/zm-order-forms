(function() {

	// Add a filter that replaces the icon of the "zm-order-forms/zm-order-forms" block
	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'zm-order-forms/modify_icon',
		function(settings, name) {

			// Change the icon of all "RS Downloads" blocks
			if ( name.indexOf('zm-order-forms/') !== 0 ) return settings;

			// Path string containing  the entire icon in one path. Also see assets/icon.svg
			const path_strings = [
				"M2.46 4.45v2.08h2.05V4.45Zm0 4.13v2.07h2.05V8.58zm0 4.1v2.07h2.05v-2.08zm0 4.14v2.05h2.05V16.8ZM5.9 4.45h3.4M5.89 5.83h2.07m.67 0h2.08M5.89 8.58H9.3M5.89 9.95h2.07m.67 0h2.08m-4.82 2.72H9.3m-3.42 1.4h2.07m.67 0h2.08m-4.82 2.75H9.3m-3.42 1.35h2.07M10 4.45h2.07M10 8.58h2.07M10 12.68h2.07m.68 2.77 8.22-8.25 2.74 2.75-8.24 8.22Zm-.7.67-1.38 4.13 4.13-1.38Z",
				"M14.1 12V2.4H.4v19.2h13.74v-1.35m-2.75-1.38.68.68Z"
			];

			let paths = [];

			// Create a path element for each path string
			for ( let i = 0; i < path_strings.length; i++ ) {
				paths.push( wp.element.createElement('path', { d: path_strings[i], fill: 'none' }) );
			}

			// Properties for the <svg> element include:
			let props = {
				'stroke':          'currentColor',
				'stroke-linejoin': 'bevel',
				'stroke-width':    '.78'
			};

			// Create an SVG icon for the block
			settings.icon = wp.element.createElement('svg', props, paths);

			return settings;

		}
	);

})();