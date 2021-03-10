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

/**
 * Extended Template class of BaseTemplate for mobile devices
 */
class MinervaTemplate extends BaseTemplate {
	/** @var bool Specify whether the page is a special page */
	protected $isSpecialPage;

	/** @var bool Whether or not the user is on the Special:MobileMenu page */
	protected $isSpecialMobileMenuPage;

	/** @var bool Specify whether the page is main page */
	protected $isMainPage;

	/** @var bool */
	protected $isMainPageTalk;

	/**
	 * Start render the page in template
	 */
	public function execute() {
		$title = $this->getSkin()->getTitle();
		$this->isSpecialPage = $title->isSpecialPage();
		$this->isSpecialMobileMenuPage = $this->isSpecialPage &&
			$title->isSpecial( 'MobileMenu' );
		$this->isMainPage = $title->isMainPage();
		$subjectPage = MediaWikiServices::getInstance()->getNamespaceInfo()
			->getSubjectPage( $title );

		$this->isMainPageTalk = Title::newFromLinkTarget( $subjectPage )->isMainPage();
		Hooks::run( 'MinervaPreRender', [ $this ], '1.35' );
		$this->render( $this->data );
	}

	/**
	 * Returns available page actions
	 * @return array
	 */
	protected function getPageActions() {
		return $this->isFallbackEditor() ? [] : $this->data['pageActionsMenu'];
	}

	/**
	 * Returns template data for footer
	 *
	 * @param array $data Data used to build the page
	 * @return array
	 */
	protected function getFooterTemplateData( $data ) {
		$groups = [];

		foreach ( $data['footerlinks'] as $category => $links ) {
			$items = [];
			foreach ( $links as $link ) {
				if ( isset( $this->data[$link] ) && $data[$link] !== '' && $data[$link] !== false ) {
					$items[] = [
						'category' => $category,
						'name' => $link,
						'linkhtml' => $data[$link],
					];
				}
			}
			$groups[] = [
				'name' => $category,
				'items' => $items,
			];
		}

		// This turns off the footer id and allows us to distinguish the old footer with the new design

		return [
			'lastmodified' => $this->getHistoryLinkHtml( $data ),
			'headinghtml' => $data['footer-site-heading-html'],
			// Note mobile-license is only available on the mobile skin. It is outputted as part of
			// footer-info on desktop hence the conditional check.
			'licensehtml' => ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ?
				MobileFrontendSkinHooks::getLicenseText( $this->getSkin() ) : '',
			'lists' => $groups,
		];
	}

	/**
	 * Get the HTML for rendering the available page actions
	 * @return string
	 */
	protected function getPageActionsHtml() {
		$templateParser = new TemplateParser( __DIR__ . '/../../components' );
		$pageActions = $this->getPageActions();
		$html = '';

		if ( $pageActions && $pageActions['toolbar'] ) {
			$html = $templateParser->processTemplate( 'PageActionsMenu',  $pageActions );
		}
		return $html;
	}

	/**
	 * Returns the 'Last edited' message, e.g. 'Last edited on...'
	 * @param array $data Data used to build the page
	 * @return string
	 */
	protected function getHistoryLinkHtml( $data ) {
		$action = Action::getActionName( RequestContext::getMain() );
		if ( isset( $data['historyLink'] ) && $action === 'view' ) {
			$args = [
				'historyIconClass' => MinervaUI::iconClass(
					'history-base20', 'mw-ui-icon-small', '', 'wikimedia'
				),
				'arrowIconClass' => MinervaUI::iconClass(
					'expand-gray', 'small',
					'mf-mw-ui-icon-rotate-anti-clockwise indicator',
					// Uses icon in MobileFrontend so must be prefixed mf.
					// Without MobileFrontend it will not render.
					// Rather than maintain 2 versions (and variants) of the arrow icon which can conflict
					// with each othe and bloat CSS, we'll
					// use the MobileFrontend one. Long term when T177432 and T160690 are resolved
					// we should be able to use one icon definition and break this dependency.
					'mf'
				 ),
			] + $data['historyLink'];
			$templateParser = new TemplateParser( __DIR__ );
			return $templateParser->processTemplate( 'history', $args );
		}

		return '';
	}

	/**
	 * @return bool
	 */
	protected function isFallbackEditor() {
		$action = $this->getSkin()->getRequest()->getVal( 'action' );
		return $action === 'edit';
	}

	/**
	 * Get page secondary actions
	 * @return array
	 */
	protected function getSecondaryActions() {
		if ( $this->isFallbackEditor() ) {
			return [];
		}

		return $this->data['secondary_actions'];
	}

	/**
	 * Get HTML representing secondary page actions like language selector
	 * @return string
	 */
	protected function getSecondaryActionsHtml() {
		$baseClass = MinervaUI::buttonClass( '', 'button' );
		/** @var SkinMinerva $skin */
		$skin = $this->getSkin();
		$html = '';
		// no secondary actions on the user page
		if ( $skin instanceof SkinMinerva && !$skin->getUserPageHelper()->isUserPage() ) {
			foreach ( $this->getSecondaryActions() as $el ) {
				if ( isset( $el['attributes']['class'] ) ) {
					$el['attributes']['class'] .= ' ' . $baseClass;
				} else {
					$el['attributes']['class'] = $baseClass;
				}
				// @phan-suppress-next-line PhanTypeMismatchArgument
				$html .= Html::element( 'a', $el['attributes'], $el['label'] );
			}
		}

		return $html;
	}

	/**
	 * Get the HTML for the content of a page
	 * @param array $data Data used to build the page
	 * @return string representing HTML of content
	 */
	protected function getContentHtml( $data ) {
		if ( !$data[ 'unstyledContent' ] ) {
			$content = Html::openElement( 'div', [
				'id' => 'bodyContent',
				'class' => 'content',
			] );
			$content .= $data[ 'bodytext' ];
			if ( isset( $data['subject-page'] ) ) {
				$content .= $data['subject-page'];
			}
			return $content . Html::closeElement( 'div' );
		}

		return $data[ 'bodytext' ];
	}

	/**
	 * Gets the main menu HTML.
	 * @param array $data Data used to build the page
	 * @return string
	 */
	protected function getMainMenuData( $data ) {
		return $data['mainMenu']['items'];
	}

	/**
	 * Render the entire page
	 * @param array $data Data used to build the page
	 * @todo replace with template engines
	 */
	protected function render( $data ) {
?>
<script>
(function() {
/* PsychonautWiki Telemetry */

    window.Countly = window.Countly || {};
    window.Countly.q = window.Countly.q || [];

    window.Countly.app_key = '2a7e3670a660709a2c6528048e86ff4c79d2da8f';
    window.Countly.url = 'https://psychonautwiki.org/metrics';

    window.Countly.q.push(['track_sessions', 'track_pageview', 'track_clicks', 'track_errors', 'track_links', 'track_forms']);

/* PsychonautWiki Heimdal */
    try {
        window.addEventListener('load', function () {
            /*mw.loader.using(['jquery.client'], function () {
                function c(){this.a={endpoint:"https://h.psychonautwiki.org/ingress",o:200,max:15,g:"__hd_d"};this.c={j:0,h:0};this.f=[];this.b=[];try{var a=window.Countly.device_id}catch(b){}a||d(Error("Heimdal: Could not identify user"));this.l=a}function d(a){"Raven"in window&&"captureException"in Raven&&Raven.captureException(a)}function e(a,b){var f=null;try{f=window.mw.config.values.wgUserName}catch(p){d(p)}a.b.push(a.l+";"+Date.now()+";"+f+";"+b);g(a)}function h(){try{if(window.sessionStorage._pcl="301","301"===window.sessionStorage._pcl)return window.sessionStorage.removeItem("_pcl"),1}catch(a){}try{if(window.localStorage._pcl="301","301"===window.localStorage._pcl)return window.localStorage.removeItem("_pcl"),2}catch(a){}}function g(a){var b=window.JSON.stringify(a.b);switch(h()){case 1:window.sessionStorage[a.a.g]=b;break;case 2:window.localStorage[a.a.g]=b;break;default:d(Error("Heimdal: Could not identify storage strategy"))}}function k(a){clearTimeout(a.c.j);a.c.j=setTimeout(a.m.bind(a),100+a.c.h*a.a.o)}c.prototype.i=function(){this.c.h++;k(this)};c.prototype.m=function(){var a=this;try{var b=new window.XMLHttpRequest;b.open("POST",this.a.endpoint,!0);b.setRequestHeader("Content-Type","application/json");b.onreadystatechange=function(){if(4==b.readyState){if(200!==b.status)return a.i();a.b=[];g(a);a.c.h=0}};b.onerror=this.i.bind(this);b.send(window.JSON.stringify(this.b))}catch(f){d(f)}};c.prototype.use=function(a,b){this.f.push([a,b])};var l=new c;l.use("t",function(a){e(a,"t;"+window.mw.config.values.wgPageName);k(a)});l.use("a",function(a){$("a").click(function(b){b.target&&b.target.href&&!b.target.href.match(/\#$/)&&b.target.href.match(/\/wiki\/(.*?)$/)&&(b="a;"+window.mw.config.values.wgPageName+";"+b.target.href.match(/\/wiki\/(.*?)$/)[1],e(a,b),k(a))})});var m;switch(h()){case 1:m=window.sessionStorage[l.a.g];break;case 2:m=window.localStorage[l.a.g]}try{l.b=window.JSON.parse(m)}catch(a){}l.b.length&&k(l);for(var n=0;n<l.f.length;++n)try{l.f[n][1](l)}catch(a){d(Error("Heimdal: '"+l.f[n][0]+"' failed: "+a))};
            });*/
        });

        var cly = document.createElement('script'); cly.type = 'text/javascript';
        cly.async = true;
        cly.src = 'https://psychonautwiki.org/metrics/sdk/web/countly.min.js';
        cly.onload = function(){
            window.Countly.init();
        };
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(cly, s);
    } catch (err) {
        "Raven"in window && "captureException"in Raven && Raven.captureException(err)
    }
})();</script>
<noscript><img src='https://metrics.psychonautwiki.org/pixel.png?app_key=2a7e3670a660709a2c6528048e86ff4c79d2da8f&begin_session=1'/></noscript>

<!--<script src="https://psychonautwiki.org/raven/3.17.0-raven.min.js" crossorigin="anonymous"></script>
<script>Raven.config('https://e18548be90f54266afa6a70ebe953dc9@beacon.apx.pub/5').install()</script>-->

<!-- User badge -->
<script async src="/custom/userbadge.js"></script>

<!-- DoseChart -->
<script async src="/custom/ddc-7b5d57b.js"></script>

<!--
- fixes weird newline inserted into first
  paragraph of article by substancebox template
- issue disappears when interactions are removed
  again so its related to the table before it
-->
<script>
(function(a){document.readyState!=='loading'?a():window.addEventListener('DOMContentLoaded',a)})(function(){[].slice.call((document.querySelector(".mw-parser-output > p")||document.querySelector(".mf-section-0 > p")).children).map(function(e){e.constructor===HTMLBRElement&&e.parentNode.removeChild(e)})});
</script>

<!--
migrating from 1.31 to 1.33 broke arrayprint expansion
of semantic mediawiki queries for substance interactions.

this replaces the substance names with links at runtime,
which are now simply plain text rather than actual links
because the wikitext generated by the macro in the
substance box template isn't parsed anymore.
-->
<script>
(function(a) {
    document.readyState === "complete" ? a() : window.addEventListener('load', a)
})(function() {
    try {
        [].slice.call(document.getElementsByClassName("SBInteractionLabel")).forEach(function (e) {
            if (
                e.parentNode
                && e.parentNode.parentNode
                && e.parentNode.constructor == HTMLTableRowElement
                && e.parentNode.innerText === ''
            ) {
                e.parentNode.parentNode.removeChild(e.parentNode);
                return;
            }

            var substance =
                e.innerText
                 .split('|')
                 .pop()
                 .replace(']', '')
                 .replace(/^array/i, '2');

            e.innerText = '';

            var t = document.createElement('a');
            t.href = '/wiki/' + substance.replace(/\s+/i, '_');
            t.innerText = substance;

            e.appendChild(t);
        });
    } catch (err) {alert(err.message);
    }
});
</script>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-126957892-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-126957892-1', {
    'link_attribution': true
  });
</script>

<!-- Yandex.Metrika counter --> <script type="text/javascript" > (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(53989831, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true }); </script> <noscript><div><img src="https://mc.yandex.ru/watch/53989831" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->

<!-- blog annoucement banner -->
<!--<script async src="/custom/blog-annoucement.js"></script>-->

<!--<script async src="/custom/gds.js"></script>-->

<script type="application/javascript">
    // https://github.com/psychonautwiki/4-aco-dmenut
!function(){function e(t,r,n){function o(s,a){if(!r[s]){if(!t[s]){var u="function"==typeof require&&require;if(!a&&u)return u(s,!0);if(i)return i(s,!0);var c=new Error("Cannot find module '"+s+"'");throw c.code="MODULE_NOT_FOUND",c}var f=r[s]={exports:{}};t[s][0].call(f.exports,function(e){return o(t[s][1][e]||e)},f,f.exports,e,t,r,n)}return r[s].exports}for(var i="function"==typeof require&&require,s=0;s<n.length;s++)o(n[s]);return o}return e}()({1:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{default:e}}var o=e("babel-runtime/core-js/set"),i=n(o),s=e("babel-runtime/helpers/toConsumableArray"),a=n(s),u=e("babel-runtime/helpers/slicedToArray"),c=n(u),f=e("babel-runtime/helpers/classCallCheck"),l=n(f),_=e("babel-runtime/helpers/createClass"),d=n(_),p=function(e,t){if(!e)throw new Error(t)},h=function(){function e(t,r){var n=t.document,o=t.menu,i=r.userState;(0,l.default)(this,e),this._dom={document:n,menu:o},this._dependencies={userState:i},this._menuPath="/wiki/MediaWiki:Sidebar?action=raw",this._anchorBaseUrl=[location.protocol,"//",location.host].join(""),this._menu={},this._menuUserLinks=[],this._retryCount=0,this._retryMax=3,this._populateUserLinks()}return(0,d.default)(e,[{key:"_populateUserLinks",value:function(){var e=this._dependencies.userState,t=e._flags,r=t.ANON,n=t.USER,o=e.getUserName(),i="encodeURIComponent"in window?encodeURIComponent(this._dependencies.userState.getPageName()):"";this._menuUserLinks=[[r^n,[["Watchlist","Special:Watchlist",["menu__item--unStar","mw-ui-icon","mw-ui-icon-before","mw-ui-icon-minerva-unStar"]]]],[n,[["Upload","Special:Uploads",["mw-ui-icon-upload","menu-item-upload"]]]],[r^n,[["Settings","Special:MobileOptions",["menu__item--settings","mw-ui-icon","mw-ui-icon-before","mw-ui-icon-minerva-settings"]]]],[n,[[""+o,"Special:UserProfile/"+o,["mw-ui-icon","mw-ui-icon-before","mw-ui-icon-wikimedia-userAvatar-base20","truncated-text","primary-action"]],["Logout","Special:UserLogout",["truncated-text","secondary-action","menu__item--logout","mw-ui-icon","mw-ui-icon-element","mw-ui-icon-minerva-logOut"]]]],[r,[["Login","/w/index.php?title=Special:UserLogin&returnto="+i,["menu__item--login","mw-ui-icon","mw-ui-icon-before","mw-ui-icon-minerva-logIn"]]]],[r,[["Register","/w/index.php?title=Special:UserLogin&type=signup&returnto="+i,["mw-ui-icon","mw-ui-icon-before","mw-ui-icon-userAvatarOutline"]]]]]}},{key:"_genericXhr",value:function(e,t,r,n,o){var i=new XMLHttpRequest;i.open(e,t,!0),n&&i.setRequestHeader("Content-type","application/json"),i.onreadystatechange=function(){if(4===i.readyState){if(!n)return o(i.status>=400,i.responseText);var e=void 0;try{e=JSON.parse(i.responseText)}catch(e){return o(e)}o(i.status>=400,e)}},i.send(r)}},{key:"_initSidebar",value:function(e){var t=e.err,r=e.data;try{this._processMenu({err:t,data:r})}catch(e){this._ingestError(e)}}},{key:"_ingestError",value:function(e){"Raven"in window&&"captureException"in Raven&&Raven.captureException(e)}},{key:"_handleError",value:function(e){var t=this;if(this._ingestError(e),this._retryCount>=this._retryMax){var r=new Error("Could not load sidebar.");return this._ingestError(r)}setTimeout(function(){return t._retrieveSidebar()},0)}},{key:"_parseMenu",value:function(e){return e.split("\n").filter(function(e){return e}).map(function(e){return[e.match(/\*+/)[0].length,e.match(/\*+(.*?)$/)[1]]}).reduce(function(e,t){return 1===t[0]?(++e.i,e.sections.push([t[1],[]])):e.sections[e.i][1].push(t[1]),e},{i:-1,sections:[]})}},{key:"_$createElement",value:function(){var e;return(e=this._dom.document).createElement.apply(e,arguments)}},{key:"_$wrapElement",value:function(e,t){var r=this._$createElement(e);return r.appendChild(t),r}},{key:"_$retrieveLink",value:function(e){return/^(https?|ftp):\/\//i.test(e)?e:/\/(.*)$/.test(e)?""+this._anchorBaseUrl+e:this._anchorBaseUrl+"/wiki/"+e}},{key:"_$isCurrentPage",value:function(e){var t=e.replace(/\s/g,"_"),r=window.decodeURIComponent(window.location.href).replace(/\s/g,"_");return RegExp(t,"i").test(r)}},{key:"_$menuLinkItem",value:function(e){var t=this,r=this._$createElement("li");return e.forEach(function(e){var n,o=(0,c.default)(e,3),i=o[0],s=o[1],u=o[2],f=t._$createElement("a");f.href=t._$retrieveLink(s),f.innerText=i,f.classList.add("mw-ui-icon","mw-ui-icon-before"),(n=f.classList).add.apply(n,(0,a.default)(u)),r.appendChild(f),t._$isCurrentPage(s)&&r.classList.add("mw-ui-menu-current-page")}),r}},{key:"_renderSection",value:function(e){var t=this,r=this._$createElement("h3");r.innerText=e[0];var n=[this._$wrapElement("li",r)].concat((0,a.default)(e[1].map(function(e){var r=e.split("|"),n=(0,c.default)(r,2),o=n[0],i=n[1],s=i.replace(/\s+/gi,"-"),a="icon-"+s;return t._$menuLinkItem([[i,o,[a]]])}))),o=this._$createElement("ul");return n.forEach(function(e){return o.appendChild(e)}),o}},{key:"_renderUserSection",value:function(){var e=this,t=this._dependencies.userState.getUserFlags(),r=this._$createElement("ul");r.classList.add("mw-ui-menu-user-section");var n=this._$createElement("h3");return n.innerText="User actions",r.appendChild(this._$wrapElement("li",n)),this._menuUserLinks.map(function(n){var o=(0,c.default)(n,2),i=o[0],s=o[1];return t&i&&r.appendChild(e._$menuLinkItem(s))}),[r]}},{key:"_renderMenu",value:function(e){var t=this,r=e.map(function(e){return t._renderSection(e)}),n=this._dom.document.createElement("div");n.classList.add("menu","view-border-box","toggle-list__list"),n.id="mw-mf-page-left";var o=this._renderUserSection();return[].concat((0,a.default)(r),(0,a.default)(o)).forEach(function(e){return n.appendChild(e)}),n}},{key:"_processMenu",value:function(e){var t=e.err,r=e.data;if(t)return this._handleError(t);p(r,"Data is not trueish"),p(r.constructor===String,"Data is not a string");var n=this._parseMenu(r),o=n.sections,i=this._renderMenu(o),s=this._dom.menu,a=s.parentNode;a.removeChild(s),a.appendChild(i)}},{key:"_retrieveSidebar",value:function(){var e=this;++this._retryCount,this._genericXhr("GET",this._menuPath,null,!1,function(t,r){return e._initSidebar({err:t,data:r})})}},{key:"_setup",value:function(){this._retrieveSidebar()}}],[{key:"initialize",value:function(t){var r=t.dom,n=t.userState,o=new e(r,{userState:n});return o._setup(),o}}]),e}(),v=function(){function e(){(0,l.default)(this,e),this._userName=null,this._userFlags=0,this._pageName=null,this._flags={ANON:1,USER:2,ADMIN:4,SYSOP:8},this._populateUser(),this._populatePage()}return(0,d.default)(e,[{key:"_hasMWMetadata",value:function(){return"mw"in window&&"config"in window.mw}},{key:"_populateUser",value:function(){if(this._hasMWMetadata()){this._userName=window.mw.config.get("wgUserName"),null===this._userName&&(this._userFlags^=this._flags.ANON);var e=new i.default(window.mw.config.get("wgUserGroups"));e.has("user")&&(this._userFlags^=this._flags.USER),e.has("admin")&&(this._userFlags^=this._flags.ADMIN),e.has("sysop")&&(this._userFlags^=this._flags.SYSOP)}}},{key:"getUserName",value:function(){return this._userName}},{key:"getUserFlags",value:function(){return this._userFlags}},{key:"isLoggedIn",value:function(){return null!==this._userName}},{key:"_populatePage",value:function(){this._hasMWMetadata()&&(this._pageName=window.mw.config.get("wgPageName"))}},{key:"getPageName",value:function(){return this._pageName}}]),e}(),m=function(){return document.querySelector(".navigation-drawer .menu")},y=function(){var e=m(),t=new v;h.initialize({dom:{document:document,menu:e},userState:t})},b=function(){var e=0;!function t(){if(20!==e)return null===m()?(++e,void setTimeout(t,20*e)):void y()}()};"complete"===document.readyState?b():window.addEventListener("load",b)},{"babel-runtime/core-js/set":6,"babel-runtime/helpers/classCallCheck":7,"babel-runtime/helpers/createClass":8,"babel-runtime/helpers/slicedToArray":9,"babel-runtime/helpers/toConsumableArray":10}],2:[function(e,t,r){t.exports={default:e("core-js/library/fn/array/from"),__esModule:!0}},{"core-js/library/fn/array/from":11}],3:[function(e,t,r){t.exports={default:e("core-js/library/fn/get-iterator"),__esModule:!0}},{"core-js/library/fn/get-iterator":12}],4:[function(e,t,r){t.exports={default:e("core-js/library/fn/is-iterable"),__esModule:!0}},{"core-js/library/fn/is-iterable":13}],5:[function(e,t,r){t.exports={default:e("core-js/library/fn/object/define-property"),__esModule:!0}},{"core-js/library/fn/object/define-property":14}],6:[function(e,t,r){t.exports={default:e("core-js/library/fn/set"),__esModule:!0}},{"core-js/library/fn/set":15}],7:[function(e,t,r){"use strict";r.__esModule=!0,r.default=function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}},{}],8:[function(e,t,r){"use strict";r.__esModule=!0;var n=e("../core-js/object/define-property"),o=function(e){return e&&e.__esModule?e:{default:e}}(n);r.default=function(){function e(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),(0,o.default)(e,n.key,n)}}return function(t,r,n){return r&&e(t.prototype,r),n&&e(t,n),t}}()},{"../core-js/object/define-property":5}],9:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{default:e}}r.__esModule=!0;var o=e("../core-js/is-iterable"),i=n(o),s=e("../core-js/get-iterator"),a=n(s);r.default=function(){function e(e,t){var r=[],n=!0,o=!1,i=void 0;try{for(var s,u=(0,a.default)(e);!(n=(s=u.next()).done)&&(r.push(s.value),!t||r.length!==t);n=!0);}catch(e){o=!0,i=e}finally{try{!n&&u.return&&u.return()}finally{if(o)throw i}}return r}return function(t,r){if(Array.isArray(t))return t;if((0,i.default)(Object(t)))return e(t,r);throw new TypeError("Invalid attempt to destructure non-iterable instance")}}()},{"../core-js/get-iterator":3,"../core-js/is-iterable":4}],10:[function(e,t,r){"use strict";r.__esModule=!0;var n=e("../core-js/array/from"),o=function(e){return e&&e.__esModule?e:{default:e}}(n);r.default=function(e){if(Array.isArray(e)){for(var t=0,r=Array(e.length);t<e.length;t++)r[t]=e[t];return r}return(0,o.default)(e)}},{"../core-js/array/from":2}],11:[function(e,t,r){e("../../modules/es6.string.iterator"),e("../../modules/es6.array.from"),t.exports=e("../../modules/_core").Array.from},{"../../modules/_core":30,"../../modules/es6.array.from":85,"../../modules/es6.string.iterator":90}],12:[function(e,t,r){e("../modules/web.dom.iterable"),e("../modules/es6.string.iterator"),t.exports=e("../modules/core.get-iterator")},{"../modules/core.get-iterator":83,"../modules/es6.string.iterator":90,"../modules/web.dom.iterable":94}],13:[function(e,t,r){e("../modules/web.dom.iterable"),e("../modules/es6.string.iterator"),t.exports=e("../modules/core.is-iterable")},{"../modules/core.is-iterable":84,"../modules/es6.string.iterator":90,"../modules/web.dom.iterable":94}],14:[function(e,t,r){e("../../modules/es6.object.define-property");var n=e("../../modules/_core").Object;t.exports=function(e,t,r){return n.defineProperty(e,t,r)}},{"../../modules/_core":30,"../../modules/es6.object.define-property":87}],15:[function(e,t,r){e("../modules/es6.object.to-string"),e("../modules/es6.string.iterator"),e("../modules/web.dom.iterable"),e("../modules/es6.set"),e("../modules/es7.set.to-json"),e("../modules/es7.set.of"),e("../modules/es7.set.from"),t.exports=e("../modules/_core").Set},{"../modules/_core":30,"../modules/es6.object.to-string":88,"../modules/es6.set":89,"../modules/es6.string.iterator":90,"../modules/es7.set.from":91,"../modules/es7.set.of":92,"../modules/es7.set.to-json":93,"../modules/web.dom.iterable":94}],16:[function(e,t,r){t.exports=function(e){if("function"!=typeof e)throw TypeError(e+" is not a function!");return e}},{}],17:[function(e,t,r){t.exports=function(){}},{}],18:[function(e,t,r){t.exports=function(e,t,r,n){if(!(e instanceof t)||void 0!==n&&n in e)throw TypeError(r+": incorrect invocation!");return e}},{}],19:[function(e,t,r){var n=e("./_is-object");t.exports=function(e){if(!n(e))throw TypeError(e+" is not an object!");return e}},{"./_is-object":48}],20:[function(e,t,r){var n=e("./_for-of");t.exports=function(e,t){var r=[];return n(e,!1,r.push,r,t),r}},{"./_for-of":39}],21:[function(e,t,r){var n=e("./_to-iobject"),o=e("./_to-length"),i=e("./_to-absolute-index");t.exports=function(e){return function(t,r,s){var a,u=n(t),c=o(u.length),f=i(s,c);if(e&&r!=r){for(;c>f;)if((a=u[f++])!=a)return!0}else for(;c>f;f++)if((e||f in u)&&u[f]===r)return e||f||0;return!e&&-1}}},{"./_to-absolute-index":73,"./_to-iobject":75,"./_to-length":76}],22:[function(e,t,r){var n=e("./_ctx"),o=e("./_iobject"),i=e("./_to-object"),s=e("./_to-length"),a=e("./_array-species-create");t.exports=function(e,t){var r=1==e,u=2==e,c=3==e,f=4==e,l=6==e,_=5==e||l,d=t||a;return function(t,a,p){for(var h,v,m=i(t),y=o(m),b=n(a,p,3),g=s(y.length),w=0,x=r?d(t,g):u?d(t,0):void 0;g>w;w++)if((_||w in y)&&(h=y[w],v=b(h,w,m),e))if(r)x[w]=v;else if(v)switch(e){case 3:return!0;case 5:return h;case 6:return w;case 2:x.push(h)}else if(f)return!1;return l?-1:c||f?f:x}}},{"./_array-species-create":24,"./_ctx":32,"./_iobject":45,"./_to-length":76,"./_to-object":77}],23:[function(e,t,r){var n=e("./_is-object"),o=e("./_is-array"),i=e("./_wks")("species");t.exports=function(e){var t;return o(e)&&(t=e.constructor,"function"!=typeof t||t!==Array&&!o(t.prototype)||(t=void 0),n(t)&&null===(t=t[i])&&(t=void 0)),void 0===t?Array:t}},{"./_is-array":47,"./_is-object":48,"./_wks":81}],24:[function(e,t,r){var n=e("./_array-species-constructor");t.exports=function(e,t){return new(n(e))(t)}},{"./_array-species-constructor":23}],25:[function(e,t,r){var n=e("./_cof"),o=e("./_wks")("toStringTag"),i="Arguments"==n(function(){return arguments}()),s=function(e,t){try{return e[t]}catch(e){}};t.exports=function(e){var t,r,a;return void 0===e?"Undefined":null===e?"Null":"string"==typeof(r=s(t=Object(e),o))?r:i?n(t):"Object"==(a=n(t))&&"function"==typeof t.callee?"Arguments":a}},{"./_cof":26,"./_wks":81}],26:[function(e,t,r){var n={}.toString;t.exports=function(e){return n.call(e).slice(8,-1)}},{}],27:[function(e,t,r){"use strict";var n=e("./_object-dp").f,o=e("./_object-create"),i=e("./_redefine-all"),s=e("./_ctx"),a=e("./_an-instance"),u=e("./_for-of"),c=e("./_iter-define"),f=e("./_iter-step"),l=e("./_set-species"),_=e("./_descriptors"),d=e("./_meta").fastKey,p=e("./_validate-collection"),h=_?"_s":"size",v=function(e,t){var r,n=d(t);if("F"!==n)return e._i[n];for(r=e._f;r;r=r.n)if(r.k==t)return r};t.exports={getConstructor:function(e,t,r,c){var f=e(function(e,n){a(e,f,t,"_i"),e._t=t,e._i=o(null),e._f=void 0,e._l=void 0,e[h]=0,void 0!=n&&u(n,r,e[c],e)});return i(f.prototype,{clear:function(){for(var e=p(this,t),r=e._i,n=e._f;n;n=n.n)n.r=!0,n.p&&(n.p=n.p.n=void 0),delete r[n.i];e._f=e._l=void 0,e[h]=0},delete:function(e){var r=p(this,t),n=v(r,e);if(n){var o=n.n,i=n.p;delete r._i[n.i],n.r=!0,i&&(i.n=o),o&&(o.p=i),r._f==n&&(r._f=o),r._l==n&&(r._l=i),r[h]--}return!!n},forEach:function(e){p(this,t);for(var r,n=s(e,arguments.length>1?arguments[1]:void 0,3);r=r?r.n:this._f;)for(n(r.v,r.k,this);r&&r.r;)r=r.p},has:function(e){return!!v(p(this,t),e)}}),_&&n(f.prototype,"size",{get:function(){return p(this,t)[h]}}),f},def:function(e,t,r){var n,o,i=v(e,t);return i?i.v=r:(e._l=i={i:o=d(t,!0),k:t,v:r,p:n=e._l,n:void 0,r:!1},e._f||(e._f=i),n&&(n.n=i),e[h]++,"F"!==o&&(e._i[o]=i)),e},getEntry:v,setStrong:function(e,t,r){c(e,t,function(e,r){this._t=p(e,t),this._k=r,this._l=void 0},function(){for(var e=this,t=e._k,r=e._l;r&&r.r;)r=r.p;return e._t&&(e._l=r=r?r.n:e._t._f)?"keys"==t?f(0,r.k):"values"==t?f(0,r.v):f(0,[r.k,r.v]):(e._t=void 0,f(1))},r?"entries":"values",!r,!0),l(t)}}},{"./_an-instance":18,"./_ctx":32,"./_descriptors":34,"./_for-of":39,"./_iter-define":51,"./_iter-step":53,"./_meta":56,"./_object-create":57,"./_object-dp":58,"./_redefine-all":64,"./_set-species":68,"./_validate-collection":80}],28:[function(e,t,r){var n=e("./_classof"),o=e("./_array-from-iterable");t.exports=function(e){return function(){if(n(this)!=e)throw TypeError(e+"#toJSON isn't generic");return o(this)}}},{"./_array-from-iterable":20,"./_classof":25}],29:[function(e,t,r){"use strict";var n=e("./_global"),o=e("./_export"),i=e("./_meta"),s=e("./_fails"),a=e("./_hide"),u=e("./_redefine-all"),c=e("./_for-of"),f=e("./_an-instance"),l=e("./_is-object"),_=e("./_set-to-string-tag"),d=e("./_object-dp").f,p=e("./_array-methods")(0),h=e("./_descriptors");t.exports=function(e,t,r,v,m,y){var b=n[e],g=b,w=m?"set":"add",x=g&&g.prototype,j={};return h&&"function"==typeof g&&(y||x.forEach&&!s(function(){(new g).entries().next()}))?(g=t(function(t,r){f(t,g,e,"_c"),t._c=new b,void 0!=r&&c(r,m,t[w],t)}),p("add,clear,delete,forEach,get,has,set,keys,values,entries,toJSON".split(","),function(e){var t="add"==e||"set"==e;e in x&&(!y||"clear"!=e)&&a(g.prototype,e,function(r,n){if(f(this,g,e),!t&&y&&!l(r))return"get"==e&&void 0;var o=this._c[e](0===r?0:r,n);return t?this:o})}),y||d(g.prototype,"size",{get:function(){return this._c.size}})):(g=v.getConstructor(t,e,m,w),u(g.prototype,r),i.NEED=!0),_(g,e),j[e]=g,o(o.G+o.W+o.F,j),y||v.setStrong(g,e,m),g}},{"./_an-instance":18,"./_array-methods":22,"./_descriptors":34,"./_export":37,"./_fails":38,"./_for-of":39,"./_global":40,"./_hide":42,"./_is-object":48,"./_meta":56,"./_object-dp":58,"./_redefine-all":64,"./_set-to-string-tag":69}],30:[function(e,t,r){var n=t.exports={version:"2.6.12"};"number"==typeof __e&&(__e=n)},{}],31:[function(e,t,r){"use strict";var n=e("./_object-dp"),o=e("./_property-desc");t.exports=function(e,t,r){t in e?n.f(e,t,o(0,r)):e[t]=r}},{"./_object-dp":58,"./_property-desc":63}],32:[function(e,t,r){var n=e("./_a-function");t.exports=function(e,t,r){if(n(e),void 0===t)return e;switch(r){case 1:return function(r){return e.call(t,r)};case 2:return function(r,n){return e.call(t,r,n)};case 3:return function(r,n,o){return e.call(t,r,n,o)}}return function(){return e.apply(t,arguments)}}},{"./_a-function":16}],33:[function(e,t,r){t.exports=function(e){if(void 0==e)throw TypeError("Can't call method on  "+e);return e}},{}],34:[function(e,t,r){t.exports=!e("./_fails")(function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a})},{"./_fails":38}],35:[function(e,t,r){var n=e("./_is-object"),o=e("./_global").document,i=n(o)&&n(o.createElement);t.exports=function(e){return i?o.createElement(e):{}}},{"./_global":40,"./_is-object":48}],36:[function(e,t,r){t.exports="constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf".split(",")},{}],37:[function(e,t,r){var n=e("./_global"),o=e("./_core"),i=e("./_ctx"),s=e("./_hide"),a=e("./_has"),u=function(e,t,r){var c,f,l,_=e&u.F,d=e&u.G,p=e&u.S,h=e&u.P,v=e&u.B,m=e&u.W,y=d?o:o[t]||(o[t]={}),b=y.prototype,g=d?n:p?n[t]:(n[t]||{}).prototype;d&&(r=t);for(c in r)(f=!_&&g&&void 0!==g[c])&&a(y,c)||(l=f?g[c]:r[c],y[c]=d&&"function"!=typeof g[c]?r[c]:v&&f?i(l,n):m&&g[c]==l?function(e){var t=function(t,r,n){if(this instanceof e){switch(arguments.length){case 0:return new e;case 1:return new e(t);case 2:return new e(t,r)}return new e(t,r,n)}return e.apply(this,arguments)};return t.prototype=e.prototype,t}(l):h&&"function"==typeof l?i(Function.call,l):l,h&&((y.virtual||(y.virtual={}))[c]=l,e&u.R&&b&&!b[c]&&s(b,c,l)))};u.F=1,u.G=2,u.S=4,u.P=8,u.B=16,u.W=32,u.U=64,u.R=128,t.exports=u},{"./_core":30,"./_ctx":32,"./_global":40,"./_has":41,"./_hide":42}],38:[function(e,t,r){t.exports=function(e){try{return!!e()}catch(e){return!0}}},{}],39:[function(e,t,r){var n=e("./_ctx"),o=e("./_iter-call"),i=e("./_is-array-iter"),s=e("./_an-object"),a=e("./_to-length"),u=e("./core.get-iterator-method"),c={},f={},r=t.exports=function(e,t,r,l,_){var d,p,h,v,m=_?function(){return e}:u(e),y=n(r,l,t?2:1),b=0;if("function"!=typeof m)throw TypeError(e+" is not iterable!");if(i(m)){for(d=a(e.length);d>b;b++)if((v=t?y(s(p=e[b])[0],p[1]):y(e[b]))===c||v===f)return v}else for(h=m.call(e);!(p=h.next()).done;)if((v=o(h,y,p.value,t))===c||v===f)return v};r.BREAK=c,r.RETURN=f},{"./_an-object":19,"./_ctx":32,"./_is-array-iter":46,"./_iter-call":49,"./_to-length":76,"./core.get-iterator-method":82}],40:[function(e,t,r){var n=t.exports="undefined"!=typeof window&&window.Math==Math?window:"undefined"!=typeof self&&self.Math==Math?self:Function("return this")();"number"==typeof __g&&(__g=n)},{}],41:[function(e,t,r){var n={}.hasOwnProperty;t.exports=function(e,t){return n.call(e,t)}},{}],42:[function(e,t,r){var n=e("./_object-dp"),o=e("./_property-desc");t.exports=e("./_descriptors")?function(e,t,r){return n.f(e,t,o(1,r))}:function(e,t,r){return e[t]=r,e}},{"./_descriptors":34,"./_object-dp":58,"./_property-desc":63}],43:[function(e,t,r){var n=e("./_global").document;t.exports=n&&n.documentElement},{"./_global":40}],44:[function(e,t,r){t.exports=!e("./_descriptors")&&!e("./_fails")(function(){return 7!=Object.defineProperty(e("./_dom-create")("div"),"a",{get:function(){return 7}}).a})},{"./_descriptors":34,"./_dom-create":35,"./_fails":38}],45:[function(e,t,r){var n=e("./_cof");t.exports=Object("z").propertyIsEnumerable(0)?Object:function(e){return"String"==n(e)?e.split(""):Object(e)}},{"./_cof":26}],46:[function(e,t,r){var n=e("./_iterators"),o=e("./_wks")("iterator"),i=Array.prototype;t.exports=function(e){return void 0!==e&&(n.Array===e||i[o]===e)}},{"./_iterators":54,"./_wks":81}],47:[function(e,t,r){var n=e("./_cof");t.exports=Array.isArray||function(e){return"Array"==n(e)}},{"./_cof":26}],48:[function(e,t,r){t.exports=function(e){return"object"==typeof e?null!==e:"function"==typeof e}},{}],49:[function(e,t,r){var n=e("./_an-object");t.exports=function(e,t,r,o){try{return o?t(n(r)[0],r[1]):t(r)}catch(t){var i=e.return;throw void 0!==i&&n(i.call(e)),t}}},{"./_an-object":19}],50:[function(e,t,r){"use strict";var n=e("./_object-create"),o=e("./_property-desc"),i=e("./_set-to-string-tag"),s={};e("./_hide")(s,e("./_wks")("iterator"),function(){return this}),t.exports=function(e,t,r){e.prototype=n(s,{next:o(1,r)}),i(e,t+" Iterator")}},{"./_hide":42,"./_object-create":57,"./_property-desc":63,"./_set-to-string-tag":69,"./_wks":81}],51:[function(e,t,r){"use strict";var n=e("./_library"),o=e("./_export"),i=e("./_redefine"),s=e("./_hide"),a=e("./_iterators"),u=e("./_iter-create"),c=e("./_set-to-string-tag"),f=e("./_object-gpo"),l=e("./_wks")("iterator"),_=!([].keys&&"next"in[].keys()),d=function(){return this};t.exports=function(e,t,r,p,h,v,m){u(r,t,p);var y,b,g,w=function(e){if(!_&&e in S)return S[e];switch(e){case"keys":case"values":return function(){return new r(this,e)}}return function(){return new r(this,e)}},x=t+" Iterator",j="values"==h,k=!1,S=e.prototype,E=S[l]||S["@@iterator"]||h&&S[h],O=E||w(h),M=h?j?w("entries"):O:void 0,L="Array"==t?S.entries||E:E;if(L&&(g=f(L.call(new e)))!==Object.prototype&&g.next&&(c(g,x,!0),n||"function"==typeof g[l]||s(g,l,d)),j&&E&&"values"!==E.name&&(k=!0,O=function(){return E.call(this)}),n&&!m||!_&&!k&&S[l]||s(S,l,O),a[t]=O,a[x]=d,h)if(y={values:j?O:w("values"),keys:v?O:w("keys"),entries:M},m)for(b in y)b in S||i(S,b,y[b]);else o(o.P+o.F*(_||k),t,y);return y}},{"./_export":37,"./_hide":42,"./_iter-create":50,"./_iterators":54,"./_library":55,"./_object-gpo":60,"./_redefine":65,"./_set-to-string-tag":69,"./_wks":81}],52:[function(e,t,r){var n=e("./_wks")("iterator"),o=!1;try{var i=[7][n]();i.return=function(){o=!0},Array.from(i,function(){throw 2})}catch(e){}t.exports=function(e,t){if(!t&&!o)return!1;var r=!1;try{var i=[7],s=i[n]();s.next=function(){return{done:r=!0}},i[n]=function(){return s},e(i)}catch(e){}return r}},{"./_wks":81}],53:[function(e,t,r){t.exports=function(e,t){return{value:t,done:!!e}}},{}],54:[function(e,t,r){t.exports={}},{}],55:[function(e,t,r){t.exports=!0},{}],56:[function(e,t,r){var n=e("./_uid")("meta"),o=e("./_is-object"),i=e("./_has"),s=e("./_object-dp").f,a=0,u=Object.isExtensible||function(){return!0},c=!e("./_fails")(function(){return u(Object.preventExtensions({}))}),f=function(e){s(e,n,{value:{i:"O"+ ++a,w:{}}})},l=function(e,t){if(!o(e))return"symbol"==typeof e?e:("string"==typeof e?"S":"P")+e;if(!i(e,n)){if(!u(e))return"F";if(!t)return"E";f(e)}return e[n].i},_=function(e,t){if(!i(e,n)){if(!u(e))return!0;if(!t)return!1;f(e)}return e[n].w},d=function(e){return c&&p.NEED&&u(e)&&!i(e,n)&&f(e),e},p=t.exports={KEY:n,NEED:!1,fastKey:l,getWeak:_,onFreeze:d}},{"./_fails":38,"./_has":41,"./_is-object":48,"./_object-dp":58,"./_uid":79}],57:[function(e,t,r){var n=e("./_an-object"),o=e("./_object-dps"),i=e("./_enum-bug-keys"),s=e("./_shared-key")("IE_PROTO"),a=function(){},u=function(){var t,r=e("./_dom-create")("iframe"),n=i.length;for(r.style.display="none",e("./_html").appendChild(r),r.src="javascript:",t=r.contentWindow.document,t.open(),t.write("<script>document.F=Object<\/script>"),t.close(),u=t.F;n--;)delete u.prototype[i[n]];return u()};t.exports=Object.create||function(e,t){var r;return null!==e?(a.prototype=n(e),r=new a,a.prototype=null,r[s]=e):r=u(),void 0===t?r:o(r,t)}},{"./_an-object":19,"./_dom-create":35,"./_enum-bug-keys":36,"./_html":43,"./_object-dps":59,"./_shared-key":70}],58:[function(e,t,r){var n=e("./_an-object"),o=e("./_ie8-dom-define"),i=e("./_to-primitive"),s=Object.defineProperty;r.f=e("./_descriptors")?Object.defineProperty:function(e,t,r){if(n(e),t=i(t,!0),n(r),o)try{return s(e,t,r)}catch(e){}if("get"in r||"set"in r)throw TypeError("Accessors not supported!");return"value"in r&&(e[t]=r.value),e}},{"./_an-object":19,"./_descriptors":34,"./_ie8-dom-define":44,"./_to-primitive":78}],59:[function(e,t,r){var n=e("./_object-dp"),o=e("./_an-object"),i=e("./_object-keys");t.exports=e("./_descriptors")?Object.defineProperties:function(e,t){o(e);for(var r,s=i(t),a=s.length,u=0;a>u;)n.f(e,r=s[u++],t[r]);return e}},{"./_an-object":19,"./_descriptors":34,"./_object-dp":58,"./_object-keys":62}],60:[function(e,t,r){var n=e("./_has"),o=e("./_to-object"),i=e("./_shared-key")("IE_PROTO"),s=Object.prototype;t.exports=Object.getPrototypeOf||function(e){return e=o(e),n(e,i)?e[i]:"function"==typeof e.constructor&&e instanceof e.constructor?e.constructor.prototype:e instanceof Object?s:null}},{"./_has":41,"./_shared-key":70,"./_to-object":77}],61:[function(e,t,r){var n=e("./_has"),o=e("./_to-iobject"),i=e("./_array-includes")(!1),s=e("./_shared-key")("IE_PROTO");t.exports=function(e,t){var r,a=o(e),u=0,c=[];for(r in a)r!=s&&n(a,r)&&c.push(r);for(;t.length>u;)n(a,r=t[u++])&&(~i(c,r)||c.push(r));return c}},{"./_array-includes":21,"./_has":41,"./_shared-key":70,"./_to-iobject":75}],62:[function(e,t,r){var n=e("./_object-keys-internal"),o=e("./_enum-bug-keys");t.exports=Object.keys||function(e){return n(e,o)}},{"./_enum-bug-keys":36,"./_object-keys-internal":61}],63:[function(e,t,r){t.exports=function(e,t){return{enumerable:!(1&e),configurable:!(2&e),writable:!(4&e),value:t}}},{}],64:[function(e,t,r){var n=e("./_hide");t.exports=function(e,t,r){for(var o in t)r&&e[o]?e[o]=t[o]:n(e,o,t[o]);return e}},{"./_hide":42}],65:[function(e,t,r){t.exports=e("./_hide")},{"./_hide":42}],66:[function(e,t,r){"use strict";var n=e("./_export"),o=e("./_a-function"),i=e("./_ctx"),s=e("./_for-of");t.exports=function(e){n(n.S,e,{from:function(e){var t,r,n,a,u=arguments[1];return o(this),t=void 0!==u,t&&o(u),void 0==e?new this:(r=[],t?(n=0,a=i(u,arguments[2],2),s(e,!1,function(e){r.push(a(e,n++))})):s(e,!1,r.push,r),new this(r))}})}},{"./_a-function":16,"./_ctx":32,"./_export":37,"./_for-of":39}],67:[function(e,t,r){"use strict";var n=e("./_export");t.exports=function(e){n(n.S,e,{of:function(){for(var e=arguments.length,t=new Array(e);e--;)t[e]=arguments[e];return new this(t)}})}},{"./_export":37}],68:[function(e,t,r){"use strict";var n=e("./_global"),o=e("./_core"),i=e("./_object-dp"),s=e("./_descriptors"),a=e("./_wks")("species");t.exports=function(e){var t="function"==typeof o[e]?o[e]:n[e];s&&t&&!t[a]&&i.f(t,a,{configurable:!0,get:function(){return this}})}},{"./_core":30,"./_descriptors":34,"./_global":40,"./_object-dp":58,"./_wks":81}],69:[function(e,t,r){var n=e("./_object-dp").f,o=e("./_has"),i=e("./_wks")("toStringTag");t.exports=function(e,t,r){e&&!o(e=r?e:e.prototype,i)&&n(e,i,{configurable:!0,value:t})}},{"./_has":41,"./_object-dp":58,"./_wks":81}],70:[function(e,t,r){var n=e("./_shared")("keys"),o=e("./_uid");t.exports=function(e){return n[e]||(n[e]=o(e))}},{"./_shared":71,"./_uid":79}],71:[function(e,t,r){var n=e("./_core"),o=e("./_global"),i=o["__core-js_shared__"]||(o["__core-js_shared__"]={});(t.exports=function(e,t){return i[e]||(i[e]=void 0!==t?t:{})})("versions",[]).push({version:n.version,mode:e("./_library")?"pure":"global",copyright:"© 2020 Denis Pushkarev (zloirock.ru)"})},{"./_core":30,"./_global":40,"./_library":55}],72:[function(e,t,r){var n=e("./_to-integer"),o=e("./_defined");t.exports=function(e){return function(t,r){var i,s,a=String(o(t)),u=n(r),c=a.length;return u<0||u>=c?e?"":void 0:(i=a.charCodeAt(u),i<55296||i>56319||u+1===c||(s=a.charCodeAt(u+1))<56320||s>57343?e?a.charAt(u):i:e?a.slice(u,u+2):s-56320+(i-55296<<10)+65536)}}},{"./_defined":33,"./_to-integer":74}],73:[function(e,t,r){var n=e("./_to-integer"),o=Math.max,i=Math.min;t.exports=function(e,t){return e=n(e),e<0?o(e+t,0):i(e,t)}},{"./_to-integer":74}],74:[function(e,t,r){var n=Math.ceil,o=Math.floor;t.exports=function(e){return isNaN(e=+e)?0:(e>0?o:n)(e)}},{}],75:[function(e,t,r){var n=e("./_iobject"),o=e("./_defined");t.exports=function(e){return n(o(e))}},{"./_defined":33,"./_iobject":45}],76:[function(e,t,r){var n=e("./_to-integer"),o=Math.min;t.exports=function(e){return e>0?o(n(e),9007199254740991):0}},{"./_to-integer":74}],77:[function(e,t,r){var n=e("./_defined");t.exports=function(e){return Object(n(e))}},{"./_defined":33}],78:[function(e,t,r){var n=e("./_is-object");t.exports=function(e,t){if(!n(e))return e;var r,o;if(t&&"function"==typeof(r=e.toString)&&!n(o=r.call(e)))return o;if("function"==typeof(r=e.valueOf)&&!n(o=r.call(e)))return o;if(!t&&"function"==typeof(r=e.toString)&&!n(o=r.call(e)))return o;throw TypeError("Can't convert object to primitive value")}},{"./_is-object":48}],79:[function(e,t,r){var n=0,o=Math.random();t.exports=function(e){return"Symbol(".concat(void 0===e?"":e,")_",(++n+o).toString(36))}},{}],80:[function(e,t,r){var n=e("./_is-object");t.exports=function(e,t){if(!n(e)||e._t!==t)throw TypeError("Incompatible receiver, "+t+" required!");return e}},{"./_is-object":48}],81:[function(e,t,r){var n=e("./_shared")("wks"),o=e("./_uid"),i=e("./_global").Symbol,s="function"==typeof i;(t.exports=function(e){return n[e]||(n[e]=s&&i[e]||(s?i:o)("Symbol."+e))}).store=n},{"./_global":40,"./_shared":71,"./_uid":79}],82:[function(e,t,r){var n=e("./_classof"),o=e("./_wks")("iterator"),i=e("./_iterators");t.exports=e("./_core").getIteratorMethod=function(e){if(void 0!=e)return e[o]||e["@@iterator"]||i[n(e)]}},{"./_classof":25,"./_core":30,"./_iterators":54,"./_wks":81}],83:[function(e,t,r){var n=e("./_an-object"),o=e("./core.get-iterator-method");t.exports=e("./_core").getIterator=function(e){var t=o(e);if("function"!=typeof t)throw TypeError(e+" is not iterable!");return n(t.call(e))}},{"./_an-object":19,"./_core":30,"./core.get-iterator-method":82}],84:[function(e,t,r){var n=e("./_classof"),o=e("./_wks")("iterator"),i=e("./_iterators");t.exports=e("./_core").isIterable=function(e){var t=Object(e);return void 0!==t[o]||"@@iterator"in t||i.hasOwnProperty(n(t))}},{"./_classof":25,"./_core":30,"./_iterators":54,"./_wks":81}],85:[function(e,t,r){"use strict";var n=e("./_ctx"),o=e("./_export"),i=e("./_to-object"),s=e("./_iter-call"),a=e("./_is-array-iter"),u=e("./_to-length"),c=e("./_create-property"),f=e("./core.get-iterator-method");o(o.S+o.F*!e("./_iter-detect")(function(e){Array.from(e)}),"Array",{from:function(e){var t,r,o,l,_=i(e),d="function"==typeof this?this:Array,p=arguments.length,h=p>1?arguments[1]:void 0,v=void 0!==h,m=0,y=f(_);if(v&&(h=n(h,p>2?arguments[2]:void 0,2)),void 0==y||d==Array&&a(y))for(t=u(_.length),r=new d(t);t>m;m++)c(r,m,v?h(_[m],m):_[m]);else for(l=y.call(_),r=new d;!(o=l.next()).done;m++)c(r,m,v?s(l,h,[o.value,m],!0):o.value);return r.length=m,r}})},{
"./_create-property":31,"./_ctx":32,"./_export":37,"./_is-array-iter":46,"./_iter-call":49,"./_iter-detect":52,"./_to-length":76,"./_to-object":77,"./core.get-iterator-method":82}],86:[function(e,t,r){"use strict";var n=e("./_add-to-unscopables"),o=e("./_iter-step"),i=e("./_iterators"),s=e("./_to-iobject");t.exports=e("./_iter-define")(Array,"Array",function(e,t){this._t=s(e),this._i=0,this._k=t},function(){var e=this._t,t=this._k,r=this._i++;return!e||r>=e.length?(this._t=void 0,o(1)):"keys"==t?o(0,r):"values"==t?o(0,e[r]):o(0,[r,e[r]])},"values"),i.Arguments=i.Array,n("keys"),n("values"),n("entries")},{"./_add-to-unscopables":17,"./_iter-define":51,"./_iter-step":53,"./_iterators":54,"./_to-iobject":75}],87:[function(e,t,r){var n=e("./_export");n(n.S+n.F*!e("./_descriptors"),"Object",{defineProperty:e("./_object-dp").f})},{"./_descriptors":34,"./_export":37,"./_object-dp":58}],88:[function(e,t,r){},{}],89:[function(e,t,r){"use strict";var n=e("./_collection-strong"),o=e("./_validate-collection");t.exports=e("./_collection")("Set",function(e){return function(){return e(this,arguments.length>0?arguments[0]:void 0)}},{add:function(e){return n.def(o(this,"Set"),e=0===e?0:e,e)}},n)},{"./_collection":29,"./_collection-strong":27,"./_validate-collection":80}],90:[function(e,t,r){"use strict";var n=e("./_string-at")(!0);e("./_iter-define")(String,"String",function(e){this._t=String(e),this._i=0},function(){var e,t=this._t,r=this._i;return r>=t.length?{value:void 0,done:!0}:(e=n(t,r),this._i+=e.length,{value:e,done:!1})})},{"./_iter-define":51,"./_string-at":72}],91:[function(e,t,r){e("./_set-collection-from")("Set")},{"./_set-collection-from":66}],92:[function(e,t,r){e("./_set-collection-of")("Set")},{"./_set-collection-of":67}],93:[function(e,t,r){var n=e("./_export");n(n.P+n.R,"Set",{toJSON:e("./_collection-to-json")("Set")})},{"./_collection-to-json":28,"./_export":37}],94:[function(e,t,r){e("./es6.array.iterator");for(var n=e("./_global"),o=e("./_hide"),i=e("./_iterators"),s=e("./_wks")("toStringTag"),a="CSSRuleList,CSSStyleDeclaration,CSSValueList,ClientRectList,DOMRectList,DOMStringList,DOMTokenList,DataTransferItemList,FileList,HTMLAllCollection,HTMLCollection,HTMLFormElement,HTMLSelectElement,MediaList,MimeTypeArray,NamedNodeMap,NodeList,PaintRequestList,Plugin,PluginArray,SVGLengthList,SVGNumberList,SVGPathSegList,SVGPointList,SVGStringList,SVGTransformList,SourceBufferList,StyleSheetList,TextTrackCueList,TextTrackList,TouchList".split(","),u=0;u<a.length;u++){var c=a[u],f=n[c],l=f&&f.prototype;l&&!l[s]&&o(l,s,c),i[c]=i.Array}},{"./_global":40,"./_hide":42,"./_iterators":54,"./_wks":81,"./es6.array.iterator":86}]},{},[1]);
</script>
<?php
		$skinOptions = MediaWikiServices::getInstance()->getService( 'Minerva.SkinOptions' );
		$templateParser = new TemplateParser( __DIR__ );

		$internalBanner = $data[ 'internalBanner' ];
		$preBodyHtml = $data['prebodyhtml'] ?? '';
		$hasHeadingHolder = $internalBanner || $preBodyHtml || isset( $data['pageActionsMenu'] );
		$hasPageActions = $this->hasPageActions( $data['skin']->getContext() );

		// prepare template data
		$templateData = [
			'banners' => $data['banners'],
			'wgScript' => $data['wgScript'],
			'isAnon' => $data['username'] === null,
			'search' => $data['search'],
			'placeholder' => wfMessage( 'mobile-frontend-placeholder' ),
			'headelement' => $data[ 'headelement' ],
			'main-menu-tooltip' => $this->getMsg( 'mobile-frontend-main-menu-button-tooltip' ),
			'siteheading' => $data['footer-site-heading-html'],
			'mainPageURL' => Title::newMainPage()->getLocalURL(),
			'userNavigationLabel' => wfMessage( 'minerva-user-navigation' ),
			// A button when clicked will submit the form
			// This is used so that on tablet devices with JS disabled the search button
			// passes the value of input to the search
			// We avoid using input[type=submit] as these cannot be easily styled as mediawiki ui icons
			// which is problematic in Opera Mini (see T140490)
			'searchButton' => Html::rawElement( 'button', [
				'id' => 'searchIcon',
				'class' => MinervaUI::iconClass(
					'search-base20', 'element', 'skin-minerva-search-trigger', 'wikimedia'
				)
			], wfMessage( 'searchbutton' )->escaped() ),
			'userNotificationsHTML' => $data['userNotificationsHTML'] ?? '',
			'data-main-menu' => $this->getMainMenuData( $data ),
			'hasheadingholder' => $hasHeadingHolder,
			'taglinehtml' => $data['taglinehtml'],
			'internalBanner' => $internalBanner,
			'prebodyhtml' => $preBodyHtml,
			'headinghtml' => $data['headinghtml'] ?? '',
			'postheadinghtml' => $data['postheadinghtml'] ?? '',
			'pageactionshtml' => $hasPageActions ? $this->getPageActionsHtml() : '',
			'userMenuHTML' => $data['userMenuHTML'],
			'subtitle' => $data['subtitle'],
			'contenthtml' => $this->getContentHtml( $data ),
			'secondaryactionshtml' => $this->getSecondaryActionsHtml(),
			'dataAfterContent' => $this->get( 'dataAfterContent' ),
			'footer' => $this->getFooterTemplateData( $data ),
			'isBeta' => $skinOptions->get( SkinOptions::BETA_MODE ),
			'tabs' => $this->showTalkTabs( $hasPageActions, $skinOptions ) &&
					  $skinOptions->get( SkinOptions::TALK_AT_TOP ) ? [
				'items' => array_values( $data['content_navigation']['namespaces'] ),
			] : false,
		];
		// begin rendering
		echo $templateParser->processTemplate( 'skin', $templateData );
		$this->printTrail();
		?>
		</body>
		</html>
		<?php
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	private function hasPageActions( IContextSource $context ) {
		return !$this->isSpecialPage && !$this->isMainPage &&
		   Action::getActionName( $context ) === 'view';
	}

	/**
	 * @param bool $hasPageActions
	 * @param SkinOptions $skinOptions
	 * @return bool
	 */
	private function showTalkTabs( $hasPageActions, SkinOptions $skinOptions ) {
		$hasTalkTabs = $hasPageActions && !$this->isMainPageTalk;
		if ( !$hasTalkTabs && $this->isSpecialPage &&
			 $skinOptions->get( SkinOptions::TABS_ON_SPECIALS ) ) {
			$hasTalkTabs = true;
		}
		return $hasTalkTabs;
	}
}
