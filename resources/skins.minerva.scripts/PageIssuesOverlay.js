( function ( M, mwMsg ) {
	var
		Overlay = M.require( 'mobile.startup/Overlay' ),
		util = M.require( 'mobile.startup/util' ),
		KEYWORD_ALL_SECTIONS = 'all',
		NS_MAIN = 0,
		NS_TALK = 1,
		NS_CATEGORY = 14;

	/**
	 * Overlay for displaying page issues
	 * @class PageIssuesOverlay
	 * @extends Overlay
	 *
	 * @param {IssueSummary[]} issues list of page issue summaries for display.
	 * @param {PageIssuesLogger} logger E.g., { log: console.log }.
	 * @param {string} section
	 * @param {number} namespaceID
	 */
	function PageIssuesOverlay( issues, logger, section, namespaceID ) {
		var
			options,
			// Note only the main namespace is expected to make use of section issues, so the heading will
			// always be minerva-meta-data-issues-section-header regardless of namespace.
			headingText = section === '0' || section === KEYWORD_ALL_SECTIONS ?
				getNamespaceHeadingText( namespaceID ) :
				mwMsg( 'minerva-meta-data-issues-section-header' );

		this.issues = issues;
		this.logger = logger;

		options = {};
		options.issues = issues;
		options.heading = '<strong>' + headingText + '</strong>';
		Overlay.call( this, options );

		this.on( Overlay.EVENT_EXIT, this.onExit.bind( this ) );
	}

	OO.mfExtend( PageIssuesOverlay, Overlay, {
		/**
		 * @memberof PageIssuesOverlay
		 * @instance
		 */
		className: 'overlay overlay-issues',

		/**
		 * @memberof PageIssuesOverlay
		 * @instance
		 */
		events: util.extend( {}, Overlay.prototype.events, {
			'click a:not(.external):not([href*=edit])': 'onInternalClick',
			'click a[href*="edit"]': 'onEditClick'
		} ),

		/**
		 * @memberof PageIssuesOverlay
		 * @instance
		 */
		templatePartials: util.extend( {}, Overlay.prototype.templatePartials, {
			content: mw.template.get( 'skins.minerva.scripts', 'PageIssuesOverlayContent.hogan' )
		} ),

		/**
		 * Note: an "on enter" state is tracked by the issueClicked log event.
		 * @return {void}
		 */
		onExit: function () {
			this.logger.log( {
				action: 'modalClose',
				issuesSeverity: this.issues.map( issueSummaryToSeverity )
			} );
		},

		/**
		 * Event that is triggered when an internal link inside the overlay is clicked. This event will
		 * not be triggered if the link contains the edit keyword, in which case onEditClick will be
		 * fired. This is primarily used for instrumenting page issues (see
		 * https://meta.wikimedia.org/wiki/Schema:PageIssues).
		 * @param {JQuery.Event} ev
		 * @memberof PageIssuesOverlay
		 * @instance
		 */
		onInternalClick: function ( ev ) {
			var severity = parseSeverity( this.$( ev.target ) );
			this.logger.log( {
				action: 'modalInternalClicked',
				issuesSeverity: [ severity ]
			} );
		},

		/**
		 * Event that is triggered when an edit link inside the overlay is clicked. This is primarily
		 * used for instrumenting page issues (see https://meta.wikimedia.org/wiki/Schema:PageIssues).
		 * @param {JQuery.Event} ev
		 * @memberof PageIssuesOverlay
		 * @instance
		 */
		onEditClick: function ( ev ) {
			var severity = parseSeverity( this.$( ev.target ) );
			this.logger.log( {
				action: 'modalEditClicked',
				issuesSeverity: [ severity ]
			} );
		}
	} );

	/**
	 * Obtain severity associated with a given $target node by looking at associated parent node
	 * (defined by templatePartials, PageIssuesOverlayContent.hogan).
	 *
	 * @param {JQuery.Object} $target
	 * @return {string[]} severity as defined in associated PageIssue
	 */
	function parseSeverity( $target ) {
		return $target.parents( '.issue-notice' ).data( 'severity' );
	}

	/**
	 * @param {IssueSummary} issue
	 * @return {string} A PageIssue.severity.
	 */
	function issueSummaryToSeverity( issue ) {
		return issue.severity;
	}

	/**
	 * Obtain a suitable heading for the issues overlay based on the namespace
	 * @param {number} namespaceID is the namespace to generate heading for
	 * @return {string} heading for overlay
	 */
	function getNamespaceHeadingText( namespaceID ) {
		switch ( namespaceID ) {
			case NS_CATEGORY:
				return mw.msg( 'mobile-frontend-meta-data-issues-categories' );
			case NS_TALK:
				return mw.msg( 'mobile-frontend-meta-data-issues-talk' );
			case NS_MAIN:
				return mw.msg( 'mobile-frontend-meta-data-issues' );
			default:
				return '';
		}
	}

	M.define( 'skins.minerva.scripts/PageIssuesOverlay', PageIssuesOverlay );
}( mw.mobileFrontend, mw.msg ) );
