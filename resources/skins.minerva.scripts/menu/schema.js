mw.loader.using( [
	'ext.eventLogging'
] ).then( function () {
	var M = mw.mobileFrontend,
		user = mw.user,
		editCount = mw.config.get( 'wgUserEditCount' ),
		// Need to make amc default to false because it will not exist in mw.config
		// if using desktop Minerva or if MobileFrontend extension is not installed.
		amc = mw.config.get( 'wgMFAmc', false ),
		// Schema class provided by ext.eventLogging module
		Schema = mw.eventLog.Schema, // resource-modules-disable-line
		context = M.require( 'mobile.startup' ).context,
		DEFAULT_SAMPLING_RATE = mw.config.get( 'wgMinervaSchemaMainMenuClickTrackingSampleRate' ),
		// T218627: Sampling rate should be 100% if user has amc enabled
		AMC_SAMPLING_RATE = 1,
		/**
		 * MobileWebMainMenuClickTracking schema
		 * https://meta.wikimedia.org/wiki/Schema:MobileWebMainMenuClickTracking
		 *
		 * @class MobileWebMainMenuClickTracking
		 * @deprecated and to be removed the moment that T220016 is live.
		 * @singleton
		 */
		schemaMobileWebMainMenuClickTracking = new Schema(
			'MobileWebMainMenuClickTracking',
			amc ? AMC_SAMPLING_RATE : DEFAULT_SAMPLING_RATE,
			/**
			 * @property {Object} defaults Default options hash.
			 * @property {string} defaults.mode whether user is in stable, beta, or desktop
			 * @property {boolean} defaults.amc whether or not the user has advanced
			 * contributions mode enabled (true) or disabled (false)
			 * @property {string} [defaults.username] Username if the user is logged in,
			 *  otherwise - undefined.
			 *  Assigning undefined will make event logger omit this property when sending
			 *  the data to a server. According to the schema username is optional.
			 * @property {number} [defaults.userEditCount] The number of edits the user has made
			 *  if the user is logged in, otherwise - undefined. Assigning undefined will make event
			 *  logger omit this property when sending the data to a server. According to the schema
			 *  userEditCount is optional.
			 */
			{
				mode: context.getMode() || 'desktop',
				amc: amc,
				username: user.getName() || undefined,
				// FIXME: Use edit bucket here (T210106)
				userEditCount: typeof editCount === 'number' ? editCount : undefined
			}
		);

	mw.trackSubscribe( 'minerva.schemaMobileWebMainMenuClickTracking', function ( topic, data ) {
		schemaMobileWebMainMenuClickTracking.log( data );
	} );
} );