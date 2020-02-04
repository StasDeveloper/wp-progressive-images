(function () {
	'use strict';

	// set progressive image loading
	var progressiveMedias = document.querySelectorAll('.progressiveMedia-image');
	for (var i = 0; i < progressiveMedias.length; i++) {
		if (progressiveMedias[i].offsetParent !== null) {
			loadImage(progressiveMedias[i]);
		}
	}

	// global function
	function loadImage(progressiveMedia) {
		var imgParent = progressiveMedia.parentElement;

		if (!imgParent.style.position) {
			imgParent.style.position = 'relative';
		}

		var imgWidth = (progressiveMedia.getAttribute('width')) ? progressiveMedia.getAttribute('width') : progressiveMedia.dataset.width,
			imgHeight = imgWidth / progressiveMedia.dataset.width * progressiveMedia.dataset.height;
		progressiveMedia.width = imgWidth;

		if (!progressiveMedia.getAttribute('height')) {
			progressiveMedia.setAttribute('style', 'max-height:' + imgHeight + 'px;');
		}

		// make canvas fun part
		var canvas = imgParent.querySelector('.progressiveMedia-canvas'),
			context = canvas.getContext('2d');

		canvas.width = progressiveMedia.width;
		canvas.height = progressiveMedia.height;

		var img = new Image();
		img.src = progressiveMedia.src;

		img.onload = function () {
			// context.drawImage(img, 0, 0);
			// draw canvas
			var canvasImage = new CanvasImage(canvas, img);
			canvasImage.blur(2);

			// load canvas visible
			imgParent.classList.add('is-canvasLoaded');
		};


		// grab data-src from original image
		// from progressiveMedia-image
		var lgImage = progressiveMedia;
		lgImage.src = lgImage.dataset.src;

		// onload image visible
		lgImage.onload = function () {
			imgParent.classList.add('is-imageLoaded');
		}
	}

})();

// canvas blur function
CanvasImage = function (e, t) {
	this.image = t;
	this.element = e;
	e.width = t.width;
	e.height = t.height;
	this.context = e.getContext('2d');
	this.context.drawImage(t, 0, 0);
};

CanvasImage.prototype = {
	blur: function (e) {
		this.context.globalAlpha = 0.5;
		for (var t = -e; t <= e; t += 2) {
			for (var n = -e; n <= e; n += 2) {
				this.context.drawImage(this.element, n, t);
				var blob = n >= 0 && t >= 0 && this.context.drawImage(this.element, -(n - 1), -(t - 1));
			}
		}
	}
};