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

use MediaWiki\MediaWikiServices;
use MediaWiki\Minerva\SkinOptions;
use MediaWiki\Minerva\SkinUserPageHelper;

/**
 * Hook handlers for Minerva skin.
 *
 * Hook handler method names should be in the form of:
 *	on<HookName>()
 */
class MinervaHooks {
	const FEATURE_OVERFLOW_PAGE_ACTIONS = 'MinervaOverflowInPageActions';

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 *
	 * Registers:
	 *
	 * * EventLogging schema modules, if the EventLogging extension is loaded;
	 * * Modules for the Visual Editor overlay, if the VisualEditor extension is loaded; and
	 * * Modules for the notifications overlay, if the Echo extension is loaded.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 *
	 * @param ResourceLoader &$resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader &$resourceLoader ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$resourceLoader->register( [
				'mobile.startup' => [
					'dependencies' => [ 'mediawiki.searchSuggest' ],
					'localBasePath' => dirname( __DIR__ ),
					'remoteExtPath' => 'Minerva',
					'scripts' => 'resources/mobile.startup.stub.js',
					'targets' => [ 'desktop', 'mobile' ],
				]
			] );
		}
	}

	/**
	 * Disable recent changes enhanced mode (table mode)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FetchChangesList
	 *
	 * @param User $user
	 * @param Skin &$skin
	 * @param array &$list
	 * @param array $groups
	 * @return bool|null
	 */
	public static function onFetchChangesList( User $user, Skin &$skin, &$list, $groups = [] ) {
		if ( $skin->getSkinName() === 'minerva' ) {
			// The new changes list (table-based) does not work with Minerva
			$list = new OldChangesList( $skin->getContext(), $groups );
			// returning false makes sure $list is used instead.
			return false;
		}
	}

	/**
	 * Register mobile web beta features
	 * @see https://www.mediawiki.org/wiki/
	 *  Extension:MobileFrontend/MobileFrontendFeaturesRegistration
	 *
	 * @param MobileFrontend\Features\FeaturesManager $featureManager
	 */
	public static function onMobileFrontendFeaturesRegistration( $featureManager ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'minerva' );

		try {
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaShowCategoriesButton',
					'skin-minerva',
					$config->get( 'MinervaShowCategoriesButton' )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaPageIssuesNewTreatment',
					'skin-minerva',
					$config->get( 'MinervaPageIssuesNewTreatment' )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaTalkAtTop',
					'skin-minerva',
					$config->get( 'MinervaTalkAtTop' )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaHistoryInPageActions',
					'skin-minerva',
					$config->get( 'MinervaHistoryInPageActions' )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					self::FEATURE_OVERFLOW_PAGE_ACTIONS,
					'skin-minerva',
					$config->get( self::FEATURE_OVERFLOW_PAGE_ACTIONS )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaAdvancedMainMenu',
					'skin-minerva',
					$config->get( 'MinervaAdvancedMainMenu' )
				)
			);
			$featureManager->registerFeature(
				new MobileFrontend\Features\Feature(
					'MinervaPersonalMenu',
					'skin-minerva',
					$config->get( 'MinervaPersonalMenu' )
				)
			);
		} catch ( RuntimeException $e ) {
			// features already registered...
			// due to a bug it's possible for this to run twice
			// https://phabricator.wikimedia.org/T165068
		}
	}

	/**
	 * Invocation of hook SpecialPageBeforeExecute
	 *
	 * We use this hook to ensure that login/account creation pages
	 * are redirected to HTTPS if they are not accessed via HTTPS and
	 * $wgSecureLogin == true - but only when using the
	 * mobile site.
	 *
	 * @param SpecialPage $special
	 * @param string $subpage
	 */
	public static function onSpecialPageBeforeExecute( SpecialPage $special, $subpage ) {
		$name = $special->getName();
		$out = $special->getOutput();
		$skin = $out->getSkin();
		$request = $special->getRequest();

		if ( $skin instanceof SkinMinerva ) {
			switch ( $name ) {
				case 'MobileMenu':
					$out->addModuleStyles( [
						'skins.minerva.mainMenu.icons',
						'skins.minerva.mainMenu.styles',
					] );
					break;
				case 'Recentchanges':
					$isEnhancedDefaultForUser = $special->getUser()->getBoolOption( 'usenewrc' );
					$enhanced = $request->getBool( 'enhanced', $isEnhancedDefaultForUser );
					if ( $enhanced ) {
						$out->addHTML( Html::warningBox(
							$special->msg( 'skin-minerva-recentchanges-warning-enhanced-not-supported' )
						) );
					}
					break;
				case 'Userlogin':
				case 'CreateAccount':
					// Add default warning message to Special:UserLogin and Special:UserCreate
					// if no warning message set.
					if (
						!$request->getVal( 'warning' ) &&
						!$special->getUser()->isLoggedIn() &&
						!$request->wasPosted()
					) {
						$request->setVal( 'warning', 'mobile-frontend-generic-login-new' );
					}
					break;
			}
		}
	}

	/**
	 * Set the skin options for Minerva
	 *
	 * @param MobileContext $mobileContext
	 * @param Skin $skin
	 */
	private static function setMinervaSkinOptions(
		MobileContext $mobileContext, Skin $skin
	) {
		// setSkinOptions is not available
		if ( $skin instanceof SkinMinerva ) {
			$services = MediaWikiServices::getInstance();
			$featureManager = $services
				->getService( 'MobileFrontend.FeaturesManager' );
			$skinOptions = $services->getService( 'Minerva.SkinOptions' );
			$title = $skin->getTitle();
			// T232653: TALK_AT_TOP, HISTORY_IN_PAGE_ACTIONS, TOOLBAR_SUBMENU should
			// be true on user pages and user talk pages for all users
			//
			// For some reason using $services->getService( 'SkinUserPageHelper' )
			// here results in a circular dependency error which is why
			// SkinUserPageHelper is being instantiated instead.
			$relevantUserPageHelper = new SkinUserPageHelper(
				$title->inNamespace( NS_USER_TALK ) ? $title->getSubjectPage() : $title
			);
			$isUserPageOrUserTalkPage = $relevantUserPageHelper->isUserPage();

			$isBeta = $mobileContext->isBetaGroupMember();
			$skinOptions->setMultiple( [
				SkinOptions::TALK_AT_TOP => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser( 'MinervaTalkAtTop' ),
				SkinOptions::BETA_MODE
					=> $isBeta,
				SkinOptions::CATEGORIES
					=> $featureManager->isFeatureAvailableForCurrentUser( 'MinervaShowCategoriesButton' ),
				SkinOptions::PAGE_ISSUES
					=> $featureManager->isFeatureAvailableForCurrentUser( 'MinervaPageIssuesNewTreatment' ),
				SkinOptions::MOBILE_OPTIONS => true,
				SkinOptions::PERSONAL_MENU => $featureManager->isFeatureAvailableForCurrentUser(
					'MinervaPersonalMenu'
				),
				SkinOptions::MAIN_MENU_EXPANDED => $featureManager->isFeatureAvailableForCurrentUser(
					'MinervaAdvancedMainMenu'
				),
				SkinOptions::HISTORY_IN_PAGE_ACTIONS => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser( 'MinervaHistoryInPageActions' ),
				SkinOptions::TOOLBAR_SUBMENU => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser(
						self::FEATURE_OVERFLOW_PAGE_ACTIONS
					),
				SkinOptions::TABS_ON_SPECIALS => false,
			] );
			Hooks::run( 'SkinMinervaOptionsInit', [ $skin, $skinOptions ] );
		}
	}

	/**
	 * MobileFrontendBeforeDOM hook handler that runs before the MobileFormatter
	 * executes. We use it to determine whether or not the talk page is eligible
	 * to be simplified (we want it only to be simplified when the MobileFormatter
	 * makes expandable sections).
	 *
	 * @param MobileContext $mobileContext
	 * @param MobileFormatter $formatter
	 */
	public static function onMobileFrontendBeforeDOM(
		MobileContext $mobileContext,
		MobileFormatter $formatter
	) {
		$services = MediaWikiServices::getInstance();
		$skinOptions = $services->getService( 'Minerva.SkinOptions' );
		$skinOptions->setMultiple( [
			SkinOptions::SIMPLIFIED_TALK => true
		] );
	}

	/**
	 * UserLogoutComplete hook handler.
	 * Resets skin options if a user logout occurs - this is necessary as the
	 * RequestContextCreateSkinMobile hook runs before the UserLogout hook.
	 *
	 * @param User $user
	 */
	public static function onUserLogoutComplete( User $user ) {
		try {
			$ctx = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Context' );
			self::setMinervaSkinOptions( $ctx, $ctx->getSkin() );
		} catch ( Wikimedia\Services\NoSuchServiceException $ex ) {
			// MobileFrontend not installed. Not important.
		}
	}

	/**
	 * BeforePageDisplayMobile hook handler.
	 *
	 * @param MobileContext $mobileContext
	 * @param Skin $skin
	 */
	public static function onRequestContextCreateSkinMobile(
		MobileContext $mobileContext, Skin $skin
	) {
		self::setMinervaSkinOptions( $mobileContext, $skin );
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 * Used for setting JS variables which are pulled in dynamically with RL
	 * instead of embedded directly on the page with a script tag.
	 * These vars have a shorter cache-life than those in `getSkinConfigVariables`.
	 *
	 * @param array &$vars Array of variables to be added into the output of the RL startup module.
	 * @param string $skin
	 */
	public static function onResourceLoaderGetConfigVars( &$vars, $skin ) {
		if ( $skin === 'minerva' ) {
			$config = MediaWikiServices::getInstance()->getConfigFactory()
				->makeConfig( 'minerva' );
			// This is to let the UI adjust itself to a wiki that is always read-only.
			// Ignore temporary read-only on live wikis, requires heavy DB check (T233458).
			$roConf = MediaWikiServices::getInstance()->getConfiguredReadOnlyMode();
			$vars += [
				'wgMinervaABSamplingRate' => $config->get( 'MinervaABSamplingRate' ),
				'wgMinervaCountErrors' => $config->get( 'MinervaCountErrors' ),
				'wgMinervaReadOnly' => $roConf->isReadOnly(),
			];
		}
	}
}
