<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Minerva\Menu\Main\MainMenuDirector;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Minerva\SkinOptions;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;

/**
 * Minerva: Born from the godhead of Jupiter with weapons!
 * A skin that works on both desktop and mobile
 * @ingroup Skins
 */
class SkinMinerva extends SkinTemplate {
	/** @const LEAD_SECTION_NUMBER integer which corresponds to the lead section
	 * in editing mode
	 */
	public const LEAD_SECTION_NUMBER = 0;

	/** @var string $skinname Name of this skin */
	public $skinname = 'minerva';
	/** @var string $template Name of this used template */
	public $template = 'MinervaTemplate';

	/** @var SkinOptions */
	private $skinOptions;

	/**
	 * This variable is lazy loaded, please use getPermissions() getter
	 * @see SkinMinerva::getPermissions()
	 * @var IMinervaPagePermissions
	 */
	private $permissions;

	/**
	 * Initialize Minerva Skin
	 */
	public function __construct() {
		parent::__construct( 'minerva' );
		$this->skinOptions = MediaWikiServices::getInstance()->getService( 'Minerva.SkinOptions' );
	}

	/**
	 * Lazy load the permissions object. We don't want to initialize it as it requires many
	 * dependencies, sometimes some of those dependencies cannot be fulfilled (like missing Title
	 * object)
	 * @return IMinervaPagePermissions
	 */
	private function getPermissions(): IMinervaPagePermissions {
		if ( $this->permissions === null ) {
			$this->permissions = MediaWikiServices::getInstance()
				->getService( 'Minerva.Permissions' )
				->setContext( $this->getContext() );
		}
		return $this->permissions;
	}

	/**
	 * Initalized main menu. Please use getter.
	 * @var MainMenuDirector
	 */
	private $mainMenu;

	/**
	 * Build the Main Menu Director by passing the skin options
	 *
	 * @return MainMenuDirector
	 */
	protected function getMainMenu(): MainMenuDirector {
		if ( !$this->mainMenu ) {
			$this->mainMenu = MediaWikiServices::getInstance()->getService( 'Minerva.Menu.MainDirector' );
		}
		return $this->mainMenu;
	}

	/**
	 * Returns the site name for the footer, either as a text or <img> tag
	 * @return string
	 */
	public function getSitename() {
		$config = $this->getConfig();
		$logos = ResourceLoaderSkinModule::getAvailableLogos( $config );
		$wordmark = $logos['wordmark'] ?? false;

		$footerSitename = $this->msg( 'mobile-frontend-footer-sitename' )->text();

		// If there's a custom site logo, use that instead of text.
		if ( $wordmark ) {
			$wordmarkAttrs = [];

			foreach ( [ 'src', 'width', 'height' ] as $key ) {
				if ( isset( $wordmark[ $key ] ) ) {
					$wordmarkAttrs[ $key ] = $wordmark[ $key ];
				}
			}
			$attributes = $wordmarkAttrs + [
				'alt' => $footerSitename,
			];
			if ( isset( $wordmark[ '1x' ] ) ) {
				$attributes['srcset'] = $wordmark['1x'] . ' 1x';
			}
			$sitename = Html::element( 'img', $attributes );
		} else {
			$sitename = $footerSitename;
		}

		return $sitename;
	}

	/**
	 * initialize various variables and generate the template
	 * @return QuickTemplate
	 * @suppress PhanTypeMismatchArgument
	 */
	protected function prepareQuickTemplate() {
		$out = $this->getOutput();

		// add head items
		$out->addMeta( 'viewport', 'initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, ' .
				'maximum-scale=5.0, width=device-width'
		);

		$local_path = dirname(__FILE__);

		// Kenan
		$styleFeck = file_get_contents( $local_path . '/pw-minerva.css' );

		$pwCommonCSS = file_get_contents( $local_path . '/../../../pw-common.css' );

		$unifiedCSS = $styleFeck . $pwCommonCSS;

		$out->addInlineStyle( $unifiedCSS );

		$ensureTeaserSectionScript = <<<EOT
var f="function"==typeof Object.defineProperties?Object.defineProperty:function(a,b,d){a!=Array.prototype&&a!=Object.prototype&&(a[b]=d.value)},l="undefined"!=typeof window&&window===this?this:"undefined"!=typeof global&&null!=global?global:this;function n(){n=function(){};l.Symbol||(l.Symbol=p)}var p=function(){var a=0;return function(b){return"jscomp_symbol_"+(b||"")+a++}}();function r(){n();var a=l.Symbol.iterator;a||(a=l.Symbol.iterator=l.Symbol("iterator"));"function"!=typeof Array.prototype[a]&&f(Array.prototype,a,{configurable:!0,writable:!0,value:function(){return t(this)}});r=function(){}}function t(a){var b=0;return w(function(){return b<a.length?{done:!1,value:a[b++]}:{done:!0}})}function w(a){r();a={next:a};a[l.Symbol.iterator]=function(){return this};return a}function x(a){r();n();r();var b=a[Symbol.iterator];return b?b.call(a):t(a)}function y(a){if(!(a instanceof Array)){a=x(a);for(var b,d=[];!(b=a.next()).done;)d.push(b.value);a=d}return a}function z(){try{var a=document.querySelector(".mf-section-0");if(a&&a.children.length){var b=!1,d=null,c=x([].concat(y(a.children))),e=c.next().value,g=c.next().value,h=c.next().value,A=c.next().value;e.constructor===HTMLDivElement&&e.classList.contains("flex-panel")&&(b=!0,d=e,e=g,g=h,h=A);var q=x([e,g,h].map(function(a,b,c){return 0!==b&&1!==b||a.constructor!==HTMLTableElement||1!==a.querySelectorAll("a").length||!/summary sheet/i.test(a.innerText)?0!==b&&1!==b||a.constructor!==HTMLTableElement||"InfoTable"!==a.id&&!a.classList.contains("InfoTable")?1!==b&&2!==b||a.constructor!==HTMLParagraphElement||2!==b||c[1].constructor===HTMLParagraphElement||c[0].constructor===HTMLParagraphElement?!1:"firstp":"infotable":"sslink"})),m=q.next().value,k=q.next().value,u=q.next().value;if(!1!==m&&!1!==k&&("firstp"===k||!1!==u)&&(c=null,"sslink"===m&&"infotable"===k&&"firstp"===u&&(c=[0,2,1]),"sslink"!==m||"firstp"!==k)&&("infotable"===m&&"firstp"===k&&(c=[1,0]),null!==c)){c.forEach(function(b){if(0===b)return a.removeChild(e);if(1===b)return a.removeChild(g);if(2===b)return a.removeChild(h)});b&&a.removeChild(d);var v=c.map(function(a){if(0===a)return e;if(1===a)return g;if(2===a)return h});b&&v.unshift(d);v.reverse().forEach(function(b){a.insertBefore(b,a.firstChild)})}}}catch(B){}}"complete"===document.readyState?z():window.addEventListener("load",z);
EOT;

		$out->addInlineScript( $ensureTeaserSectionScript );

		// Generate skin template
		$tpl = parent::prepareQuickTemplate();

		// Set whether or not the page content should be wrapped in div.content (for
		// example, on a special page)
		$tpl->set( 'unstyledContent', $out->getProperty( 'unstyledContent' ) );

		// Set the links for page secondary actions
		$tpl->set( 'secondary_actions', $this->getSecondaryActions( $tpl ) );

		// Construct various Minerva-specific interface elements
		$this->prepareMenus( $tpl );
		$this->preparePageContent( $tpl );
		$this->prepareHeaderAndFooter( $tpl );
		$this->prepareBanners( $tpl );
		$this->prepareUserNotificationsButton( $tpl, $tpl->get( 'newtalk' ) );
		$this->prepareLanguages( $tpl );

		return $tpl;
	}

	/**
	 * Prepare all Minerva menus
	 * @param BaseTemplate $tpl
	 * @throws MWException
	 */
	private function prepareMenus( BaseTemplate $tpl ) {
		$services = MediaWikiServices::getInstance();
		/** @var \MediaWiki\Minerva\Menu\PageActions\PageActionsDirector $pageActionsDirector */
		$pageActionsDirector = $services->getService( 'Minerva.Menu.PageActionsDirector' );
		/** @var \MediaWiki\Minerva\Menu\User\UserMenuDirector $userMenuDirector */
		$userMenuDirector = $services->getService( 'Minerva.Menu.UserMenuDirector' );

		$sidebar = parent::buildSidebar();
		$personalUrls = $tpl->get( 'personal_urls' );
		$personalTools = $this->getSkin()->getPersonalToolsForMakeListItem( $personalUrls );

		$tpl->set( 'mainMenu', $this->getMainMenu()->getMenuData() );
		$tpl->set( 'pageActionsMenu', $pageActionsDirector->buildMenu( $sidebar['TOOLBOX'] ) );
		$tpl->set( 'userMenuHTML', $userMenuDirector->renderMenuData( $personalTools ) );
	}

	/**
	 * Prepares the header and the content of a page
	 * Stores in QuickTemplate prebodytext, postbodytext keys
	 * @param QuickTemplate $tpl
	 */
	protected function preparePageContent( QuickTemplate $tpl ) {
		$services = MediaWikiServices::getInstance();
		$title = $this->getTitle();

		// If it's a talk page, add a link to the main namespace page
		// In AMC we do not need to do this as there is an easy way back to the article page
		// via the talk/article tabs.
		if ( $title->isTalkPage() && !$this->skinOptions->get( SkinOptions::TALK_AT_TOP ) ) {
			// if it's a talk page for which we have a special message, use it
			switch ( $title->getNamespace() ) {
				case NS_USER_TALK:
					$msg = 'mobile-frontend-talk-back-to-userpage';
					break;
				case NS_PROJECT_TALK:
					$msg = 'mobile-frontend-talk-back-to-projectpage';
					break;
				case NS_FILE_TALK:
					$msg = 'mobile-frontend-talk-back-to-filepage';
					break;
				default: // generic (all other NS)
					$msg = 'mobile-frontend-talk-back-to-page';
			}
			$subjectPage = $services->getNamespaceInfo()->getSubjectPage( $title );

			$tpl->set( 'subject-page', MediaWikiServices::getInstance()->getLinkRenderer()->makeLink(
				$subjectPage,
				$this->msg( $msg, $title->getText() )->text(),
				[ 'class' => 'return-link' ]
			) );
		}
	}

	/**
	 * Overrides Skin::doEditSectionLink
	 * @param Title $nt The title being linked to (may not be the same as
	 *   the current page, if the section is included from a template)
	 * @param string $section
	 * @param string|null $tooltip
	 * @param Language $lang
	 * @return string
	 */
	public function doEditSectionLink( Title $nt, $section, $tooltip, Language $lang ) {
		if ( $this->getPermissions()->isAllowed( IMinervaPagePermissions::EDIT_OR_CREATE ) &&
			 !$nt->isMainPage() ) {
			$message = $this->msg( 'mobile-frontend-editor-edit' )->inLanguage( $lang )->text();
			$html = Html::openElement( 'span', [ 'class' => 'mw-editsection' ] );
			$html .= Html::element( 'a', [
				'href' => $nt->getLocalURL( [ 'action' => 'edit', 'section' => $section ] ),
				'title' => $this->msg( 'editsectionhint', $tooltip )->inLanguage( $lang )->text(),
				'data-section' => $section,
				// Note visibility of the edit section link button is controlled by .edit-page in ui.less so
				// we default to enabled even though this may not be true.
				'class' => MinervaUI::iconClass(
					'edit-base20', 'element', 'edit-page mw-ui-icon-flush-right', 'wikimedia'
				),
			], $message );
			$html .= Html::closeElement( 'span' );
			return $html;
		}
		return '';
	}

	/**
	 * Takes a title and returns classes to apply to the body tag
	 * @param Title $title
	 * @return string
	 */
	public function getPageClasses( $title ) {
		$className = parent::getPageClasses( $title );
		$className .= ' ' . ( $this->skinOptions->get( SkinOptions::BETA_MODE )
				? 'beta' : 'stable' );

		if ( $title->isMainPage() ) {
			$className .= ' page-Main_Page ';
		}

		if ( $this->getUser()->isLoggedIn() ) {
			$className .= ' is-authenticated';
		}
		// The new treatment should only apply to the main namespace
		if (
			$title->getNamespace() === NS_MAIN &&
			$this->skinOptions->get( SkinOptions::PAGE_ISSUES )
		) {
			$className .= ' issues-group-B';
		}
		return $className;
	}

	/**
	 * Whether the output page contains category links and the category feature is enabled.
	 * @return bool
	 */
	private function hasCategoryLinks() {
		if ( !$this->skinOptions->get( SkinOptions::CATEGORIES ) ) {
			return false;
		}
		$categoryLinks = $this->getOutput()->getCategoryLinks();

		if ( !count( $categoryLinks ) ) {
			return false;
		}
		return !empty( $categoryLinks['normal'] ) || !empty( $categoryLinks['hidden'] );
	}

	/**
	 * @return SkinUserPageHelper
	 */
	public function getUserPageHelper() {
		return MediaWikiServices::getInstance()->getService( 'Minerva.SkinUserPageHelper' );
	}

	/**
	 * Prepares the user button.
	 * @param QuickTemplate $tpl
	 * @param string $newTalks New talk page messages for the current user
	 */
	protected function prepareUserNotificationsButton( QuickTemplate $tpl, $newTalks ) {
		$user = $this->getUser();
		$currentTitle = $this->getTitle();
		$notificationsMsg = $this->msg( 'mobile-frontend-user-button-tooltip' )->text();
		$notificationIconClass = MinervaUI::iconClass( 'bellOutline-base20',
			'element', '', 'wikimedia' );

		if ( $user->isLoggedIn() ) {
			$badge = Html::element( 'a', [
				'class' => $notificationIconClass,
				'href' => SpecialPage::getTitleFor( 'Mytalk' )->getLocalURL(
					[ 'returnto' => $currentTitle->getPrefixedText() ]
				),
			], $notificationsMsg );
			Hooks::run( 'SkinMinervaReplaceNotificationsBadge',
				[ $user, $currentTitle, &$badge ] );
			$tpl->set( 'userNotificationsHTML', $badge );
		}
	}

	/**
	 * Rewrites the language list so that it cannot be contaminated by other extensions with things
	 * other than languages
	 * See bug 57094.
	 *
	 * @todo Remove when Special:Languages link goes stable
	 * @param QuickTemplate $tpl
	 */
	protected function prepareLanguages( $tpl ) {
		$lang = $this->getTitle()->getPageViewLanguage();
		$tpl->set( 'pageLang', $lang->getHtmlCode() );
		$tpl->set( 'pageDir', $lang->getDir() );
		// If the array is empty, then instead give the skin boolean false
		$language_urls = $this->getLanguages() ?: false;
		$tpl->set( 'language_urls', $language_urls );
	}

	/**
	 * Get a history link which describes author and relative time of last edit
	 * @param Title $title The Title object of the page being viewed
	 * @param string $timestamp
	 * @return array
	 */
	protected function getRelativeHistoryLink( Title $title, $timestamp ) {
		$user = $this->getUser();
		$userDate = $this->getLanguage()->userDate( $timestamp, $user );
		$text = $this->msg(
			'minerva-last-modified-date', $userDate,
			$this->getLanguage()->userTime( $timestamp, $user )
		)->parse();
		return [
			// Use $edit['timestamp'] (Unix format) instead of $timestamp (MW format)
			'data-timestamp' => wfTimestamp( TS_UNIX, $timestamp ),
			'href' => $this->getHistoryUrl( $title ),
			'text' => $text,
		] + $this->getRevisionEditorData( $title );
	}

	/**
	 * Get a history link which makes no reference to user or last edited time
	 * @param Title $title The Title object of the page being viewed
	 * @return array
	 */
	protected function getGenericHistoryLink( Title $title ) {
		$text = $this->msg( 'mobile-frontend-history' )->plain();
		return [
			'href' => $this->getHistoryUrl( $title ),
			'text' => $text,
		];
	}

	/**
	 * Get the URL for the history page for the given title using Special:History
	 * when available.
	 * @param Title $title The Title object of the page being viewed
	 * @return string
	 */
	protected function getHistoryUrl( Title $title ) {
		return ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			SpecialMobileHistory::shouldUseSpecialHistory( $title, $this->getUser() ) ?
			SpecialPage::getTitleFor( 'History', $title )->getLocalURL() :
			$title->getLocalURL( [ 'action' => 'history' ] );
	}

	/**
	 * Prepare the content for the 'last edited' message, e.g. 'Last edited on 30 August
	 * 2013, at 23:31'. This message is different for the main page since main page
	 * content is typically transcluded rather than edited directly.
	 *
	 * The relative time is only rendered on the latest revision.
	 * For older revisions the last modified information will not render with a relative time
	 * nor will it show the name of the editor.
	 * @param Title $title The Title object of the page being viewed
	 * @return array
	 */
	protected function getHistoryLink( Title $title ) {
		$isLatestRevision = $this->getRevisionId() === $title->getLatestRevID();
		// Get rev_timestamp of current revision (preloaded by MediaWiki core)
		$timestamp = $this->getOutput()->getRevisionTimestamp();
		# No cached timestamp, load it from the database
		if ( $timestamp === null ) {
			$timestamp = MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getTimestampFromId( $this->getOutput()->getRevisionId() );
		}

		return !$isLatestRevision || $title->isMainPage() ?
			$this->getGenericHistoryLink( $title ) :
			$this->getRelativeHistoryLink( $title, $timestamp );
	}

	/**
	 * Returns data attributes representing the editor for the current revision.
	 * @param LinkTarget $title The Title object of the page being viewed
	 * @return array representing user with name and gender fields. Empty if the editor no longer
	 *   exists in the database or is hidden from public view.
	 */
	private function getRevisionEditorData( LinkTarget $title ) {
		$rev = MediaWikiServices::getInstance()->getRevisionLookup()
			->getRevisionByTitle( $title );
		$result = [];
		if ( $rev ) {
			$revUser = $rev->getUser();
			// Note the user will only be returned if that information is public
			if ( $revUser ) {
				$revUser = User::newFromIdentity( $revUser );
				$editorName = $revUser->getName();
				$editorGender = $revUser->getOption( 'gender' );
				$result += [
					'data-user-name' => $editorName,
					'data-user-gender' => $editorGender,
				];
			}
		}
		return $result;
	}

	/**
	 * Returns the HTML representing the tagline
	 * @return string HTML for tagline
	 */
	protected function getTaglineHtml() {
		$tagline = '';

		if ( $this->getUserPageHelper()->isUserPage() ) {
			$pageUser = $this->getUserPageHelper()->getPageUser();
			$fromDate = $pageUser->getRegistration();
			if ( is_string( $fromDate ) ) {
				$fromDateTs = wfTimestamp( TS_UNIX, $fromDate );

				// This is shown when js is disabled. js enhancement made due to caching
				$tagline = $this->msg( 'mobile-frontend-user-page-member-since',
						$this->getLanguage()->userDate( new MWTimestamp( $fromDateTs ), $this->getUser() ),
						$pageUser )->text();

				// Define html attributes for usage with js enhancement (unix timestamp, gender)
				$attrs = [ 'id' => 'tagline-userpage',
					'data-userpage-registration-date' => $fromDateTs,
					'data-userpage-gender' => $pageUser->getOption( 'gender' ) ];
			}
		} else {
			$title = $this->getTitle();
			if ( $title ) {
				$out = $this->getOutput();
				$tagline = $out->getProperty( 'wgMFDescription' );
			}
		}

		$attrs[ 'class' ] = 'tagline';
		return Html::element( 'div', $attrs, $tagline );
	}

	/**
	 * Returns the HTML representing the heading.
	 * @return string HTML for header
	 */
	protected function getHeadingHtml() {
		if ( $this->getUserPageHelper()->isUserPage() ) {
			// The heading is just the username without namespace
			$heading = $this->getUserPageHelper()->getPageUser()->getName();
		} else {
			$heading = $this->getOutput()->getPageTitle();
		}
		return Html::rawElement( 'h1', [ 'id' => 'section_0' ], $heading );
	}

	/**
	 * @return bool Whether or not current title is a Talk page with the default
	 * action ('view')
	 */
	private function isTalkPageWithViewAction() {
		$title = $this->getTitle();

		return $title->isTalkPage() && Action::getActionName( $this->getContext() ) === "view";
	}

	/**
	 * @internal Should not be used outside Minerva.
	 * @todo Find better place for this.
	 *
	 * @return bool Whether or not the simplified talk page is enabled and action is 'view'
	 */
	public function isSimplifiedTalkPageEnabled(): bool {
		$title = $this->getTitle();

		return $this->isTalkPageWithViewAction() &&
			$this->skinOptions->get( SkinOptions::SIMPLIFIED_TALK ) &&
			// Only if viewing the latest revision, as we can't get the section numbers otherwise
			// (and even if we could, they would be useless, because edits often add and remove sections).
			$this->getRevisionId() === $title->getLatestRevID() &&
			$title->getContentModel() === CONTENT_MODEL_WIKITEXT;
	}

	/**
	 * Returns the postheadinghtml for the talk page with view action
	 *
	 * @return string HTML for postheadinghtml
	 */
	private function getTalkPagePostHeadingHtml() {
		$title = $this->getTitle();
		$html = '';

		// T237589: We don't want to show the add discussion button on Flow pages,
		// only wikitext pages
		if ( $this->getPermissions()->isTalkAllowed() &&
			$title->getContentModel() === CONTENT_MODEL_WIKITEXT
		) {
			$addTopicButton = $this->getTalkButton( $title, wfMessage(
				'minerva-talk-add-topic' )->text(), true );
			$html = Html::element( 'a', $addTopicButton['attributes'], $addTopicButton['label'] );
		}

		if ( $this->isSimplifiedTalkPageEnabled() && $this->canUseWikiPage() ) {
			$wikiPage = $this->getWikiPage();
			$parserOptions = $wikiPage->makeParserOptions( $this->getContext() );
			$parserOutput = $wikiPage->getParserOutput( $parserOptions );
			$sectionCount = $parserOutput ? count( $parserOutput->getSections() ) : 0;

			$message = $sectionCount > 0 ? wfMessage( 'minerva-talk-explained' )
				: wfMessage( 'minerva-talk-explained-empty' );
			$html = $html . Html::element( 'div', [ 'class' =>
				'minerva-talk-content-explained' ], $message->text() );
		}

		return $html;
	}

	/**
	 * Create and prepare header and footer content
	 * @param BaseTemplate $tpl
	 */
	protected function prepareHeaderAndFooter( BaseTemplate $tpl ) {
		$title = $this->getTitle();
		$user = $this->getUser();
		$out = $this->getOutput();
		$tpl->set( 'taglinehtml', $this->getTaglineHtml() );

		if ( $title->isMainPage() ) {
			$pageTitle = '';
			$msg = $this->msg( 'mobile-frontend-logged-in-homepage-notification', $user->getName() );

			if ( $user->isLoggedIn() && !$msg->isDisabled() ) {
				$pageTitle = $msg->text();
			}

			$out->setPageTitle( $pageTitle );
		} elseif ( $this->isTalkPageWithViewAction() ) {
			// We only want the simplified talk page to show for the view action of the
			// talk (e.g. not history action)
			$tpl->set( 'postheadinghtml', $this->getTalkPagePostHeadingHtml() );
		}

		if ( $this->canUseWikiPage() && $this->getWikiPage()->exists() ) {
			$tpl->set( 'historyLink', $this->getHistoryLink( $title ) );
		}
		$tpl->set( 'headinghtml', $this->getHeadingHtml() );

               $siteHeadingLogo .= <<<EOL
<a href="/wiki/Main_Page" title="PsychonautWiki">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 351.09 47.76" style="margin: 0 0 0 0;padding-left: 3px;max-width: 250px;"><path d="M0 2.76L.25.8h17.6c1.54 0 2.97.18 4.3.55 1.32.37 2.47.93 3.44 1.68.97.75 1.74 1.69 2.31 2.81s.85 2.42.85 3.89c0 1.67-.38 3.13-1.13 4.36-.75 1.24-1.75 2.3-2.99 3.18-1.24.89-2.63 1.62-4.17 2.21-1.54.59-3.12 1.08-4.73 1.48H10.6v12.42l6.03.65-.25 1.94H.25L0 34.03l4.27-.65V3.42L0 2.76zm10.61 15.39h3.87c1.04 0 2.01-.18 2.92-.55.9-.37 1.69-.89 2.36-1.56.67-.67 1.19-1.48 1.56-2.44.37-.96.55-2.04.55-3.24 0-2.21-.61-3.99-1.83-5.33-1.22-1.34-2.99-2.01-5.3-2.01l-4.12.35v14.78zM44.04 15.48c-.3-.27-.79-.56-1.46-.88-.67-.32-1.46-.48-2.36-.48-1.01 0-1.87.24-2.59.73-.72.49-1.08 1.17-1.08 2.04 0 .77.28 1.42.85 1.94s1.49 1.1 2.77 1.73l3.82 1.91c1.61.8 2.8 1.72 3.57 2.74s1.16 2.29 1.16 3.8c0 1.17-.23 2.17-.68 2.99-.45.82-1.1 1.53-1.94 2.11-.84.59-1.87 1.09-3.09 1.51-1.22.42-2.61.8-4.15 1.13-.7 0-1.42-.07-2.16-.2-.74-.13-1.45-.31-2.14-.53-.69-.22-1.34-.45-1.96-.7-.62-.25-1.15-.48-1.58-.68l-.96-6.33h3.27l1.56 4.02c.57.47 1.23.85 1.99 1.16.75.3 1.53.45 2.34.45 1.21 0 2.13-.31 2.76-.93.64-.62.96-1.43.96-2.44 0-.87-.25-1.57-.75-2.11-.5-.54-1.39-1.12-2.66-1.76l-3.87-2.01a11.32 11.32 0 0 1-3.19-2.41c-.89-.97-1.33-2.19-1.33-3.67 0-1.78.75-3.23 2.26-4.35 1.51-1.12 3.92-2.07 7.24-2.84 1.71 0 3.19.23 4.45.68s2.22.91 2.89 1.38v5.63h-2.77l-1.17-3.63zM53.74 47.76v-5.78h5.03l4.98-7.24-3.07.96-8.14-21.11-2.87-.5.25-1.94h11.66l.25 1.94-3.07.5L63.9 28.6l.5 3.82 5.68-17.8-2.36-.5.25-1.99H76.71l.25 1.99-3.12.5-7.64 20.26-5.68 12.87h-6.78zM90.79 15.03c-.34-.27-.68-.45-1.03-.55-.35-.1-.81-.15-1.38-.15-1.51 0-2.78.8-3.82 2.39S83 20.46 83 23.17c0 1.31.18 2.52.53 3.64.35 1.12.86 2.09 1.53 2.92.67.82 1.47 1.47 2.41 1.94.94.47 2.01.7 3.22.7.97 0 1.93-.19 2.87-.58.94-.39 1.79-.85 2.56-1.38l1.01 2.06c-1.17.9-2.46 1.7-3.85 2.39-1.39.69-2.99 1.31-4.8 1.88-1.68 0-3.23-.27-4.65-.8-1.42-.54-2.66-1.32-3.7-2.36-1.04-1.04-1.84-2.29-2.41-3.77-.57-1.47-.86-3.17-.86-5.08 0-1.94.33-3.65 1.01-5.13.67-1.47 1.57-2.75 2.71-3.82 1.14-1.07 2.45-1.97 3.95-2.69 1.49-.72 3.06-1.28 4.7-1.68 1.37 0 2.74.24 4.1.73 1.36.49 2.44 1.08 3.24 1.78v5.68h-4.62l-1.16-4.57zM108.49 33.41l2.51.55-.25 1.94H99.74l-.25-1.94 3.22-.55V3.69h-3.27l-.25-1.98 6.54-1.49 2.76-.2v16.09l7.49-4.8c1.54 0 2.81.14 3.79.43.99.29 1.76.76 2.31 1.43s.94 1.54 1.16 2.62c.22 1.07.33 2.4.33 3.98v13.64l3.47.55-.25 1.94h-11.06l-.25-1.94 2.26-.55V19.26c0-1.17-.26-2.04-.78-2.59-.52-.55-1.25-.83-2.19-.83-.74 0-1.53.22-2.36.66-.84.44-1.69.93-2.56 1.47l-1.36.86v14.58zM141.36 11.41c1.64 0 3.15.3 4.52.91s2.56 1.44 3.57 2.51c1.01 1.07 1.79 2.35 2.34 3.85s.83 3.11.83 4.85c0 1.98-.31 3.71-.93 5.2-.62 1.49-1.49 2.77-2.61 3.85-1.12 1.07-2.47 1.95-4.05 2.64-1.58.69-3.32 1.2-5.23 1.53-1.64 0-3.15-.3-4.52-.9-1.37-.6-2.56-1.44-3.57-2.51-1-1.07-1.78-2.35-2.34-3.82-.55-1.47-.83-3.08-.83-4.83 0-1.98.31-3.72.93-5.23.62-1.51 1.49-2.8 2.61-3.87 1.12-1.07 2.47-1.94 4.05-2.61 1.58-.68 3.32-1.2 5.23-1.57zm-6.68 11.66c0 1.64.16 3.13.48 4.47.32 1.34.77 2.5 1.36 3.47.59.97 1.27 1.73 2.06 2.26.79.54 1.67.8 2.64.8 1.71 0 3.01-.8 3.9-2.41s1.33-3.8 1.33-6.59c0-3.52-.62-6.23-1.86-8.14-1.24-1.91-2.8-2.87-4.68-2.87-1.71 0-3.01.8-3.9 2.41-.89 1.63-1.33 3.82-1.33 6.6zM161.57 35.94h-5.98l-.25-1.94 3.27-.55V15.49l-3.32.25-.25-2.09 6.54-1.53h2.56l.15 4.1 7.54-4.85c1.54 0 2.81.13 3.8.4.99.27 1.77.71 2.34 1.33.57.62.96 1.44 1.18 2.47.22 1.02.33 2.29.33 3.8v14.09l3.47.55-.25 1.94h-11.16l-.25-1.94 2.31-.55V19.31c0-2.28-1.01-3.42-3.02-3.42-.84 0-1.66.22-2.46.66-.8.44-1.61.93-2.41 1.47l-1.31.91v14.53l2.46.55-.25 1.94h-5.04zM195.76 11.41c1.67 0 3.07.12 4.17.35 1.11.23 1.99.65 2.64 1.26.65.6 1.11 1.42 1.38 2.44.27 1.02.4 2.3.4 3.85v9.8c0 1.07.07 1.87.2 2.39s.33.98.6 1.38h2.46l.25 2.01-7.44 1.51-1.61-3.97-5.88 4.32c-2.25 0-4.08-.59-5.5-1.76s-2.14-2.78-2.14-4.83c0-.97.18-1.84.53-2.61.35-.77.83-1.51 1.43-2.21l11.26-3.67v-2.71c0-1.51-.18-2.62-.53-3.34-.35-.72-1.1-1.08-2.24-1.08-.64 0-1.28.08-1.94.25-.65.17-1.23.37-1.73.6l-.8 4.37h-5.18v-4.22l9.67-4.13zm2.76 12.37l-6.38 2.61c-.37.4-.64.87-.8 1.41-.17.54-.25 1.09-.25 1.66 0 1.27.29 2.16.85 2.66.57.5 1.19.75 1.86.75.64 0 1.21-.13 1.71-.4s.92-.52 1.26-.75l1.76-1.21v-6.73zM232.76 29.38c0 1.03.06 1.8.18 2.3s.28.95.48 1.35h2.31l.2 2.01-7.24 1.36-1.46-4.22-7.19 4.57c-1.61 0-2.92-.14-3.95-.43-1.02-.29-1.83-.74-2.41-1.36-.59-.62-.99-1.44-1.21-2.46-.22-1.02-.33-2.27-.33-3.75V15.53l-3.22.25-.25-2.27 6.28-1.4h3.02v16.56c0 1.2.26 2.08.78 2.65.52.57 1.37.85 2.54.85.67 0 1.42-.21 2.24-.63.82-.42 1.65-.91 2.49-1.48l.96-.65V15.53l-2.81.25-.25-2.27 5.83-1.4h3.02v17.27zM236.93 15.38v-.85l1.86-1.91h2.97V9.3l3.87-3.47h1.76v7.09h7.24v2.46h-7.19v14.18c0 .8.19 1.52.58 2.14.38.62 1.06.93 2.04.93.74 0 1.41-.11 2.01-.33.6-.22 1.26-.51 1.96-.88l.96 1.91-6.23 3.42c-2.55 0-4.37-.5-5.46-1.51-1.09-1.01-1.63-2.73-1.63-5.18V15.38h-4.74zM268.15 36.87l-5.28-33.83-3.77-.55.5-1.68h11.76v1.68l-3.36.65 3.42 24.78-.5 2.87 8.65-22.27-.75-5.48-2.76-.55.5-1.68h11.76v1.68l-4.37.65 3.42 24.78-.5 2.87 11.11-27.65-4.27-.65.5-1.68h9.7v1.68l-2.51.55-14.23 32.83-3.07 1.01-3.67-22.52.1-2.77-9.3 24.28-3.08 1zM301.18 16.74l-.55-1.41 8.24-3.72 1.56 1.61-3.47 19.76 4.17-2.61.85 1.26-7.14 5.13-2.56-1.61 3.57-20.06-4.67 1.65zm7.08-15.33l1.46-.25 2.82.25v2.41l-.85 2.16-1.36.25-3.17-.25.2-2.51.9-2.06zM316.41 4.27l-.55-1.51L324.7 0l1.21 1.16-3.62 20.71.96-.25 9.1-9.5h4.83l-1 4.67h-3.07c-.87.24-1.66.55-2.36.93-.7.39-1.49.93-2.36 1.63l-3.52 2.92 1.36.5 5.48 10.31 4.32-2.71.85 1.26-7.14 5.13-1.96-1.21-5.73-12.42L319.84 36h-4.27l5.78-33.08-4.94 1.35zM339.73 16.74l-.55-1.41 8.24-3.72 1.56 1.61-3.47 19.76 4.17-2.61.85 1.26-7.14 5.13-2.56-1.61 3.57-20.06-4.67 1.65zm7.09-15.33l1.46-.25 2.82.25v2.41l-.85 2.16-1.36.25-3.17-.25.2-2.51.9-2.06z"></path></svg>
</a>
EOL;

		//$tpl->set( 'footer-site-heading-html', $this->getSitename() );

		$tpl->set( 'footer-site-heading-html', $siteHeadingLogo );

		// set defaults
		if ( !isset( $tpl->data['postbodytext'] ) ) {
			$tpl->set( 'postbodytext', '' ); // not currently set in desktop skin
		}
	}

	/**
	 * Load internal banner content to show in pre content in template
	 * Beware of HTML caching when using this function.
	 * Content set as "internalbanner"
	 * @param BaseTemplate $tpl
	 */
	protected function prepareBanners( BaseTemplate $tpl ) {
		// Make sure Zero banner are always on top
		$banners = [ '<div id="siteNotice"></div>' ];
		if ( $this->getConfig()->get( 'MinervaEnableSiteNotice' ) ) {
			$siteNotice = $this->getSiteNotice();
			if ( $siteNotice ) {
				$banners[] = $siteNotice;
			}
		}
		$tpl->set( 'banners', $banners );
		// These banners unlike 'banners' show inside the main content chrome underneath the
		// page actions.
		$tpl->set( 'internalBanner', '' );
	}

	/**
	 * Returns an array with details for a language button.
	 * @return array
	 */
	protected function getLanguageButton() {
		$languageUrl = SpecialPage::getTitleFor(
			'MobileLanguages',
			$this->getSkin()->getTitle()
		)->getLocalURL();

		return [
			'attributes' => [
				'class' => 'language-selector',
				'href' => $languageUrl,
			],
			'label' => $this->msg( 'mobile-frontend-language-article-heading' )->text()
		];
	}

	/**
	 * Returns an array with details for a talk button.
	 * @param Title $talkTitle Title object of the talk page
	 * @param string $label Button label
	 * @param bool $addSection (optional) when added the talk button will render
	 *  as an add topic button. Defaults to false.
	 * @return array
	 */
	protected function getTalkButton( $talkTitle, $label, $addSection = false ) {
		if ( $addSection ) {
			$params = [ 'action' => 'edit', 'section' => 'new' ];
			$className = 'minerva-talk-add-button ' . MinervaUI::buttonClass( 'progressive', 'button' );
		} else {
			$params = [];
			$className = 'talk';
		}

		return [
			'attributes' => [
				'href' => $talkTitle->getLinkURL( $params ),
				'data-title' => $talkTitle->getFullText(),
				'class' => $className,
			],
			'label' => $label,
		];
	}

	/**
	 * Returns an array with details for a categories button.
	 * @return array
	 */
	protected function getCategoryButton() {
		return [
			'attributes' => [
				'href' => '#/categories',
				// add hidden class (the overlay works only, when JS is enabled (class will
				// be removed in categories/init.js)
				'class' => 'category-button hidden',
			],
			'label' => $this->msg( 'categories' )->text()
		];
	}

	/**
	 * Returns an array of links for page secondary actions
	 * @param BaseTemplate $tpl
	 * @return array
	 */
	protected function getSecondaryActions( BaseTemplate $tpl ) {
		$services = MediaWikiServices::getInstance();
		$namespaceInfo = $services->getNamespaceInfo();
		/** @var \MediaWiki\Minerva\LanguagesHelper $languagesHelper */
		$languagesHelper = $services->getService( 'Minerva.LanguagesHelper' );
		$buttons = [];
		// always add a button to link to the talk page
		// it will link to the wikitext talk page
		$title = $this->getTitle();
		$subjectPage = Title::newFromLinkTarget( $namespaceInfo->getSubjectPage( $title ) );
		$talkAtBottom = !$this->skinOptions->get( SkinOptions::TALK_AT_TOP ) ||
			$subjectPage->isMainPage();
		if ( !$this->getUserPageHelper()->isUserPage() &&
			$this->getPermissions()->isTalkAllowed() && $talkAtBottom &&
			!$this->isTalkPageWithViewAction()
		) {
			$namespaces = $tpl->data['content_navigation']['namespaces'];
			// FIXME [core]: This seems unnecessary..
			$subjectId = $title->getNamespaceKey( '' );
			$talkId = $subjectId === 'main' ? 'talk' : "{$subjectId}_talk";

			if ( isset( $namespaces[$talkId] ) ) {
				$talkButton = $namespaces[$talkId];
				$talkTitle = Title::newFromLinkTarget( $namespaceInfo->getTalkPage( $title ) );

				$buttons['talk'] = $this->getTalkButton( $talkTitle, $talkButton['text'] );
			}
		}

		if ( $languagesHelper->doesTitleHasLanguagesOrVariants( $title ) && $title->isMainPage() ) {
			$buttons['language'] = $this->getLanguageButton();
		}

		if ( $this->hasCategoryLinks() ) {
			$buttons['categories'] = $this->getCategoryButton();
		}

		return $buttons;
	}

	/**
	 * Minerva skin do not have sidebar, there is no need to calculate that.
	 * @return array
	 */
	public function buildSidebar() {
		return [];
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	protected function getJsConfigVars() : array {
		$title = $this->getTitle();

		return array_merge( parent::getJsConfigVars(), [
			'wgMinervaPermissions' => [
				'watch' => $this->getPermissions()->isAllowed( IMinervaPagePermissions::WATCH ),
				'talk' => $this->getUserPageHelper()->isUserPage() ||
					( $this->getPermissions()->isTalkAllowed() || $title->isTalkPage() ) &&
					$this->isWikiTextTalkPage()
			],
			'wgMinervaFeatures' => $this->skinOptions->getAll(),
			'wgMinervaDownloadNamespaces' => $this->getConfig()->get( 'MinervaDownloadNamespaces' ),
		] );
	}

	/**
	 * Returns true, if the talk page of this page is wikitext-based.
	 * @return bool
	 */
	protected function isWikiTextTalkPage() {
		$title = $this->getTitle();
		if ( !$title->isTalkPage() ) {
			$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
			$title = Title::newFromLinkTarget( $namespaceInfo->getTalkPage( $title ) );
		}
		return $title->isWikitextPage();
	}

	/**
	 * Returns an array of modules related to the current context of the page.
	 * @return array
	 */
	public function getContextSpecificModules() {
		$modules = [];
		$mobileFrontend = ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' );
		if ( $this->skinOptions->hasSkinOptions() && $mobileFrontend ) {
			$modules[] = 'skins.minerva.options';
		}

		return $modules;
	}

	/**
	 * Returns the javascript entry modules to load. Only modules that need to
	 * be overriden or added conditionally should be placed here.
	 * @return array
	 */
	public function getDefaultModules() {
		$modules = parent::getDefaultModules();

		// FIXME: T223204: Dequeue default content modules except for the history
		// action. Allow default history action content modules
		// (mediawiki.page.ready, jquery.makeCollapsible,
		// jquery.makeCollapsible.styles, etc) in order to enable toggling of the
		// filters. Long term this won't be necessary when T111565 is resolved and a
		// more general solution can be used.
		if ( Action::getActionName( $this->getContext() ) !== 'history' ) {
			// dequeue default content modules (toc, sortable, collapsible, etc.)
			$modules['content'] = [];
			// dequeue styles associated with `content` key.
			$modules['styles']['content'] = [];
		}
		$modules['styles']['core'] = $this->getSkinStyles();

		$modules['minerva'] = array_merge(
			$this->getContextSpecificModules(),
			[
				'skins.minerva.scripts'
			]
		);

		Hooks::run( 'SkinMinervaDefaultModules', [ $this, &$modules ] );

		return $modules;
	}

	/**
	 * Provide styles required to present the server rendered page in this skin. Additional styles
	 * may be loaded dynamically by the client.
	 *
	 * Any styles returned by this method are loaded on the critical rendering path as linked
	 * stylesheets. I.e., they are required to load on the client before first paint.
	 *
	 * @return array
	 */
	protected function getSkinStyles(): array {
		$title = $this->getTitle();
		$styles = [
			'skins.minerva.base.styles',
			'skins.minerva.content.styles',
			'skins.minerva.content.styles.images',
			'mediawiki.hlist',
			'mediawiki.ui.icon',
			'mediawiki.ui.button',
			'skins.minerva.icons.wikimedia',
			'skins.minerva.mainMenu.icons',
			'skins.minerva.mainMenu.styles',
		];
		if ( $title->isMainPage() ) {
			$styles[] = 'skins.minerva.mainPage.styles';
		} elseif ( $this->getUserPageHelper()->isUserPage() ) {
			$styles[] = 'skins.minerva.userpage.styles';
		} elseif ( $this->isTalkPageWithViewAction() ) {
			$styles[] = 'skins.minerva.talk.styles';
		}

		if ( $this->getUser()->isLoggedIn() ) {
			$styles[] = 'skins.minerva.loggedin.styles';
			$styles[] = 'skins.minerva.icons.loggedin';
		}

		// When any of these features are enabled in production
		// remove the if condition
		// and move the associated LESS file inside `skins.minerva.amc.styles`
		// into a more appropriate module.
		if (
			$this->skinOptions->get( SkinOptions::PERSONAL_MENU ) ||
			$this->skinOptions->get( SkinOptions::TALK_AT_TOP ) ||
			$this->skinOptions->get( SkinOptions::HISTORY_IN_PAGE_ACTIONS ) ||
			$this->skinOptions->get( SkinOptions::TOOLBAR_SUBMENU )
		) {
			// SkinOptions::PERSONAL_MENU + SkinOptions::TOOLBAR_SUBMENU uses components/ToggleList
			// SkinOptions::TALK_AT_TOP uses tabs.less
			// SkinOptions::HISTORY_IN_PAGE_ACTIONS + SkinOptions::TOOLBAR_SUBMENU uses pageactions.less
			$styles[] = 'skins.minerva.amc.styles';
		}

		if ( $this->skinOptions->get( SkinOptions::PERSONAL_MENU ) ) {
			// If ever enabled as the default, please remove the duplicate icons
			// inside skins.minerva.mainMenu.icons. See comment for MAIN_MENU_EXPANDED
			$styles[] = 'skins.minerva.personalMenu.icons';
		}

		if (
			$this->skinOptions->get( SkinOptions::MAIN_MENU_EXPANDED )
		) {
			// If ever enabled as the default, please review skins.minerva.mainMenu.icons
			// and remove any unneeded icons
			$styles[] = 'skins.minerva.mainMenu.advanced.icons';
		}
		if (
			$this->skinOptions->get( SkinOptions::PERSONAL_MENU ) ||
			$this->skinOptions->get( SkinOptions::TOOLBAR_SUBMENU )
		) {
			// SkinOptions::PERSONAL_MENU requires the `userTalk` icon.
			// SkinOptions::TOOLBAR_SUBMENU requires the rest of the icons including `overflow`.
			// Note `skins.minerva.overflow.icons` is pulled down by skins.minerva.scripts but the menu can
			// work without JS.
			$styles[] = 'skins.minerva.overflow.icons';
		}

		return $styles;
	}
}

// Setup alias for compatibility with SkinMinervaNeue.
class_alias( 'SkinMinerva', 'SkinMinervaNeue' );
