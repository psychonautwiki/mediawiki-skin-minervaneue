const ArticlePage = require( '../support/pages/article_page' );
const { iClickTheOverlayCloseButton, iSeeAnOverlay, iDoNotSeeAnOverlay } = require( './common_steps' );

const iHaveNoNotifications = () => {
	ArticlePage.notifications_button_element.waitForVisible();
	// This is somewhat hacky, but we don't want this test making use of
	// Echo's APIs which may change
	browser.execute( '$( function () { $( ".notification-count span" ).hide(); } );' );
};

const iClickOnTheNotificationIcon = () => {
	ArticlePage.waitUntilResourceLoaderModuleReady( 'skins.minerva.notifications' );
	ArticlePage.notifications_button_element.click();
};

const iShouldSeeTheNotificationsOverlay = () => {
	iSeeAnOverlay();
};

const iClickTheNotificationsOverlayCloseButton = () => {
	iClickTheOverlayCloseButton();
};

const iShouldNotSeeTheNotificationsOverlay = () => {
	iDoNotSeeAnOverlay();
};

module.exports = {
	iHaveNoNotifications, iClickOnTheNotificationIcon,
	iShouldSeeTheNotificationsOverlay, iClickTheNotificationsOverlayCloseButton,
	iShouldNotSeeTheNotificationsOverlay
};