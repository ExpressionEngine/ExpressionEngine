/*
 * ES2015 accessible modal window system, using ARIA
 * Website: https://van11y.net/accessible-modal/
 * @license MIT: https://github.com/nico3333fr/van11y-accessible-modal-window-aria/blob/master/LICENSE
 */
(doc => {

	'use strict';

	const MODAL_JS_CLASS = 'eeFrontEdit';
	const MODAL_ID_PREFIX = 'eeFrontEdit-label_modal_';
	const MODAL_CLASS_SUFFIX = 'eeFrontEdit-modal';
	const MODAL_DATA_BACKGROUND_ATTR = 'data-modal-background-click';
	const MODAL_PREFIX_CLASS_ATTR = 'data-modal-prefix-class';
	const MODAL_TEXT_ATTR = 'data-modal-text';
	const MODAL_CONTENT_ID_ATTR = 'data-modal-content-id';
	const MODAL_DESCRIBEDBY_ID_ATTR = 'data-modal-describedby-id';
	const MODAL_TITLE_ATTR = 'data-modal-title';
	const MODAL_FOCUS_TO_ATTR = 'data-modal-focus-toid';
	const MODAL_CLOSE_TEXT_ATTR = 'data-modal-close-text';
	const MODAL_CLOSE_TITLE_ATTR = 'data-modal-close-title';
	const MODAL_CLOSE_IMG_ATTR = 'data-modal-close-img';
	const MODAL_ROLE = 'dialog';

	const MODAL_BUTTON_CLASS_SUFFIX = 'eeFrontEdit-modal-close';
	const MODAL_BUTTON_JS_ID = 'eeFrontEdit-js-modal-close';
	const MODAL_BUTTON_JS_CLASS = 'eeFrontEdit-js-modal-close';
	const MODAL_BUTTON_CONTENT_BACK_ID = 'data-content-back-id';
	const MODAL_BUTTON_FOCUS_BACK_ID = 'data-focus-back';

	const MODAL_WRAPPER_CLASS_SUFFIX = 'eeFrontEdit-modal__wrapper';
	const MODAL_CONTENT_CLASS_SUFFIX = 'eeFrontEdit-modal__content';
	const MODAL_CONTENT_JS_ID = 'eeFrontEdit-js-modal-content';

	const MODAL_CLOSE_IMG_CLASS_SUFFIX = 'eeFrontEdit-modal__closeimg';
	const MODAL_CLOSE_TEXT_CLASS_SUFFIX = 'eeFrontEdit-modal-close__text';

	const MODAL_TITLE_ID = 'eeFrontEdit-modal-title';
	const MODAL_TITLE_CLASS_SUFFIX = 'eeFrontEdit-modal-title';

	const FOCUSABLE_ELEMENTS_STRING = "a[href], area[href], input:not([type='hidden']):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]";
	const WRAPPER_PAGE_JS = 'eeFrontEdit-js-modal-page';

	const MODAL_JS_ID = 'eeFrontEdit-js-modal';

	const MODAL_OVERLAY_ID = 'eeFrontEdit-js-modal-overlay';
	const MODAL_OVERLAY_CLASS_SUFFIX = 'eeFrontEdit-modal-overlay';
	const MODAL_OVERLAY_TXT = 'Close modal';
	const MODAL_OVERLAY_BG_ENABLED_ATTR = 'data-background-click';

	const VISUALLY_HIDDEN_CLASS = 'invisible';
	const NO_SCROLL_CLASS = 'eeFrontEdit-no-scroll';

	const ATTR_ROLE = 'role';
	const ATTR_OPEN = 'open';
	const ATTR_LABELLEDBY = 'aria-labelledby';
	const ATTR_DESCRIBEDBY = 'aria-describedby';
	const ATTR_HIDDEN = 'aria-hidden';
	//const ATTR_MODAL = 'aria-modal="true"';
	const ATTR_HASPOPUP = 'aria-haspopup';
	const ATTR_HASPOPUP_VALUE = 'dialog';



	const findById = id => doc.getElementById(id);

	const addClass = (el, className) => {
		if (el.classList) {
			el.classList.add(className); // IE 10+
		} else {
			el.className += ' ' + className; // IE 8+
		}
	}

	const removeClass = (el, className) => {
		if (el.classList) {
			el.classList.remove(className); // IE 10+
		} else {
			el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' '); // IE 8+
		}
	}

	const hasClass = (el, className) => {
		if (el.classList) {
			return el.classList.contains(className); // IE 10+
		} else {
			return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className); // IE 8+ ?
		}
	}
	/*const wrapInner = (el, wrapper_el) => { // doesn't work on IE/Edge, f…
			while (el.firstChild)
					wrapper_el.append(el.firstChild);
			el.append(wrapper_el);

	}*/
	function wrapInner(parent, wrapper) {
		if (typeof wrapper === "string")
			wrapper = document.createElement(wrapper);

		parent.appendChild(wrapper);

		while (parent.firstChild !== wrapper)
			wrapper.appendChild(parent.firstChild);
	}

	function remove(el) { /* node.remove() is too modern for IE≤11 */
		el.parentNode.removeChild(el);
	}

	/* gets an element el, search if it is child of parent class, returns id of the parent */
	let searchParent = (el, parentClass) => {
		let found = false;
		let parentElement = el.parentNode;
		while (parentElement && found === false) {
			if (hasClass(parentElement, parentClass) === true) {
				found = true;
			} else {
				parentElement = parentElement.parentNode;
			}
		}
		if (found === true) {
			return parentElement.getAttribute('id');
		} else {
			return '';
		}
	}

	/**
	 * Create the template for an overlay
	 * @param  {Object} config
	 * @return {String}
	 */
	const createOverlay = config => {

		let id = MODAL_OVERLAY_ID;
		let overlayText = config.text || MODAL_OVERLAY_TXT;
		let overlayClass = config.prefixClass + MODAL_OVERLAY_CLASS_SUFFIX;
		let overlayBackgroundEnabled = config.backgroundEnabled === 'disabled' ? 'disabled' : 'enabled';

		return `<span
									id="${id}"
									class="${overlayClass}"
									${MODAL_OVERLAY_BG_ENABLED_ATTR}="${overlayBackgroundEnabled}"
									title="${overlayText}"
									>
									<span class="${VISUALLY_HIDDEN_CLASS}">${overlayText}</span>
								</span>`;

	};

	/**
	 * Create the template for a modal
	 * @param  {Object} config
	 * @return {String}
	 */
	const createModal = config => {

		let id = MODAL_JS_ID;
		let modalClassName = config.modalPrefixClass + MODAL_CLASS_SUFFIX;
		let modalClassWrapper = config.modalPrefixClass + MODAL_WRAPPER_CLASS_SUFFIX;
		let buttonCloseClassName = config.modalPrefixClass + MODAL_BUTTON_CLASS_SUFFIX;
		let buttonCloseInner = config.modalCloseImgPath ?
			`<img src="${config.modalCloseImgPath}" alt="${config.modalCloseText}" class="${config.modalPrefixClass}${MODAL_CLOSE_IMG_CLASS_SUFFIX}" />` :
			`<span class="${config.modalPrefixClass}${MODAL_CLOSE_TEXT_CLASS_SUFFIX}">
																				${config.modalCloseText}
																			 </span>`;
		let contentClassName = config.modalPrefixClass + MODAL_CONTENT_CLASS_SUFFIX;
		let titleClassName = config.modalPrefixClass + MODAL_TITLE_CLASS_SUFFIX;
		let title = config.modalTitle !== '' ?
			`<h1 id="${MODAL_TITLE_ID}" class="${titleClassName}">
																				${config.modalTitle}
																			 </h1>` :
			'';
		let button_close = `<button type="button" class="${MODAL_BUTTON_JS_CLASS} ${buttonCloseClassName}" id="${MODAL_BUTTON_JS_ID}" title="${config.modalCloseTitle}" ${MODAL_BUTTON_CONTENT_BACK_ID}="${config.modalContentId}" ${MODAL_BUTTON_FOCUS_BACK_ID}="${config.modalFocusBackId}">
														 ${buttonCloseInner}
														</button>`;
		let content = config.modalText;
		let describedById = config.modalDescribedById !== '' ? `${ATTR_DESCRIBEDBY}="${config.modalDescribedById}"` : '';

		// If there is no content but an id we try to fetch content id
		if (content === '' && config.modalContentId) {
			let contentFromId = findById(config.modalContentId);
			if (contentFromId) {
				if (config.modalLink)
				{
					content = `<div id="${MODAL_CONTENT_JS_ID}"><iframe width="100%" height="100%" src="${config.modalLink}"></iframe></div`;
					/*let xhr = new XMLHttpRequest();
					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4) {
							if (xhr.status === 200) {
								let innerContent = findById(MODAL_CONTENT_JS_ID);
								if (innerContent)
								{
									innerContent.innerHTML = xhr.responseText;								
								}
							}
						}
					};
					xhr.open('GET', config.modalLink);
					xhr.send();*/
				}
				else
				{
					content = `<div id="${MODAL_CONTENT_JS_ID}">
														${contentFromId.innerHTML}
													 </div`;
				}
				// we remove content from its source to avoid id duplicates, etc.
				contentFromId.innerHTML = '';
			}

		}


		return `<dialog id="${id}" class="${modalClassName}" ${ATTR_ROLE}="${MODAL_ROLE}" ${describedById} ${ATTR_OPEN} ${ATTR_LABELLEDBY}="${MODAL_TITLE_ID}">
									<div role="document" class="${modalClassWrapper}">
										${button_close}
										<div class="${contentClassName}">
											${title}
											${content}
										</div>
									</div>
								</dialog>`;

	};

	const closeModal = config => {

		remove(config.modal);
		remove(config.overlay);

		if (config.contentBackId !== '') {
			let contentBack = findById(config.contentBackId);
			if (contentBack) {
				contentBack.innerHTML = config.modalContent;
			}
		}

		if (config.modalFocusBackId) {
			let contentFocus = findById(config.modalFocusBackId);
			if (contentFocus) {
				contentFocus.focus();
			}
		}


	}

	/** Find all modals inside a container
	 * @param  {Node} node Default document
	 * @return {Array}
	 */
	const $listModals = (node = doc) => [].slice.call(node.querySelectorAll('.' + MODAL_JS_CLASS));


	/**
	 * Build modals for a container
	 * @param  {Node} node
	 */
	const attach = (node, addListeners = true) => {

		$listModals(node)
			.forEach((modal_node) => {

				let iLisible = Math.random().toString(32).slice(2, 12);
				let wrapperBody = findById(WRAPPER_PAGE_JS);
				let body = doc.querySelector('body');

				modal_node.setAttribute('id', MODAL_ID_PREFIX + iLisible);
				modal_node.setAttribute(ATTR_HASPOPUP, ATTR_HASPOPUP_VALUE);

				if (wrapperBody === null || wrapperBody.length === 0) {
					let wrapper = doc.createElement('DIV');
					wrapper.setAttribute('id', WRAPPER_PAGE_JS);
					wrapInner(body, wrapper);
				}


			});

		if (addListeners) {

			/* listeners */
			['click', 'keydown']
			.forEach(eventName => {

				doc.body
					.addEventListener(eventName, e => {

						e.preventDefault(); //

						// click on link modal
						let parentModalLauncher = searchParent(e.target, MODAL_JS_CLASS);
						if ((hasClass(e.target, MODAL_JS_CLASS) === true || parentModalLauncher !== '') && eventName === 'click') {
							let body = doc.querySelector('body');
							let modalLauncher = parentModalLauncher !== '' ? findById(parentModalLauncher) : e.target;
							let modalPrefixClass = modalLauncher.hasAttribute(MODAL_PREFIX_CLASS_ATTR) === true ? modalLauncher.getAttribute(MODAL_PREFIX_CLASS_ATTR) + '-' : '';
							let modalText = modalLauncher.hasAttribute(MODAL_TEXT_ATTR) === true ? modalLauncher.getAttribute(MODAL_TEXT_ATTR) : '';
							let modalLink = modalLauncher.hasAttribute('href') === true ? modalLauncher.getAttribute('href') : '';
							let modalContentId = modalLauncher.hasAttribute(MODAL_CONTENT_ID_ATTR) === true ? modalLauncher.getAttribute(MODAL_CONTENT_ID_ATTR) : '';
							let modalDescribedById = modalLauncher.hasAttribute(MODAL_DESCRIBEDBY_ID_ATTR) === true ? modalLauncher.getAttribute(MODAL_DESCRIBEDBY_ID_ATTR) : '';
							let modalTitle = modalLauncher.hasAttribute(MODAL_TITLE_ATTR) === true ? modalLauncher.getAttribute(MODAL_TITLE_ATTR) : '';
							let modalCloseText = modalLauncher.hasAttribute(MODAL_CLOSE_TEXT_ATTR) === true ? modalLauncher.getAttribute(MODAL_CLOSE_TEXT_ATTR) : MODAL_OVERLAY_TXT;
							let modalCloseTitle = modalLauncher.hasAttribute(MODAL_CLOSE_TITLE_ATTR) === true ? modalLauncher.getAttribute(MODAL_CLOSE_TITLE_ATTR) : modalCloseText;
							let modalCloseImgPath = modalLauncher.hasAttribute(MODAL_CLOSE_IMG_ATTR) === true ? modalLauncher.getAttribute(MODAL_CLOSE_IMG_ATTR) : '';
							let backgroundEnabled = modalLauncher.hasAttribute(MODAL_DATA_BACKGROUND_ATTR) === true ? modalLauncher.getAttribute(MODAL_DATA_BACKGROUND_ATTR) : '';
							let modalGiveFocusToId = modalLauncher.hasAttribute(MODAL_FOCUS_TO_ATTR) === true ? modalLauncher.getAttribute(MODAL_FOCUS_TO_ATTR) : '';

							let wrapperBody = findById(WRAPPER_PAGE_JS);

							// insert overlay
							body.insertAdjacentHTML('beforeEnd', createOverlay({
								text: modalCloseTitle,
								backgroundEnabled: backgroundEnabled,
								prefixClass: modalPrefixClass
							}));

							// insert modal
							body.insertAdjacentHTML('beforeEnd', createModal({
								modalText: modalText,
								modalLink: modalLink,
								modalPrefixClass: modalPrefixClass,
								backgroundEnabled: backgroundEnabled,
								modalTitle: modalTitle,
								modalCloseText: modalCloseText,
								modalCloseTitle: modalCloseTitle,
								modalCloseImgPath: modalCloseImgPath,
								modalContentId: modalContentId,
								modalDescribedById: modalDescribedById,
								modalFocusBackId: modalLauncher.getAttribute('id')
							}));

							// hide page
							wrapperBody.setAttribute(ATTR_HIDDEN, 'true');

							// add class noscroll to body
							addClass(body, NO_SCROLL_CLASS);

							// give focus to close button or specified element
							let closeButton = findById(MODAL_BUTTON_JS_ID);
							if (modalGiveFocusToId !== '') {
								let focusTo = findById(modalGiveFocusToId);
								if (focusTo) {
									focusTo.focus();
								} else {
									closeButton.focus();
								}
							} else {
								closeButton.focus();
							}

							

						}


						// click on close button or on overlay not blocked
						let parentButton = searchParent(e.target, MODAL_BUTTON_JS_CLASS);
						if (
							(
								e.target.getAttribute('id') === MODAL_BUTTON_JS_ID || parentButton !== '' ||
								e.target.getAttribute('id') === MODAL_OVERLAY_ID ||
								hasClass(e.target, MODAL_BUTTON_JS_CLASS) === true
							) &&
							eventName === 'click'
						) {
							let body = doc.querySelector('body');
							let wrapperBody = findById(WRAPPER_PAGE_JS);
							let modal = findById(MODAL_JS_ID);
							let modalContent = findById(MODAL_CONTENT_JS_ID) ? findById(MODAL_CONTENT_JS_ID).innerHTML : '';
							let overlay = findById(MODAL_OVERLAY_ID);
							let modalButtonClose = findById(MODAL_BUTTON_JS_ID);
							let modalFocusBackId = modalButtonClose.getAttribute(MODAL_BUTTON_FOCUS_BACK_ID);
							let contentBackId = modalButtonClose.getAttribute(MODAL_BUTTON_CONTENT_BACK_ID);
							let backgroundEnabled = overlay.getAttribute(MODAL_OVERLAY_BG_ENABLED_ATTR);

							if (!(e.target.getAttribute('id') === MODAL_OVERLAY_ID && backgroundEnabled === 'disabled')) {

								closeModal({
									modal: modal,
									modalContent: modalContent,
									overlay: overlay,
									modalFocusBackId: modalFocusBackId,
									contentBackId: contentBackId,
									backgroundEnabled: backgroundEnabled,
									fromId: e.target.getAttribute('id')
								});

								// show back page
								wrapperBody.removeAttribute(ATTR_HIDDEN);

								// remove class noscroll to body
								removeClass(body, NO_SCROLL_CLASS);

							}
						}

						// strike a key when modal opened
						if (findById(MODAL_JS_ID) && eventName === 'keydown') {
							let body = doc.querySelector('body');
							let wrapperBody = findById(WRAPPER_PAGE_JS);
							let modal = findById(MODAL_JS_ID);
							let modalContent = findById(MODAL_CONTENT_JS_ID) ? findById(MODAL_CONTENT_JS_ID).innerHTML : '';
							let overlay = findById(MODAL_OVERLAY_ID);
							let modalButtonClose = findById(MODAL_BUTTON_JS_ID);
							let modalFocusBackId = modalButtonClose.getAttribute(MODAL_BUTTON_FOCUS_BACK_ID);
							let contentBackId = modalButtonClose.getAttribute(MODAL_BUTTON_CONTENT_BACK_ID);
							let $listFocusables = [].slice.call(modal.querySelectorAll(FOCUSABLE_ELEMENTS_STRING));

							// esc
							if (e.keyCode === 27) {

								closeModal({
									modal: modal,
									modalContent: modalContent,
									overlay: overlay,
									modalFocusBackId: modalFocusBackId,
									contentBackId: contentBackId,
								});

								// show back page
								wrapperBody.removeAttribute(ATTR_HIDDEN);

								// remove class noscroll to body
								removeClass(body, NO_SCROLL_CLASS);
							}

							// tab or Maj Tab in modal => capture focus
							if (e.keyCode === 9 && $listFocusables.indexOf(e.target) >= 0) {

								// maj-tab on first element focusable => focus on last
								if (e.shiftKey) {
									if (e.target === $listFocusables[0]) {
										$listFocusables[$listFocusables.length - 1].focus();
										e.preventDefault();
									}
								} else {
									// tab on last element focusable => focus on first
									if (e.target === $listFocusables[$listFocusables.length - 1]) {
										$listFocusables[0].focus();
										e.preventDefault();
									}
								}

							}

							// tab outside modal => put it in focus
							if (e.keyCode === 9 && $listFocusables.indexOf(e.target) === -1) {
								e.preventDefault();
								$listFocusables[0].focus();
							}


						}




					}, true);

			});


		}

	};



	const onLoad = () => {
		attach();
		document.removeEventListener('DOMContentLoaded', onLoad);
	}

	document.addEventListener('DOMContentLoaded', onLoad);

	window.van11yAccessibleModalWindowAria = attach;


})(document);
